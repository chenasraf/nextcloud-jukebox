<?php

declare(strict_types=1);

namespace OCA\Jukebox\Controller;

use OCA\Jukebox\Service\SettingsService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Handles user-specific settings such as the music folder path.
 */
class SettingsController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private SettingsService $settings,
		private IUserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Save user-specific settings
	 *
	 * @param array<string, mixed> $data Data to save
	 * @return DataResponse<Http::STATUS_OK, array{status: non-empty-string}, array{}>
	 *
	 * 200: Settings saved
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/settings')]
	public function saveSettings(mixed $data): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(['status' => 'unauthenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$uid = $user->getUID();

		if (array_key_exists('music_folder_path', $data)) {
			$this->settings->setString($uid, 'music_folder_path', $data['music_folder_path']);
		}
		if (array_key_exists('download_podcast_episodes', $data)) {
			$this->settings->setBool($uid, 'download_podcast_episodes', $data['download_podcast_episodes']);
		}
		if (array_key_exists('podcast_download_path', $data)) {
			$this->settings->setString($uid, 'podcast_download_path', $data['podcast_download_path']);
		}
		if (array_key_exists('audiobooks_folder_path', $data)) {
			$this->settings->setString($uid, 'audiobooks_folder_path', $data['audiobooks_folder_path']);
		}

		return new JSONResponse(['status' => 'OK']);
	}

	/**
	 * Fetch all user-specific settings
	 *
	 * @return DataResponse<Http::STATUS_OK, array<string, string>, array{}>
	 *
	 * 200: Current settings
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/settings')]
	public function getSettings(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}

		$uid = $user->getUID();
		$result = [];

		$result['music_folder_path'] = $this->settings->getString($uid, 'music_folder_path', 'Music');
		$result['download_podcast_episodes'] = $this->settings->getBool($uid, 'download_podcast_episodes', false);
		$result['podcast_download_path'] = $this->settings->getString($uid, 'podcast_download_path', 'Podcasts');
		$result['audiobooks_folder_path'] = $this->settings->getString($uid, 'audiobooks_folder_path', 'Audiobooks');

		return new JSONResponse($result);
	}
}
