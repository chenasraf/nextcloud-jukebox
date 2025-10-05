<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Jukebox\Command;

use OCA\Jukebox\Service\VideoScannerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScanVideos extends Command {
	public function __construct(
		private VideoScannerService $service,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('jukebox:scan-videos')
			->setDescription('Scan video files for a user')
			->addArgument('uid', InputArgument::OPTIONAL, 'User ID to scan. If not provided, uses the current session user.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$uid = $input->getArgument('uid');

		try {
			if ($uid) {
				$output->writeln("<info>Scanning video files for user '$uid'...</info>");
				$this->service->scanUserByUID($uid);
			} else {
				$output->writeln('<info>Scanning video files for the current session user...</info>');
				$this->service->scanVideoFiles();
			}

			$output->writeln('<info>Scan completed successfully.</info>');
			return Command::SUCCESS;
		} catch (\Throwable $e) {
			$output->writeln('<error>Scan failed: ' . $e->getMessage() . '</error>');
			return Command::FAILURE;
		}
	}
}
