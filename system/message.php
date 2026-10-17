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
 */

namespace Vvveb\Plugins\ContactForm\System;

use function Vvveb\__;

if (!defined('V_VERSION')) {
	exit('Invalid request!');
}

/**
 * Pure submission helpers shared by the frontend component and the admin message view.
 */
class Message {
	/**
	 * POST keys that carry no user content.
	 */
	public const CONTROL_KEYS = ['contact-form', 'form-name', 'csrf'];

	/**
	 * Flatten any submitted value to text: multi-select and checkbox
	 * groups arrive as (possibly nested) arrays which must never reach
	 * htmlspecialchars() or template output directly.
	 *
	 * @param mixed $value
	 */
	public static function flattenValue(mixed $value): string {
		if (is_array($value)) {
			$parts = [];

			foreach ($value as $item) {
				$flat = self::flattenValue($item);

				if ($flat !== '') {
					$parts[] = $flat;
				}
			}

			return implode("\n", $parts);
		}

		if ($value === null || is_bool($value)) {
			return $value ? '1' : '';
		}

		return (string)$value;
	}

	/**
	 * Whether the submission holds any user content at all.
	 *
	 * Bots and misfiring JS routinely post otherwise empty forms; saving
	 * and mailing those only creates junk rows and bounce noise.
	 *
	 * @param array $post
	 */
	public static function hasContent(array $post): bool {
		foreach ($post as $key => $value) {
			if (in_array($key, self::CONTROL_KEYS, true)) {
				continue;
			}

			if (is_array($value)) {
				if (trim(self::flattenValue($value)) !== '') {
					return true;
				}

				continue;
			}

			if (trim((string)$value) !== '') {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validated visitor address from the conventional email field, or null
	 * when missing, malformed or carrying header-injection newlines.
	 *
	 * @param array $post
	 */
	public static function visitorEmail(array $post): ?string {
		$email = trim((string)($post['email'] ?? ''));

		if ($email === '' || str_contains($email, "\r") || str_contains($email, "\n")) {
			return null;
		}

		return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
	}

	/**
	 * Subject line for the visitor confirmation email.
	 *
	 * @param string $siteTitle
	 * @param string $formName
	 */
	public static function confirmationSubject(string $siteTitle, string $formName): string {
		$siteTitle = trim($siteTitle);
		$formName  = trim($formName);

		if ($siteTitle !== '' && $formName !== '') {
			return $siteTitle . ' - ' . sprintf(__('Thank you for your %s message!'), $formName);
		}

		return __('Thank you for contacting us!');
	}
}
