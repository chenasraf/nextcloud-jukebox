<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Service;

use OCA\Jukebox\Cron\ParsePodcastSubscriptionTask;
use OCA\Jukebox\Db\GpodderPodcastEpisodeActionMapper;
use OCA\Jukebox\Db\GpodderPodcastSubscriptionMapper;
use OCA\Jukebox\Db\PodcastEpisodeMapper;
use OCA\Jukebox\Db\PodcastEpisodePlay;
use OCA\Jukebox\Db\PodcastEpisodePlayMapper;
use OCA\Jukebox\Db\PodcastSubscription;
use OCA\Jukebox\Db\PodcastSubscriptionMapper;
use OCP\App\IAppManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class GpodderSyncService {
	public function __construct(
		private LoggerInterface $logger,
		private IDBConnection $db,
		private IAppManager $appManager,
		private PodcastSubscriptionMapper $subMapper,
		private PodcastEpisodeMapper $epMapper,
		private PodcastEpisodePlayMapper $epPlayMapper,
		private PodcastFeedParserService $parser,
		private PodcastSubscriptionWriterService $subWriter,
		private PodcastEpisodeWriterService $epWriter,
		private GpodderPodcastEpisodeActionMapper $gpEpActionMapper,
		private GpodderPodcastSubscriptionMapper $gpSubMapper,
		private IJobList $jobs,
	) {
	}

	/**
	 * Check if gpoddersync app is installed and enabled
	 */
	public function isAvailable(): bool {
		return $this->appManager->isEnabledForUser('gpoddersync');
	}

	/**
	 * Import all gpodder subscriptions into jukebox metadata
	 *
	 * @param string $userId
	 * @param bool $delayedFetch If true, metadata fetching will be delayed to a background job
	 * @return int Number of imported subscriptions
	 */
	public function importSubscriptions(string $userId, bool $delayedFetch = false): int {
		if (!$this->isAvailable()) {
			return 0;
		}

		$results = $this->gpSubMapper->findAllByUserId($userId);
		$count = 0;

		foreach ($results as $row) {
			try {
				// Skip if already linked
				$this->subMapper->findByGpodderId($userId, $row->getId());
				continue;
			} catch (DoesNotExistException) {
				// Not yet imported, continue
			}

			$subscription = new PodcastSubscription();
			$subscription->setUserId($userId);
			$subscription->setSubscriptionId($row->getId());
			$subscription->setTitle('');
			$subscription->setUrl($row->getUrl());
			$this->subMapper->insert($subscription);
			if (!$delayedFetch) {
				$this->subWriter->fetchSubscriptionMetadata($subscription);
			}
			$count++;
		}

		if ($count > 0 && $delayedFetch) {
			$this->jobs->add(ParsePodcastSubscriptionTask::class, []);
		}
		return $count;
	}

	public function importEpisodes(string $userId): int {
		if (!$this->isAvailable()) {
			return 0;
		}

		$results = $this->gpSubMapper->findAllByUserId($userId);

		foreach ($results as $row) {
			$subscription = null;
			try {
				// Skip if already linked
				$subscription = $this->subMapper->findByGpodderId($userId, $row->getId());
			} catch (DoesNotExistException) {
				// Not yet imported, continue
				continue;
			}

			$feedUrl = $subscription->getUrl();
			$episodes = $this->parser->parseEpisodes($feedUrl);

			$qb = $this->db->getQueryBuilder();
			$this->epWriter->storeEpisodes($userId, $subscription, $episodes);
		}

		// Import episode actions
		return $this->importEpisodeActions($userId);
	}

	/**
	 * Import all gpodder episode actions into jukebox metadata
	 *
	 * @param string $userId
	 * @return int Number of imported episodes
	 */
	public function importEpisodeActions(string $userId): int {
		if (!$this->isAvailable()) {
			return 0;
		}

		$gpodderPlays = $this->gpEpActionMapper->findAllByUserId($userId);
		$count = 0;
		$missingEpisodes = [];

		foreach ($gpodderPlays as $row) {
			$ep = null;
			if (in_array($row->getGuid(), $missingEpisodes) !== false) {
				// Skip if we already logged this episode as missing
				continue;
			}
			try {
				$ep = $this->epMapper->findByGuid($userId, $row->getGuid());
			} catch (DoesNotExistException) {
				// Episode not found, skip this action
				$this->logger->warning('Episode not found for gpodder action', [
					'userId' => $userId,
					'guid' => $row->getGuid(),
					'action' => $row->getAction(),
				]);
				$missingEpisodes[] = $row->getGuid();
				continue;
			}

			$result = $this->epPlayMapper->findGpodderExistingMatch(
				$userId,
				$row->getGuid(),
				$row->getTimestampEpoch(),
			);
			if ($result !== null) {
				// Already exists, skip
				continue;
			}

			$epPlay = new PodcastEpisodePlay();
			$epPlay->setUserId($userId);
			$epPlay->setAction($row->getAction());
			$epPlay->setEpisodeId($ep->getId());
			$epPlay->setTotal($row->getTotal());
			$epPlay->setPosition($row->getPosition());
			$epPlay->setEpisodeGuid($row->getGuid());
			$epPlay->setDevice('gpodder');
			$epPlay->setTimestamp($row->getTimestampEpoch());

			$this->epPlayMapper->insert($epPlay);
			$count++;
		}

		return $count;
	}
}
