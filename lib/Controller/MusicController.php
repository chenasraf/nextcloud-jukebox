<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Controller;

use OCA\Jukebox\Db\JukeboxMusicMapper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class MusicController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IAppConfig $config,
		private IL10N $l,
		private JukeboxMusicMapper $musicMapper,
		private IUserSession $userSession,
		private IRootFolder $rootFolder,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List all tracks for the current user
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{tracks: list<array<string, mixed>>}, array{}>
	 *
	 * 200: List of media tracks for current user
	 */
	#[ApiRoute(verb: 'GET', url: '/api/music/tracks')]
	public function listTracks(): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$tracks = $this->musicMapper->findByUserId($user->getUID());
		return new JSONResponse(['tracks' => array_map(fn ($t) => $t->jsonSerialize(), $tracks)]);
	}

	/**
	 * Stream a track file for playback
	 *
	 * @param int $id Track ID
	 *
	 * @return FileDisplayResponse<Http::STATUS_OK, array{}>
	 *                                                       | JSONResponse<Http::STATUS_UNAUTHORIZED, array{message: string}, array{}>
	 *                                                       | JSONResponse<Http::STATUS_FORBIDDEN, array{message: string}, array{}>
	 *                                                       | JSONResponse<Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 * 200: File response returned successfully
	 * 401: User not authenticated
	 * 403: Track does not belong to current user
	 * 404: Track file or record not found
	 */
	#[ApiRoute(verb: 'GET', url: '/api/music/tracks/{id}/stream')]
	#[NoCSRFRequired]
	public function streamTrack(int $id): FileDisplayResponse|JSONResponse {
		$this->logger->info('Received request to stream track with ID: ' . $id);

		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$this->logger->info('Streaming track with ID: ' . $id, ['user' => $user->getUID()]);

		try {
			$media = $this->musicMapper->find($user->getUID(), (string)$id);

			$file = $this->rootFolder->get($media->getPath());

			if (!($file instanceof File)) {
				$this->logger->error('Track file not found: ' . $media->getPath());
				throw new NotFoundException();
			}

			return new FileDisplayResponse($file);
		} catch (NotFoundException $e) {
			$this->logger->error('Track file not found for ID: ' . $id, ['exception' => $e]);
			return new JSONResponse(['message' => 'Track not found'], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Fetch albums with grouped tracks for current user
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{albums: list<array{
	 *     album: string,
	 *     albumArtist: string,
	 *     year: int|null,
	 *     cover: string|null,
	 *     tracks: list<array<string, mixed>>
	 * }>}, array{}>
	 *
	 * 200: Grouped albums and their tracks
	 */
	#[ApiRoute(verb: 'GET', url: '/api/music/albums')]
	public function listAlbums(): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$tracks = $this->musicMapper->findByUserId($user->getUID());

		$albums = [];

		foreach ($tracks as $track) {
			$album = $track->getAlbum() ?? '';
			$albumArtist = $track->getAlbumArtist() ?? '';

			$key = $albumArtist . '|' . $album;

			if (!isset($albums[$key])) {
				$albums[$key] = [
					'album' => $album,
					'albumArtist' => $albumArtist,
					'year' => $track->getYear(),
					'cover' => $track->getAlbumArtBase64(),
					'genre' => $track->getGenre() ?? '',
					'tracks' => [],
				];
			}

			$albums[$key]['tracks'][] = $track->jsonSerialize();
		}

		return new JSONResponse(['albums' => array_values($albums)]);
	}

	/**
	 * Fetch a single album by its album & artist
	 *
	 * @param string $artist Encoded artist identifier
	 * @param string $album Encoded album identifier
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{
	 *     album: string,
	 *     albumArtist: string,
	 *     year: int|null,
	 *     cover: string|null,
	 *     tracks: list<array<string, mixed>>
	 * }, array{}>
	 *
	 * 200: Album and its tracks
	 */
	#[ApiRoute(verb: 'GET', url: '/api/music/albums/{artist}/{album}')]
	public function getAlbumById(string $artist, string $album): JSONResponse {
		try {
			$this->logger->debug('Received request to get album by artist: ' . $artist . ', album: ' . $album);

			$user = $this->userSession->getUser();
			if (!$user) {
				return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
			}

			$decodedArtist = base64_decode($artist, true);
			$decodedAlbum = base64_decode($album, true);

			if ($decodedArtist === false || !mb_check_encoding($decodedArtist, 'UTF-8')) {
				return new JSONResponse(['message' => 'Invalid artist encoding'], Http::STATUS_BAD_REQUEST);
			}
			if ($decodedAlbum === false || !mb_check_encoding($decodedAlbum, 'UTF-8')) {
				return new JSONResponse(['message' => 'Invalid album encoding'], Http::STATUS_BAD_REQUEST);
			}

			$this->logger->debug('Looking up album', ['artist' => $decodedArtist, 'album' => $decodedAlbum]);

			$tracks = $this->musicMapper->findByAlbum($user->getUID(), $decodedArtist, $decodedAlbum);

			if (empty($tracks)) {
				return new JSONResponse(['message' => 'Album not found'], Http::STATUS_NOT_FOUND);
			}

			$first = $tracks[0];

			return new JSONResponse([
				'album' => $first->getAlbum() ?? '',
				'albumArtist' => $first->getAlbumArtist() ?? '',
				'year' => $first->getYear(),
				'cover' => $first->getAlbumArtBase64(),
				'genre' => $first->getGenre() ?? '',
				'tracks' => array_map(fn ($track) => $track->jsonSerialize(), $tracks),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Failed to get album by ID', ['exception' => $e]);
			return new JSONResponse(['message' => 'Internal server error'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Fetch a list of unique artists for the current user
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{artists: list<array{
	 *   name: string,
	 *   cover: string|null,
	 *   genre: string|null
	 * }>}, array{}>
	 *
	 * 200: List of unique artists
	 */
	#[ApiRoute(verb: 'GET', url: '/api/music/artists')]
	public function listArtists(): JSONResponse {
		try {
			$user = $this->userSession->getUser();
			if (!$user) {
				return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
			}

			$artists = $this->musicMapper->listGroupedArtists($user->getUID());

			return new JSONResponse(['artists' => $artists]);
		} catch (\Exception $e) {
			$this->logger->error('Failed to list artists', ['exception' => $e]);
			return new JSONResponse(['message' => 'Internal server error'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Fetch a single artist by their ID
	 *
	 * @param string $id Base64-encoded artist name
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{
	 *     name: string,
	 *     cover: string|null,
	 *     genre: string|null,
	 *     tracks: list<array<string, mixed>>
	 * }, array{}>
	 *
	 * 200: Artist details, their albums and tracks
	 */
	#[ApiRoute(verb: 'GET', url: '/api/music/artists/{id}')]
	public function getArtistById(string $id): JSONResponse {
		$this->logger->info('Received request to get artist by ID: ' . $id);
		try {
			$user = $this->userSession->getUser();
			if (!$user) {
				return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
			}

			$this->logger->info('Getting artist by ID', ['id' => $id, 'user' => $user->getUID()]);
			$decoded = base64_decode($id, true);

			if ($decoded === false || !mb_check_encoding($decoded, 'UTF-8')) {
				$this->logger->warning('Invalid artist ID encoding', ['id' => $id]);
				return new JSONResponse(['message' => 'Invalid artist ID encoding'], Http::STATUS_BAD_REQUEST);
			}

			$this->logger->info('Looking up artist', ['artist' => $decoded]);

			$tracks = $this->musicMapper->findByArtist($user->getUID(), $decoded);

			if (empty($tracks)) {
				$this->logger->warning('Artist not found', ['artist' => $decoded]);
				return new JSONResponse(['message' => 'Artist not found'], Http::STATUS_NOT_FOUND);
			}

			$first = $tracks[0];

			// Group albums by "album_artist|album"
			$albums = [];
			foreach ($tracks as $track) {
				$album = $track->getAlbum() ?? '';
				$albumArtist = $track->getAlbumArtist() ?? $track->getArtist() ?? '';
				$key = $albumArtist . '|' . $album;

				if (!isset($albums[$key])) {
					$albums[$key] = [
						'album' => $album,
						'albumArtist' => $albumArtist,
						'year' => $track->getYear(),
						'cover' => $track->getAlbumArtBase64(),
						'genre' => $track->getGenre() ?? null,
					];
				}
			}

			return new JSONResponse([
				'name' => $decoded,
				'cover' => $first->getAlbumArtBase64(),
				'genre' => $first->getGenre() ?? '',
				'tracks' => array_map(fn ($track) => $track->jsonSerialize(), $tracks),
				'albums' => array_values($albums),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Failed to get artist by ID', ['exception' => $e]);
			return new JSONResponse(['message' => 'Internal server error'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
