<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Cron;

use OCA\Jukebox\Db\PodcastSubscriptionMapper;
use OCA\Jukebox\Service\GpodderSyncService;
use OCA\Jukebox\Service\PodcastFeedParserService;
use OCA\Jukebox\Service\PodcastSubscriptionWriterService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

class ParsePodcastSubscriptionTask extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private LoggerInterface $logger,
		private PodcastSubscriptionMapper $subMapper,
		private PodcastFeedParserService $parser,
		private GpodderSyncService $gpodderService,
		private PodcastSubscriptionWriterService $subWriter,
	) {
		parent::__construct($time);
	}

	protected function run($arguments): void {
		$subscriptions = $this->subMapper->findAllUnfetched();

		foreach ($subscriptions as $subscription) {
			try {
				$this->subWriter->fetchSubscriptionMetadata($subscription);
			} catch (\Exception $e) {
				$this->logger->error("Failed to parse podcast subscription {$subscription->getId()}: {$e->getMessage()}");
			}
		}
	}
}
