<?php

declare(strict_types=1);

namespace OCA\Jukebox\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method int|null getActionId()
 * @method void setActionId(?int $id)
 * @method int getSubscriptionId()
 * @method void setSubscriptionId(int $id)
 * @method string|null getTitle()
 * @method void setTitle(?string $title)
 * @method string|null getGuid()
 * @method void setGuid(?string $guid)
 * @method \DateTimeInterface|null getPubDate()
 * @method void setPubDate(?\DateTimeInterface $date)
 * @method int|null getDuration()
 * @method void setDuration(?int $duration)
 * @method string|null getMediaUrl()
 * @method void setMediaUrl(?string $url)
 * @method string|null getDescription()
 * @method void setDescription(?string $desc)
 * @method string|null getUserId()
 * @method void setUserId(?string $uid)
 */
class PodcastEpisode extends Entity implements JsonSerializable {
	protected ?int $actionId = null;
	protected ?int $subscriptionId = null;
	protected ?string $title = null;
	protected ?string $guid = null;
	protected ?\DateTimeInterface $pubDate = null;
	protected ?int $duration = null;
	protected ?string $mediaUrl = null;
	protected ?string $description = null;
	protected ?string $userId = null;

	public function __construct() {
		$this->addType('actionId', 'integer');
		$this->addType('subscriptionId', 'integer');
		$this->addType('title', 'string');
		$this->addType('guid', 'string');
		$this->addType('pubDate', 'datetime');
		$this->addType('duration', 'integer');
		$this->addType('mediaUrl', 'string');
		$this->addType('description', 'string');
		$this->addType('userId', 'string');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'action_id' => $this->getActionId(),
			'subscription_id' => $this->getSubscriptionId(),
			'title' => $this->getTitle(),
			'guid' => $this->getGuid(),
			'pub_date' => $this->getPubDate()?->format(DATE_ATOM),
			'duration' => $this->getDuration(),
			'media_url' => $this->getMediaUrl(),
			'description' => $this->getDescription(),
			'user_id' => $this->getUserId(),
		];
	}
}
