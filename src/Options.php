<?php

namespace WP_Shlink;

class Options {

	static $instance;

	static function init() {
		if (! self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function __construct() {
		$defaults = [
			'base_url' => '',
			'api_key' => '',
			'generate_on_save' => false
		];
		$saved_options = get_option('shlink_options') ?: [];
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
		update_option('shlink_options', $this->options, false);
	}
}
