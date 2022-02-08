<?php

namespace WP_Shlink;

use WP_Shlink\API;
use WP_Shlink\Options;

class Manager {

	function __construct() {
		$this->api = API::init();
		add_action('admin_menu', [$this, 'on_admin_menu']);
		add_action('admin_enqueue_scripts', [$this, 'on_enqueue_assets']);
		add_action('wp_ajax_get_shlinks', [$this, 'ajax_get_shlinks']);
		add_action('wp_ajax_create_shlink', [$this, 'ajax_create_shlink']);
		add_action('wp_ajax_update_shlink', [$this, 'ajax_update_shlink']);
	}

	function on_admin_menu() {
		add_menu_page(
			__('Shlink Manager', 'wp-shlink'),
			__('Shlinks', 'wp-shlink'),
			'edit_posts',
			'shlinks',
			[$this, 'manager_page'],
			'dashicons-admin-links',
			20
		);
	}

	function on_enqueue_assets($suffix) {
		if ($suffix != 'toplevel_page_shlinks') {
			return;
		}

		wp_enqueue_script(
			'wp-shlink-manager',
			plugins_url('build/manager.js', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/manager.js')
		);

		wp_enqueue_style(
			'wp-shlink-manager',
			plugins_url('build/manager.css', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/manager.css')
		);

		wp_add_inline_script('wp-shlink-manager', 'var nonces = ' . wp_json_encode([
			'get_shlinks'   => wp_create_nonce('get_shlinks'),
			'create_shlink' => wp_create_nonce('create_shlink'),
			'update_shlink'   => wp_create_nonce('update_shlink')
		]) . ';', 'before');
	}

	function manager_page() {

		$options = Options::init();
		if (! $options->get('base_url') || ! $options->get('api_key')) {
			return $this->config_error();
		}

		?>
		<div class="wrap">
			<h1><?php _e('Shlink Manager', 'wp-shlink'); ?></h1>
			<form action="/wp-admin/admin-ajax.php" method="post" class="shlink-create">
				<input type="hidden" name="action" value="create_shlink">
				<div class="shlink-create-feedback"></div>
				<div class="shlink-edit-field">
					<label for="shlink-create__long-url" class="shlink-label">URL to shorten</label>
					<input type="text" name="long_url" id="shlink-create__long-url" class="shlink-long-url regular-text ltr">
				</div>
				<div class="shlink-edit-field">
					<label for="shlink-create__title" class="shlink-label shlink-label--optional">Title</label>
					<input type="text" name="title" id="shlink-create__title" class="shlink-title regular-text ltr">
				</div>
				<div class="shlink-edit-field">
					<label for="shlink-create__short-code" class="shlink-label shlink-label--optional">Short code</label>
					<?php $this->short_code_domain(); ?><input type="text" name="short_code" id="shlink-create__short-code" class="shlink-short-code regular-text ltr">
				</div>
				<div class="shlink-buttons">
					<input type="submit" value="Shorten" class="shlinkn-submit button button-primary">
				</div>
			</form>
			<div class="shlink-manager">
				<div class="shlink-loading">
					<span class="shlink-loading-dot shlink-loading-dot--1"></span>
					<span class="shlink-loading-dot shlink-loading-dot--2"></span>
					<span class="shlink-loading-dot shlink-loading-dot--3"></span>
				</div>
			</div>
		</div>
		<?php
	}

	function short_code_domain() {
		$options = Options::init();
		$domains = $options->get('domains');
		$default = $options->get('default_domain');
		if (count($domains) == 1) {
			$domain = htmlentities($domains[0]);
			echo "<span class=\"shlink-short-code-domain\">https://$domain/</span>";
			echo "<input type=\"hidden\" name=\"domain\" value=\"$domain\" class=\"shlink-domain\">";
		} else {
			echo "<select class=\"shlink-short-code-domain shlink-domain\">\n";
			foreach ($domains as $domain) {
				$selected = ($domain == $default) ? ' selected="selected"' : '';
				$domain = htmlentities($domain);
				echo "<option value=\"$domain\"$selected>https://$domain</option>\n";
			}
			echo "</select> /\n";
		}
	}

	function config_error() {
		?>
		<div class="wrap">
			<h1><?php _e('Shlink Manager', 'wp-shlink'); ?></h1>
			<div class="notice notice-warning">
				<p>
					Cannot connect to Shlink Server.
					<a href="/wp-admin/options-general.php?page=shlink"><?php _e('Please configure Shlink API settings.', 'wp-shlink'); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	function ajax_get_shlinks() {
		try {
			check_ajax_referer('get_shlinks');
			$response = $this->api->get_shlinks([
				'page'         => 1,
				'itemsPerPage' => 25,
				'orderBy'      => 'dateCreated-DESC'
			]);

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

	function ajax_create_shlink() {
		check_ajax_referer('create_shlink');
		$request = [
			'longUrl' => $_POST['long_url'],
			'title'   => $_POST['title']
		];
		if (! empty($_POST['short_code'])) {
			$request['customSlug'] = $_POST['short_code'];
		}
		$response = $this->api->create_shlink($request);
		header('Content-Type: application/json');
		echo wp_json_encode([
			'ok' => true,
			'shlink' => $response
		]);
		exit;
	}

	function ajax_update_shlink() {
		check_ajax_referer('update_shlink');
		$response = $this->api->update_shlink($_POST['short_code'], [
			'longUrl' => $_POST['long_url'],
			'title'   => $_POST['title']
		]);
		header('Content-Type: application/json');
		echo wp_json_encode([
			'ok' => true,
			'shlink' => $response
		]);
		exit;
	}

}
