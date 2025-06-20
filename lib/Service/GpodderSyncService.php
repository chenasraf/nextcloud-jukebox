<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <casraf@pm.me>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Service;

use OCA\Jukebox\Cron\ParsePodcastSubscriptionTask;
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

		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'url')
			->from('gpodder_subscriptions')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		$results = $qb->executeQuery()->fetchAll();
		$count = 0;

		foreach ($results as $row) {
			try {
				// Skip if already linked
				$this->subMapper->findByGpodderId($userId, (int)$row['id']);
				continue;
			} catch (DoesNotExistException) {
				// Not yet imported, continue
			}

			$subscription = new PodcastSubscription();
			$subscription->setUserId($userId);
			$subscription->setSubscriptionId((int)$row['id']);
			$subscription->setTitle('');
			$subscription->setUrl($row['url']);
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

		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'url')
			->from('gpodder_subscriptions')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		$results = $qb->executeQuery()->fetchAll();

		foreach ($results as $row) {
			$subscription = null;
			try {
				// Skip if already linked
				$subscription = $this->subMapper->findByGpodderId($userId, (int)$row['id']);
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

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('gpodder_episode_action')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		$gpodderPlays = $qb->executeQuery()->fetchAll();
		$count = 0;

		foreach ($gpodderPlays as $row) {
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('id')
					->from('jukebox_podcast_ep_plays')
					->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
					->andWhere($qb->expr()->eq('episode_id', $qb->createNamedParameter($row['id'])))
					->andWhere($qb->expr()->eq('timestamp', $qb->createNamedParameter($row['timestamp'])))
					->setMaxResults(1);

				$result = $qb->executeQuery()->fetchOne();
				if ($result !== false) {
					// Already exists, skip
					continue;
				}
			} catch (DoesNotExistException) {
				// Not yet imported, continue
			}

			$epPlay = new PodcastEpisodePlay();
			$epPlay->setUserId($userId);
			$epPlay->setAction($row['action']);
			$epPlay->setEpisodeId($row['id']);
			$epPlay->setTotal($row['total']);
			$epPlay->setPosition($row['position']);
			$epPlay->setEpisodeGuid($row['guid']);
			$epPlay->setDevice('gpodder');
			$epPlay->setTimestamp($row['timestamp_epoch']);

			$this->epPlayMapper->insert($epPlay);
			$count++;
		}

		return $count;
	}
}
