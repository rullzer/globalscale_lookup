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

namespace OCA\GlobalScaleLookup\Controller;

use OCA\GlobalScaleLookup\Service\AuthKeyService;
use OCA\GlobalScaleLookup\Service\SearchService;
use OCA\GlobalScaleLookup\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class LookupController extends Controller {

	/** @var AuthKeyService */
	private $authKeyService;
	/** @var UserService */
	private $userService;
	/** @var SearchService */
	private $searchService;

	public function __construct(string $appName,
								IRequest $request,
								AuthKeyService $authKeyService,
								UserService $userService,
								SearchService $searchService) {
		parent::__construct($appName, $request);
		$this->authKeyService = $authKeyService;
		$this->userService = $userService;
		$this->searchService = $searchService;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * TODO: add option to limit request to certain subnets, block/throttle otherwise
	 */
	public function search(string $search = '', bool $exact = false, string $keys = '{}', bool $exactCloudId = false): JSONResponse {
		if ($search === '') {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		$keys = json_decode($keys, true, 2);
		if ($keys === null) {
			$keys = [];
		}
		
		$data = $this->searchService->search($search, $exact, $keys, $exactCloudId);

		return new JSONResponse($data);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @BruteForceProtection(action=gslookup_modify)
	 *
	 * TODO: verify users format
	 */
	public function register(string $authKey, array $users): JSONResponse {
		if (!$this->authKeyService->isValidAuthKey($authKey)) {
			$response = new JSONResponse([], Http::STATUS_BAD_REQUEST);
			$response->throttle();
			return $response;
		}

		foreach ($users as $cloudId => $data) {
			$this->userService->insertOrUpdate($cloudId, $data);
		}

		return new JSONResponse();
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @BruteForceProtection(action=gslookup_modify)
	 *
	 * TODO: verify users array
	 */
	public function remove(string $authKey, array $users): JSONResponse {
		if (!$this->authKeyService->isValidAuthKey($authKey)) {
			$response = new JSONResponse([], Http::STATUS_BAD_REQUEST);
			$response->throttle();
			return $response;
		}

		foreach ($users as $cloudId) {
			$this->userService->delete($cloudId);
		}

		return new JSONResponse();
		
	}
}
