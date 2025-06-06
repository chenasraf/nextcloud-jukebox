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
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable('jukebox_media')) {
			$schema->dropTable('jukebox_media');
		}

		$table = $schema->createTable('jukebox_media');

		$table->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
		]);

		$table->addColumn('media_type', 'string', [
			'length' => 20,
			'notnull' => true,
			'default' => 'track',
			'comment' => 'track, podcast, audiobook, video',
		]);

		$table->addColumn('path', 'string', [
			'notnull' => true,
			'length' => 1024,
		]);

		$table->addColumn('title', 'string', [
			'notnull' => false,
			'length' => 255,
		]);

		$table->addColumn('track_number', 'integer', [
			'notnull' => false,
		]);

		$table->addColumn('artist', 'string', [
			'notnull' => false,
			'length' => 255,
		]);

		$table->addColumn('album', 'string', [
			'notnull' => false,
			'length' => 255,
		]);

		$table->addColumn('album_artist', 'string', [
			'notnull' => false,
			'length' => 255,
		]);

		$table->addColumn('duration', 'integer', [
			'notnull' => false,
			'comment' => 'Duration in seconds',
		]);

		$table->addColumn('album_art', 'blob', [
			'notnull' => false,
			'comment' => 'Raw binary image data for album art',
		]);

		$table->addColumn('genre', 'string', [
			'notnull' => false,
			'length' => 255,
		]);

		$table->addColumn('year', 'smallint', [
			'notnull' => false,
		]);

		$table->addColumn('bitrate', 'integer', [
			'notnull' => false,
			'comment' => 'In kbps',
		]);

		$table->addColumn('codec', 'string', [
			'notnull' => false,
			'length' => 100,
		]);

		$table->addColumn('user_id', 'string', [
			'notnull' => true,
			'length' => 64,
		]);

		$table->addColumn('mtime', 'bigint', [
			'notnull' => true,
			'comment' => 'File modified time',
		]);

		$table->addColumn('raw_id3', 'text', [
			'notnull' => false,
			'comment' => 'Raw ID3 metadata as JSON',
		]);

		$table->setPrimaryKey(['id']);
		$table->addIndex(['user_id'], 'media_user_idx');
		$table->addIndex(['media_type'], 'media_type_idx');
		$table->addIndex(['path'], 'media_path_idx');

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
