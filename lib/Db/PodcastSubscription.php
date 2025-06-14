<?php

declare(strict_types=1);

namespace OCA\Jukebox\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method int|null getSubscriptionId()
 * @method void setSubscriptionId(?int $id)
 * @method string|null getTitle()
 * @method void setTitle(?string $title)
 * @method string|null getAuthor()
 * @method void setAuthor(?string $author)
 * @method string|null getDescription()
 * @method void setDescription(?string $desc)
 * @method string|null getUrl()
 * @method void setUrl(?string $url)
 * @method string|null getUserId()
 * @method void setUserId(?string $uid)
 * @method string|null getImage()
 * @method void setImage(?string $image)
 * @method bool getSubscribed()
 * @method void setSubscribed(bool $value)
 * @method \DateTimeInterface getUpdated()
 * @method void setUpdated(\DateTimeInterface $dt)
 */
class PodcastSubscription extends Entity implements JsonSerializable {
	protected ?int $subscriptionId = null;
	protected ?string $title = null;
	protected ?string $author = null;
	protected ?string $description = null;
	protected ?string $url = null;
	protected ?string $userId = null;
	protected ?string $image = null;
	protected bool $subscribed = true;
	protected ?\DateTimeInterface $updated = null;

	public function __construct() {
		$this->addType('subscriptionId', 'integer');
		$this->addType('title', 'string');
		$this->addType('author', 'string');
		$this->addType('description', 'string');
		$this->addType('url', 'string');
		$this->addType('userId', 'string');
		$this->addType('image', 'string');
		$this->addType('subscribed', 'boolean');
		$this->addType('updated', 'datetime');
	}

	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'subscription_id' => $this->getSubscriptionId(),
			'title' => $this->getTitle(),
			'author' => $this->getAuthor(),
			'description' => $this->getDescription(),
			'url' => $this->getUrl(),
			'user_id' => $this->getUserId(),
			'subscribed' => $this->getSubscribed(),
			'image' => $this->getImage(),
			'updated' => $this->getUpdated()?->format(DATE_ATOM),
		];
	}
}
