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
 * @template-extends QBMapper<JukeboxMedia>
 */
class JukeboxMediaMapper extends QBMapper {
	public function __construct(
		IDBConnection $db,
		private LoggerInterface $logger,
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
	 * @param string $albumArtist
	 * @param string $album
	 * @return array<JukeboxMedia>
	 */
	public function findByAlbum(string $userId, string $artist, string $album): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->andX(
					$qb->expr()->eq('user_id', $qb->createNamedParameter($userId)),
					$qb->expr()->eq('album', $qb->createNamedParameter($album)),
					$qb->expr()->eq('album_artist', $qb->createNamedParameter($artist)),
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

	/**
	 * Group media entries by artist name for the current user.
	 *
	 * @param string $userId
	 * @return array<array{name: string, cover: string|null, genre: string|null}>
	 */
	public function listGroupedArtists(string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$expr = $qb->expr();

		$qb->selectAlias(
			$qb->createFunction('COALESCE(`album_artist`, `artist`)'),
			'name'
		)
			->selectAlias($qb->createFunction('MIN(`album_art`)'), 'album_art')
			->selectAlias($qb->createFunction('MIN(`genre`)'), 'genre')
			->from($this->getTableName())
			->where(
				$qb->expr()->andX(
					$expr->eq('user_id', $qb->createNamedParameter($userId)),
					$expr->isNotNull('artist'),
					$expr->neq('artist', $qb->createNamedParameter('')))
			)
			->orderBy('name')
			->groupBy('name');

		$stmt = $qb->executeQuery();
		$results = [];

		while ($row = $stmt->fetch()) {
			$results[] = [
				'name' => $row['name'],
				'cover' => $this->getImageBlobBase64($row['album_art']),
				'genre' => $row['genre'] ?? null,
			];
		}

		$stmt->closeCursor();
		return $results;
	}

	/**
	 * Returns the base64-encoded version of an image blob
	 *
	 * @param string|null $image An image blob, typically from the database
	 * @return string|null data URI like 'data:image/jpeg;base64,...' or null if no art
	 */
	private function getImageBlobBase64(?string $image): ?string {
		if ($image === '' || $image === null) {
			return null; // No image data
		}
		// Attempt to detect MIME type, fallback to jpeg
		$mime = 'image/jpeg';
		if (str_starts_with($image, "\x89PNG")) {
			$mime = 'image/png';
		} elseif (str_starts_with($image, 'GIF')) {
			$mime = 'image/gif';
		}

		return 'data:' . $mime . ';base64,' . base64_encode($image);
	}
}
