<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Controller;

use OCA\Jukebox\Cron\FetchPodcastEpisodesTask;
use OCA\Jukebox\Db\PodcastEpisode;
use OCA\Jukebox\Db\PodcastEpisodeMapper;
use OCA\Jukebox\Db\PodcastEpisodePlay;
use OCA\Jukebox\Db\PodcastEpisodePlayMapper;
use OCA\Jukebox\Db\PodcastSubscription;
use OCA\Jukebox\Db\PodcastSubscriptionMapper;
use OCA\Jukebox\Service\PodcastFeedParserService;
use OCA\Jukebox\Service\SettingsService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCSController;
use OCP\BackgroundJob\IJobList;
use OCP\Files\File;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class PodcastController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private SettingsService $settings,
		private IL10N $l,
		private LoggerInterface $logger,
		private IUserSession $userSession,
		private PodcastSubscriptionMapper $subMapper,
		private PodcastFeedParserService $parser,
		private PodcastEpisodePlayMapper $playMapper,
		private PodcastEpisodeMapper $epMapper,
		private IJobList $jobList,
		private IRootFolder $rootFolder,
		private IMimeTypeDetector $mimeTypeDetector,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get all podcast subscriptions for the current user
	 *
	 * @return JSONResponse<Http::STATUS_OK, list<array{
	 *     id: int,
	 *     url: string,
	 *     subscribed: bool,
	 *     updated: string
	 * }>, array{}>
	 *
	 * 200: Subscriptions listed
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/podcasts/subscriptions')]
	public function getSubscriptions(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}

		$subscriptions = $this->subMapper->findAllBySubscribed($user->getUID(), true);
		$data = array_map(static fn (PodcastSubscription $s) => $s->jsonSerialize(), $subscriptions);

		return new JSONResponse(['subscriptions' => $data], Http::STATUS_OK);
	}

	/**
	 * Subscribe to a podcast by URL
	 *
	 * @param string $url The podcast feed URL
	 * @return JSONResponse<Http::STATUS_CREATED|Http::STATUS_BAD_REQUEST|Http::STATUS_OK, array{
	 * subscription: array{
	 *	 id: int,
	 *	 url: string,
	 *	 subscribed: bool,
	 *	 updated: string,
	 *	 title: string,
	 *	 author: string,
	 *	 description: string,
	 *	 image: string
	 * }}, array{}>
	 *
	 * 201: Subscription created
	 * 200: Subscription updated
	 * 400: Invalid request
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/podcasts/subscriptions')]
	public function subscribe(string $url): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		$userId = $user->getUID();

		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			$this->logger->error('Invalid podcast URL provided', ['url' => $url, 'userId' => $userId]);
			return new JSONResponse(['error' => 'Invalid URL'], Http::STATUS_BAD_REQUEST);
		}

		try {
			$existing = $this->subMapper->findByUrl($userId, $url);
		} catch (\OCP\AppFramework\Db\DoesNotExistException) {
			$existing = null;
		}

		$now = new \DateTime();

		if ($existing !== null) {
			$existing->setSubscribed(true);
			$existing->setUpdated($now);
			$this->subMapper->update($existing);

			$this->logger->info('Podcast subscription updated', ['url' => $url, 'userId' => $userId]);
			return new JSONResponse(['subscription' => $existing], Http::STATUS_OK);
		}

		try {
			$feed = $this->parser->parseSubscriptionMetadata($url);
		} catch (\RuntimeException $e) {
			$this->logger->error('Failed to parse podcast feed', [
				'url' => $url,
				'userId' => $userId,
				'error' => $e->getMessage(),
			]);
			return new JSONResponse(['error' => 'Failed to parse feed'], Http::STATUS_BAD_REQUEST);
		}

		$imageBase64 = $this->parser->fetchImageBase64($feed, $userId);

		$subscription = new PodcastSubscription();
		$subscription->setUrl($url);
		$subscription->setSubscribed(true);
		$subscription->setUpdated($now);
		$subscription->setUserId($userId);
		$subscription->setTitle($feed['title']);
		$subscription->setAuthor($feed['author']);
		$subscription->setDescription($feed['description']);
		$subscription->setImage($imageBase64);

		$this->subMapper->insert($subscription);
		$this->logger->info('Podcast subscription created', ['url' => $url, 'userId' => $userId]);
		$this->jobList->add(FetchPodcastEpisodesTask::class, ['userId' => $userId, 'subscriptionId' => $subscription->getId()]);
		return new JSONResponse(['subscription' => $subscription->jsonSerialize()], Http::STATUS_CREATED);
	}

	/**
	 * Track a podcast playback action
	 *
	 * @param int $id Episode ID
	 * @param string $guid Episode GUID
	 * @param string $action e.g. "play", "pause", "complete"
	 * @param int $timestamp UNIX timestamp
	 * @param int|null $position Position in seconds
	 * @param int|null $total Duration in seconds
	 * @param string|null $device Device name or ID
	 *
	 * @return JSONResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST, array{}, array{}>
	 *
	 * 200: Action logged
	 * 400: Invalid input
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/podcasts/track')]
	public function trackAction(
		int $id,
		string $guid,
		string $action,
		int $timestamp,
		?int $position = null,
		?int $total = null,
		?string $device = null,
	): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}

		if (!in_array($action, ['play', 'pause', 'complete', 'resume'], true)) {
			return new JSONResponse(['error' => 'Invalid action'], Http::STATUS_BAD_REQUEST);
		}

		$entry = new PodcastEpisodePlay();
		$entry->setUserId($user->getUID());
		$entry->setEpisodeId($id);
		$entry->setEpisodeGuid($guid);
		$entry->setAction($action);
		$entry->setTimestamp($timestamp);
		$entry->setPosition($position);
		$entry->setTotal($total);
		$entry->setDevice($device);

		$this->playMapper->insert($entry);

		return new JSONResponse([], Http::STATUS_OK);
	}

	/**
	 * Get the next unfinished episode per podcast
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{
	 *   episodes: list<array{
	 *     id: int,
	 *     title: string|null,
	 *     guid: string|null,
	 *     pub_date: string|null,
	 *     duration: int|null,
	 *     media_url: string|null,
	 *     description: string|null,
	 *     subscription_data_id: int
	 *   }>
	 * }, array{}>
	 *
	 * 200: Next episodes listed
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/podcasts/next')]
	public function getNextEpisodes(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		$userId = $user->getUID();

		$subs = $this->subMapper->findAllBySubscribed($userId, true);
		$results = [];

		foreach ($subs as $sub) {
			$episodes = $this->epMapper->findBySubscription($sub->getId());

			usort($episodes, fn (PodcastEpisode $a, PodcastEpisode $b) =>
				($a->getPubDate()?->getTimestamp() ?? 0) <=> ($b->getPubDate()?->getTimestamp() ?? 0)
			);

			foreach ($episodes as $ep) {
				$duration = $ep->getDuration();
				if (!$duration || $duration < 60) {
					continue; // skip if no valid duration
				}

				$play = $this->playMapper->findLatestPlay($userId, (string)$ep->getGuid());
				$progress = $play && $play->getPosition() !== null
					? ($play->getPosition() / $duration)
					: 0;

				if ($progress < 0.98) {
					$results[] = [
						'id' => $ep->getId(),
						'title' => $ep->getTitle(),
						'guid' => $ep->getGuid(),
						'pub_date' => $ep->getPubDate()?->format(DATE_ATOM),
						'duration' => $duration,
						'media_url' => $ep->getMediaUrl(),
						'description' => $ep->getDescription(),
						'subscription_id' => $ep->getSubscriptionId(),
					];
					break; // only the first unfinished one per subscription
				}
			}
		}

		return new JSONResponse(['episodes' => $results], Http::STATUS_OK);
	}

	/**
	 * Get a single podcast subscription
	 *
	 * @param int $id the subscription ID
	 * @return JSONResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, array{
	 *   subscription: array<string, mixed>
	 * }, array{}>
	 *
	 * 200: Subscription found
	 * 404: Subscription not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/podcasts/subscriptions/{id}')]
	public function getSubscription(int $id): JSONResponse {
		$this->logger->debug('Fetching podcast subscription', ['id' => $id]);
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$sub = $this->subMapper->find($user->getUID(), $id);
			return new JSONResponse(['subscription' => $sub->jsonSerialize()], Http::STATUS_OK);
		} catch (\OCP\AppFramework\Db\DoesNotExistException) {
			$this->logger->error('Podcast subscription not found', ['id' => $id, 'userId' => $user->getUID()]);
			return new JSONResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Get all episodes for a podcast subscription
	 *
	 * @param int $id the subscription ID
	 * @return JSONResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, array{
	 *   episodes: list<array<string, mixed>>
	 * }, array{}>
	 *
	 * 200: Episodes listed
	 * 404: Subscription not found
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/podcasts/subscriptions/{id}/episodes')]
	public function getEpisodesForSubscription(int $id): JSONResponse {
		$this->logger->debug('Fetching podcast episodes for subscription', ['id' => $id]);
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->subMapper->find($user->getUID(), $id);
		} catch (\OCP\AppFramework\Db\DoesNotExistException) {
			$this->logger->error('Podcast subscription not found', ['id' => $id, 'userId' => $user->getUID()]);
			return new JSONResponse(['error' => 'Subscription not found'], Http::STATUS_NOT_FOUND);
		}

		$episodes = $this->epMapper->findBySubscription($user->getUID(), $id);

		usort($episodes, fn ($a, $b) =>
			($b->getPubDate()?->getTimestamp() ?? 0) <=> ($a->getPubDate()?->getTimestamp() ?? 0)
		);

		return new JSONResponse(['episodes' => array_map(fn ($ep) => $ep->jsonSerialize(), $episodes)], Http::STATUS_OK);
	}

	/**
	 * Stream a podcast episode
	 *
	 * @param int $id Episode ID
	 * @param string|null $range Optional HTTP Range header for seeking support
	 *
	 * @return StreamResponse<Http::STATUS_OK, mixed>
	 * @return StreamResponse<Http::STATUS_PARTIAL_CONTENT, mixed>
	 * @return JSONResponse<Http::STATUS_UNAUTHORIZED, array{ message: string }, array{}>
	 * @return JSONResponse<Http::STATUS_NOT_FOUND, array{ message: string }, array{}>
	 * @return JSONResponse<Http::STATUS_BAD_REQUEST, array{ message: string }, array{}>
	 * @return JSONResponse<Http::STATUS_INTERNAL_SERVER_ERROR, array{ message: string }, array{}>
	 *
	 * 200: Full content stream returned
	 * 206: Partial content stream returned
	 * 400: Invalid or missing media URL
	 * 401: User is not authenticated
	 * 404: Episode not found
	 * 500: Error occurred while streaming
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/podcasts/episodes/{id}/stream')]
	public function streamEpisode(
		int $id,
		?string $range = null,
	): Response {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new \OCP\AppFramework\Http\JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$episode = $this->epMapper->find($user->getUID(), $id);
		} catch (\OCP\AppFramework\Db\DoesNotExistException) {
			return new \OCP\AppFramework\Http\JSONResponse(['message' => 'Episode not found'], Http::STATUS_NOT_FOUND);
		}

		if ($this->settings->getBool($user->getUID(), 'download_podcast_episodes', false)) {
			return $this->downloadAndStreamLocal($user, $episode);
		}

		return $this->streamRemote($user, $episode);
	}

	/**
	 * @return Http\JSONResponse<int,array|object|stdClass|JsonSerializable,array<string,mixed>>
	 */
	private function streamRemote(IUser $user, PodcastEpisode $episode): JSONResponse {
		$url = $episode->getMediaUrl();
		if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
			return new \OCP\AppFramework\Http\JSONResponse(['message' => 'Invalid media URL'], Http::STATUS_BAD_REQUEST);
		}

		$rangeHeader = $this->request->getHeader('range');
		$headers = [];
		if ($rangeHeader !== null) {
			$headers[] = 'range: ' . $rangeHeader;
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		$response = curl_exec($ch);
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($response === false || $headerSize === false) {
			$this->logger->error('Failed to stream podcast episode via cURL', [
				'userId' => $user->getUID(),
				'episodeId' => $episode->getId(),
			]);
			return new JSONResponse(['message' => 'Stream failed'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$headersText = substr($response, 0, $headerSize);
		$body = substr($response, $headerSize);

		$lines = explode("\r\n", $headersText);
		$statusLine = array_shift($lines);

		// Start clean output
		header_remove();
		foreach ($lines as $line) {
			if (stripos($line, 'Content-Type:') === 0 ||
				stripos($line, 'Content-Range:') === 0 ||
				stripos($line, 'Content-Length:') === 0 ||
				stripos($line, 'Accept-Ranges:') === 0) {
				header($line, true);
			}
		}

		// Always allow browser cache
		header('Cache-Control: public, max-age=31536000');
		header('Content-Transfer-Encoding: binary');

		http_response_code($statusCode);
		echo $body;
		exit;
	}

	/**
	 * Stream a locally downloaded podcast episode.
	 * Downloads it if not available locally.
	 *
	 * @param IUser $user
	 * @param PodcastEpisode $episode
	 *
	 * @return FileDisplayResponse|JSONResponse
	 */
	private function downloadAndStreamLocal(IUser $user, PodcastEpisode $episode): FileDisplayResponse|JSONResponse {
		try {
			$path = $this->settings->getPodcastDownloadPath($user->getUID(), $episode->getSubscriptionId(), $episode->getId());
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());

			// Check if file exists already
			if (!$userFolder->nodeExists($path)) {
				$mediaUrl = $episode->getMediaUrl();
				if (!$mediaUrl || !filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
					return new JSONResponse(['message' => 'Invalid media URL'], Http::STATUS_BAD_REQUEST);
				}

				// Download to temporary stream
				$tempStream = fopen('php://temp', 'r+');
				$download = @fopen($mediaUrl, 'r');
				if (!$download) {
					return new JSONResponse(['message' => 'Failed to download episode'], Http::STATUS_BAD_GATEWAY);
				}
				stream_copy_to_stream($download, $tempStream);
				fclose($download);
				rewind($tempStream);

				// Ensure intermediate folders exist
				$segments = explode('/', $path);
				$fileName = array_pop($segments);
				$current = $userFolder;

				foreach ($segments as $segment) {
					if (!$current->nodeExists($segment)) {
						$current = $current->newFolder($segment);
					} else {
						$current = $current->get($segment);
					}
				}

				// Create and write the file via stream to preserve range support
				$file = $current->newFile($fileName);
				$streamWrapper = $file->fopen('w');
				stream_copy_to_stream($tempStream, $streamWrapper);
				fclose($streamWrapper);
				fclose($tempStream);

				$mimeType = $this->mimeTypeDetector->detect($file->getName());
				$this->logger->info('Streaming local podcast episode', [
					'filePath' => $file->getPath(),
					'fileName' => $file->getName(),
					'mimeType' => $mimeType,
				]);
				$response = new FileDisplayResponse($file, Http::STATUS_PARTIAL_CONTENT);
				$response->addHeader('Content-Type', $mimeType);
				return $response;
			}

			// File already exists, stream it
			$file = $userFolder->get($path);
			if (!($file instanceof File)) {
				throw new NotFoundException();
			}

			$mimeType = $this->mimeTypeDetector->detect($file->getName());
			$this->logger->info('Streaming local podcast episode', [
				'filePath' => $file->getPath(),
				'fileName' => $file->getName(),
				'mimeType' => $mimeType,
			]);
			$response = new FileDisplayResponse($file, Http::STATUS_PARTIAL_CONTENT);
			$response->addHeader('Content-Type', $mimeType);
			return $response;
		} catch (NotFoundException $e) {
			$this->logger->error('Local podcast file not found', [
				'userId' => $user->getUID(),
				'episodeId' => $episode->getId(),
				'exception' => $e,
			]);
			return new JSONResponse(['message' => 'Episode file not found'], Http::STATUS_NOT_FOUND);
		} catch (\Throwable $e) {
			$this->logger->error('Failed to stream or download podcast episode', [
				'userId' => $user->getUID(),
				'episodeId' => $episode->getId(),
				'exception' => $e,
			]);
			return new JSONResponse(['message' => 'Internal server error'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get the last known playback position for a podcast episode
	 *
	 * @param int $id Episode ID
	 *
	 * @return JSONResponse<Http::STATUS_OK, array{ position: int }, array{}>
	 * @return JSONResponse<Http::STATUS_UNAUTHORIZED, array{ message: string }, array{}>
	 * @return JSONResponse<Http::STATUS_NOT_FOUND, array{ message: string }, array{}>
	 *
	 * 200: Playback position returned
	 * 401: User not authenticated
	 * 404: Episode not found
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/podcasts/episodes/{id}/position')]
	public function getEpisodePosition(int $id): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$position = $this->playMapper->getPositionForEpisode($user->getUID(), $id);

		return new JSONResponse(['position' => $position ?? 0]);
	}
}
