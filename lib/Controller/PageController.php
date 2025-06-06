<?php

declare(strict_types=1);

namespace OCA\Jukebox\Controller;

use OCA\Jukebox\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class PageController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private LoggerInterface $logger,
	) {
		$this->logger->info('Jukebox page controller loaded');
		parent::__construct($appName, $request);
	}

	/**
	 * Main app page
	 *
	 * @return TemplateResponse<Http::STATUS_OK,array{}>
	 *
	 * 200: OK
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function index(): TemplateResponse {
		$this->logger->info('Jukebox main page loaded');
		return new TemplateResponse(Application::APP_ID, 'app', [
			'script' => 'main',
		]);
	}
}
