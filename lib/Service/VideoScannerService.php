<?php

declare(strict_types=1);

namespace OCA\Jukebox\Service;

use getID3;
use OCA\Jukebox\AppInfo\Application;
use OCA\Jukebox\Db\Video;
use OCA\Jukebox\Db\VideoMapper;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Class VideoScannerService
 *
 * Scans user folders for video files and extracts metadata such as title, duration, and video properties.
 */
class VideoScannerService {
	private IRootFolder $rootFolder;
	private IUserSession $userSession;

	public function __construct(
		IRootFolder $rootFolder,
		IUserSession $userSession,
		private LoggerInterface $logger,
		private IAppConfig $appConfig,
		private VideoMapper $videoMapper,
		private IDBConnection $db,
	) {
		$this->rootFolder = $rootFolder;
		$this->userSession = $userSession;
	}

	/**
	 * Starts scanning the user's configured video directory for video files.
	 *
	 * @return void
	 */

	public function scanVideoFiles(): void {
		$user = $this->userSession->getUser();
		if ($user === null) {
			$this->logger->warning('Video scan aborted: no user session.');
			return;
		}

		$this->scanUserByUID($user->getUID());
	}

	/**
	 * Scans the video directory for a specific user by UID.
	 *
	 * @param string $uid
	 * @return void
	 */
	public function scanUserByUID(string $uid): void {
		try {
			$this->db->beginTransaction();
			$userFolder = $this->rootFolder->getUserFolder($uid);

			$relativePath = $this->appConfig->getValueString(Application::APP_ID, 'videos_folder_path_' . $uid, 'Videos');

			/** @var Folder $videoFolder */
			$videoFolder = $userFolder->get($relativePath);
			if (!($videoFolder instanceof Folder)) {
				$this->logger->warning("Configured video path '$relativePath' for user $uid is not a folder.");
				return;
			}

			$this->logger->info("Starting video scan for user '$uid' in folder '$relativePath'");
			$this->traverseFolder($videoFolder, $uid);
			$this->db->commit();
		} catch (NotFoundException $e) {
			$this->logger->error("Could not find video folder for user $uid: " . $e->getMessage());
		} catch (\Throwable $e) {
			$this->db->rollBack();
			$this->logger->error('Scan failed: ' . $e->getMessage());
		}
	}

	/**
	 * Recursively traverses a folder and processes video files.
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
				if (str_starts_with($mimeType, 'video/')) {
					$this->logger->info('Found video file: ' . $node->getPath() . " (MIME: $mimeType)");
					$this->processVideoFile($node, $uid);
				}
			} elseif ($node instanceof Folder) {
				$this->traverseFolder($node, $uid);
			}
		}
	}

	/**
	 * Downloads a video file, reads metadata, and processes it.
	 *
	 * @param File $file
	 * @param string $uid
	 * @return void
	 */
	private function processVideoFile(File $file, string $uid): void {
		$this->logger->info('Processing video file: ' . $file->getPath());

		$tempPath = tempnam(sys_get_temp_dir(), 'jukebox_video_');
		if ($tempPath === false) {
			$this->logger->error('Could not create temporary file for video processing.');
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
	 * Save video metadata to database.
	 *
	 * @param string $userId
	 * @param File $file
	 * @param array $info
	 * @return void
	 */
	private function saveMetadataToDatabase(string $userId, File $file, array $info): void {
		try {
			$path = $file->getPath();
			$mtime = $file->getMTime();

			$title = $info['tags']['quicktime']['title'][0]
				?? $info['filename']
				?? $file->getName();

			// Check for existing
			$existing = $this->videoMapper->findByUserIdAndPath($userId, $path);
			$video = $existing ?? new Video();

			$video->setUserId($userId);
			$video->setPath($path);
			$video->setMtime($mtime);
			$video->setTitle($title);

			$video->setDuration((int)($info['playtime_seconds'] ?? 0));
			$video->setGenre($info['tags']['quicktime']['genre'][0] ?? null);
			$video->setYear((int)($info['tags']['quicktime']['year'][0] ?? 0));
			$video->setBitrate((int)($info['bitrate'] ?? 0) / 1000);

			// Video-specific metadata
			$video->setWidth((int)($info['video']['resolution_x'] ?? 0));
			$video->setHeight((int)($info['video']['resolution_y'] ?? 0));
			$video->setVideoCodec($info['video']['dataformat'] ?? null);
			$video->setAudioCodec($info['audio']['dataformat'] ?? null);
			$video->setFramerate((float)($info['video']['frame_rate'] ?? 0));

			// Extract thumbnail if available
			if (!empty($info['comments']['picture'][0]['data'])) {
				$video->setThumbnail($info['comments']['picture'][0]['data']);
			}

			$sanitizedInfo = $this->sanitizeForJson($info);
			$rawData = json_encode($sanitizedInfo);
			if ($rawData !== false) {
				$video->setRawData($rawData);
			} else {
				$this->logger->warning("Failed to encode metadata for file '{$file->getPath()}'");
				if (json_last_error() !== JSON_ERROR_NONE) {
					$this->logger->warning('JSON encode error: ' . json_last_error_msg());
				}
			}

			if ($existing) {
				$this->videoMapper->update($video);
			} else {
				$this->videoMapper->insert($video);
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
