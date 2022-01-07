<?php
/**
 * Plugin Name:     WP Shlink
 * Plugin URI:      https://github.com/the-markup/wp-shlink
 * Description:     Create and manage Shlink short links from WordPress
 * Author:          The Markup
 * Author URI:      https://themarkup.org/
 * Text Domain:     wp-shlink
 * Domain Path:     /languages
 * Version:         0.0.1
 *
 * @package         Shlink
 */

 if (! class_exists('Shlink')) {

 	class Shlink {

 		function __construct() {
			add_action('admin_menu', [$this, 'on_admin_menu']);
			add_action('admin_init', [$this, 'on_admin_init']);
			$defaults = [
				'base_url' => '',
				'api_key' => '',
				'generate_on_save' => false
			];
			$saved_options = get_option('shlink_options') ?: [];
			$this->options = array_merge( $defaults, $saved_options );
			if (empty($this->options['base_url']) ||
			    empty($this->options['api_key'])) {
 				return;
 			}
 			if (! empty($this->options['generate_on_save'])) {
 				add_action('save_post', [$this, 'on_save_post']);
 			}
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
			$value = htmlentities($this->options['base_url']);
			echo '<input type="text" name="shlink_options[base_url]" class="regular-text ltr" value="' . $value . '">';
		}

		function api_key_field() {
			$value = htmlentities($this->options['api_key']);
			echo '<input type="text" name="shlink_options[api_key]" class="regular-text ltr" value="' . $value . '">';
		}

		function generate_on_save_field() {
			$value = $this->options['generate_on_save'];
			echo '<input type="checkbox" name="shlink_options[generate_on_save]" value="1" ' . checked( 1, $value, false ) . '>';
		}

 		function on_save_post($post_id) {
 			try {
 				$post = get_post($post_id);
 				if ($post->post_type != 'post') {
 					return;
 				}

 				if ($post->post_status == 'future' &&
 				    $post->post_status == 'publish') {
 					$long_url = get_permalink($post);
 				} else {
 					$long_url = $this->get_expected_permalink($post);
 				}

 				if (empty($long_url)) {
 					return;
 				}

 				$shlink = $this->get_post_shlink($post);
 				$site_url = parse_url(get_site_url());

 				$request = [
 					'longUrl' => $long_url,
 					'title' => $post->post_title,
 					'tags' => [
 						'wp-shlink-onsave',
 						"wp-shlink-site:{$site_url['host']}",
 						"wp-shlink-post:{$post->ID}"
 					]
 				];

 				if (empty($shlink)) {
 					$response = $this->create_shlink($request);
 					$this->save_response($response, $post);
 				} else if ($shlink['longUrl'] != $long_url) {
 					$short_code = $shlink['shortCode'];
 					$response = $this->update_shlink($short_code, $request);
 					$this->save_response($response, $post);
 				}

 			} catch (Exception $err) {
				\dbug($err->getMessage());
 				if ( function_exists( '\wp_sentry_safe' ) ) {
 					\wp_sentry_safe( function ( $client ) use ( $err ) {
 						$client->captureException( $err );
 					} );
 				} else {
 					error_log( $err->getMessage() );
 				}
 			}
 		}

 		function get_post_shlink($post) {
 			$json = get_post_meta($post->ID, 'shlink', true);
 			if (empty($json)) {
 				return null;
 			}
 			$shlink = json_decode($json, 'array');
 			if (empty($shlink['shortUrl'])) {
 				return null;
 			}
 			return $shlink;
 		}

 		function get_expected_permalink($post) {

 			// kinda hacky, based on https://wordpress.stackexchange.com/a/42988

 			$expected = clone $post;
 			$expected->post_status = 'publish';
 			if (! $expected->post_name) {
 				if (! $expected->post_title) {
 					return null;
 				}
 				$expected->post_name = sanitize_title($expected->post_title);
 			}
 			return get_permalink($expected);
 		}

 		function create_shlink($request) {
			$base_url = $this->options['base_url'];
 			$endpoint = "$base_url/rest/v2/short-urls";
 			return $this->request('POST', $endpoint, $request);
 		}

 		function update_shlink($short_code, $request) {
			$base_url = $this->options['base_url'];
 			$endpoint = "$base_url/rest/v2/short-urls/$short_code";
 			return $this->request('PATCH', $endpoint, $request);
 		}

 		function request($method, $endpoint, $request) {
 			$request = apply_filters('wp_shlink_request', $request);
 			return wp_remote_request($endpoint, [
 				'method'  => $method,
 				'body'    => wp_json_encode($request),
 				'headers' => [
 					'Accept'       => 'application/json',
 					'Content-Type' => 'application/json',
 					'X-Api-Key'    => $this->options['api_key']
 				]
 			]);
 		}

 		function save_response($rsp, $post) {
 			if (is_wp_error($rsp)) {
 				throw new Exception('wp-shlink: ' . $rsp->get_error_message());
 			}
 			if (! empty($rsp['response']['code']) &&
 			    $rsp['response']['code'] != 200) {
 				$code = $rsp['response']['code'];
 				$message = $rsp['response']['message'];
 				throw new Exception("wp-shlink: HTTP $code $message");
 			}
 			$shlink = json_decode($rsp['body'], 'array');
 			if (! empty($shlink['shortUrl'])) {
 				update_post_meta($post->ID, 'shlink', $rsp['body']);
 			} else {
 				throw new Exception("wp-shlink: no 'shortUrl' found in API response");
 			}
 		}

 	}

 }

 $shlink = new Shlink();
