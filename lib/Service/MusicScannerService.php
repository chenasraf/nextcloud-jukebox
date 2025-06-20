<?php

declare(strict_types=1);

namespace OCA\Jukebox\Service;

use getID3;
use OCA\Jukebox\AppInfo\Application;
use OCA\Jukebox\Db\Track;
use OCA\Jukebox\Db\TrackMapper;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Class MusicScannerService
 *
 * Scans user folders for audio files and extracts metadata such as artist, album, and title.
 */
class MusicScannerService {
	private IRootFolder $rootFolder;
	private IUserSession $userSession;

	public function __construct(
		IRootFolder $rootFolder,
		IUserSession $userSession,
		private LoggerInterface $logger,
		private IAppConfig $appConfig,
		private TrackMapper $musicMapper,
		private IDBConnection $db,
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
			$this->db->beginTransaction();
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
			$this->db->commit();
		} catch (NotFoundException $e) {
			$this->logger->error("Could not find music folder for user $uid: " . $e->getMessage());
		} catch (\Throwable $e) {
			$this->db->rollBack();
			$this->logger->error('Scan failed: ' . $e->getMessage());
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

		$stream = $file->fopen('r');
		$handle = fopen($tempPath, 'w');

		if ($stream === false || $handle === false) {
			$this->logger->error('Failed to open file stream or temp handle.');
			return;
		}

		stream_copy_to_stream($stream, $handle);
		fclose($stream);
		fclose($handle);

		$getID3 = new getID3();
		$info = $getID3->analyze($tempPath);
		unlink($tempPath);

		$this->saveMetadataToDatabase($uid, $file, $info);
	}

	/**
	 * Placeholder method to save music metadata.
	 *
	 * @param string $userId
	 * @param File $fileId
	 * @param array $info
	 * @return void
	 */
	private function saveMetadataToDatabase(string $userId, File $file, array $info): void {
		try {
			$path = $file->getPath();
			$mtime = $file->getMTime();

			$title = $info['tags']['id3v2']['title'][0]
				?? $file->getName();

			$trackArtist = $info['tags']['id3v2']['artist'][0] ?? '';
			$albumArtist
				= $info['tags']['id3v2']['band'][0]
				?? $info['tags']['id3v2']['album_artist'][0]
				?? $info['tags']['quicktime']['album_artist'][0]
				?? $info['tags']['asf']['WM/AlbumArtist'][0]
				?? '';
			$album = $info['tags']['id3v2']['album'][0] ?? '';

			// Check for existing
			$existing = $this->musicMapper->findByUserIdAndPath($userId, $path);
			$media = $existing ?? new Track();

			$media->setUserId($userId);
			$media->setPath($path);
			$media->setMtime($mtime);
			$media->setTitle($title);
			$media->setArtist($trackArtist);
			$media->setAlbumArtist($albumArtist);
			$media->setAlbum($album);

			$media->setTrackNumber($info['tags']['id3v2']['track_number'][0] ?? null);
			$media->setDuration((int)($info['playtime_seconds'] ?? 0));
			$media->setGenre($info['tags']['id3v2']['genre'][0] ?? null);
			$media->setYear((int)($info['tags']['id3v2']['year'][0] ?? 0));
			$media->setBitrate((int)($info['audio']['bitrate'] ?? 0) / 1000);
			$media->setCodec($info['audio']['dataformat'] ?? null);
			if (!empty($info['comments']['picture'][0]['data'])) {
				$media->setAlbumArt($info['comments']['picture'][0]['data']);
			}

			$sanitizedInfo = $this->sanitizeForJson($info);
			$rawData = json_encode($sanitizedInfo);
			if ($rawData !== false) {
				$media->setRawData($rawData);
			} else {
				$this->logger->warning("Failed to encode ID3 data for file '{$file->getPath()}'");
				if (json_last_error() !== JSON_ERROR_NONE) {
					$this->logger->warning('JSON encode error: ' . json_last_error_msg());
				}
			}

			if ($existing) {
				$this->musicMapper->update($media);
			} else {
				$this->musicMapper->insert($media);
			}

			$this->logger->info("Saved metadata for '$path'");
		} catch (\Throwable $e) {
			$this->logger->error("Failed to save metadata for file '{$file->getPath()}': " . $e->getMessage());
		}
	}

	private function sanitizeForJson(mixed $data, int $depth = 0): mixed {
		if ($depth > 30) {
			return '**depth limit exceeded**';
		}

		if (is_resource($data)) {
			return '**resource**';
		}

		if (is_object($data)) {
			if (method_exists($data, '__toString')) {
				return (string)$data;
			}
			return '**object**';
		}

		if (is_array($data)) {
			$sanitized = [];
			foreach ($data as $key => $value) {
				if (
					$key === 'data'
					&& is_string($value)
					&& isset($data['picturetype']) // heuristic for image
				) {
					// $sanitized[$key] = base64_encode($value);
					$sanitized[$key] = '**binary data**'; // avoid large base64 strings in JSON
				} else {
					$sanitized[$key] = $this->sanitizeForJson($value, $depth + 1);
				}
			}
			return $sanitized;
		}

		if (is_string($data)) {
			// Convert broken encodings to valid UTF-8 using iconv with translit
			if (!mb_check_encoding($data, 'UTF-8')) {
				$converted = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $data);
				return $converted !== false ? $converted : '**invalid string**';
			}
		}

		return $data;
	}
}
