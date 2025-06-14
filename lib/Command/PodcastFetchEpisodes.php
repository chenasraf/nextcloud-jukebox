<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: Chen Asraf <casraf@pm.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Jukebox\Command;

use OCA\Jukebox\Db\PodcastSubscriptionMapper;
use OCA\Jukebox\Service\PodcastEpisodeWriterService;
use OCA\Jukebox\Service\PodcastFeedParserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PodcastFetchEpisodes extends Command {
	public function __construct(
		private PodcastSubscriptionMapper $subMapper,
		private PodcastFeedParserService $parser,
		private PodcastEpisodeWriterService $writer,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('jukebox:podcast-fetch-episodes')
			->setDescription('Fetch new podcast episodes for all or specific subscriptions')
			->addArgument('userId', InputArgument::OPTIONAL, 'User ID')
			->addArgument('subscriptionId', InputArgument::OPTIONAL, 'Subscription ID');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('userId');
		$subscriptionId = $input->getArgument('subscriptionId');

		$output->writeln('<info>Running FetchPodcastEpisodesTask</info>');
		if ($userId && $subscriptionId) {
			$output->writeln("Arguments received: userId={$userId}, subscriptionId={$subscriptionId}");
			$sub = $this->subMapper->find($userId, $subscriptionId);
			$allSubs = $sub ? [$sub] : [];
		} else {
			$output->writeln('<info>No specific arguments received. Fetching all subscribed feeds.</info>');
			$allSubs = $this->subMapper->findAllSubscribed();
		}

		foreach ($allSubs as $sub) {
			$userId = $sub->getUserId();
			$url = $sub->getUrl();

			if (!$userId || !$url) {
				$output->writeln("<comment>Skipping sub {$sub->getId()} due to missing userId or url</comment>");
				continue;
			}

			$output->writeln("<info>Fetching episodes for user {$userId} from {$url}</info>");

			try {
				$episodes = $this->parser->parseEpisodes($url);
				$this->writer->storeEpisodes($userId, $sub, $episodes);
				$output->writeln('<info>Fetched ' . count($episodes) . ' episodes</info>');
			} catch (\Throwable $e) {
				$output->writeln("<error>Failed to fetch episodes for {$url}: {$e->getMessage()}</error>");
			}
		}

		return Command::SUCCESS;
	}
}
