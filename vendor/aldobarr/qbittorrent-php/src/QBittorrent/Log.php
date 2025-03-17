<?php

namespace AldoBarr\QBittorrent;

use AldoBarr\QBittorrent;

class Log extends QBittorrent {
	protected string $prefix = 'log/';

	protected function __construct() {}

	public function main(bool $normal = true, bool $info = true, bool $warning = true, bool $critical = true, int $last_known_id = -1): array {
		$response = $this->client->get($this->prefix . 'main', [
			'query' => [
				'normal' => $normal ? 'true' : 'false',
				'info' => $info ? 'true' : 'false',
				'warning' => $warning ? 'true' : 'false',
				'critical' => $critical ? 'true' : 'false',
				'last_known_id' => $last_known_id
			]
		]);

		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}

	public function peers(int $last_known_id = -1): array {
		$response = $this->client->get($this->prefix . 'main', [
			'query' => [
				'last_known_id' => $last_known_id
			]
		]);

		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}
}
