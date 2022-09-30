<?php
/**
 * Class ShlinkException
 *
 * @package   Shlinkify
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2022 The Markup
 */

namespace Shlinkify;

class ShlinkException extends \Exception {

	public $response;

	function __construct($response) {
		$this->response = $response;
		$message = $response['detail'] ?: 'Unknown error';
		parent::__construct($message);
	}

}
