<?php

declare(strict_types=1);

namespace OCA\Jukebox\Service;

use OCA\Jukebox\Db\PodcastEpisode;
use OCA\Jukebox\Db\PodcastEpisodeMapper;
use OCA\Jukebox\Db\PodcastSubscription;
use OCA\Jukebox\Db\PodcastSubscriptionMapper;
use OCP\App\IAppManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class GpodderSyncService {
	public function __construct(
		private LoggerInterface $logger,
		private IDBConnection $db,
		private IAppManager $appManager,
		private PodcastSubscriptionMapper $subMapper,
		private PodcastEpisodeMapper $epMapper,
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
	 * @return int Number of imported subscriptions
	 */
	public function importSubscriptions(string $userId): int {
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

			$entity = new PodcastSubscription();
			$entity->setUserId($userId);
			$entity->setSubscriptionId((int)$row['id']);
			$entity->setTitle(null); // You can fetch metadata later via parser
			$entity->setUrl($row['url']);
			$this->subMapper->insert($entity);
			$count++;
		}

		return $count;
	}

	/**
	 * Import all gpodder episode actions into jukebox metadata
	 *
	 * @param string $userId
	 * @return int Number of imported episodes
	 */
	public function importEpisodes(string $userId): int {
		if (!$this->isAvailable()) {
			return 0;
		}

		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'podcast', 'episode', 'guid', 'timestamp')
			->from('gpodder_episode_action')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		$results = $qb->executeQuery()->fetchAll();
		$count = 0;

		foreach ($results as $row) {
			try {
				// Skip if already imported
				$this->epMapper->findByGpodderId($userId, (int)$row['id']);
				continue;
			} catch (DoesNotExistException) {
				// Not yet imported
			}

			$entity = new PodcastEpisode();
			$entity->setUserId($userId);
			$entity->setActionId((int)$row['id']);
			$entity->setGuid($row['guid'] ?? $row['episode']);
			$entity->setTitle($row['episode']);
			$entity->setPubDate(
				$row['timestamp'] ? new \DateTimeImmutable($row['timestamp']) : null
			);

			// You can link to a jukebox_podcast_subscription_data row if podcast URL matches
			// For now, leave subscriptionDataId unset

			$this->epMapper->insert($entity);
			$count++;
		}

		return $count;
	}
}
