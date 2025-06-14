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
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getEpisodeId()
 * @method void setEpisodeId(int $episodeGuid)
 * @method string getEpisodeGuid()
 * @method void setEpisodeGuid(string $episodeGuid)
 * @method string getAction()
 * @method void setAction(string $action)
 * @method int getTimestamp()
 * @method void setTimestamp(int $timestamp)
 * @method int|null getPosition()
 * @method void setPosition(?int $position)
 * @method int|null getTotal()
 * @method void setTotal(?int $total)
 * @method string|null getDevice()
 * @method void setDevice(?string $device)
 */
class PodcastEpisodePlay extends Entity implements JsonSerializable {
	protected string $userId = '';
	protected string $episodeGuid = '';
	protected int $episodeId = 0;
	protected string $action = '';
	protected int $timestamp = 0;
	protected ?int $position = null;
	protected ?int $total = null;
	protected ?string $device = null;

	public function __construct() {
		$this->addType('id', 'int');
		$this->addType('userId', 'string');
		$this->addType('episodeGuid', 'string');
		$this->addType('episodeId', 'string');
		$this->addType('action', 'string');
		$this->addType('timestamp', 'int');
		$this->addType('position', 'int');
		$this->addType('total', 'int');
		$this->addType('device', 'string');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'userId' => $this->getUserId(),
			'episodeId' => $this->getEpisodeGuid(),
			'episodeGuid' => $this->getEpisodeGuid(),
			'action' => $this->getAction(),
			'timestamp' => $this->getTimestamp(),
			'position' => $this->getPosition(),
			'total' => $this->getTotal(),
			'device' => $this->getDevice(),
		];
	}
}
