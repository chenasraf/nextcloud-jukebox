<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getPodcast()
 * @method void setPodcast(string $podcast)
 * @method string getEpisode()
 * @method void setEpisode(string $episode)
 * @method string getAction()
 * @method void setAction(string $action)
 * @method int getPosition()
 * @method void setPosition(int $position)
 * @method int getStarted()
 * @method void setStarted(int $started)
 * @method int getTotal()
 * @method void setTotal(int $total)
 * @method string getTimestamp()
 * @method void setTimestamp(string $timestamp)
 * @method int getTimestampEpoch()
 * @method void setTimestampEpoch(int $timestampEpoch)
 * @method string|null getGuid()
 * @method void setGuid(?string $guid)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 */
class GpodderPodcastEpisodeAction extends Entity implements JsonSerializable {
	protected string $podcast = '';
	protected string $episode = '';
	protected string $action = '';
	protected int $position = -1;
	protected int $started = -1;
	protected int $total = -1;
	protected string $timestamp = '';
	protected int $timestampEpoch = 0;
	protected ?string $guid = null;
	protected string $userId = '';

	public function jsonSerialize(): array {
		return [
			'podcast' => $this->getPodcast(),
			'episode' => $this->getEpisode(),
			'action' => $this->getAction(),
			'position' => $this->getPosition(),
			'started' => $this->getStarted(),
			'total' => $this->getTotal(),
			'timestamp' => $this->getTimestamp(),
			'timestampEpoch' => $this->getTimestampEpoch(),
			'guid' => $this->getGuid(),
			'userId' => $this->getUserId(),
		];
	}
}
