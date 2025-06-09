<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getRemoteUuid()
 * @method void setRemoteUuid(string $remoteUuid)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getStreamUrl()
 * @method void setStreamUrl(string $streamUrl)
 * @method string|null getHomepage()
 * @method void setHomepage(?string $homepage)
 * @method string|null getFavicon()
 * @method void setFavicon(?string $favicon)
 * @method string|null getCountry()
 * @method void setCountry(?string $country)
 * @method string|null getState()
 * @method void setState(?string $state)
 * @method string|null getLanguage()
 * @method void setLanguage(?string $language)
 * @method int|null getBitrate()
 * @method void setBitrate(?int $bitrate)
 * @method string|null getCodec()
 * @method void setCodec(?string $codec)
 * @method string|null getTags()
 * @method void setTags(?string $tags)
 * @method string|null getRawData()
 * @method void setRawData(?string $rawData)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getLastUpdated()
 * @method void setLastUpdated(int $lastUpdated)
 * @method bool isFavorited()
 * @method void setFavorited(bool $favorited)
 */
class JukeboxRadioStation extends Entity implements JsonSerializable {
	protected string $remoteUuid = '';
	protected string $name = '';
	protected string $streamUrl = '';
	protected ?string $homepage = null;
	protected ?string $favicon = null;
	protected ?string $country = null;
	protected ?string $state = null;
	protected ?string $language = null;
	protected ?int $bitrate = null;
	protected ?string $codec = null;
	protected ?string $tags = null;
	protected ?string $rawData = null;
	protected string $userId = '';
	protected int $lastUpdated = 0;
	protected bool $favorited = false;

	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'remoteUuid' => $this->remoteUuid,
			'name' => $this->name,
			'streamUrl' => $this->streamUrl,
			'homepage' => $this->homepage,
			'favicon' => $this->favicon,
			'country' => $this->country,
			'state' => $this->state,
			'language' => $this->language,
			'bitrate' => $this->bitrate,
			'codec' => $this->codec,
			'tags' => $this->tags,
			'rawData' => $this->rawData,
			'userId' => $this->userId,
			'lastUpdated' => $this->lastUpdated,
			'favorited' => $this->favorited,
		];
	}
}
