<?php

namespace AldoBarr\QBittorrent;

use AldoBarr\QBittorrent;
use AldoBarr\QBittorrent\Exceptions\InvalidArgumentException;

class Transfer extends QBittorrent {
	protected string $prefix = 'transfer/';

	protected function __construct() {}

	public function banPeers(string $peers): bool {
		$response = $this->client->post($this->prefix . 'banPeers', [
			'form_params' => [
				'peers' => $peers
			]
		]);

		return $response->getStatusCode() === 200;
	}

	public function downloadLimit(): int {
		$response = $this->client->get($this->prefix . 'downloadLimit');
		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}

	public function info(): object {
		$response = $this->client->get($this->prefix . 'info');
		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}

	public function setDownloadLimit(int $limit): bool {
		if ($limit < 0) {
			throw new InvalidArgumentException('The limit must be a positive integer or 0.');
		}

		$response = $this->client->post($this->prefix . 'setDownloadLimit', [
			'form_params' => [
				'limit' => $limit
			]
		]);

		return $response->getStatusCode() === 200;
	}

	public function setUploadLimit(int $limit): bool {
		if ($limit < 0) {
			throw new InvalidArgumentException('The limit must be a positive integer or 0.');
		}

		$response = $this->client->post($this->prefix . 'setUploadLimit', [
			'form_params' => [
				'limit' => $limit
			]
		]);

		return $response->getStatusCode() === 200;
	}

	public function speedLimitsMode(): bool {
		$response = $this->client->get($this->prefix . 'speedLimitsMode');
		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data === 1;
	}

	public function toggleSpeedLimitsMode(): bool {
		$response = $this->client->post($this->prefix . 'toggleSpeedLimitsMode');
		return $response->getStatusCode() === 200;
	}

	public function uploadLimit(): int {
		$response = $this->client->get($this->prefix . 'uploadLimit');
		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}
}
