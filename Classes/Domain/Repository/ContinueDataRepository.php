<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
namespace Ecsec\Eidlogin\Domain\Repository;

use Ecsec\Eidlogin\Domain\Model\ContinueData;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * Class ContinueDataRepository
 */
class ContinueDataRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Find by reqid
     *
     * @param string $reqid
     * @return ContinueData $continueData The ContinueData or null if none is found
     */
    public function findByReqid(string $reqid)
    {
        $query = $this->createQuery();
        return $query
            ->matching($query->equals('reqid', $reqid))
            ->execute()
            ->getFirst();
    }

    /**
     * Find by uid.
     *
     * @param mixed $uid The uid for which to find ContinueData
     * @return ContinueData $continueData The ContinueData or null if none is found
     */
    public function findByUid($uid): ContinueData
    {
        $query = $this->createQuery();
        return $query
            ->matching($query->equals('uid', $uid))
            ->execute()
            ->getFirst();
    }

    /**
     * Find data older than a given limit.
     *
     * @param int $limit The limit as timestamp
     * @return QueryResult The query result
     */
    public function findOlderThan(int $limit): QueryResult
    {
        $query = $this->createQuery();
        return $query->matching($query->lessThan('time', $limit))
                ->execute();
    }
}
