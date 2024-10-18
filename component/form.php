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

namespace Vvveb\Plugins\ContactForm\Component;

use function Vvveb\__;
use function Vvveb\session as sess;
use function Vvveb\email;
use function Vvveb\humanReadable;
use function Vvveb\siteSettings;
use Vvveb\Sql\Plugins\ContactForm\MessageSQL;
use Vvveb\System\Core\Request;
use Vvveb\System\Core\View;
use Vvveb\System\Event;

if (! defined('V_VERSION')) {
	die('Invalid request!');
}

class Form extends \Vvveb\System\Component\ComponentBase {
	public static $defaultOptions = [
		'save'    	     => true,
		'email'         => true,
		'sendto'        => null,
		'confirm-email' => null,
		'name'          => '', //unique form identifier
	];

	//these must be empty, they are hidden in html and only bots will fill them
	//todo: add dynamic field name
	protected $spamFields =  [
		'firstname-empty',
		'lastname-empty',
		'subject-empty',
		'contact-form',
	];

	function checkIfSpam(&$message) {
		return $message;
	}

	function arrayToText($message, &$html, &$txt) {
		$html .= '<table>';

		foreach ($message as $name => $value) {
			$name = humanReadable($name);
			$html .= "<tr><th>$name</th><td>$value</td></tr>";
			$txt .= "$name : $value\n";
		}
		$html .= '</table>';

		return [$html, $txt];
	}

	function isSpam(&$message) {
		$spam = false;

		foreach ($this->spamFields as $field) {
			if (isset($message[$field]) && ! empty($message[$field])) {
				return $spam = true;
			}
		}

		return $spam;
	}

	function removeSpamCatchFields(&$message) {
		foreach ($this->spamFields as $field) {
			if (isset($message[$field])) {
				unset($message[$field]);
			}
		}

		return $message;
	}

	function request(&$results, $index = 0) {
		$request = Request::getInstance();

		if (isset($request->post['contact-form'])) {
			$post = $request->post;

			list($results, $post) = Event :: trigger(__CLASS__,__FUNCTION__, $results, $post);

			//if $post still has data, some filter above set by a spam plugin might remove the message
			if ($post && ! $this->isSpam($post)) {
				$view       = View::getInstance();
				$formName   = $this->options['name'];
				$post       = $this->removeSpamCatchFields($post);
				$meta       = $request->server;
				$metaFields = ['HTTP_USER_AGENT', 'REMOTE_ADDR', 'REQUEST_TIME', 'REQUEST_URI'];
				$meta       = array_intersect_key($request->server, array_flip($metaFields));
				$msg        = ['message' => ['data' => json_encode($post), 'meta' => json_encode($meta), 'type' => $formName]];

				if ($this->options['save'] == true) {
					$message    = new MessageSQL();

					if ($message->add($msg)) {
						$view->success[] = __('Message was sent!');
					} else {
						$view->errors[] = __('Error sending message!');
					}
				}

				$formName = humanReadable($formName);
				$html     = "<h2>$formName</h2>";
				$txt      =  "$formName\n\n";

				list($html, $txt) = $this->arrayToText($post, $html, $txt);

				$html .= '<h3>' . __('Meta') . '</h3>';
				$txt .= __('Meta') . "\n\n";

				list($html, $txt) = $this->arrayToText($meta, $html, $txt);

				if ($this->options['email'] == true) {
					$site    = siteSettings(SITE_ID, sess('language_id') ?? 1);
					$subject = ($site['description']['title'] ?? '') . ' - ' . $formName . (isset($post['subject']) ? ' - ' . $post['subject'] : '');
					$to      = $this->options['sendto'] ?? $site['contact-email'] ?? false;

					if ($to) {
						try {
							$error =  __('Error sending mail!');

							if (email($to, $subject, ['html'=> $html, 'txt' => $txt])) {
								//$view->success[] = __('Email sent!');
							} else {
								$view->errors[] = $error;
							}
						} catch (\Exception $e) {
							$error .= "\n" . $e->getMessage();
							$view->errors[] = $error;
						}
					}
				}
			}
		}
	}

	function results() {
		$results       = [];
		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
