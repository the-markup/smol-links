<?php

namespace WP_Shlink;

require_once(__DIR__ . '/Options.php');
require_once(__DIR__ . '/Admin.php');
require_once(__DIR__ . '/API.php');

class Plugin {

	static $instance;

	static function init() {
		if (! self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function __construct() {
		$this->options = \WP_Shlink\Options::init();
		$this->admin = new \WP_Shlink\Admin($this->options);
		$this->api = new \WP_Shlink\API();
		add_action('save_post', [$this, 'on_save_post']);
	}

	function on_save_post($post_id) {
		try {

			if (! $this->options->get('base_url') ||
				! $this->options->get('api_key')) {
				return;
			}

			if (! $this->options->get('generate_on_save')) {
				return;
			}

			$post = get_post($post_id);
			if ($post->post_type != 'post') {
				return;
			}

			if ($post->post_status == 'future' &&
			    $post->post_status == 'publish') {
				$long_url = get_permalink($post);
			} else if ($post->post_status != 'auto-draft') {
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
				$response = $this->api->create_shlink($request);
				$this->save_response($response, $post);
			} else if ($shlink['longUrl'] != $long_url) {
				$short_code = $shlink['shortCode'];
				$response = $this->api->update_shlink($short_code, $request);
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
