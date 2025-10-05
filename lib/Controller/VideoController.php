<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Controller;

use OCA\Jukebox\Db\VideoMapper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
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

class VideoController extends OCSController {
	/**
	 * Video constructor.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private IAppConfig $config,
		private IL10N $l,
		private LoggerInterface $logger,
		private VideoMapper $videoMapper,
		private IUserSession $userSession,
		private IRootFolder $rootFolder,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List all videos for the current user
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{videos: list<array<string, mixed>>}, array{}>
	 *
	 * 200: List of videos for current user
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/video')]
	public function index(): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$videos = $this->videoMapper->findByUserId($user->getUID());
		return new JSONResponse(['videos' => array_map(fn ($v) => $v->jsonSerialize(), $videos)]);
	}

	/**
	 * Get a single video by ID
	 *
	 * @param int $id Video ID
	 *
	 * @return JSONResponse<Http::STATUS_OK, array<string, mixed>, array{}>
	 * @return JSONResponse<Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 * 200: Video details
	 * 404: Video not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/video/{id}')]
	public function show(int $id): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$video = $this->videoMapper->find($user->getUID(), (string)$id);
			return new JSONResponse($video->jsonSerialize());
		} catch (NotFoundException $e) {
			return new JSONResponse(['message' => 'Video not found'], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Stream a video file for playback
	 *
	 * @param int $id Video ID
	 *
	 * @return FileDisplayResponse<Http::STATUS_OK, array{}>
	 *                                                       | JSONResponse<Http::STATUS_UNAUTHORIZED, array{message: string}, array{}>
	 *                                                       | JSONResponse<Http::STATUS_FORBIDDEN, array{message: string}, array{}>
	 *                                                       | JSONResponse<Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 *
	 * 200: File response returned successfully
	 * 401: User not authenticated
	 * 403: Video does not belong to current user
	 * 404: Video file or record not found
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/video/{id}/stream')]
	public function streamVideo(int $id): FileDisplayResponse|JSONResponse {
		$this->logger->info('Received request to stream video with ID: ' . $id);

		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$this->logger->info('Streaming video with ID: ' . $id, ['user' => $user->getUID()]);

		try {
			$video = $this->videoMapper->find($user->getUID(), (string)$id);

			$file = $this->rootFolder->get($video->getPath());

			if (!($file instanceof File)) {
				$this->logger->error('Video file not found: ' . $video->getPath());
				throw new NotFoundException();
			}

			return new FileDisplayResponse($file);
		} catch (NotFoundException $e) {
			$this->logger->error('Video file not found for ID: ' . $id, ['exception' => $e]);
			return new JSONResponse(['message' => 'Video not found'], Http::STATUS_NOT_FOUND);
		}
	}

}
