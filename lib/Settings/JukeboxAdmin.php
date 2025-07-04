<?php

namespace OCA\Jukebox\Settings;

use OCA\Jukebox\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;
use OCP\Util;

class JukeboxAdmin implements ISettings {
	private IL10N $l;
	private IAppConfig $config;

	public function __construct(IAppConfig $config, IL10N $l) {
		$this->config = $config;
		$this->l = $l;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		Util::addScript(Application::APP_ID, Application::JS_DIR . '/Jukebox-main');
		Util::addStyle(Application::APP_ID, Application::CSS_DIR . '/Jukebox-style');
		return new TemplateResponse(Application::APP_ID, 'settings', [], '');
	}

	public function getSection(): string {
		return Application::APP_ID;
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 10;
	}
}
