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

/**
 * @template-extends QBMapper<JukeboxMedia>
 */
class JukeboxMediaMapper extends QBMapper {
	public function __construct(
		IDBConnection $db,
	) {
		parent::__construct($db, Application::tableName('media'), JukeboxMedia::class);
	}

	/**
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws DoesNotExistException
	 */
	public function find(string $id): JukeboxMedia {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
			);
		return $this->findEntity($qb);
	}

	/**
	 * @return array<JukeboxMedia>
	 */
	public function findAll(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName());
		return $this->findEntities($qb);
	}

	/**
	 * @param string $userId
	 * @param string $mediaType
	 * @return array<JukeboxMedia>
	 */
	public function findByMediaType(string $userId, string $mediaType): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('user_id', $qb->createNamedParameter($userId)),
					$qb->expr()->eq('media_type', $qb->createNamedParameter($mediaType))
				)
			);
		return $this->findEntities($qb);
	}

	/**
	 * @param string $userId
	 * @param string|null $mediaType
	 * @param string $query
	 * @return array<JukeboxMedia>
	 */
	public function searchMedia(string $userId, ?string $mediaType, string $query): array {
		$qb = $this->db->getQueryBuilder();
		$expr = $qb->expr();

		$searchExpr = $expr->orX(
			$expr->iLike('title', $qb->createNamedParameter('%' . $query . '%')),
			$expr->iLike('artist', $qb->createNamedParameter('%' . $query . '%')),
			$expr->iLike('album', $qb->createNamedParameter('%' . $query . '%'))
		);

		$conditions = [
			$expr->eq('user_id', $qb->createNamedParameter($userId)),
			$searchExpr,
		];

		if ($mediaType !== null) {
			$conditions[] = $expr->eq('media_type', $qb->createNamedParameter($mediaType));
		}

		$qb->select('*')
			->from($this->getTableName())
			->where($expr->andX(...$conditions));

		return $this->findEntities($qb);
	}

	/**
	 * @param string $userId
	 * @param string $album
	 * @return array<JukeboxMedia>
	 */
	public function findByAlbum(string $userId, string $album): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('user_id', $qb->createNamedParameter($userId)),
					$qb->expr()->eq('album', $qb->createNamedParameter($album))
				)
			);
		return $this->findEntities($qb);
	}

	/**
	 * @param string $userId
	 * @param string $artist
	 * @return array<JukeboxMedia>
	 */
	public function findByArtist(string $userId, string $artist): array {
		$qb = $this->db->getQueryBuilder();
		$expr = $qb->expr();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$expr->andX(
					$expr->eq('user_id', $qb->createNamedParameter($userId)),
					$expr->orX(
						$expr->eq('artist', $qb->createNamedParameter($artist)),
						$expr->eq('album_artist', $qb->createNamedParameter($artist))
					)
				)
			);
		return $this->findEntities($qb);
	}

	/**
	 * @param string $userId
	 * @param string $path
	 * @return JukeboxMedia|null
	 */
	public function findByUserIdAndPath(string $userId, string $path): ?JukeboxMedia {
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
