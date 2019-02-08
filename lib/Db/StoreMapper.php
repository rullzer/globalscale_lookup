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

namespace OCA\GlobalScaleLookup\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class StoreMapper extends QBMapper {

	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'gslookup_store', Store::class);
	}

	public function getByUserId(int $userId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId))
			);

		return $this->findEntities($qb);
	}

	/**
	 * Search for $search in the store for values of $keys
	 * Returns an array of userids
	 */
	public function searchInValues(string $search, bool $exact, array $keys): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('user_id', $qb->func()->count('value', 'matches'))
			->from($this->getTableName());

		if ($exact) {
			$qb->where(
				$qb->expr()->eq('value', $qb->createNamedParameter($search))
			);
		} else {
			$qb->where(
				$qb->expr()->like('value', $qb->createNamedParameter('%'.$search.'%'))
			);
		}

		if ($keys !== []) {
			$qb->andWhere(
				$qb->expr()->in('key', $keys)
			);
		}

		$qb->groupBy('user_id');
		$qb->orderBy('matches','user_id');
		$qb->setMaxResults(50);

		$cursor = $qb->execute();
		$data = $cursor->fetchAll();
		$cursor->closeCursor();
		
		$data = array_map(function(array $row) {
			return $row['user_id'];
		}, $data);

		return $data;
	}

}
