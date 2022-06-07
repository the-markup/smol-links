<?php
/**
 * Class Options
 *
 * @package   Shlinkify
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2022 The Markup
 */

namespace Shlinkify;

/**
 * Interface to WordPress options store
 */
class Options {

	function __construct($plugin) {
		$this->plugin = $plugin;
		$defaults = [
			'base_url' => '',
			'api_key' => '',
			'generate_on_save' => false,
			'domains' => [],
			'default_domain' => null
		];
		$saved_options = get_option('shlinkify_options') ?: [];
		$this->options = array_merge($defaults, $saved_options);
	}

	function get($key) {
		if (isset($this->options[$key])) {
			return $this->options[$key];
		}
		return null;
	}

	function set($key, $value) {
		$this->options[$key] = $value;
		update_option('shlinkify_options', $this->options, false);
	}
}
