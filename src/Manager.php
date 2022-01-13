<?php

namespace WP_Shlink;

use WP_Shlink\API;

class Manager {

	function __construct() {
		$this->api = API::init();
		add_action('admin_menu', [$this, 'on_admin_menu']);
		add_action('admin_enqueue_scripts', [$this, 'on_enqueue_assets']);
		add_action('wp_ajax_get_shlinks', [$this, 'ajax_get_shlinks']);
		add_action('wp_ajax_create_shlink', [$this, 'ajax_create_shlink']);
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
			['jquery'],
			filemtime(plugin_dir_path(__DIR__) . 'build/manager.js')
		);

		wp_enqueue_style(
			'wp-shlink-manager',
			plugins_url('build/manager.css', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/manager.css')
		);
	}

	function manager_page() {
		?>
		<div class="wrap">
			<h1><?php _e('Shlink Manager', 'wp-shlink'); ?></h1>
			<form action="/wp-admin/admin-ajax.php" method="post" class="shlink-create">
				<input type="hidden" name="action" value="create_shlink">
				<label for="long_url">URL to shorten</label>
				<input type="text" name="long_url" id="long_url" class="shlink-long-url">
				<input type="submit" value="Shorten">
			</form>
			<div class="shlink-manager">Loading...</div>
		</div>
		<?php
	}

	function ajax_get_shlinks() {
		try {
			$response = $this->api->get_shlinks([
				'page'         => 1,
				'itemsPerPage' => 25,
				'orderBy'      => 'dateCreated-DESC'
			]);

			header('Content-Type: application/json');
			echo json_encode([
				'ok' => true,
				'shlink' => $response
			]);
		} catch (\Exception $error) {
			header('Content-Type: application/json');
			echo json_encode([
				'ok' => false,
				'error' => $error->getMessage()
			]);
		}
		exit;
	}

	function ajax_create_shlink() {
		$response = $this->api->create_shlink([
			'longUrl' => $_POST['long_url']
		]);
		header('Content-Type: application/json');
		echo json_encode([
			'ok' => true,
			'shlink' => $response
		]);
		exit;
	}

}
