<?php

/**
 * Vvveb
 *
 * Copyright (C) 2023  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Vvveb\Plugins\ContactForm;

use Vvveb\System\Import\Sql;

if (!defined('V_VERSION')) {
	exit('Invalid request!');
}

#[\AllowDynamicProperties]
class Install {
	public function import(): void {
		try {
			$engine = DB_ENGINE;
			$import = new Sql();
			$import->setPath(__DIR__ . "/install/sql/$engine/schema/");
			$import->createTables();
			$import->setPath(__DIR__ . '/install/sql/insert/');
			$import->insertData();
		} catch (\Exception $e) {
			\Vvveb\logError($e->getMessage());
		}
	}

	public function run(): void {
		$this->import();
	}
}
