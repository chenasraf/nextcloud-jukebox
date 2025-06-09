<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: Chen Asraf <casraf@pm.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Jukebox\Command;

use OCA\Jukebox\Db\JukeboxRadioStationMapper;
use OCA\Jukebox\Service\RadioSourcesService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportRadioStations extends Command {
	public function __construct(
		private RadioSourcesService $service,
		private JukeboxRadioStationMapper $stationMapper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('jukebox:import-radio')
			->setDescription('Import internet radio stations for a user')
			->addArgument('uid', InputArgument::REQUIRED, 'User ID to import stations for');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = $input->getArgument('uid');

		try {
			$existingCount = $this->stationMapper->countForUser($uid);
			$output->writeln("<info>Importing radio stations for user '$uid' (starting from offset $existingCount)...</info>");

			$count = $this->service->importStations($uid, $existingCount);

			$output->writeln("<info>Successfully imported or updated $count stations.</info>");
			return Command::SUCCESS;
		} catch (\Throwable $e) {
			$output->writeln('<error>Import failed: ' . $e->getMessage() . '</error>');
			return Command::FAILURE;
		}
	}
}
