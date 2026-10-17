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

namespace Vvveb\Plugins\ContactForm\Component;

use function Vvveb\__;
use function Vvveb\email;
use function Vvveb\humanReadable;

use Vvveb\Plugins\ContactForm\System\Message as MessageHelper;

use function Vvveb\session as sess;
use function Vvveb\siteSettings;

use Vvveb\Sql\Plugins\ContactForm\MessageSQL;
use Vvveb\System\CacheManager;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Core\Request;
use Vvveb\System\Core\View;
use Vvveb\System\Event;
use Vvveb\System\Notifications;
use Vvveb\System\Traits\Spam;

if (!defined('V_VERSION')) {
	exit('Invalid request!');
}

class Form extends ComponentBase {
	use Spam;

	public static array $defaultOptions = [
		'save'          => true,
		'email'         => true,
		'sendto'        => null,
		'confirm-email' => null,
		'name'          => '', //unique form identifier
		'success'       => null, //optional success message
	];

	public int $cacheExpire = 0;

	public function arrayToText($message, &$html, &$txt) {
		$html .= '<table>';

		foreach ($message as $name => $value) {
			$name  = humanReadable(htmlspecialchars($name));
			$value = htmlspecialchars(MessageHelper::flattenValue($value));

			$html .= "<tr><th>{$name}</th><td>{$value}</td></tr>";
			$txt .= "{$name} : {$value}\n";
		}
		$html .= '</table>';

		return [$html, $txt];
	}

	/**
	 * Show an error for the current form (keyed by form name when present).
	 *
	 * @param string  $error
	 * @param ?string $formName
	 */
	protected function formError(string $error, ?string $formName = null): void {
		$view     = View::getInstance();
		$formName ??= ($this->options['name'] ?? null);

		if ($formName) {
			$view->errors[$formName] = $error;
		} else {
			$view->errors[] = $error;
		}
	}

	public function request(&$results, $index = 0) {
		$request = Request::getInstance();

		if (isset($request->post['contact-form'])) {
			$post = $request->post;

			[$results, $post] = Event::trigger(__CLASS__, __FUNCTION__, $results, $post);

			//if $post still has data, some filter above set by a spam plugin might remove the message
			if ($post) {
				$view       = View::getInstance();
				$name       = $this->options['name'];
				$formName   = $post['form-name'] ?? null; //data-v-type
				$formName ??= $name;

				if (!$this->isSpam($post)) {
					$post       = $this->removeSpamCatchFields($post);

					//bots and misfiring scripts post otherwise empty forms -
					//saving and mailing those only creates junk rows
					if (!MessageHelper::hasContent($post)) {
						$this->formError(__('Please complete the form!'), $formName);

						return;
					}

					$meta       = $request->server;
					$metaFields = ['HTTP_USER_AGENT', 'REMOTE_ADDR', 'REQUEST_TIME', 'REQUEST_URI'];
					$meta       = array_intersect_key($request->server, array_flip($metaFields));
					$msg        = ['message' => ['data' => json_encode($post), 'meta' => json_encode($meta), 'type' => $formName]];
					//success message comes from options only, never from POST data
					$success    = $this->options['success'] ?? null; //data-v-success

					if ($formName && ($formName != $name)) {
						return $results;
					}

					if ($this->options['save'] == true) {
						$message    = new MessageSQL();

						if ($message->add($msg)) {
							CacheManager::clearObjectCache('component', 'notifications');
							$success = $success ?: __('Message was sent!');
							$results['success'] = $success;

							if ($formName) {
								$view->success[$formName] = $success;
							} else {
								$view->success[] = $success;
							}
						} else {
							$error = __('Error sending message!');

							if ($formName) {
								$view->errors[$formName] = $error;
							} else {
								$view->errors[] = $error;
							}
						}
					}

					$formName = humanReadable($formName);
					$html     = '<h2>' . htmlspecialchars($formName) . '</h2>';
					$txt      =  "{$formName}\n\n";

					[$html, $txt] = $this->arrayToText($post, $html, $txt);

					$html .= '<h3>' . __('Meta') . '</h3>';
					$txt .= __('Meta') . "\n\n";

					[$html, $txt] = $this->arrayToText($meta, $html, $txt);

					if ($this->options['email'] == true) {
						$site    = siteSettings(SITE_ID, sess('language_id') ?? 1);
						$subject = ($site['description']['title'] ?? '') . ' - ' . $formName . (isset($post['subject']) ? ' - ' . str_replace(["\r", "\n"], '', $post['subject']) : '');
						$to      = $this->options['sendto'] ?? $site['contact-email'] ?? false;

						try {
							// Explicit "to" preserves the plugin's send-to configuration;
							// without it the event's configured recipients apply
							Notifications::trigger('contact-form.message', [
								'subject' => $subject,
								'message' => $txt,
								'html'    => $html,
							] + ($to ? ['to' => $to] : []) + [
								'form' => $post,
								'meta' => $meta,
							]);
						} catch (\Throwable $e) {
							$error          = __('Error sending mail!') . "\n" . $e->getMessage();
							$view->errors[] = $error;
						}

						//show feedback when saving is disabled:
						//with saving disabled this is the only success signal
						if (!isset($error) && $this->options['save'] != true) {
							$success            = $success ?: __('Message was sent!');
							$results['success'] = $success;

							if ($formName) {
								$view->success[$formName] = $success;
							} else {
								$view->success[] = $success;
							}
						}
					}

					//visitor confirmation email (page builder confirm-email option),
					//sent even when admin saving/mailing is disabled
					if (!empty($this->options['confirm-email']) && ($visitorEmail = MessageHelper::visitorEmail($post))) {
						$this->sendConfirmation($visitorEmail, $post, $formName);
					}
				} else {
					$error =  __('Sending message failed!');
					if ($formName) {
						$view->errors[$formName] = $error;
					} else {
						$view->errors[] = $error;
					}
				}
			}
		}
	}

	/**
	 * Send the visitor a confirmation email for their submission.
	 *
	 * Failures are only logged: the submission itself already succeeded
	 * and must not be reported as failed because of the courtesy copy.
	 *
	 * @param string  $visitorEmail
	 * @param array   $post
	 * @param ?string $formName
	 */
	protected function sendConfirmation(string $visitorEmail, array $post, ?string $formName): void {
		try {
			$site        = siteSettings(SITE_ID, sess('language_id') ?? 1);
			$description = $site['description'] ?? '';
			$siteTitle   = is_array($description) ? ($description['title'] ?? '') : (string)$description;
			$subject     = MessageHelper::confirmationSubject($siteTitle, humanReadable((string)$formName));

			$html = '<h2>' . htmlspecialchars($subject) . '</h2>';
			$txt  = $subject . "\n\n";

			[$html, $txt] = $this->arrayToText($post, $html, $txt);

			email($visitorEmail, $subject, ['html' => $html, 'txt' => $txt]);
		} catch (\Throwable $e) {
			\Vvveb\logError('Contact form confirmation: ' . $e->getMessage());
		}
	}

	public function results(): mixed {
		$results       = [];
		[$results]     = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
