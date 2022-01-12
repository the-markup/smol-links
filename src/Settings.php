<?php

namespace WP_Shlink;

use WP_Shlink\Options;

class Settings {

	function __construct() {
		$this->options = Options::init();
		add_action('admin_menu', [$this, 'on_admin_menu']);
		add_action('admin_init', [$this, 'on_admin_init']);
	}

	function on_admin_menu() {
		add_options_page(
			__('Shlink Settings', 'wp-shlink'),
			'Shlink',
			'manage_options',
			'shlink',
			[$this, 'settings_page']
		);
	}

	function on_admin_init() {
		register_setting('shlink', 'shlink_options');
		add_settings_section(
			'shlink-server',
			__('Server', 'wp-shlink'),
			[$this, 'server_settings'],
			'shlink'
		);
		add_settings_section(
			'shlink-generate',
			__('Generating Shlinks', 'wp-shlink'),
			[$this, 'generate_settings'],
			'shlink'
		);
	}

	function settings_page() {
		?>
		<div class="wrap">
			<h1><?php _e('Shlink Settings', 'wp-shlink'); ?></h1>

			<p>Create and manage Shlink short links from WordPress</p>

			<form action="options.php" method="post">
				<?php \settings_fields( 'shlink' ); ?>
				<?php \do_settings_sections( 'shlink' ); ?>
				<?php \submit_button( 'Save' ); ?>
			</form>
		</div>
		<?php
	}

	function server_settings() {
		add_settings_field(
			'shlink-base-url',
			__('Base URL', 'wp-shlink'),
			[$this, 'base_url_field'],
			'shlink',
			'shlink-server'
		);
		add_settings_field(
			'shlink-api-key',
			__('API Key', 'wp-shlink'),
			[$this, 'api_key_field'],
			'shlink',
			'shlink-server'
		);
	}

	function generate_settings() {
		add_settings_field(
			'shlink-generate-on-save',
			__('Generate upon saving a post', 'wp-shlink'),
			[$this, 'generate_on_save_field'],
			'shlink',
			'shlink-generate'
		);
	}

	function base_url_field() {
		$value = htmlentities($this->options->get('base_url'));
		echo '<input type="text" name="shlink_options[base_url]" class="regular-text ltr" value="' . $value . '">';
	}

	function api_key_field() {
		$value = htmlentities($this->options->get('api_key'));
		echo '<input type="text" name="shlink_options[api_key]" class="regular-text ltr" value="' . $value . '">';
	}

	function generate_on_save_field() {
		$value = htmlentities($this->options->get('generate_on_save'));
		echo '<input type="checkbox" name="shlink_options[generate_on_save]" value="1" ' . checked( 1, $value, false ) . '>';
	}
}
