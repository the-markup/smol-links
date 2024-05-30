<?php
/**
 * Class ShlinkException
 *
 * @package   Smol Links
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2024 The Markup
 */

namespace SmolLinks;

class ShlinkException extends \Exception {

	public $response;

	function __construct($response) {
		$this->response = $response;
		$message = $response['detail'] ?: 'Unknown error';
		parent::__construct($message);
	}

}
