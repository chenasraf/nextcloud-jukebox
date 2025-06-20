<?php

declare(strict_types=1);

namespace OCA\Jukebox\Service;

use Psr\Log\LoggerInterface;
use SimplePie\Item;
use SimplePie\SimplePie;

class PodcastFeedParserService {
	public function __construct(
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Parse basic subscription metadata (title, image, etc.)
	 *
	 * @param string $url The URL of the podcast feed.
	 * @return array{
	 *     title: string|null,
	 *     description: string|null,
	 *     link: string|null,
	 *     author: string|null,
	 *     imageUrl: string|null
	 * }
	 *
	 * @throws \RuntimeException if feed is invalid
	 */
	public function parseSubscriptionMetadata(string $url): array {
		$feed = $this->loadFeed($url);

		return [
			'title' => $feed->get_title(),
			'description' => $feed->get_description(),
			'link' => $feed->get_link(),
			'author' => $feed->get_author()?->get_name() ?? null,
			'imageUrl' => $feed->get_image_url(),
		];
	}

	/**
	 * Parse episode entries only.
	 *
	 * @param string $feedUrl The URL of the podcast feed.
	 * @return array<int, array{
	 *     guid: string|null,
	 *     title: string|null,
	 *     pubDate: \DateTimeInterface|null,
	 *     duration: int|null,
	 *     mediaUrl: string|null,
	 *     description: string|null
	 * }>
	 *
	 * @return list<array{
	 * 	 guid: string|null,
	 * 	 title: string|null,
	 * 	 pubDate: \DateTimeInterface|null,
	 * 	 duration: int|null,
	 * 	 mediaUrl: string|null,
	 * 	 description: string|null
	 * }>
	 *
	 * @throws \RuntimeException If the feed cannot be parsed.
	 */
	public function parseEpisodes(string $feedUrl): array {
		$feed = $this->loadFeed($feedUrl);
		$episodes = [];

		foreach ($feed->get_items() as $item) {
			$enclosure = $item->get_enclosure();

			$episodes[] = [
				'guid' => $item->get_id(),
				'title' => $item->get_title(),
				'pubDate' => ($date = $item->get_date('c')) ? new \DateTimeImmutable($date) : null,
				'duration' => $this->parseDuration($item),
				'mediaUrl' => $enclosure?->get_link(),
				'description' => $item->get_description(),
			];
		}

		return $episodes;
	}

	/**
	 * Load and initialize a SimplePie feed from a URL.
	 *
	 * @param string $url The feed URL.
	 * @return SimplePie The initialized feed object.
	 * @throws \RuntimeException If the feed cannot be parsed.
	 */
	private function loadFeed(string $url): SimplePie {
		$feed = new SimplePie();
		$feed->set_feed_url($url);
		$feed->enable_cache(false);
		$feed->init();

		if ($feed->error()) {
			$this->logger->warning("Failed to parse feed: {$feed->error()}");
			throw new \RuntimeException("Failed to parse podcast feed: {$feed->error()}");
		}

		return $feed;
	}

	/**
	 * Parse the duration of a podcast episode item.
	 *
	 * @param Item $item The SimplePie item.
	 * @return int|null Duration in seconds, or null if not available.
	 */
	private function parseDuration(Item $item): ?int {
		$durationTag = $item->get_item_tags('http://www.itunes.com/dtds/podcast-1.0.dtd', 'duration');
		$raw = $durationTag[0]['data'] ?? null;

		if (!is_string($raw)) {
			return null;
		}

		$parts = array_reverse(explode(':', $raw));
		$seconds = 0;
		foreach ($parts as $i => $part) {
			$seconds += ((int)$part) * (60 ** $i);
		}
		return $seconds;
	}

	/**
	 * Fetch an image from a URL and encode it as a base64 data URI.
	 *
	 * @param string $imageUrl The image URL.
	 * @return string|null The base64-encoded image data URI, or null on failure.
	 */
	public function fetchImageBase64(string $imageUrl): ?string {
		$imageBase64 = null;
		if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
			try {
				$imageData = @file_get_contents($imageUrl);
				if ($imageData !== false) {
					$mimeType = finfo_buffer(finfo_open(), $imageData, FILEINFO_MIME_TYPE);
					$imageBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
				}
			} catch (\Throwable $e) {
				$this->logger->warning('Failed to fetch or encode podcast image', [
					'url' => $imageUrl,
					'error' => $e->getMessage(),
				]);
			}
		}
		return $imageBase64;
	}
}
