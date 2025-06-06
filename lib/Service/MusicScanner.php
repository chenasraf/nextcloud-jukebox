<?php

declare(strict_types=1);

namespace OCA\Jukebox\Service;

use getID3;
use OCA\Jukebox\AppInfo\Application;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Class MusicScanner
 *
 * Scans user folders for audio files and extracts metadata such as artist, album, and title.
 */
class MusicScanner {
	private IRootFolder $rootFolder;
	private IUserSession $userSession;

	public function __construct(
		IRootFolder $rootFolder,
		IUserSession $userSession,
		private LoggerInterface $logger,
		private IAppConfig $appConfig,
	) {
		$this->rootFolder = $rootFolder;
		$this->userSession = $userSession;
	}

	/**
	 * Starts scanning the user's configured music directory for audio files.
	 *
	 * @return void
	 */

	public function scanMusicFiles(): void {
		$user = $this->userSession->getUser();
		if ($user === null) {
			$this->logger->warning('Music scan aborted: no user session.');
			return;
		}

		$this->scanUserByUID($user->getUID());
	}

	/**
	 * Scans the music directory for a specific user by UID.
	 *
	 * @param string $uid
	 * @return void
	 */
	public function scanUserByUID(string $uid): void {
		try {
			$userFolder = $this->rootFolder->getUserFolder($uid);

			$relativePath = $this->appConfig->getValueString(Application::APP_ID, 'music_folder_path_' . $uid, 'Music');

			/** @var Folder $musicFolder */
			$musicFolder = $userFolder->get($relativePath);
			if (!($musicFolder instanceof Folder)) {
				$this->logger->warning("Configured music path '$relativePath' for user $uid is not a folder.");
				return;
			}

			$this->logger->info("Starting music scan for user '$uid' in folder '$relativePath'");
			$this->traverseFolder($musicFolder, $uid);
		} catch (NotFoundException $e) {
			$this->logger->error("Could not find music folder for user $uid: " . $e->getMessage());
		}
	}

	/**
	 * Recursively traverses a folder and processes audio files.
	 *
	 * @param Folder $folder
	 * @param string $uid
	 * @return void
	 */
	private function traverseFolder(Folder $folder, string $uid): void {
		$this->logger->info('Scanning folder: ' . $folder->getPath());

		foreach ($folder->getDirectoryListing() as $node) {
			if ($node instanceof File) {
				$mimeType = $node->getMimeType();
				if (str_starts_with($mimeType, 'audio/')) {
					$this->logger->info('Found audio file: ' . $node->getPath() . " (MIME: $mimeType)");
					$this->processAudioFile($node, $uid);
				}
			} elseif ($node instanceof Folder) {
				$this->traverseFolder($node, $uid);
			}
		}
	}

	/**
	 * Downloads an audio file, reads metadata, and processes it.
	 *
	 * @param File $file
	 * @param string $uid
	 * @return void
	 */
	private function processAudioFile(File $file, string $uid): void {
		$this->logger->info('Processing audio file: ' . $file->getPath());

		$tempPath = tempnam(sys_get_temp_dir(), 'jukebox_');
		if ($tempPath === false) {
			$this->logger->error('Could not create temporary file for audio processing.');
			return;
		}

		$handle = fopen($tempPath, 'w');
		if ($handle === false) {
			$this->logger->error('Failed to open temporary file handle.');
			return;
		}

		$stream = $file->fopen('r');
		if ($stream === false) {
			fclose($handle);
			$this->logger->error('Failed to open file stream for ' . $file->getPath());
			return;
		}

		stream_copy_to_stream($stream, $handle);
		fclose($stream);
		fclose($handle);

		$getID3 = new getID3();
		$info = $getID3->analyze($tempPath);

		$songArtist = $info['tags']['id3v2']['artist'][0] ?? '';
		$albumArtist =
			$info['tags']['id3v2']['band'][0] ??
			$info['tags']['id3v2']['album_artist'][0] ??
			$info['tags']['quicktime']['album_artist'][0] ??
			$info['tags']['asf']['WM/AlbumArtist'][0] ??
			'';
		$album = $info['tags']['id3v2']['album'][0] ?? '';
		$title = $info['tags']['id3v2']['title'][0] ?? $file->getName();

		$this->logger->info("Scanned metadata for '{$file->getPath()}': Song Artist='{$songArtist}', Album Artist='{$albumArtist}', Album='{$album}', Title='{$title}'");

		unlink($tempPath);

		$this->saveMetadataToDatabase($uid, $file->getId(), $songArtist, $album, $title);
	}


	/**
	 * Placeholder method to save music metadata.
	 *
	 * @param string $userId
	 * @param int $fileId
	 * @param string $artist
	 * @param string $album
	 * @param string $title
	 * @return void
	 */
	private function saveMetadataToDatabase(string $userId, int $fileId, string $artist, string $album, string $title): void {
		// TODO: Implement database saving logic
	}
}
