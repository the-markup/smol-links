<?php
/**
 * Class TestAPI
 *
 * @package   Shlink
 * @author    The Markup
 * @license   GPL-2.0-or-later
 * @link      https://themarkup.org/
 * @copyright 2022 The Markup
 */

namespace WP_Shlink;

require_once(dirname(__DIR__) . '/src/API.php');

use WP_Shlink\API;

class TestAPI extends API {

	function request($method, $endpoint, $request) {

		$tags = isset($request['tags']) ? $request['tags'] : [];
		$domain = isset($request['domain']) ? $request['domain'] : null;
		$title = isset($request['title']) ? $request['title'] : null;
		$crawlable = isset($request['crawlable']) ? $request['crawlable'] : true;
		$forwardQuery = isset($request['forwardQuery']) ? $request['forwardQuery'] : true;

		$response = [
			"shortCode"      => "xxxxx",
			"shortUrl"       => "https://example.com/xxxxx",
			"longUrl"        => $request['longUrl'],
			"dateCreated"    => current_time(DATE_RFC3339),
			"visitsCount"    => 0,
			"tags"           => $tags,
			"meta"           => [
				"validSince" => null,
				"validUntil" => null,
				"maxVisits"  => null
			],
			"domain"         => $domain,
			"title"          => $title,
			"crawlable"      => $crawlable,
			"forwardQuery"   => $forwardQuery
		];

		return [
			'response' => [
				'code' => 200,
				'message' => 'OK'
			],
			'body' => json_encode($response)
		];
	}

}
