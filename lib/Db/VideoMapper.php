<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Db;

use OCA\Jukebox\AppInfo\Application;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<Video>
 */
class VideoMapper extends QBMapper {
	public function __construct(
		IDBConnection $db,
		private LoggerInterface $logger,
	) {
		parent::__construct($db, Application::tableName('videos'), Video::class);
	}

	/**
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
	public function find(string $userId, string $id): Video {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
			)
			->andWhere(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId))
			);
		return $this->findEntity($qb);
	}

	/**
	 * Find all video entries for a specific user
	 *
	 * @param string $userId
	 * @return array<Video>
	 */
	public function findByUserId(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId))
			);

		return $this->findEntities($qb);
	}

	/**
	 * @return array<Video>
	 */
	public function findAll(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName());
		return $this->findEntities($qb);
	}

	/**
	 * @param string $userId
	 * @param string $query
	 * @return array<Video>
	 */
	public function searchVideos(string $userId, string $query): array {
		$qb = $this->db->getQueryBuilder();
		$expr = $qb->expr();

		$searchExpr = $expr->iLike('title', $qb->createNamedParameter('%' . $query . '%'));

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$expr->andX(
					$expr->eq('user_id', $qb->createNamedParameter($userId)),
					$searchExpr
				)
			);

		return $this->findEntities($qb);
	}

	/**
	 * @param string $userId
	 * @param string $path
	 * @return Video|null
	 */
	public function findByUserIdAndPath(string $userId, string $path): ?Video {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('user_id', $qb->createNamedParameter($userId)),
					$qb->expr()->eq('path', $qb->createNamedParameter($path))
				)
			)
			->setMaxResults(1);

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			return null;
		}
	}

}
