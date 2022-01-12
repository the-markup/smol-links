<?php
/**
 * Class Admin
 *
 * @package   Shlink
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2022 The Markup
 */

namespace WP_Shlink;

require_once(__DIR__ . '/Settings.php');
require_once(__DIR__ . '/Editor.php');
require_once(__DIR__ . '/Manager.php');

use WP_Shlink\Settings;
use WP_Shlink\Editor;
use WP_Shlink\Manager;

/**
 * Handles WordPress admin settings interface
 */
class Admin {

	function __construct() {
		$this->settings = new Settings();
		$this->editor   = new Editor();
		$this->manager  = new Manager();
	}

}
