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

use function Vvveb\__;

use Vvveb\Controller\Crud;

use function Vvveb\humanReadable;
use function Vvveb\model;

use Vvveb\Plugins\ContactForm\System\Message as MessageHelper;
use Vvveb\System\CacheManager;

class Message extends Crud {
	protected string $type = 'message';

	protected string $modelName = 'Plugins\ContactForm\Message';

	protected string $module = 'plugins/contact-form';

	/**
	 * Move a message back to unread (status = 0).
	 */
	public function unread(): void {
		$messageId = (int)($this->request->get['message_id'] ?? 0);

		if ($messageId) {
			model($this->modelName)->edit([
				'message'    => ['status' => 0],
				'message_id' => $messageId,
			]);
			CacheManager::clearObjectCache('component', 'notifications');
			$this->view->success[] = __('Message marked as unread!');
		}

		$this->redirect(['module' => 'plugins/contact-form/messages']);
	}

	public function index(): void {
		parent::index();

		if ($this->view->message) {
			$message = &$this->view->message;
			$data    = json_decode($message['data'] ?? '{}', true);
			$meta    = json_decode($message['meta'] ?? '{}', true);

			if (is_array($data)) {
				foreach ($data as $key => $value) {
					unset($data[$key]);

					if (in_array($key, ['csrf'])) {
						continue;
					}
					//multi-value fields arrive as arrays, flatten them for display
					$data[__(humanReadable($key))] = MessageHelper::flattenValue($value);
				}

				foreach ($meta as $key => $value) {
					unset($meta[$key]);
					$meta[__(humanReadable(strtolower($key)))] = MessageHelper::flattenValue($value);
				}

				$message['message'] = $data;
				$message['meta']    = $meta;

				if ($message['status'] == 0) {
					$messageSql = model($this->modelName);
					$messageSql->edit(['message' => ['status' => 1], 'message_id' => $message['message_id']]);
					CacheManager::clearObjectCache('component', 'notifications');
				}
			}
		} else {
			$this->notFound();
		}
	}
}
