<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Service;

use OCA\Jukebox\Db\JukeboxRadioStation;
use OCA\Jukebox\Db\JukeboxRadioStationMapper;
use OCP\Http\Client\IClientService;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class RadioSourcesService {
	private const API_URL = 'http://de2.api.radio-browser.info/json/stations';

	public function __construct(
		private LoggerInterface $logger,
		private JukeboxRadioStationMapper $stationMapper,
		private IClientService $httpClientService,
		private IDBConnection $db,
	) {
	}

	/**
	 * Fetch and persist internet radio stations for the given user.
	 *
	 * @param string $userId
	 * @param int $startOffset Optional offset to start from (default: 0)
	 * @return int Number of imported or updated stations
	 */
	public function importStations(string $userId, int $startOffset = 0): int {
		$client = $this->httpClientService->newClient();
		$count = 0;
		$limit = 200;
		$offset = $startOffset;

		try {
			while (true) {
				$this->logger->info("Fetching radio stations with limit $limit and offset $offset");

				$response = $client->post(
					'http://de2.api.radio-browser.info/json/stations/search',
					[
						'headers' => [
							'User-Agent' => 'Nextcloud-Jukebox/1.0 (+https://github.com/chenasraf/nextcloud-jukebox)',
							'Content-Type' => 'application/json',
						],
						'body' => json_encode([
							'limit' => $limit,
							'offset' => $offset,
						], JSON_THROW_ON_ERROR),
					]
				);

				$stations = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
				if (empty($stations)) {
					break;
				}

				$this->db->beginTransaction();
				foreach ($stations as $data) {
					try {
						$uuid = $data['stationuuid'] ?? null;
						if (!$uuid) {
							continue;
						}

						$existing = $this->stationMapper->findByRemoteUuid($uuid);
						$station = $existing ?? new JukeboxRadioStation();

						$station->setRemoteUuid($uuid);
						$station->setName($data['name'] ?? '');
						$station->setStreamUrl($data['url_resolved'] ?? '');
						$station->setHomepage($data['homepage'] ?? null);
						$station->setCountry($data['country'] ?? null);
						$station->setState($data['state'] ?? null);
						$station->setLanguage($data['language'] ?? null);
						$station->setBitrate($data['bitrate'] ?? null);
						$station->setCodec($data['codec'] ?? null);
						$station->setTags($data['tags'] ?? null);
						$station->setRawData(json_encode($data, JSON_THROW_ON_ERROR));
						$station->setUserId($userId);
						$station->setLastUpdated(time());

						if ($existing === null && !empty($data['favicon']) && filter_var($data['favicon'], FILTER_VALIDATE_URL)) {
							$host = parse_url($data['favicon'], PHP_URL_HOST);
							if ($host && filter_var(gethostbyname($host), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
								try {
									$faviconResponse = $client->get($data['favicon']);
									$station->setFavicon($faviconResponse->getBody());
								} catch (\Throwable $e) {
									$this->logger->debug('Failed to fetch favicon for station: ' . $uuid, ['exception' => $e]);
								}
							}
						}

						$this->stationMapper->insertOrUpdate($station);
						$count++;
						$this->logger->info("Processed radio station {$count}: {$station->getName()} ({$station->getRemoteUuid()})");
					} catch (\Throwable $e) {
						$this->logger->error('Failed to process radio station', [
							'station' => $data,
							'exception' => $e,
						]);
					}
				}
				$this->db->commit();
				$this->logger->info("Processed $count stations so far");

				$offset += $limit;
			}
		} catch (\Throwable $e) {
			$this->db->rollBack();
			$this->logger->error('Failed to import radio stations transactionally', ['exception' => $e]);
			return 0;
		}

		return $count;
	}
}
