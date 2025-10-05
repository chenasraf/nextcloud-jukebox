<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getId()
 * @method void setId(int $id)
 * @method string getPath()
 * @method void setPath(string $path)
 * @method string|null getTitle()
 * @method void setTitle(?string $title)
 * @method int|null getDuration()
 * @method void setDuration(?int $duration)
 * @method string|null getThumbnail()
 * @method void setThumbnail(?string $thumbnail)
 * @method string|null getGenre()
 * @method void setGenre(?string $genre)
 * @method int|null getYear()
 * @method void setYear(?int $year)
 * @method int|null getBitrate()
 * @method void setBitrate(?int $bitrate)
 * @method int|null getWidth()
 * @method void setWidth(?int $width)
 * @method int|null getHeight()
 * @method void setHeight(?int $height)
 * @method string|null getVideoCodec()
 * @method void setVideoCodec(?string $videoCodec)
 * @method string|null getAudioCodec()
 * @method void setAudioCodec(?string $audioCodec)
 * @method float|null getFramerate()
 * @method void setFramerate(?float $framerate)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getMtime()
 * @method void setMtime(int $mtime)
 * @method string|null getRawData()
 * @method void setRawData(?string $rawData)
 * @method bool isFavorited()
 * @method void setFavorited(bool $favorited)
 */
class Video extends Entity implements JsonSerializable {
	protected string $path = '';
	protected ?string $title = null;
	protected ?int $duration = null;
	protected ?string $thumbnail = null;
	protected ?string $genre = null;
	protected ?int $year = null;
	protected ?int $bitrate = null;
	protected ?int $width = null;
	protected ?int $height = null;
	protected ?string $videoCodec = null;
	protected ?string $audioCodec = null;
	protected ?float $framerate = null;
	protected string $userId = '';
	protected int $mtime = 0;
	protected ?string $rawData = null;
	protected bool $favorited = false;

	public function __construct() {
		$this->addType('title', 'string');
		$this->addType('duration', 'int');
		$this->addType('thumbnail', 'string');
		$this->addType('genre', 'string');
		$this->addType('year', 'int');
		$this->addType('bitrate', 'int');
		$this->addType('width', 'int');
		$this->addType('height', 'int');
		$this->addType('videoCodec', 'string');
		$this->addType('audioCodec', 'string');
		$this->addType('framerate', 'float');
		$this->addType('rawData', 'string');
		$this->addType('favorited', 'boolean');
	}

	/**
	 * Returns the base64-encoded version of the thumbnail blob
	 *
	 * @return string|null data URI like 'data:image/jpeg;base64,...' or null if no thumbnail
	 */
	public function getThumbnailBase64(): ?string {
		if ($this->thumbnail === null) {
			return null;
		}

		// Attempt to detect MIME type, fallback to jpeg
		$mime = 'image/jpeg';
		if (str_starts_with($this->thumbnail, "\x89PNG")) {
			$mime = 'image/png';
		} elseif (str_starts_with($this->thumbnail, 'GIF')) {
			$mime = 'image/gif';
		}

		return 'data:' . $mime . ';base64,' . base64_encode($this->thumbnail);
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'path' => $this->path,
			'title' => $this->title,
			'duration' => $this->duration,
			'thumbnail' => $this->getThumbnailBase64(),
			'genre' => $this->genre,
			'year' => $this->year,
			'bitrate' => $this->bitrate,
			'width' => $this->width,
			'height' => $this->height,
			'videoCodec' => $this->videoCodec,
			'audioCodec' => $this->audioCodec,
			'framerate' => $this->framerate,
			'userId' => $this->userId,
			'mtime' => $this->mtime,
			'rawData' => $this->rawData,
			'favorited' => $this->favorited,
		];
	}
}
