<?php

namespace WP_Shlink;

class Manager {

	var $default_tabs = [
		'All'            => [],
		'Manual'         => ['tags[]' => 'shlinkify-manual'],
		'Auto-generated' => ['tags[]' => 'shlinkify-auto']
	];

	function __construct($plugin) {
		$this->plugin = $plugin;
		add_filter('shlinkify_manager_tabs', [$this, 'add_my_links_tab']);
		add_action('init', [$this, 'on_init']);
		add_action('admin_menu', [$this, 'on_admin_menu']);
		add_action('admin_enqueue_scripts', [$this, 'on_enqueue_assets']);
		add_action('wp_ajax_get_shlinks', [$this, 'ajax_get_shlinks']);
		add_action('wp_ajax_create_shlink', [$this, 'ajax_create_shlink']);
		add_action('wp_ajax_update_shlink', [$this, 'ajax_update_shlink']);
	}

	function on_init() {
		$this->tabs = apply_filters('shlinkify_manager_tabs', $this->default_tabs);
	}

	function on_admin_menu() {
		add_menu_page(
			__('Shlinkify Manager', 'shlinkify'),
			__('Shlinkify', 'shlinkify'),
			'edit_posts',
			'shlinkify',
			[$this, 'manager_page'],
			'dashicons-admin-links',
			20
		);
	}

	function on_enqueue_assets($suffix) {
		if ($suffix != 'toplevel_page_shlinkify') {
			return;
		}

		wp_enqueue_script(
			'shlinkify-manager',
			plugins_url('build/manager.js', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/manager.js')
		);

		wp_enqueue_style(
			'shlinkify-manager',
			plugins_url('build/manager.css', __DIR__),
			[],
			filemtime(plugin_dir_path(__DIR__) . 'build/manager.css')
		);

		wp_add_inline_script('shlinkify-manager', 'var shlinkify_nonces = ' . wp_json_encode([
			'get_shlinks'   => wp_create_nonce('get_shlinks'),
			'create_shlink' => wp_create_nonce('create_shlink'),
			'update_shlink'   => wp_create_nonce('update_shlink')
		]) . ';', 'before');
	}

	function manager_page() {

		if (! $this->plugin->options->get('base_url') ||
		    ! $this->plugin->options->get('api_key')) {
			return $this->config_error();
		}

		?>
		<div class="wrap">
			<h1><?php _e('Shlinkify Manager', 'shlinkify'); ?></h1>
			<form action="/wp-admin/admin-ajax.php" method="post" class="shlinkify-create">
				<input type="hidden" name="action" value="create_shlink">
				<div class="shlinkify-create-feedback"></div>
				<div class="shlinkify-edit-field">
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
				<?php $this->manager_tabs(); ?>
				<div class="shlink-list">
					<div class="shlink-loading">
						<span class="shlink-loading-dot shlink-loading-dot--1"></span>
						<span class="shlink-loading-dot shlink-loading-dot--2"></span>
						<span class="shlink-loading-dot shlink-loading-dot--3"></span>
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
				$new_tabs['My Links'] = ['tags[]' => "shlinkify-user:{$user->user_login}"];
			}
		}
		return $new_tabs;
	}

	function manager_tabs() {
		?>
		<div class="shlink-tabs">
			<ul>
				<?php

				foreach ($this->tabs as $tab => $query) {
					$selected = ($this->current_tab() == $tab) ? ' class="selected"' : '';
					echo "<li><a href=\"?page=shlinks&tab=$tab\"$selected>$tab</a></li>";
				}

				?>
			</ul>
		</div>
		<?php
	}

	function current_tab() {
		// Check for a 'tab' query string
		if (! empty($_GET['tab'])) {
			$tab = $_GET['tab'];
			if (isset($this->tabs[$tab])) {
				return $tab;
			}
		}

		// Otherwise default to the the first tab
		foreach ($this->tabs as $tab => $query) {
			return $tab;
		}
	}

	function short_code_domain() {
		$domains = $this->plugin->options->get('domains');
		$default = $this->plugin->options->get('default_domain');
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
			<h1><?php _e('Shlink Manager', 'shlinkify'); ?></h1>
			<div class="notice notice-warning">
				<p>
					Cannot connect to Shlink Server.
					<a href="/wp-admin/options-general.php?page=shlink"><?php _e('Please configure Shlink API settings.', 'shlinkify'); ?></a>
				</p>
			</div>
		</div>
		<?php
	}

	function ajax_get_shlinks() {
		try {
			check_ajax_referer('get_shlinks');
			$request = [
				'page'         => 1,
				'itemsPerPage' => 25,
				'orderBy'      => 'dateCreated-DESC'
			];

			$tab = 'All';
			if (! empty($_GET['tab'])) {
				$tab = $_GET['tab'];
			}

			if (! isset($this->tabs[$tab])) {
				$tab = 'All';
			}

			$query = $this->tabs[$tab];
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

	function ajax_create_shlink() {
		check_ajax_referer('create_shlink');

		$request = [
			'longUrl' => apply_filters('shlink_long_url', $_POST['long_url']),
			'title'   => $_POST['title']
		];

		if (! empty($_POST['short_code'])) {
			$request['customSlug'] = $_POST['short_code'];
		}

		$tags = apply_filters('shlink_tags', ['shlinkify-manager']);
		if (is_array($tags)) {
			$request['tags'] = $tags;
		}

		$response = $this->plugin->api->create_shlink($request);

		header('Content-Type: application/json');
		echo wp_json_encode([
			'ok' => true,
			'shlink' => $response
		]);
		exit;
	}

	function ajax_update_shlink() {
		check_ajax_referer('update_shlink');

		$request = [
			'longUrl' => apply_filters('shlink_long_url', $_POST['long_url']),
			'title'   => $_POST['title']
		];

		$tags = apply_filters('shlink_tags', ['shlinkify-manager']);
		if (is_array($tags)) {
			$request['tags'] = $tags;
		}

		$response = $this->plugin->api->update_shlink($_POST['short_code'], $request);

		header('Content-Type: application/json');
		echo wp_json_encode([
			'ok' => true,
			'shlink' => $response
		]);
		exit;
	}

}
