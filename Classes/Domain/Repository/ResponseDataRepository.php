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

use Ecsec\Eidlogin\Domain\Model\ResponseData;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;

/**
 * Class ResponseDataRepository
 */
class ResponseDataRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Find ResponseData by the rspid
     *
     * @param string $rspid
     * @return ResponseData $responseData The ResponseData or null if none is found
     */
    public function findByRspid(string $rspid)
    {
        $query = $this->createQuery();
        return $query
            ->matching($query->equals('rspid', $rspid))
            ->execute()
            ->getFirst();
    }

    /**
     * Find by uid.
     *
     * @param mixed $uid The uid to find ResponseData for
     * @return ResponseData $responseData The ResponseData or null if none is found
     */
    public function findByUid($uid): ResponseData
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
     * @param int $limit The limit for removal as timestamp
     * @return QueryResult The query result
     */
    public function findOlderThan(int $limit): QueryResult
    {
        $query = $this->createQuery();
        return $query->matching($query->lessThan('time', $limit))
                ->execute();
    }
}
