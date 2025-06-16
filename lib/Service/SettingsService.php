<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Service;

use OCA\Jukebox\AppInfo\Application;
use OCA\Jukebox\Db\PodcastEpisodeMapper;
use OCA\Jukebox\Db\PodcastSubscriptionMapper;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

class SettingsService {
	public function __construct(
		private LoggerInterface $logger,
		private IAppConfig $config,
		private PodcastSubscriptionMapper $subsMapper,
		private PodcastEpisodeMapper $epMapper,
	) {
		//
	}

	public function setString(string $userId, string $name, string $value): void {
		$key = "{$name}_{$userId}";
		$this->config->setValueString(Application::APP_ID, $key, $value);
	}

	public function getString(string $userId, string $name, ?string $default): ?string {
		$key = "{$name}_{$userId}";
		return $this->config->getValueString(Application::APP_ID, $key, $default);
	}

	public function setBool(string $userId, string $name, bool $value): void {
		$key = "{$name}_{$userId}";
		$this->config->setValueBool(Application::APP_ID, $key, $value);
	}

	public function getBool(string $userId, string $name, ?bool $default): ?bool {
		$key = "{$name}_{$userId}";
		return $this->config->getValueBool(Application::APP_ID, $key, $default);
	}

	public function getPodcastDownloadPath(string $userId, int $subscriptionId, int $episodeId): string {
		$path = $this->getString($userId, 'podcast_download_path', 'Podcasts');
		$sub = $this->subsMapper->find($userId, $subscriptionId);
		$ep = $this->epMapper->find($userId, $episodeId);
		return rtrim($path, '/') . "/{$sub->getTitle()}/{$ep->getTitle()}.mp3";
	}
}
