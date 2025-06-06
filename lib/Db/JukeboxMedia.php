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
 * @method string getMediaType()
 * @method void setMediaType(string $mediaType)
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
 * @method string|null getRawId3()
 * @method void setRawId3(?string $rawId3)
 */
class JukeboxMedia extends Entity implements JsonSerializable {
	protected string $mediaType = 'track';
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
	protected ?string $rawId3 = null;

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'mediaType' => $this->mediaType,
			'path' => $this->path,
			'title' => $this->title,
			'trackNumber' => $this->trackNumber,
			'artist' => $this->artist,
			'album' => $this->album,
			'albumArtist' => $this->albumArtist,
			'duration' => $this->duration,
			'albumArt' => $this->albumArt,
			'genre' => $this->genre,
			'year' => $this->year,
			'bitrate' => $this->bitrate,
			'codec' => $this->codec,
			'userId' => $this->userId,
			'mtime' => $this->mtime,
			'rawId3' => $this->rawId3,
		];
	}
}
