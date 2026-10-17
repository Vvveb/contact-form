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

use Vvveb\Controller\Listing;

use function Vvveb\humanReadable;
use function Vvveb\model;

class Messages extends Listing {
	protected string $type = 'message';

	protected string $modelName = 'Plugins\ContactForm\Message';

	//protected $model = 'message';

	protected string $list = 'message';

	protected string $controller = 'message';

	protected string $listController = 'messages';

	protected string $module = 'plugins/contact-form';

	public function index(): void {
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

	/**
	 * Export messages as CSV, flattening the submitted form fields.
	 */
	public function csv(): void {
		$list = model($this->modelName);
		$rows = $list->getAll(['start' => 0, 'limit' => 100000] + $this->global)['message'] ?? [];

		//collect every field submitted across all forms (capped to keep the
		//columns manageable; fields beyond the cap only appear in json data)
		$fields   = [];
		$expanded = [];

		foreach ($rows as $row) {
			$data = json_decode($row['data'] ?? '{}', true) ?: [];
			unset($data['csrf']);
			$expanded[] = [$row, $data];

			foreach (array_keys($data) as $key) {
				if (!in_array($key, $fields) && count($fields) < 50) {
					$fields[] = $key;
				}
			}
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="contact-form-messages.csv"');

		$fp = fopen('php://output', 'w');
		//UTF-8 BOM so Excel renders accented characters correctly
		fputs($fp, "\xEF\xBB\xBF");

		fputcsv($fp, array_merge(
			['Message ID', 'Type', 'Date', 'Status'],
			array_map(fn ($f) => __(humanReadable($f)), $fields)
		), ',', '"', '\\', "\n");

		foreach ($expanded as [$row, $data]) {
			$line = [
				$row['message_id'] ?? '',
				$row['type'] ?? '',
				$row['created_at'] ?? '',
				(empty($row['status']) ? 'Unread' : 'Read'),
			];

			foreach ($fields as $field) {
				$value  = $data[$field] ?? '';
				$line[] = is_array($value) ? json_encode($value) : $value;
			}

			fputcsv($fp, $line, ',', '"', '\\', "\n");
		}

		fclose($fp);
		exit();
	}
}
