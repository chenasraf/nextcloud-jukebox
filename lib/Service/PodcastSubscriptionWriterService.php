<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Service;

use OCA\Jukebox\Db\PodcastSubscription;
use OCA\Jukebox\Db\PodcastSubscriptionMapper;
use Psr\Log\LoggerInterface;

class PodcastSubscriptionWriterService {
	public function __construct(
		private LoggerInterface $logger,
		private PodcastSubscriptionMapper $subMapper,
		private PodcastFeedParserService $parser,
	) {
		//
	}

	public function fetchSubscriptionMetadata(PodcastSubscription $subscription): void {
		$feed = $this->parser->parseSubscriptionMetadata($subscription->getUrl());

		if (empty($subscription->getImage())) {
			$imageBase64 = $this->parser->fetchImageBase64($feed['imageUrl']);
		}
		$now = new \DateTime();

		$subscription->setUpdated($now);
		$subscription->setTitle($feed['title']);
		$subscription->setAuthor($feed['author']);
		$subscription->setDescription($feed['description']);
		if (empty($subscription->getImage()) && !empty($imageBase64)) {
			$subscription->setImage($imageBase64);
		}
		$this->subMapper->update($subscription);
	}
}
