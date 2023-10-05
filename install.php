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

use function Vvveb\__;
use Vvveb\System\Import\Sql;

if (! defined('V_VERSION')) {
	die('Invalid request!');
}

#[\AllowDynamicProperties]
class Install {
	function import() {
		try {
			$engine = DB_ENGINE;
			$import = new Sql();
			$import->setPath(__DIR__ . "/install/sql/$engine/schema/");
			$import->createTables();
			$import->setPath(__DIR__ . '/install/sql/insert/');
			$import->insertData();
		} catch (\Exception $e) {
			$this->view->errors[] = sprintf(__('Db error: "%s" Error code: "%s"'), $e->getMessage(), $e->getCode());
		}
	}

	function run() {
		$this->import();
	}
}
