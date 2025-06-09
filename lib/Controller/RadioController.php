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

class RadioController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private IAppConfig $config,
		private IL10N $l,
		private JukeboxRadioStationMapper $stationMapper,
		private IUserSession $userSession,
		private IClientService $httpClientService,
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
	 * Add a radio station to the database by UUID
	 *
	 * @param string $uuid Unique identifier for the radio station from Radio Browser
	 * @return JSONResponse<Http::STATUS_OK, array{message: string}, array{}>
	 *
	 * 200: Station was added successfully
	 */
	#[ApiRoute(verb: 'POST', url: '/api/radio/add/{uuid}')]
	public function addByUuid(string $uuid): JSONResponse {
		$user = $this->userSession->getUser();
		if (!$user) {
			return new JSONResponse(['message' => 'Unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$client = $this->httpClientService->newClient();
			$response = $client->get('http://de2.api.radio-browser.info/json/stations/byuuid/' . urlencode($uuid), [
				'headers' => [
					'User-Agent' => 'Nextcloud-Jukebox/1.0 (+https://github.com/chenasraf/nextcloud-jukebox)'
				],
			]);
			$data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
			if (empty($data[0])) {
				return new JSONResponse(['message' => 'Station not found'], Http::STATUS_NOT_FOUND);
			}

			// TODO: persist station in DB here (you may want to use RadioSourcesService)

			return new JSONResponse(['message' => 'Station added']);
		} catch (\Throwable $e) {
			return new JSONResponse(['message' => 'Failed to add station'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
