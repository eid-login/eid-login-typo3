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

use Ecsec\Eidlogin\Domain\Model\Eid;
use Ecsec\Eidlogin\Service\EidService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class EidRepository
 */
class EidRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Find an eid by the feuid
     *
     * @param int $feuid
     * @return Eid $eid The eid or null if none is found
     */
    public function findByFeuid(int $feuid)
    {
        $query = $this->createQuery();
        return $query
            ->matching($query->equals('feuid', $feuid))
            ->execute()
            ->getFirst();
    }

    /**
     * Count by the feuid
     *
     * @param int $feuid
     * @return int The number of Eids with the given feuid
     */
    public function countByFeuid(int $feuid)
    {
        $query = $this->createQuery();
        return $query
            ->matching($query->equals('feuid', $feuid))
            ->count();
    }

    /**
     * Count by the beuid
     *
     * @param int $beuid
     * @return int The number of Eids with the given beuid
     */
    public function countByBeuid(int $beuid)
    {
        $query = $this->createQuery();
        return $query
            ->matching($query->equals('beuid', $beuid))
            ->count();
    }

    /**
     * Find by pid
     *
     * @param string $pid
     *
     * @return array<Eid> $res The result
     */
    public function findByPid(string $pid)
    {
        $query = $this->createQuery();
        $query->matching($query->equals('pid', $pid));
        return $query->execute()
                    ->toArray();
    }

    /**
     * Find by pid and eidvalue
     *
     * @param string $pid
     * @param string $eidvalue, may be null
     *
     * @return array<Eid> $res The result
     */
    public function findByPidAndEidvalue(string $pid, string $eidvalue)
    {
        $query = $this->createQuery();
        $query->matching($query->equals('pid', $pid));
        $query->matching($query->equals('eidvalue', $eidvalue));
        return $query->execute()
                    ->toArray();
    }

    /**
     * Find a type and eidValue
     *
     * @param string $type
     * @param string $eidvalue
     * @return Eid $eid The eid or null if none is found
     */
    public function findByTypeAndEidvalue(string $type, string $eidvalue)
    {
        if ($type !== EidService::TYPE_FE && $type !== EidService::TYPE_BE) {
            throw new \Exception('invalid type');
        }
        $query = $this->createQuery();
        if ($type==EidService::TYPE_FE) {
            $query->matching($query->logicalNot($query->equals('feuid', '""')));
        }
        if ($type==EidService::TYPE_BE) {
            $query->matching($query->logicalNot($query->equals('beuid', '""')));
        }
        $query->matching($query->equals('eidvalue', $eidvalue));

        return $query->execute()
                    ->getFirst();
    }

    /**
     * Find eid entry without matching fe or be user
     *
     * @param string $type Match FE or BE users
     * @return Eid[] $eids The orphaned eids
     */
    public function findOrphaned(string $type=EidService::TYPE_FE)
    {
        if ($type !== EidService::TYPE_FE && $type !== EidService::TYPE_BE) {
            throw new \Exception('invalid type');
        }
        $userTable = null;
        $userColumn = null;
        if ($type==EidService::TYPE_FE) {
            $userTable = 'fe_users';
            $userColumn = 'feuid';
        }
        if ($type==EidService::TYPE_BE) {
            $userTable = 'be_users';
            $userColumn = 'beuid';
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($userTable);
        $queryBuilderUser = $connection->createQueryBuilder();
        $rows = $queryBuilderUser
            ->select('uid')
            ->from($userTable)
            ->execute()
            ->fetchAll();
        $uids = [];
        foreach ($rows as $row) {
            $uids[] = $row['uid'];
        }
        $query = $this->createQuery();
        $queryResult = $query
            ->matching(
                $query->logicalNot(
                    $query->in($userColumn, $uids)
                )
            )
            ->execute();

        return $queryResult->toArray();
    }
}
