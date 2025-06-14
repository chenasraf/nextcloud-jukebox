<?php

declare(strict_types=1);

namespace OCA\Jukebox\Db;

use OCA\Jukebox\AppInfo\Application;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<PodcastSubscription>
 */
class PodcastSubscriptionMapper extends QBMapper {
	public function __construct(
		IDBConnection $db,
	) {
		parent::__construct($db, Application::tableName('podcast_subs'), PodcastSubscription::class);
	}

	public function findByGpodderId(string $userId, int $gpodderId): PodcastSubscription {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('subscription_id', $qb->createNamedParameter($gpodderId)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		return $this->findEntity($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
	public function find(string $userId, int $id): PodcastSubscription {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		return $this->findEntity($qb);
	}

	/**
	 * @param string $userId
	 * @return PodcastSubscription[]
	 */
	public function findAll(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		return $this->findEntities($qb);
	}

	/**
	 * Find all subscribed podcast metadata entries for a user
	 *
	 * @param string $userId
	 * @param bool $subscribed
	 * @return PodcastSubscription[]
	 */
	public function findAllBySubscribed(string $userId, bool $subscribed): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('subscribed', $qb->createNamedParameter($subscribed, IQueryBuilder::PARAM_BOOL)));
		return $this->findEntities($qb);
	}

	/**
	 * Find all subscribed podcast metadata entries
	 *
	 * @return PodcastSubscription[]
	 */
	public function findAllSubscribed(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('subscribed', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)));
		return $this->findEntities($qb);
	}

	/**
	 * Find a podcast metadata entry by URL
	 *
	 * @param string $userId
	 * @param string $url
	 * @return PodcastSubscription|null
	 */
	public function findByUrl(string $userId, string $url): ?PodcastSubscription {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('url', $qb->createNamedParameter($url)));

		return $this->findEntity($qb, true);
	}
}
