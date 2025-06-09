<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Controller;

use OCA\Jukebox\Db\JukeboxRadioStation;
use OCA\Jukebox\Db\JukeboxRadioStationMapper;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class RadioController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IAppConfig $config,
		private IL10N $l,
		private JukeboxRadioStationMapper $stationMapper,
		private IUserSession $userSession,
		private IClientService $httpClientService,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List all saved radio stations for current user, paginated
	 *
	 * @param int $offset Offset for pagination
	 * @param int $limit Number of items to return
	 * @return JSONResponse<Http::STATUS_OK, array{stations: list<array<string, mixed>>}, array{}>
	 *
	 * 200: List of radio stations returned
	 */
	#[ApiRoute(verb: 'GET', url: '/api/radio/stations')]
	public function index(int $offset = 0, int $limit = 50): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$stations = $this->stationMapper->findPaginatedByUserId($user->getUID(), $offset, $limit);
		return new JSONResponse(['stations' => array_map(fn ($s) => $s->jsonSerialize(), $stations)]);
	}

	/**
	 * List all favorited radio stations for current user, paginated
	 *
	 * @param int $offset Offset for pagination
	 * @param int $limit Number of items to return
	 * @return JSONResponse<Http::STATUS_OK, array{stations: list<array<string, mixed>>}, array{}>
	 *
	 * 200: List of radio stations returned
	 */
	#[ApiRoute(verb: 'GET', url: '/api/radio/favorites')]
	public function favorites(int $offset = 0, int $limit = 50): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$stations = $this->stationMapper->findFavoritesByUserId($user->getUID(), $offset, $limit);
		return new JSONResponse(['stations' => array_map(fn ($s) => $s->jsonSerialize(), $stations)]);
	}

	/**
	 * Search radio stations from Radio Browser by name
	 *
	 * @param string $name Name or partial name of the radio station
	 * @return JSONResponse<Http::STATUS_OK, array{stations: list<array<string, mixed>>}, array{}>
	 *
	 * 200: Matching radio stations returned
	 */
	#[ApiRoute(verb: 'GET', url: '/api/radio/search/{name}')]
	public function search(string $name): JSONResponse {
		try {
			$client = $this->httpClientService->newClient();
			$response = $client->get('http://de2.api.radio-browser.info/json/stations/byname/' . urlencode($name), [
				'headers' => [
					'User-Agent' => 'Nextcloud-Jukebox/1.0 (+https://github.com/chenasraf/nextcloud-jukebox)'
				],
			]);
			$data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

			$stations = [];
			foreach ($data as $item) {
				if (!isset($item['stationuuid'])) {
					continue;
				}
				$station = new JukeboxRadioStation();
				$station->setRemoteUuid($item['stationuuid']);
				$station->setName($item['name'] ?? '');
				$station->setStreamUrl($item['url_resolved'] ?? '');
				$station->setHomepage($item['homepage'] ?? null);
				$station->setCountry($item['country'] ?? null);
				$station->setState($item['state'] ?? null);
				$station->setLanguage($item['language'] ?? null);
				$station->setBitrate($item['bitrate'] ?? null);
				$station->setCodec($item['codec'] ?? null);
				$station->setTags($item['tags'] ?? null);
				$station->setLastUpdated(time());
				// favicon is not downloaded here, but we can store the URL temporarily in rawData
				$station->setRawData(json_encode($item, JSON_THROW_ON_ERROR));

				$stations[] = $station->jsonSerialize();
			}

			return new JSONResponse(['stations' => $stations]);
		} catch (\Throwable $e) {
			return new JSONResponse(['message' => 'Search failed'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * List all favorited radio stations for current user, paginated
	 *
	 * @param string $uuid UUID of the radio station
	 * @return JSONResponse<Http::STATUS_OK, array{station: array<string, mixed>}, array{}>
	 *
	 * 200: Radio station returned
	 */
	#[ApiRoute(verb: 'GET', url: '/api/radio/{uuid}')]
	public function getByUuid(string $uuid): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$stations = $this->stationMapper->findByRemoteUuid($user->getUID(), $uuid);
		return new JSONResponse(['stations' => array_map(fn ($s) => $s->jsonSerialize(), $stations)]);
	}

	/**
	 * Add a radio station to the database
	 *
	 * @param array<string, mixed> $station Data to add the station
	 * @return JSONResponse<Http::STATUS_OK, array{station: array<string, mixed>}, array{}>
	 *
	 * 200: Station was added successfully
	 */
	#[ApiRoute(verb: 'POST', url: '/api/radio/stations')]
	public function addByUuid(array $station): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$stationEntity = new JukeboxRadioStation();
			$stationEntity->setUserId($user->getUID());
			$stationEntity->setRemoteUuid($station['remoteUuid']);
			$stationEntity->setName($station['name'] ?? '');
			$stationEntity->setStreamUrl($station['streamUrl'] ?? '');
			$stationEntity->setHomepage($station['homepage'] ?? null);
			$stationEntity->setCountry($station['country'] ?? null);
			$stationEntity->setState($station['state'] ?? null);
			$stationEntity->setLanguage($station['language'] ?? null);
			$stationEntity->setBitrate($station['bitrate'] ?? null);
			$stationEntity->setCodec($station['codec'] ?? null);
			$stationEntity->setTags($station['tags'] ?? null);
			$stationEntity->setLastUpdated(time());

			unset($station['rawData']);
			unset($station['favorited']);

			$stationEntity->setRawData(json_encode($station, JSON_THROW_ON_ERROR));

			$this->stationMapper->insert($stationEntity);

			return new JSONResponse(['station' => $stationEntity->jsonSerialize()]);
		} catch (\Throwable $e) {
			$this->logger->error('Failed to add station', ['exception' => $e]);
			return new JSONResponse(['message' => 'Failed to add station'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Update an existing radio station by UUID
	 *
	 * @param string $uuid UUID of the station to update
	 * @param array<string, mixed> $station Data to add the station
	 * @return JSONResponse<Http::STATUS_OK, array{station: array<string, mixed>}, array{}>
	 *
	 * 200: Station was added successfully
	 */
	#[ApiRoute(verb: 'PUT', url: '/api/radio/stations/{uuid}')]
	public function updateByUuid(string $uuid, array $station): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$stationEntity = $this->stationMapper->findByRemoteUuid($user->getUID(), $uuid);
			$modified = false;

			if (isset($station['name'])) {
				$stationEntity->setName($station['name'] ?? '');
				$modified = true;
			}
			if (isset($station['streamUrl'])) {
				$stationEntity->setStreamUrl($station['streamUrl'] ?? '');
				$modified = true;
			}
			if (isset($station['homepage'])) {
				$stationEntity->setHomepage($station['homepage'] ?? null);
				$modified = true;
			}
			if (isset($station['country'])) {
				$stationEntity->setCountry($station['country'] ?? null);
				$modified = true;
			}
			if (isset($station['state'])) {
				$stationEntity->setState($station['state'] ?? null);
				$modified = true;
			}
			if (isset($station['language'])) {
				$stationEntity->setLanguage($station['language'] ?? null);
				$modified = true;
			}
			if (isset($station['bitrate'])) {
				$stationEntity->setBitrate($station['bitrate'] ?? null);
				$modified = true;
			}
			if (isset($station['codec'])) {
				$stationEntity->setCodec($station['codec'] ?? null);
				$modified = true;
			}
			if (isset($station['tags'])) {
				$stationEntity->setTags($station['tags'] ?? null);
				$modified = true;
			}
			if (isset($station['favorited'])) {
				$stationEntity->setFavorited($station['favorited'] ?? null);
				$modified = true;
			}

			if ($modified) {
				$stationEntity->setLastUpdated(time());
			}

			$this->stationMapper->insertOrUpdate($stationEntity);

			return new JSONResponse(['station' => $stationEntity->jsonSerialize()]);
		} catch (\Throwable $e) {
			$this->logger->error('Failed to add station', ['exception' => $e]);
			return new JSONResponse(['message' => 'Failed to add station'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
