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

namespace Vvveb\Plugins\ContactForm\Controller;

use Vvveb\Controller\Listing;

class Messages extends Listing {
	protected $type = 'message';

	protected $modelName = 'Plugins\ContactForm\Message';

	//protected $model = 'message';

	protected $list = 'message';

	protected $listController = 'messages';

	protected $module = 'plugins/contact-form';

	function index() {
		parent::index();

		//expand fields in the json
		if ($this->view->message) {
			foreach ($this->view->message as &$message) {
				$data = json_decode($message['data'] ?? '{}', true);

				if (is_array($data)) {
					$message += $data;
				}
			}
		}
	}
}
