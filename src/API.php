<?php
/**
 * Class API
 *
 * @package   Smol Links
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2024 The Markup
 */

namespace SmolLinks;

/**
 * Interface to the Shlink REST API
 */
class API {

	function __construct($plugin) {
		$this->plugin = $plugin;
	}

	function create_shlink($args) {
		$base_url = $this->plugin->options->get('base_url');
		$endpoint = "$base_url/rest/v3/short-urls";
		return $this->request('POST', $endpoint, $args);
	}

	function update_shlink($short_code, $args) {
		$base_url = $this->plugin->options->get('base_url');
		$endpoint = "$base_url/rest/v3/short-urls/$short_code";
		return $this->request('PATCH', $endpoint, $args);
	}

	function get_shlinks($args = null) {
		$base_url = $this->plugin->options->get('base_url');
		$endpoint = "$base_url/rest/v3/short-urls";
		return $this->request('GET', $endpoint, $args);
	}

	function get_domains($options = null) {
		if (!$options) {
			$options = $this->plugin->options->all();
		}
		$endpoint = "{$options['base_url']}/rest/v3/domains";
		return $this->request('GET', $endpoint, [], $options);
	}

	function request($method, $endpoint, $args = null, $options = null) {
		if (!$options) {
			$options = $this->plugin->options->all();
		}
		$url = $endpoint;
		$request = [
			'method'  => $method,
			'headers' => [
				'Accept'       => 'application/json',
				'X-Api-Key'    => $options['api_key']
			]
		];
		if ($args) {
			if (strtoupper($method) == 'GET') {
				$url .= '?' . build_query($args);
			} else {
				$request['headers']['Content-Type'] = 'application/json';
				$request['body'] = wp_json_encode($args);
			}
		}
		$response = wp_remote_request($url, $request);
		$status = wp_remote_retrieve_response_code($response);
		if (is_wp_error($response)) {
			throw new \Exception('Smol Links: ' . $response->get_error_message());
		} else if ($status != 200) {
			$rsp = json_decode($response['body'], 'array');
			if ($rsp) {
				throw new ShlinkException($rsp);
			}
			throw new \Exception("Smol Links: HTTP $status {$response['body']}");
		} else if (! empty($response['body'])) {
			return json_decode($response['body'], 'array');
		} else {
			throw new \Exception("Smol Links: error loading $endpoint");
		}
	}

}
