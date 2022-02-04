<?php

namespace WP_Shlink;

use WP_Shlink\Options;
use WP_Shlink\API;

class Settings {

	function __construct() {
		$this->options = Options::init();
		$this->setup_domains();
		add_action('admin_menu', [$this, 'on_admin_menu']);
		add_action('admin_init', [$this, 'on_admin_init']);
		add_action('admin_enqueue_scripts', [$this, 'on_enqueue_assets']);
		add_action('wp_ajax_reload_domains', [$this, 'ajax_reload_domains']);
	}

	function setup_domains() {
		if (! $this->options->get('base_url') || ! $this->options->get('api_key')) {
			return;
		}
		$domains = $this->options->get('domains');
		$default = $this->options->get('default_domain');
		if (empty($domains) || empty($default)) {
			$domains = $this->load_domains();
			if ($domains) {
				$this->set_domains_option($domains);
				$this->set_default_domain_option($domains);
			}
		}
	}

	function load_domains() {
		$api = API::init();
		$result = $api->get_domains();
		if (! empty($result['domains']['data'])) {
			return $result['domains']['data'];
		}
		return false;
	}

	function set_domains_option($domains) {
		$domain_list = [];
		foreach ($domains as $domain) {
			$domain_list[] = $domain['domain'];
		}
		$this->options->set('domains', $domain_list);
		return $domain_list;
	}

	function set_default_domain_option($domains) {
		$default = null;
		foreach ($domains as $domain) {
			if (! empty($domain['isDefault'])) {
				$default = $domain['domain'];
			}
		}
		if (empty($default)) {
			$default = $domains[0]['domain'];
		}
		$this->options->set('default_domain', $default);
		return $default;
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
		if (! empty($this->options->get('domains'))) {
			add_settings_field(
				'shlink-default-domain',
				__('Default domain', 'wp-shlink'),
				[$this, 'default_domain_field'],
				'shlink',
				'shlink-generate'
			);
		}
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

	function default_domain_field() {
		$domains = $this->options->get('domains');
		$default = $this->options->get('default_domain');
		echo "<select name=\"shlink_options[default_domain]\" class=\"shlink-domain-list\">\n";
		foreach ($domains as $domain) {
			$selected = ($default == $domain) ? ' selected="selected"' : '';
			echo "<option value=\"$domain\">$domain</option>\n";
		}
		echo "</select>\n";
		echo "<p><a href=\"#\" class=\"shlink-reload-domains\">Reload domain list</a></p>\n";
	}

	function on_enqueue_assets($suffix) {
		if ($suffix != 'settings_page_shlink') {
			return;
		}

		wp_enqueue_script(
			'wp-shlink-settings',
			plugins_url('build/settings.js', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/settings.js')
		);

		wp_enqueue_style(
			'wp-shlink-manager',
			plugins_url('build/settings.css', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/settings.css')
		);
	}

	function ajax_reload_domains() {
		$errorMessage = 'Could not reload domains.';
		try {
			$domains = $this->load_domains();
			if ($domains) {
				$list = $this->set_domains_option($domains);
				$default = $this->set_default_domain_option($domains);
				header('Content-Type: application/json');
				echo wp_json_encode([
					'ok' => true,
					'domains' => $list,
					'default_domain' => $default
				]);
				exit;
			}
		} catch (Exception $error) {
			$errorMessage = $error->getMessage();
		}
		echo wp_json_encode([
			'ok' => false,
			'error' => $errorMessage
		]);
		exit;
	}
}
