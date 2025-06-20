<?php

declare(strict_types=1);

namespace OCA\Jukebox\Service;

// SPDX-FileCopyrightText: Chen Asraf <casraf@pm.me>
// SPDX-License-Identifier: AGPL-3.0-or-later

use DateTimeInterface;
use OCA\Jukebox\Db\PodcastEpisode;
use OCA\Jukebox\Db\PodcastEpisodeMapper;
use OCA\Jukebox\Db\PodcastSubscription;
use Psr\Log\LoggerInterface;

class PodcastEpisodeWriterService {
	public function __construct(
		private LoggerInterface $logger,
		private PodcastEpisodeMapper $epMapper,
	) {
	}

	/**
	 * Save or update episodes for a given podcast subscription
	 *
	 * @param string $userId The user ID
	 * @param PodcastSubscription $sub The podcast subscription
	 * @param array<int, array{
	 *     guid: string|null,
	 *     title: string|null,
	 *     pubDate: DateTimeInterface|null,
	 *     duration: int|null,
	 *     mediaUrl: string|null,
	 *     description: string|null
	 * }> $episodes Parsed episode data
	 *
	 * @return PodcastEpisode[]
	 */
	public function storeEpisodes(string $userId, PodcastSubscription $sub, array $episodes): array {
		$results = [];
		foreach ($episodes as $data) {
			if (empty($data['guid'])) {
				$this->logger->debug('Skipping episode without GUID', ['subscriptionId' => $sub->getId()]);
				continue;
			}

			/** @var PodcastEpisode $existing */
			$ep = new PodcastEpisode();
			/** @var PodcastEpisode|null $existing */
			$existing = null;

			try {
				$existing = $this->epMapper->findByGuid($userId, (string)$data['guid']);
				$ep = $existing;
			} catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
				//
			}

			$this->logger->debug('Processing episode', [
				'guid' => $data['guid'],
				'title' => $data['title'],
				'subscriptionId' => $sub->getId(),
				'existing' => $existing ? 'yes' : 'no',
			]);

			$ep->setUserId($userId);
			$ep->setSubscriptionId((int)$sub->getId());
			$ep->setGuid((string)$data['guid']);
			$ep->setTitle($data['title']);
			$ep->setPubDate($data['pubDate'] instanceof \DateTimeInterface ? \DateTime::createFromInterface($data['pubDate']) : null);
			$ep->setDuration($data['duration']);
			$ep->setMediaUrl($data['mediaUrl']);
			$ep->setDescription($data['description']);

			if ($existing) {
				$this->epMapper->update($ep);
			} else {
				$this->epMapper->insert($ep);
			}
			$results[] = $ep;
		}

		return $results;
	}
}
