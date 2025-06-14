<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Db;

use OCA\Jukebox\AppInfo\Application;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<PodcastEpisodePlay>
 */
class PodcastEpisodePlayMapper extends QBMapper {
	public function __construct(
		IDBConnection $db,
		private LoggerInterface $logger,
	) {
		parent::__construct($db, Application::tableName('podcast_ep_plays'), PodcastEpisodePlay::class);
	}

	/**
	 * Find play for a given user and episode ID
	 *
	 * @param string $userId
	 * @param int $id
	 * @return PodcastEpisodePlay
	 */
	public function findOneByEpisodeId(string $userId, int $episodeId): PodcastEpisodePlay {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('episode_id', $qb->createNamedParameter($episodeId)))
			->orderBy('timestamp', 'DESC')
			->setMaxResults(1);
		return $this->findEntity($qb);
	}

	/**
	 * Get the position of a podcast episode play for a user and episode ID
	 *
	 * @param string $userId
	 * @param int $episodeId
	 * @return ?int
	 */
	public function getPositionForEpisode(string $userId, int $episodeId): ?int {
		try {
			$row = $this->findOneByEpisodeId($userId, $episodeId);

			return $row?->getPosition();
		} catch (\Exception $e) {
			$this->logger->error('Failed to get position for episode play: ' . $e->getMessage());
			return null;
		}
	}

	/**
	 * Find plays for a given user and episode GUID
	 *
	 * @param string $userId
	 * @param string $guid
	 * @return PodcastEpisodePlay[]
	 */
	public function findByUserAndGuid(string $userId, string $guid): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('episode_guid', $qb->createNamedParameter($guid)));

		return $this->findEntities($qb);
	}

	/**
	 * Get latest playback for a user and episode (e.g. for resume)
	 *
	 * @param string $userId
	 * @param string $guid
	 * @return PodcastEpisodePlay|null
	 */
	public function findLatestPlay(string $userId, string $guid): ?PodcastEpisodePlay {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
			->andWhere($qb->expr()->eq('episode_guid', $qb->createNamedParameter($guid)))
			->orderBy('timestamp', 'DESC')
			->setMaxResults(1);

		return $this->findEntity($qb, true);
	}
}
