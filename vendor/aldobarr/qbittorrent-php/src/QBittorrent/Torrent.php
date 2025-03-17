<?php

namespace AldoBarr\QBittorrent;

use AldoBarr\QBittorrent;
use AldoBarr\QBittorrent\Exceptions\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class Torrent extends QBittorrent {
	protected string $hash;
	protected string $prefix = 'torrents/';

	protected function __construct() {}

	protected function setHash(string $hash): void {
		$this->hash = $hash;
	}

	/**
	 * Adds peer(s) to the selected torrent referenced by this Torrent object.
	 *
	 * @param array $peers The peer(s) to add to the torrent. Each peer is a colon-separated `host:port`
	 * @return boolean True if at least one supplied peer is added. Otherwise false indicated no valid peers was supplied.
	 */
	public function addPeers(array $peers): bool {
		if (empty($peers)) {
			return true;
		}

		$response = $this->client->post($this->prefix . 'addPeers', [
			'form_params' => [
				'hashes' => $this->hash,
				'peers' => implode('|', array_map('trim', $peers))
			]
		]);

		return $response->getStatusCode() === 200;
	}

	public function addTrackers(array $urls): bool {
		if (empty($urls)) {
			return true;
		}

		$response = $this->client->post($this->prefix . 'addTrackers', [
			'form_params' => [
				'hash' => $this->hash,
				'urls' => implode("\n", array_map('trim', $urls))
			]
		]);

		return $response->getStatusCode() === 200;
	}

	/**
	 * Sets the priority of the currently referenced torrent by this Torrent object to the bottom priority.
	 *
	 * @return boolean True on success or false if torrent queueing is not enabled.
	 */
	public function bottomPrio(): bool {
		$response = $this->client->post($this->prefix . 'bottomPrio', [
			'query' => [
				'hashes' => $this->hash
			]
		]);

		return $response->getStatusCode() === 200;
	}

	/**
	 * Decreases the priority of the currently referenced torrent by this Torrent object.
	 *
	 * @return boolean True on success or false if torrent queueing is not enabled.
	 */
	public function decreasePrio(): bool {
		$response = $this->client->post($this->prefix . 'decreasePrio', [
			'query' => [
				'hashes' => $this->hash
			]
		]);

		return $response->getStatusCode() === 200;
	}

	public function delete(bool $deleteFiles = false): bool {
		$response = $this->client->post($this->prefix . 'delete', [
			'query' => [
				'hashes' => $this->hash,
				'deleteFiles' => $deleteFiles ? 'true' : 'false'
			]
		]);

		return $response->getStatusCode() === 200;
	}

	/**
	 * Edit a tracker for the torrent referenced by this torrent object.
	 *
	 * @param string $original
	 * @param string $new
	 * @return boolean True if the tracker was modified or the new url was already a tracker. Otherwise false.
	 * @throws InvalidArgumentException When `$original` or `$new` are invalid
	 */
	public function editTracker(string $original, string $new): bool {
		$response = $this->client->post($this->prefix . 'editTracker', [
			'form_params' => [
				'hash' => $this->hash,
				'origUrl' => $original,
				'newUrl' => $new
			]
		]);

		switch ($response->getStatusCode()) {
			case 400:
				throw new InvalidArgumentException('The new url "' . $new . '" is not a valid url');
				return false;

			case 404:
				return false;

			case 409:
				$body = $response->getBody();
				$data = $body->getContents();
				$body->close();
				if (stristr($data, 'not found')) {
					throw new InvalidArgumentException('The original url "' . $original . '" was not found');
					return false;
				}

				return true;
		}

		return true;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $ids id values (integers) correspond to file position inside the array returned by torrent contents API,
	 * 				e.g. id=0 for first file, id=1 for second file, etc. Since 2.8.2 it is reccomended to use index
	 * 				field returned by torrent contents API (since the files can be filtered and the index value may
	 * 				differ from the position inside the response array).
	 * @param integer $priority File priority to set where 0="Do not download", 1="Normal priority", 6="High priority",
	 * 				and 7="Maximum priority"
	 * @return boolean
	 * @throws InvalidArgumentException When an invalid priority is supplied or a value in `$ids` is not an integer.
	 */
	public function filePrio(array $ids, int $priority): bool {
		if (empty($ids)) {
			return true;
		}

		$possible_prios = [0, 1, 6, 7];
		if (!in_array($priority, $possible_prios)) {
			throw new InvalidArgumentException('The priority ' . $priority . ' is invalid');
		}

		foreach ($ids as $id) {
			if (!is_int($id)) {
				throw new InvalidArgumentException('The file id ' . $id . ' is invalid');
			}
		}

		$response = $this->client->post($this->prefix . 'filePrio', [
			'form_params' => [
				'hash' => $this->hash,
				'id' => implode('|', $ids),
				'priority' => $priority
			]
		]);

		if ($response->getStatusCode() === 409) {
			$body = $response->getBody();
			$data = $body->getContents();
			$body->close();
			if (stristr($data, 'file id')) {
				throw new InvalidArgumentException('The file id ' . $id . ' was not found');
			}
		}

		return $response->getStatusCode() === 200;
	}

	public function files(string $indexes = ''): array {
		$options = ['query' => ['hash' => $this->hash]];
		if (!empty($indexes)) {
			$options['query']['indexes'] = $indexes;
		}

		$response = $this->client->get($this->prefix . 'files', $options);
		if ($response->getStatusCode() === 404) {
			return [];
		}

		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}

	public function getHash(): string {
		return $this->hash;
	}

	/**
	 * Increases the priority of the currently referenced torrent by this Torrent object.
	 *
	 * @return boolean True on success or false if torrent queueing is not enabled.
	 */
	public function increasePrio(): bool {
		$response = $this->client->post($this->prefix . 'increasePrio', [
			'query' => [
				'hashes' => $this->hash
			]
		]);

		return $response->getStatusCode() === 200;
	}

	public function pause(): bool {
		$response = $this->client->post($this->prefix . 'pause', [
			'query' => [
				'hashes' => $this->hash
			]
		]);

		return $response->getStatusCode() === 200;
	}

	public function peers(int $rid = 0): object {
		return $this->sync()->torrentPeers($this->getHash(), $rid);
	}

	public function pieceHashes(): array {
		$response = $this->client->get($this->prefix . 'pieceHashes', [
			'query' => [
				'hash' => $this->hash
			]
		]);

		if ($response->getStatusCode() === 404) {
			return (object)[];
		}

		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}

	public function pieceStates(): array {
		$response = $this->client->get($this->prefix . 'pieceStates', [
			'query' => [
				'hash' => $this->hash
			]
		]);

		if ($response->getStatusCode() === 404) {
			return (object)[];
		}

		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}

	public function properties(): object {
		$response = $this->client->get($this->prefix . 'properties', [
			'query' => [
				'hash' => $this->hash
			]
		]);

		if ($response->getStatusCode() === 404) {
			return (object)[];
		}

		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}

	public function reannounce(): bool {
		$response = $this->client->post($this->prefix . 'reannounce', [
			'query' => [
				'hashes' => $this->hash
			]
		]);

		return $response->getStatusCode() === 200;
	}

	public function removeTrackers(array $urls): bool {
		if (empty($urls)) {
			return true;
		}

		$response = $this->client->post($this->prefix . 'removeTrackers', [
			'form_params' => [
				'hash' => $this->hash,
				'urls' => implode("\n", array_map('trim', $urls))
			]
		]);

		return $response->getStatusCode() === 200;
	}

	public function recheck(): bool {
		$response = $this->client->post($this->prefix . 'recheck', [
			'query' => [
				'hashes' => $this->hash
			]
		]);

		return $response->getStatusCode() === 200;
	}

	public function renameFile(string $old_path, string $new_path): ResponseInterface {
		return $this->client->post($this->prefix . 'renameFile', [
			'form_params' => [
				'hash' => $this->hash,
				'oldPath' => $old_path,
				'newPath' => $new_path
			]
		]);
	}

	public function renameFolder(string $old_path, string $new_path): ResponseInterface {
		return $this->client->post($this->prefix . 'renameFolder', [
			'form_params' => [
				'hash' => $this->hash,
				'oldPath' => $old_path,
				'newPath' => $new_path
			]
		]);
	}

	public function resume(): bool {
		$response = $this->client->post($this->prefix . 'resume', [
			'query' => [
				'hashes' => $this->hash
			]
		]);

		return $response->getStatusCode() === 200;
	}

	/**
	 * Sets the priority of the currently referenced torrent by this Torrent object to the top priority.
	 *
	 * @return boolean True on success or false if torrent queueing is not enabled.
	 */
	public function topPrio(): bool {
		$response = $this->client->post($this->prefix . 'topPrio', [
			'query' => [
				'hashes' => $this->hash
			]
		]);

		return $response->getStatusCode() === 200;
	}

	public function trackers(): array {
		$response = $this->client->get($this->prefix . 'trackers', [
			'query' => [
				'hash' => $this->hash
			]
		]);

		if ($response->getStatusCode() === 404) {
			return [];
		}

		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}

	public function webseeds(): array {
		$response = $this->client->get($this->prefix . 'webseeds', [
			'query' => [
				'hash' => $this->hash
			]
		]);

		if ($response->getStatusCode() === 404) {
			return [];
		}

		$body = $response->getBody();
		$data = json_decode($body->getContents());
		$body->close();

		return $data;
	}
}
