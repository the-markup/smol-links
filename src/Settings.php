<?php
/**
 * Class Settings
 *
 * @package   Smol Links
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2024 The Markup
 */

namespace SmolLinks;

class Settings {

	function __construct($plugin) {
		$this->plugin = $plugin;
		$this->setup_domains();
		add_action('admin_menu', [$this, 'on_admin_menu']);
		add_action('admin_init', [$this, 'on_admin_init']);
		add_action('admin_enqueue_scripts', [$this, 'on_enqueue_assets']);
		add_action('wp_ajax_smol_links_reload_domains', [$this, 'ajax_reload_domains']);
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
			delete_transient('smol_links_error');
		} catch (ShlinkException $err) {
			set_transient('smol_links_error', $err->getMessage());
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
			__('Smol Links Settings', 'smol-links'),
			'Smol Links',
			'manage_options',
			'smol-links-settings',
			[$this, 'settings_page']
		);
	}

	function on_admin_init() {
		register_setting('smol-links', 'smol_links_options', [
			'type' => 'array',
			'sanitize_callback' => [$this, 'sanitize_options']
		]);
		add_settings_section(
			'smol-links-server',
			__('Server', 'smol-links'),
			[$this, 'server_settings'],
			'smol-links'
		);
		add_settings_section(
			'smol-links-generate',
			__('Generating Shlinks', 'smol-links'),
			[$this, 'generate_settings'],
			'smol-links'
		);
	}

	function settings_page() {
		?>
		<div class="wrap">
			<h1><?php _e('Smol Links Settings', 'smol-links'); ?></h1>

			<p>Create and manage Shlink short links from WordPress</p>

			<form action="options.php" method="post">
				<?php \settings_fields( 'smol-links' ); ?>
				<?php \do_settings_sections( 'smol-links' ); ?>
				<?php \submit_button( 'Save' ); ?>
			</form>
		</div>
		<?php
	}

	function server_settings() {
		add_settings_field(
			'smol-links-base-url',
			__('Base URL', 'smol-links'),
			[$this, 'base_url_field'],
			'smol-links',
			'smol-links-server'
		);
		add_settings_field(
			'smol-links-api-key',
			__('API Key', 'smol-links'),
			[$this, 'api_key_field'],
			'smol-links',
			'smol-links-server'
		);
	}

	function generate_settings() {
		add_settings_field(
			'smol-links-generate-on-save',
			__('Generate upon saving a post', 'smol-links'),
			[$this, 'generate_on_save_field'],
			'smol-links',
			'smol-links-generate'
		);
		if (! empty($this->plugin->options->get('domains'))) {
			add_settings_field(
				'smol-links-default-domain',
				__('Default domain', 'smol-links'),
				[$this, 'default_domain_field'],
				'smol-links',
				'smol-links-generate'
			);
		}
	}

	function sanitize_options($input) {
		$base_url_sanitized = sanitize_text_field($input['base_url']);
		$api_key_sanitized = sanitize_text_field($input['api_key']);
		$generate_on_save_sanitized = intval($input['generate_on_save']);
		$default_domain_sanitized = sanitize_text_field($input['default_domain']);

		try {
			if (empty($base_url_sanitized) || empty($api_key_sanitized)) {
				throw new \Exception(__('Please enter a base URL and API key.', 'smol-links'));
			}
			if (!preg_match('/^https?:\/\//', $base_url_sanitized)) {
				throw new \Exception(sprintf(
					__('Invalid base URL: <code>%s</code>. Try adding <code>https://</code>.', 'smol-links'),
					esc_html($base_url_sanitized)
				));
			}
			if (preg_match('/\/$/', $base_url_sanitized)) {
				// Strip trailing slash
				$base_url_sanitized = substr($base_url_sanitized, 0, -1);
			}

			$response = wp_remote_get("$base_url_sanitized/rest/health");
			if (is_wp_error($response)) {
				throw new \Exception(sprintf(
					__('Error loading <code>%s</code>.', 'smol-links'),
					esc_url("$base_url_sanitized/rest/health")
				));
			}

			$this->plugin->api->get_domains([
				'base_url' => $base_url_sanitized,
				'api_key' => $api_key_sanitized
			]);

			return [
				'base_url' => $base_url_sanitized,
				'api_key' => $api_key_sanitized,
				'generate_on_save' => $generate_on_save_sanitized,
				'default_domain' => $default_domain_sanitized,
			];
		} catch (\Exception $error) {
			add_settings_error(
				'smol-links-api-key',
				'smol-links-error',
				$error->getMessage()
			);
			foreach ($input as $key => $value) {
				// Store the submitted value so we can restore it to the form input
				set_transient("smol_links_settings_$key", $value);
			}
			// Revert back to previous settings
			return $this->plugin->options->all();
		}
	}

	function base_url_field() {
		$value = $this->field_value('base_url');
		echo '<input 
				type="url" 
				placeholder="https://example.com" 
				pattern="^(http(s){0,1}:\/\/.){0,1}[\-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([\-a-zA-Z0-9@:%_\+.~#?&\/\/=]*)$" 
				name="smol_links_options[base_url]" 
				class="regular-text ltr" 
				value="' . esc_attr($value) . '"
				required
			  >';
	}

	function api_key_field() {
		$value = $this->field_value('api_key');
		echo '<input type="text" name="smol_links_options[api_key]" class="regular-text ltr" value="' . esc_attr($value) . '" required>';
	}

	function generate_on_save_field() {
		$value = $this->field_value('generate_on_save');
		echo '<input type="checkbox" name="smol_links_options[generate_on_save]" value="1" ' . checked(1, $value, false) . '>';
	}

	function field_value($key) {
		$transient = get_transient("smol_links_settings_$key");
		if ($transient !== false) {
			delete_transient("smol_links_settings_$key");
			return $transient;
		}
		return $this->plugin->options->get($key);
	}

	function default_domain_field() {
		$domains = $this->plugin->options->get('domains');
		$default = $this->plugin->options->get('default_domain');
		echo "<select name=\"smol_links_options[default_domain]\" class=\"smol-links-domain-list\">\n";
		foreach ($domains as $domain) {
			$selected = ($default == $domain) ? ' selected' : '';
			echo '<option value="' . esc_attr($domain) . '"' . esc_attr($selected) . '>' . esc_html($domain) . "</option>\n";
		}
		echo "</select>\n";
		echo "<p><a href=\"#\" class=\"smol-links-reload-domains\">Reload domain list</a></p>\n";
	}

	function on_enqueue_assets($suffix) {
		if ($suffix != 'settings_page_smol-links-settings') {
			return;
		}

		wp_enqueue_script(
			'smol-links-settings',
			plugins_url('build/settings.js', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/settings.js')
		);

		wp_enqueue_style(
			'smol-links-manager',
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
				set_transient('smol_links_info', 'reloaded domain list.');
				header('Content-Type: application/json');
				echo wp_json_encode([
					'ok' => true
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
