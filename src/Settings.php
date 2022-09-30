<?php

namespace Shlinkify;

class Settings {

	function __construct($plugin) {
		$this->plugin = $plugin;
		$this->setup_domains();
		add_action('admin_menu', [$this, 'on_admin_menu']);
		add_action('admin_init', [$this, 'on_admin_init']);
		add_action('admin_enqueue_scripts', [$this, 'on_enqueue_assets']);
		add_action('wp_ajax_reload_domains', [$this, 'ajax_reload_domains']);
	}

	function setup_domains() {
		if (! $this->plugin->options->get('base_url') ||
		    ! $this->plugin->options->get('api_key')) {
			return;
		}
		$domains = $this->plugin->options->get('domains');
		$default = $this->plugin->options->get('default_domain');
		if (empty($domains) || empty($default)) {
			$domains = $this->load_domains();
			if ($domains) {
				$this->set_domains_option($domains);
				$this->set_default_domain_option($domains);
			}
		}
	}

	function load_domains() {
		try {
			$result = $this->plugin->api->get_domains();
			if (! empty($result['domains']['data'])) {
				return $result['domains']['data'];
			}
			delete_transient('shlinkify_error');
		} catch (ShlinkException $err) {
			set_transient('shlinkify_error', $err->getMessage());
		}
		return false;
	}

	function set_domains_option($domains) {
		$domain_list = [];
		foreach ($domains as $domain) {
			$domain_list[] = $domain['domain'];
		}
		$this->plugin->options->set('domains', $domain_list);
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
		$this->plugin->options->set('default_domain', $default);
		return $default;
	}

	function on_admin_menu() {
		add_options_page(
			__('Shlinkify Settings', 'shlinkify'),
			'Shlinkify',
			'manage_options',
			'shlinkify-settings',
			[$this, 'settings_page']
		);
	}

	function on_admin_init() {
		register_setting('shlinkify', 'shlinkify_options');
		add_settings_section(
			'shlinkify-server',
			__('Server', 'shlinkify'),
			[$this, 'server_settings'],
			'shlinkify'
		);
		add_settings_section(
			'shlinkify-generate',
			__('Generating Shlinks', 'shlinkify'),
			[$this, 'generate_settings'],
			'shlinkify'
		);
	}

	function settings_page() {
		?>
		<div class="wrap">
			<h1><?php _e('Shlinkify Settings', 'shlinkify'); ?></h1>

			<p>Create and manage Shlink short links from WordPress</p>

			<form action="options.php" method="post">
				<?php \settings_fields( 'shlinkify' ); ?>
				<?php \do_settings_sections( 'shlinkify' ); ?>
				<?php \submit_button( 'Save' ); ?>
			</form>
		</div>
		<?php
	}

	function server_settings() {
		add_settings_field(
			'shlinkify-base-url',
			__('Base URL', 'shlinkify'),
			[$this, 'base_url_field'],
			'shlinkify',
			'shlinkify-server'
		);
		add_settings_field(
			'shlinkify-api-key',
			__('API Key', 'shlinkify'),
			[$this, 'api_key_field'],
			'shlinkify',
			'shlinkify-server'
		);
	}

	function generate_settings() {
		add_settings_field(
			'shlinkify-generate-on-save',
			__('Generate upon saving a post', 'shlinkify'),
			[$this, 'generate_on_save_field'],
			'shlinkify',
			'shlinkify-generate'
		);
		if (! empty($this->plugin->options->get('domains'))) {
			add_settings_field(
				'shlinkify-default-domain',
				__('Default domain', 'shlinkify'),
				[$this, 'default_domain_field'],
				'shlinkify',
				'shlinkify-generate'
			);
		}
	}

	function base_url_field() {
		$value = htmlentities($this->plugin->options->get('base_url'));
		echo '<input type="text" name="shlinkify_options[base_url]" class="regular-text ltr" value="' . $value . '">';
	}

	function api_key_field() {
		$value = htmlentities($this->plugin->options->get('api_key'));
		echo '<input type="text" name="shlinkify_options[api_key]" class="regular-text ltr" value="' . $value . '">';
	}

	function generate_on_save_field() {
		$value = htmlentities($this->plugin->options->get('generate_on_save'));
		echo '<input type="checkbox" name="shlinkify_options[generate_on_save]" value="1" ' . checked( 1, $value, false ) . '>';
	}

	function default_domain_field() {
		$domains = $this->plugin->options->get('domains');
		$default = $this->plugin->options->get('default_domain');
		echo "<select name=\"shlinkify_options[default_domain]\" class=\"shlink-domain-list\">\n";
		foreach ($domains as $domain) {
			$selected = ($default == $domain) ? ' selected="selected"' : '';
			echo "<option value=\"$domain\">$domain</option>\n";
		}
		echo "</select>\n";
		echo "<p><a href=\"#\" class=\"shlinkify-reload-domains\">Reload domain list</a></p>\n";
	}

	function on_enqueue_assets($suffix) {
		if ($suffix != 'settings_page_shlinkify') {
			return;
		}

		wp_enqueue_script(
			'shlinkify-settings',
			plugins_url('build/settings.js', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/settings.js')
		);

		wp_enqueue_style(
			'shlinkify-manager',
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
		} catch (\Exception $error) {
			$errorMessage = $error->getMessage();
		}
		echo wp_json_encode([
			'ok' => false,
			'error' => $errorMessage
		]);
		exit;
	}
}
