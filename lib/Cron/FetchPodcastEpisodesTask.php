<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Cron;

use OCA\Jukebox\Db\PodcastSubscriptionMapper;
use OCA\Jukebox\Service\PodcastEpisodeWriterService;
use OCA\Jukebox\Service\PodcastFeedParserService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class FetchPodcastEpisodesTask extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private LoggerInterface $logger,
		private PodcastSubscriptionMapper $subMapper,
		private PodcastFeedParserService $parser,
		private PodcastEpisodeWriterService $writer,
	) {
		parent::__construct($time);

		// Run once an hour
		$this->setInterval(3600);
	}

	protected function run($arguments): void {
		$this->logger->info('Running FetchPodcastEpisodesTask', [
			'arguments' => $arguments,
		]);
		if (isset($arguments['userId']) && isset($arguments['subscriptionId'])) {
			$sub = $this->subMapper->find($arguments['userId'], $arguments['subscriptionId']);
			$allSubs = $sub ? [$sub] : [];
		} else {
			$allSubs = $this->subMapper->findAllSubscribed();
		}

		foreach ($allSubs as $sub) {
			$userId = $sub->getUserId();
			$url = $sub->getUrl();

			if (!$userId || !$url) {
				$this->logger->warning('Skipping podcast subscription with missing userId or url', [
					'subscriptionId' => $sub->getId(),
					'userId' => $userId,
					'url' => $url,
				]);
				continue;
			}

			$this->logger->info('Fetching podcast episodes', [
				'userId' => $userId,
				'url' => $url,
			]);

			try {
				$parsed = $this->parser->parseEpisodes($url);
				$this->writer->storeEpisodes($userId, $sub, $parsed['episodes']);
				$this->logger->info('Fetched podcast episodes', [
					'userId' => $userId,
					'url' => $url,
					'episodesCount' => count($parsed['episodes']),
				]);
			} catch (\Throwable $e) {
				$this->logger->error('Failed to fetch podcast episodes', [
					'userId' => $userId,
					'url' => $url,
					'error' => $e->getMessage(),
				]);
			}
		}
	}
}
