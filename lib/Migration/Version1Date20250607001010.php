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

		// 🎵 Music Table
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

		// 📻 Radio Table
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

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
