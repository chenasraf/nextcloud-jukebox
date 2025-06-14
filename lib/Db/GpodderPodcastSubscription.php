<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Chen Asraf <contact@casraf.dev>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Jukebox\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId($value)
 * @method string getUrl()
 * @method void setUrl($value)
 * @method bool getSubscribed()
 * @method void setSubscribed($value)
 * @method int getUpdated()
 * @method void setUpdated($value)
 */
class GpoddePodcastSubscription extends Entity implements JsonSerializable {
	protected $userId = '';
	protected $url = '';
	protected $subscribed = false;
	protected $updated = 0;

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('url', 'string');
		$this->addType('subscribed', 'bool');
		$this->addType('updated', 'int');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'url' => $this->getUrl(),
			'subscribed' => $this->getSubscribed(),
			'updated' => $this->getUpdated(),
		];
	}
}
