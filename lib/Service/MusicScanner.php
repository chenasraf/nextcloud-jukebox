<?php

declare(strict_types=1);

namespace OCA\Jukebox\Service;

use getID3;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUser;
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

	/**
	 * @param IRootFolder $rootFolder
	 * @param IUserSession $userSession
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		IRootFolder $rootFolder,
		IUserSession $userSession,
		private LoggerInterface $logger,
	) {
		$this->rootFolder = $rootFolder;
		$this->userSession = $userSession;
	}

	/**
	 * Starts scanning the authenticated user's folder for music files.
	 *
	 * @return void
	 */
	public function scanMusicFiles(): void {
		$user = $this->userSession->getUser();
		if ($user === null) {
			// Handle unauthenticated user
			return;
		}

		try {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$this->traverseFolder($userFolder, $user);
		} catch (NotFoundException $e) {
			// Handle folder not found
		}
	}

	/**
	 * Recursively traverses a folder and processes audio files.
	 *
	 * @param Folder $folder
	 * @param IUser $user
	 * @return void
	 */
	private function traverseFolder(Folder $folder, IUser $user): void {
		foreach ($folder->getDirectoryListing() as $node) {
			if ($node instanceof File) {
				$mimeType = $node->getMimeType();
				if (str_starts_with($mimeType, 'audio/')) {
					$this->processAudioFile($node, $user);
				}
			} elseif ($node instanceof Folder) {
				$this->traverseFolder($node, $user);
			}
		}
	}

	/**
	 * Downloads an audio file, reads metadata, and processes it.
	 *
	 * @param File $file
	 * @param IUser $user
	 * @return void
	 */
	private function processAudioFile(File $file, IUser $user): void {
		$tempPath = tempnam(sys_get_temp_dir(), 'jukebox_');
		if ($tempPath === false) {
			$this->logger->error('Could not create temporary file for audio processing.');
			return; // Could not create temp file
		}

		$handle = fopen($tempPath, 'w');
		if ($handle === false) {
			return;
		}

		$stream = $file->fopen('r');
		if ($stream === false) {
			fclose($handle);
			return;
		}

		stream_copy_to_stream($stream, $handle);
		fclose($stream);
		fclose($handle);

		$getID3 = new getID3();
		$info = $getID3->analyze($tempPath);

		$artist = $info['tags']['id3v2']['artist'][0] ?? '';
		$album = $info['tags']['id3v2']['album'][0] ?? '';
		$title = $info['tags']['id3v2']['title'][0] ?? $file->getName();

		// Clean up temp file
		unlink($tempPath);

		// Stub: Save metadata to DB
		$this->saveMetadataToDatabase($user->getUID(), $file->getId(), $artist, $album, $title);
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
