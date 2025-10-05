<?php

declare(strict_types=1);

namespace OCA\Jukebox\Cron;

use OCA\Jukebox\Service\VideoScannerService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class VideoScannerJob extends TimedJob {
	public function __construct(
		private ITimeFactory $time,
		private VideoScannerService $service,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->service = $service;
		$this->logger = $logger;

		// Run once a day
		$this->setInterval(3600);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
		$this->logger->info('VideoScannerJob initialized');
	}

	protected function run($argument): void {
		$this->service->scanVideoFiles();
	}
}
