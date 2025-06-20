<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <casraf@pm.me>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Command;

use OCA\Jukebox\Service\GpodderSyncService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportGpodderSync extends Command {
	/**
	 * ImportGpodderSync constructor.
	 */
	public function __construct(
		private GpodderSyncService $gpodderService,
	) {
		parent::__construct();
	}

	/**
	 *
	 */
	protected function configure(): void {
		parent::configure();
		$this->setName('jukebox:import-gpodder-sync')
			->addArgument('user-id', InputArgument::REQUIRED, 'User ID to import subscriptions for');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('user-id');
		try {
			$output->writeln("<info>Importing gpodder subscriptions for user {$userId}...</info>");
			$this->gpodderService->importSubscriptions($userId, delayedFetch: false);
		} catch (\Throwable $e) {
			$output->writeln("<error>Failed to import subscriptions: {$e->getMessage()}\n{$e->getTraceAsString()}</error>");
			return 1;
		}

		try {
			$output->writeln('<info>Fetching gpodder episode actions...</info>');
			$this->gpodderService->importEpisodes($userId);
		} catch (\Throwable $e) {
			$output->writeln("<error>Failed to import episodes: {$e->getMessage()}\n{$e->getTraceAsString()}</error>");
			return 1;
		}

		$output->writeln("<info>Successfully imported subscriptions for user {$userId}.</info>");
		return 0;
	}
}
