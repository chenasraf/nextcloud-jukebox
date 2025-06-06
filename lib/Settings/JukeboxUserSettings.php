<?php

declare(strict_types=1);

namespace OCA\Jukebox\Settings;

use OCA\Jukebox\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;
use OCP\Util;

/**
 * Settings form shown under user's personal settings.
 */
class JukeboxUserSettings implements ISettings {
	private IL10N $l;
	private IAppConfig $config;

	public function __construct(IAppConfig $config, IL10N $l) {
		$this->config = $config;
		$this->l = $l;
	}

	public function getForm(): TemplateResponse {
		Util::addScript(Application::APP_ID, Application::JS_DIR . '/Jukebox-settings');
		Util::addStyle(Application::APP_ID, Application::CSS_DIR . '/Jukebox-style');
		return new TemplateResponse(Application::APP_ID, 'settings', []);
	}

	public function getSection(): string {
		return Application::APP_ID;
	}

	public function getPriority(): int {
		return 10;
	}
}
