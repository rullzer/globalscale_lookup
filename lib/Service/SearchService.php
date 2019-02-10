<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\GlobalScaleLookup\Service;

use OCA\GlobalScaleLookup\Db\Store;
use OCA\GlobalScaleLookup\Db\StoreMapper;
use OCA\GlobalScaleLookup\Db\User;
use OCA\GlobalScaleLookup\Db\UserMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class SearchService {
	/** @var StoreMapper */
	private $storeMapper;
	/** @var UserMapper */
	private $userMapper;

	public function __construct(StoreMapper $storeMapper, UserMapper $userMapper) {
		$this->storeMapper = $storeMapper;
		$this->userMapper = $userMapper;
	}

	public function search(string $search, bool $exact, array $keys, bool $exactCloudId): array {
		if ($exactCloudId) {
			return $this->getExactCloudId($search);
		}

		return $this->genericSearch($search, $exact, $keys);
	}

	protected function getExactCloudId(string $search): array {
		try {
			$user = $this->userMapper->findUserByCloudId($search);
		} catch (DoesNotExistException $e) {
			return [];
		}

		return $this->userToResult($user);
	}

	protected function genericSearch(string $search, bool $exact, array $keys): array {
		$userIds = $this->storeMapper->searchInValues($search, $exact, $keys);

		/** @var User[] $users */
		$users = $this->userMapper->findUsers($userIds);

		$results = [];
		foreach ($users as $user) {
			$results[] = $this->userToResult($user);
		}

		return $results;
	}

	protected function userToResult(User $user): array {
		$result = [
			'federationId' => $user->getFederationId()
		];

		/** @var Store[] $stores */
		$stores = $this->storeMapper->getByUserId($user->getId());

		foreach ($stores as $store) {
			$result[$store->getKey()] = $store->getValue();
		}

		return $result;
	}
}
