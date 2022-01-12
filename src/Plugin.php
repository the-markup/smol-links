<?php
/**
 * Class Plugin
 *
 * @package   Shlink
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2022 The Markup
 */

namespace WP_Shlink;

require_once(__DIR__ . '/Options.php');
require_once(__DIR__ . '/Admin.php');
require_once(__DIR__ . '/API.php');

use WP_Shlink\Options;
use WP_Shlink\Admin;
use WP_Shlink\API;

/**
 * Initial configuration, WordPress hooks, and high level behavior
 */
class Plugin {

	/**
	 * The unique singleton instance of the plugin
	 *
	 * @var WP_Shlink\Plugin
	 */
	static $instance;

	/**
	 * Gets the unique singleton instance of the plugin
	 *
	 * @return WP_Shlink\Plugin
	 */
	static function init() {
		if (! self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Setup contingent object instances (Options, Admin, and API) and WordPress
	 * hooks
	 *
	 * @return void
	 */
	function __construct() {
		$this->options = Options::init();
		$this->api = API::init();
		$this->admin = new Admin($this->options);
		add_action('save_post', [$this, 'on_save_post']);
	}

	/**
	 * Handler function invoked when a post is saved
	 *
	 * This handler listens for `save_post` actions and—if the __Generate on
	 * save__ option is enabled—automatically generates a short URL for the
	 * post.
	 *
	 * The Shlink record includes the post's title and the following tags:
	 * - `wp-shlink-onsave`
	 * - `wp-shlink-site:[WordPress site hostname]`
	 * - `wp-shlink-post:[numeric post ID]`
	 *
	 * @param number $post_id The post's numeric ID
	 * @return void
	 */
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
				$this->save_post_response($response, $post);
			} else if ($shlink['longUrl'] != $long_url) {
				$short_code = $shlink['shortCode'];
				$response = $this->api->update_shlink($short_code, $request);
				$this->save_post_response($response, $post);
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

	/**
	 * Retrieve a stored Shlink for a given post
	 *
	 * The returned object is from the raw JSON response from the Shlink API at
	 * the time the post was last updated.
	 *
	 * @param \WP_Post $post Target post to retrieve the Shlink for
	 * @return object
	 */
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

	/**
	 * Returns the current expected permalink, if the post were to be published
	 *
	 * This is kind of a hack. We create a clone of `$post`, set its status to
	 * `publish` and then pass _that_ post to `get_permalink()`. The resulting
	 * URL should match the post's eventual permalink.
	 *
	 * @param \WP_Post $post Target post to predict a permalink for
	 * @return string
	 * @see https://wordpress.stackexchange.com/a/42988
	 */
	function get_expected_permalink($post) {
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

	/**
	 * Handles the Shlink API response for a recently saved post
	 *
	 * @param array $rsp A response from `wp_remote_request()`
	 * @param \WP_Post $post The post we're saving a Shlink for
	 */
	function save_post_response($rsp, $post) {
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
