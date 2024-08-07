<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: RIKIPB <dkron@outlook.it>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\ImagePopupPreview\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version000000Date20181013124731 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('imagepopuppreview')) {
			$table = $schema->createTable('imagepopuppreview');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('title', 'string', [
				'notnull' => true,
				'length' => 200
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 200,
			]);
			$table->addColumn('content', 'text', [
				'notnull' => true,
				'default' => ''
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['user_id'], 'ipp_uid_index');
		}
		return $schema;
	}
}
