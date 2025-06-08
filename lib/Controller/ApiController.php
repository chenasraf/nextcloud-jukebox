<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Controller;

use OCA\Jukebox\Db\JukeboxMediaMapper;
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

class ApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IAppConfig $config,
		private IL10N $l,
		private JukeboxMediaMapper $mediaMapper,
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
	#[ApiRoute(verb: 'GET', url: '/api/tracks')]
	public function listTracks(): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$tracks = $this->mediaMapper->findByMediaType($user->getUID(), 'track');
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
	#[ApiRoute(verb: 'GET', url: '/api/tracks/{id}/stream')]
	#[NoCSRFRequired]
	public function streamTrack(int $id): FileDisplayResponse|JSONResponse {
		$this->logger->info('Received request to stream track with ID: ' . $id);

		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$this->logger->info('Streaming track with ID: ' . $id, ['user' => $user->getUID()]);

		try {
			$media = $this->mediaMapper->find((string)$id);
			if ($media->getUserId() !== $user->getUID()) {
				return new JSONResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
			}

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
	#[ApiRoute(verb: 'GET', url: '/api/albums')]
	public function listAlbums(): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$tracks = $this->mediaMapper->findByMediaType($user->getUID(), 'track');

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
	 * Fetch a single album by its ID
	 *
	 * @param string $id Encoded album identifier (albumArtist|album)
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
	#[ApiRoute(verb: 'GET', url: '/api/albums/{id}')]
	public function getAlbumById(string $id): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$decoded = base64_decode($id, true);

		if ($decoded === false || !mb_check_encoding($decoded, 'UTF-8')) {
			return new JSONResponse(['message' => 'Invalid album ID encoding'], Http::STATUS_BAD_REQUEST);
		}

		$parts = explode('|', $decoded, 2);
		if (count($parts) !== 2) {
			return new JSONResponse(['message' => 'Invalid album ID format'], Http::STATUS_BAD_REQUEST);
		}

		[$albumArtist, $album] = explode('|', $decoded, 2);

		$this->logger->info('Looking up album', ['artist' => $albumArtist, 'album' => $album]);

		$tracks = $this->mediaMapper->findByAlbum($user->getUID(), $album, $albumArtist);

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
	}
}
