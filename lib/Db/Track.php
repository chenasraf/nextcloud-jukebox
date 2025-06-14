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
 * @method int|null getTrackNumber()
 * @method void setTrackNumber(?int $trackNumber)
 * @method string|null getArtist()
 * @method void setArtist(?string $artist)
 * @method string|null getAlbum()
 * @method void setAlbum(?string $album)
 * @method string|null getAlbumArtist()
 * @method void setAlbumArtist(?string $albumArtist)
 * @method int|null getDuration()
 * @method void setDuration(?int $duration)
 * @method string|null getAlbumArt()
 * @method void setAlbumArt(?string $albumArt)
 * @method string|null getGenre()
 * @method void setGenre(?string $genre)
 * @method int|null getYear()
 * @method void setYear(?int $year)
 * @method int|null getBitrate()
 * @method void setBitrate(?int $bitrate)
 * @method string|null getCodec()
 * @method void setCodec(?string $codec)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getMtime()
 * @method void setMtime(int $mtime)
 * @method string|null getRawData()
 * @method void setRawData(?string $rawData)
 * @method bool isFavorited()
 * @method void setFavorited(bool $favorited)
 */
class Track extends Entity implements JsonSerializable {
	protected string $path = '';
	protected ?string $title = null;
	protected ?int $trackNumber = null;
	protected ?string $artist = null;
	protected ?string $album = null;
	protected ?string $albumArtist = null;
	protected ?int $duration = null;
	protected ?string $albumArt = null;
	protected ?string $genre = null;
	protected ?int $year = null;
	protected ?int $bitrate = null;
	protected ?string $codec = null;
	protected string $userId = '';
	protected int $mtime = 0;
	protected ?string $rawData = null;
	protected bool $favorited = false;

	public function __construct() {
		$this->addType('title', 'string');
		$this->addType('trackNumber', 'int');
		$this->addType('artist', 'string');
		$this->addType('album', 'string');
		$this->addType('albumArtist', 'string');
		$this->addType('duration', 'int');
		$this->addType('albumArt', 'string');
		$this->addType('genre', 'string');
		$this->addType('year', 'int');
		$this->addType('bitrate', 'int');
		$this->addType('codec', 'string');
		$this->addType('rawData', 'string');
		$this->addType('favorited', 'boolean');
	}

	/**
	 * Returns the base64-encoded version of the album art blob
	 *
	 * @return string|null data URI like 'data:image/jpeg;base64,...' or null if no art
	 */
	public function getAlbumArtBase64(): ?string {
		if ($this->albumArt === null) {
			return null;
		}

		// Attempt to detect MIME type, fallback to jpeg
		$mime = 'image/jpeg';
		if (str_starts_with($this->albumArt, "\x89PNG")) {
			$mime = 'image/png';
		} elseif (str_starts_with($this->albumArt, 'GIF')) {
			$mime = 'image/gif';
		}

		return 'data:' . $mime . ';base64,' . base64_encode($this->albumArt);
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'path' => $this->path,
			'title' => $this->title,
			'trackNumber' => $this->trackNumber,
			'artist' => $this->artist,
			'album' => $this->album,
			'albumArtist' => $this->albumArtist,
			'duration' => $this->duration,
			'albumArt' => $this->getAlbumArtBase64(),
			'genre' => $this->genre,
			'year' => $this->year,
			'bitrate' => $this->bitrate,
			'codec' => $this->codec,
			'userId' => $this->userId,
			'mtime' => $this->mtime,
			'rawData' => $this->rawData,
			'favorited' => $this->favorited,
		];
	}
}
