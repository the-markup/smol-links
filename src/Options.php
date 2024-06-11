<?php
/**
 * Class Options
 *
 * @package   Smol Links
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2024 The Markup
 */

namespace SmolLinks;

/**
 * Interface to WordPress options store
 */
class Options {

	function __construct($plugin) {
		$this->plugin = $plugin;
	}

	function all() {
		if (empty($this->values)) {
			$defaults = [
				'base_url' => '',
				'api_key' => '',
				'generate_on_save' => false,
				'domains' => [],
				'default_domain' => null
			];
			$saved_options = get_option('smol_links_options') ?: [];
			$this->values = array_merge($defaults, $saved_options);
		}
		return $this->values;
	}

	function get($key) {
		$values = $this->all();
		if (isset($values[$key])) {
			return $values[$key];
		}
		return null;
	}

	function set($key, $value) {
		$values = $this->all();
		$values[$key] = $value;
		$this->values = $values;
		update_option('smol_links_options', $values, false);
	}
}
