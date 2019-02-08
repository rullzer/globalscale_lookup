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

class UserService {

	/** @var UserMapper */
	private $userMapper;
	/** @var StoreMapper */
	private $storeMapper;

	public function __construct(UserMapper $userMapper, StoreMapper $storeMapper) {
		$this->userMapper = $userMapper;
		$this->storeMapper = $storeMapper;
	}

	public function insertOrUpdate(string $cloudId, array $data): void {
		try {
			$user = $this->userMapper->findUserByCloudId($cloudId);
		} catch (DoesNotExistException $e) {
			$user = new User();
			$user->setFederationId($cloudId);
			$user = $this->userMapper->insert($user);
		}

		$this->storeMapper->clearForUserId($user->getId());

		// TODO: verify fields?
		foreach ($data as $key => $value) {
			$store = new Store();
			$store->setUserId($user->getId());
			$store->setKey($key);
			$store->setValue($value);
			$this->storeMapper->insert($store);
		}
	}

	public function delete(string $cloudId): void {
		try {
			$user = $this->userMapper->findUserByCloudId($cloudId);
		} catch (DoesNotExistException $e) {
			return;
		}

		$this->storeMapper->clearForUserId($user->getId());
		$this->userMapper->delete($user);
	}
}
