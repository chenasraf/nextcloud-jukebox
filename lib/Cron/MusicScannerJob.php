<?php

declare(strict_types=1);

namespace OCA\Jukebox\Cron;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class MusicScannerJob extends TimedJob {
	public function __construct(
		private ITimeFactory $time,
		private MusicScanner $service,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->service = $service;
		$this->logger = $logger;

		// Run once a day
		$this->setInterval(3600);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
		$this->logger->info('MusicScannerJob initialized');
	}

	protected function run($argument): void {
		$this->service->scanMusicFiles();
	}
}
