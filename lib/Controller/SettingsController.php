<?php

declare(strict_types=1);

namespace OCA\Jukebox\Controller;

use OCA\Jukebox\AppInfo\Application;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Handles user-specific settings such as the music folder path.
 */
class SettingsController extends OCSController {
	private IAppConfig $config;
	private IUserSession $userSession;

	public function __construct(
		string $appName,
		IRequest $request,
		IAppConfig $config,
		IUserSession $userSession,
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->userSession = $userSession;
	}

	/**
	 * Save user-specific settings
	 *
	 * @param array<string, mixed> $data Data to save
	 * @return DataResponse<Http::STATUS_OK, array{status: non-empty-string}, array{}>
	 *
	 * 200: Settings saved
	 */
	#[ApiRoute(verb: 'PUT', url: '/api/settings')]
	public function saveSettings(mixed $data): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse(['status' => 'unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$uid = $user->getUID();

		if (array_key_exists('music_folder_path', $data)) {
			$this->config->setValueString(Application::APP_ID, 'music_folder_path_' . $uid, $data['music_folder_path']);
		}

		return new DataResponse(['status' => 'OK']);
	}

	/**
	 * Fetch all user-specific settings
	 *
	 * @return DataResponse<Http::STATUS_OK, array<string, string>, array{}>
	 *
	 * 200: Current settings
	 */
	#[ApiRoute(verb: 'GET', url: '/api/settings')]
	public function getSettings(): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}

		$uid = $user->getUID();
		$result = [];

		$musicPath = $this->config->getValueString(Application::APP_ID, 'music_folder_path_' . $uid, 'Music');
		if ($musicPath !== null) {
			$result['music_folder_path'] = $musicPath;
		}

		return new JSONResponse($result);
	}
}
