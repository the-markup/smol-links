<?php
/**
 * Class Manager
 *
 * @package   Smol Links
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2022 The Markup
 */

namespace SmolLinks;

class Manager {

	var $default_tabs = [
		'All'            => [],
		'Manual'         => ['tags[]' => 'smol-links-manager'],
		'Auto-generated' => ['tags[]' => 'smol-links-onsave']
	];

	function __construct($plugin) {
		$this->plugin = $plugin;
		add_filter('smol_links_manager_tabs', [$this, 'add_my_links_tab']);
		add_action('init', [$this, 'on_init']);
		add_action('admin_menu', [$this, 'on_admin_menu']);
		add_action('admin_enqueue_scripts', [$this, 'on_enqueue_assets']);
		add_action('wp_ajax_smol_links_load', [$this, 'ajax_load']);
		add_action('wp_ajax_smol_links_create', [$this, 'ajax_create']);
		add_action('wp_ajax_smol_links_update', [$this, 'ajax_update']);
	}

	function on_init() {
		$this->tabs = apply_filters('smol_links_manager_tabs', $this->default_tabs);
	}

	function on_admin_menu() {
		add_menu_page(
			__('Smol Links', 'smol-links'),
			__('Smol Links', 'smol-links'),
			'edit_posts',
			'smol-links',
			[$this, 'manager_page'],
			'dashicons-admin-links',
			20
		);
	}

	function on_enqueue_assets($suffix) {
		if ($suffix != 'toplevel_page_smol-links') {
			return;
		}

		wp_enqueue_script(
			'smol-links-manager',
			plugins_url('build/manager.js', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/manager.js')
		);

		wp_enqueue_style(
			'smol-links-manager',
			plugins_url('build/manager.css', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/manager.css')
		);

		wp_add_inline_script('smol-links-manager', 'var smol_links_nonces = ' . wp_json_encode([
			'load'   => wp_create_nonce('smol_links_load'),
			'create' => wp_create_nonce('smol_links_create'),
			'update' => wp_create_nonce('smol_links_update')
		]) . ';', 'before');
	}

	function manager_page() {

		if (! $this->plugin->options->get('base_url') ||
		    ! $this->plugin->options->get('api_key')) {
			return $this->config_error();
		}

		?>
		<div class="wrap">
			<h1><?php _e('Smol Links', 'smol-links'); ?></h1>
			<form action="/wp-admin/admin-ajax.php" method="post" class="smol-links-create">
				<input type="hidden" name="action" value="create_shlink">
				<div class="smol-links-edit-field">
					<label for="smol-links-create__long-url" class="smol-links-label">URL to shorten</label>
					<input type="text" name="long_url" id="smol-links-create__long-url" class="smol-links-long-url regular-text ltr">
				</div>
				<div class="smol-links-edit-field">
					<label for="smol-links-create__title" class="smol-links-label smol-links-label--optional">Title</label>
					<input type="text" name="title" id="smol-links-create__title" class="smol-links-title regular-text ltr">
				</div>
				<div class="smol-links-edit-field">
					<label for="smol-links-create__short-code" class="smol-links-label smol-links-label--optional">Short code</label>
					<?php $this->short_code_domain(); ?><input type="text" name="short_code" id="smol-links-create__short-code" class="smol-links-short-code regular-text ltr">
				</div>
				<div class="smol-links-buttons">
					<input type="submit" value="Shorten" class="shlinkn-submit button button-primary">
				</div>
				<div class="smol-links-create-feedback"></div>
			</form>
			<div class="smol-links-manager">
				<?php $this->manager_tabs(); ?>
				<div class="smol-links-list">
					<div class="smol-links-loading">
						<span class="smol-links-loading-dot smol-links-loading-dot--1"></span>
						<span class="smol-links-loading-dot smol-links-loading-dot--2"></span>
						<span class="smol-links-loading-dot smol-links-loading-dot--3"></span>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	function add_my_links_tab($tabs) {
		$user = wp_get_current_user();
		$new_tabs = [];
		foreach ($tabs as $key => $query) {
			$new_tabs[$key] = $query;
			if ($key == 'All') {
				// Add a 'My Links' tab right after 'All'
				$new_tabs['My Links'] = ['tags[]' => "smol-links-user:{$user->user_login}"];
			}
		}
		return $new_tabs;
	}

	function manager_tabs() {
		?>
		<div class="smol-links-tabs">
			<ul>
				<?php

				list($current) = $this->current_tab();
				foreach ($this->tabs as $tab => $query) {
					$slug = sanitize_title($tab);
					$tab = esc_html($tab);
					$selected = ($current == $slug) ? ' class="selected"' : '';
					echo "<li><a href=\"?page=smol-links&tab=$slug\"$selected>$tab</a></li>";
				}

				?>
			</ul>
		</div>
		<?php
	}

	function current_tab() {
		// Check for a 'tab' query string
		if (! empty($_GET['tab'])) {
			$slug = sanitize_title($_GET['tab']);
			foreach ($this->tabs as $tab => $query) {
				if (sanitize_title($tab) == $slug) {
					return [$slug, $query];
				}
			}
		}

		// Otherwise default to the the first tab
		foreach ($this->tabs as $tab => $query) {
			$slug = sanitize_title($tab);
			return [$slug, $query];
		}
	}

	function short_code_domain() {
		$domains = $this->plugin->options->get('domains');
		$default = $this->plugin->options->get('default_domain');
		if (count($domains) == 1) {
			$domain = esc_html($domains[0]);
			echo "<span class=\"smol-links-short-code-domain\">https://$domain/</span>";
			echo "<input type=\"hidden\" name=\"domain\" value=\"$domain\" class=\"smol-links-domain\">";
		} else {
			echo "<select class=\"smol-links-short-code-domain smol-links-domain\">\n";
			foreach ($domains as $domain) {
				$selected = ($domain == $default) ? ' selected="selected"' : '';
				$domain = esc_html($domain);
				echo "<option value=\"$domain\"$selected>https://$domain</option>\n";
			}
			echo "</select> /\n";
		}
	}

	function config_error() {
		?>
		<div class="wrap">
			<h1><?php _e('Smol Links', 'smol-links'); ?></h1>
			<div class="notice notice-warning">
				<p>
					Cannot connect to Shlink Server.
					<a href="/wp-admin/options-general.php?page=smol-links-settings"><?php _e('Please configure Shlink API settings.', 'smol-links'); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	function ajax_load() {
		try {
			check_ajax_referer('smol_links_load');
			$request = [
				'page'         => 1,
				'itemsPerPage' => 25,
				'orderBy'      => 'dateCreated-DESC'
			];

			list($slug, $query) = current_tab();
			$request = array_merge($request, $query);
			$response = $this->plugin->api->get_shlinks($request);

			header('Content-Type: application/json');
			echo wp_json_encode([
				'ok' => true,
				'shlink' => $response
			]);
		} catch (\Exception $error) {
			header('Content-Type: application/json');
			echo wp_json_encode([
				'ok' => false,
				'error' => $error->getMessage()
			]);
		}
		exit;
	}

	function ajax_create() {
		check_ajax_referer('smol_links_create');

		$long_url = sanitize_text_field($_POST['long_url']);
		$title = sanitize_text_field($_POST['title']);

		$request = [
			'longUrl' => apply_filters('smol_links_long_url', $long_url),
			'title'   => $title
		];

		if (! empty($_POST['short_code'])) {
			$request['customSlug'] = sanitize_text_field($_POST['short_code']);
		}

		$tags = apply_filters('smol_links_tags', ['smol-links-manager']);
		if (is_array($tags)) {
			$request['tags'] = $tags;
		}

		$ok = true;
		try {
			$response = $this->plugin->api->create_shlink($request);
		} catch (ShlinkException $err) {
			$ok = false;
			$response = [
				'detail' => $err->getMessage()
			];
		}

		header('Content-Type: application/json');
		echo wp_json_encode([
			'ok' => $ok,
			'shlink' => $response
		]);
		exit;
	}

	function ajax_update() {
		check_ajax_referer('smol_links_update');

		$long_url = sanitize_text_field($_POST['long_url']);
		$title = sanitize_text_field($_POST['title']);
		$short_code = sanitize_text_field($_POST['short_code']);

		$request = [
			'longUrl' => apply_filters('smol_links_long_url', $long_url),
			'title'   => $title
		];

		$tags = apply_filters('smol_links_tags', ['smol-links-manager']);
		if (is_array($tags)) {
			$request['tags'] = $tags;
		}

		$response = $this->plugin->api->update_shlink($short_code, $request);

		header('Content-Type: application/json');
		echo wp_json_encode([
			'ok' => true,
			'shlink' => $response
		]);
		exit;
	}

}
