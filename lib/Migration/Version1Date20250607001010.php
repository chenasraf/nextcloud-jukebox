<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: Chen Asraf <casraf@pm.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Jukebox\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1Date20250607001010 extends SimpleMigrationStep {
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		// Drop existing tables if they exist (dev only)
		if ($schema->hasTable('jukebox_music')) {
			$schema->dropTable('jukebox_music');
		}
		if ($schema->hasTable('jukebox_radio_stations')) {
			$schema->dropTable('jukebox_radio_stations');
		}
		if ($schema->hasTable('jukebox_podcast_subs')) {
			$schema->dropTable('jukebox_podcast_subs');
		}
		if ($schema->hasTable('jukebox_podcast_eps')) {
			$schema->dropTable('jukebox_podcast_eps');
		}
		if ($schema->hasTable('jukebox_podcast_ep_plays')) {
			$schema->dropTable('jukebox_podcast_ep_plays');
		}

		// Music Table
		$media = $schema->createTable('jukebox_music');

		$media->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
		]);
		$media->addColumn('path', 'string', [
			'notnull' => true,
			'length' => 1024,
		]);
		$media->addColumn('title', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$media->addColumn('track_number', 'integer', [
			'notnull' => false,
		]);
		$media->addColumn('artist', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$media->addColumn('album', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$media->addColumn('album_artist', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$media->addColumn('duration', 'integer', [
			'notnull' => false,
			'comment' => 'Duration in seconds',
		]);
		$media->addColumn('album_art', 'blob', [
			'notnull' => false,
			'comment' => 'Raw binary image data for album art',
		]);
		$media->addColumn('genre', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$media->addColumn('year', 'smallint', [
			'notnull' => false,
		]);
		$media->addColumn('bitrate', 'integer', [
			'notnull' => false,
			'comment' => 'In kbps',
		]);
		$media->addColumn('codec', 'string', [
			'notnull' => false,
			'length' => 100,
		]);
		$media->addColumn('user_id', 'string', [
			'notnull' => true,
			'length' => 64,
		]);
		$media->addColumn('mtime', 'bigint', [
			'notnull' => true,
			'comment' => 'File modified time',
		]);
		$media->addColumn('raw_data', 'text', [
			'notnull' => false,
			'comment' => 'Raw metadata (ID3) as JSON',
		]);
		$media->addColumn('favorited', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);

		$media->setPrimaryKey(['id']);
		$media->addIndex(['user_id'], 'media_user_idx');
		$media->addIndex(['path'], 'media_path_idx');

		// Radio Table
		$radio = $schema->createTable('jukebox_radio_stations');

		$radio->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
		]);
		$radio->addColumn('remote_uuid', 'string', [
			'notnull' => true,
			'length' => 255,
			'default' => '',
		]);
		$radio->addColumn('name', 'text', [
			'notnull' => true,
			'default' => '',
		]);
		$radio->addColumn('stream_url', 'string', [
			'notnull' => true,
			'length' => 1024,
			'default' => '',
		]);
		$radio->addColumn('homepage', 'string', [
			'notnull' => false,
			'length' => 1024,
		]);
		$radio->addColumn('favicon', 'blob', [
			'notnull' => false,
			'comment' => 'Raw binary image data for station icon',
		]);
		$radio->addColumn('country', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$radio->addColumn('state', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$radio->addColumn('language', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$radio->addColumn('bitrate', 'integer', [
			'notnull' => false,
		]);
		$radio->addColumn('codec', 'string', [
			'notnull' => false,
			'length' => 100,
		]);
		$radio->addColumn('tags', 'string', [
			'notnull' => false,
			'length' => 1024,
		]);
		$radio->addColumn('raw_data', 'text', [
			'notnull' => false,
			'comment' => 'Full station metadata as JSON',
		]);
		$radio->addColumn('user_id', 'string', [
			'notnull' => true,
			'length' => 64,
			'default' => '',
		]);
		$radio->addColumn('last_updated', 'bigint', [
			'notnull' => true,
			'default' => 0,
		]);
		$radio->addColumn('favorited', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);

		$radio->setPrimaryKey(['id']);
		$radio->addUniqueIndex(['remote_uuid', 'user_id'], 'radio_remote_uuid_user_id_idx');
		$radio->addIndex(['user_id'], 'radio_user_idx');

		// Podcast Subscription Metadata Table
		$subs = $schema->createTable('jukebox_podcast_subs');

		$subs->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
		]);
		$subs->addColumn('user_id', 'string', [
			'notnull' => true,
			'length' => 64,
			'default' => '',
		]);
		$subs->addColumn('subscription_id', 'integer', [
			'notnull' => false,
			'comment' => 'Optional link to gpodder_subscriptions.id',
		]);
		$subs->addColumn('title', 'string', [
			'notnull' => false,
			'length' => 512,
		]);
		$subs->addColumn('author', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$subs->addColumn('description', 'text', [
			'notnull' => false,
		]);
		$subs->addColumn('image', 'blob', [
			'notnull' => false,
			'comment' => 'Cover image binary data',
		]);
		$subs->addColumn('url', 'string', [
			'notnull' => false,
			'length' => 1024,
		]);

		$subs->addColumn('subscribed', 'boolean', [
			'notnull' => false,
			'default' => true,
		]);

		$subs->addColumn('updated', 'datetime', [
			'notnull' => true,
			'default' => '1970-01-01 00:00:00',
		]);

		$subs->setPrimaryKey(['id']);
		$subs->addUniqueIndex(['subscription_id'], 'podcast_sub_id_idx');

		// Podcast Episode Metadata Table
		$eps = $schema->createTable('jukebox_podcast_eps');

		$eps->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
		]);
		$eps->addColumn('user_id', 'string', [
			'notnull' => true,
			'length' => 64,
			'default' => '',
		]);
		$eps->addColumn('action_id', 'integer', [
			'notnull' => false,
			'comment' => 'Optional link to gpodder_episode_action.id',
		]);
		$eps->addColumn('subscription_id', 'integer', [
			'notnull' => true,
			'comment' => 'FK to jukebox_podcast_subs.id',
		]);
		$eps->addColumn('title', 'string', [
			'notnull' => false,
			'length' => 512,
		]);
		$eps->addColumn('guid', 'string', [
			'notnull' => false,
			'length' => 512,
		]);
		$eps->addColumn('pub_date', 'datetime', [
			'notnull' => false,
		]);
		$eps->addColumn('duration', 'integer', [
			'notnull' => false,
			'comment' => 'Duration in seconds',
		]);
		$eps->addColumn('media_url', 'string', [
			'notnull' => false,
			'length' => 1024,
		]);
		$eps->addColumn('description', 'text', [
			'notnull' => false,
		]);

		$eps->setPrimaryKey(['id']);
		$eps->addIndex(['subscription_id'], 'podcast_ep_data_sub_id_idx');
		$eps->addUniqueIndex(['action_id'], 'podcast_ep_data_action_id_idx');

		// Podcast Episode Playbacks Table
		$epPlays = $schema->createTable('jukebox_podcast_ep_plays');

		$epPlays->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
		]);

		$epPlays->addColumn('user_id', 'string', [
			'notnull' => true,
			'length' => 64,
		]);

		$epPlays->addColumn('episode_id', 'integer', [
			'notnull' => true,
		]);

		$epPlays->addColumn('episode_guid', 'string', [
			'notnull' => true,
			'length' => 512,
			'comment' => 'Matches the episode GUID, used like in gpodder actions',
		]);

		$epPlays->addColumn('action', 'string', [
			'notnull' => true,
			'length' => 16,
			'default' => 'play',
			'comment' => 'Playback action type (e.g. play, resume, complete)',
		]);

		$epPlays->addColumn('timestamp', 'bigint', [
			'notnull' => true,
			'comment' => 'Unix timestamp of when the action occurred',
		]);

		$epPlays->addColumn('position', 'integer', [
			'notnull' => false,
			'comment' => 'Position in seconds when stopped or finished',
		]);

		$epPlays->addColumn('total', 'integer', [
			'notnull' => false,
			'comment' => 'Total duration of the episode in seconds',
		]);

		$epPlays->addColumn('device', 'string', [
			'notnull' => false,
			'length' => 255,
			'comment' => 'Optional device ID or label',
		]);

		$epPlays->setPrimaryKey(['id'], 'ep_plays_pk');
		$epPlays->addIndex(['user_id', 'episode_guid'], 'play_user_guid_idx');

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
