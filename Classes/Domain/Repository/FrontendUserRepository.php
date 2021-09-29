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

use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Class FrontendUserRepository
 */
class FrontendUserRepository
{
    /** @var ConnectionPool */
    private $connectionPool;

    /**
     * @param ConnectionPool $connectionPool
     */
    public function __construct(
        ConnectionPool $connectionPool
    ) {
        $this->connectionPool = $connectionPool;
    }

    /**
     * Get the pid of the fe user with given uid
     *
     * @param int $uid The uid of the user
     *
     * @return int $pid The pid
     */
    public function getPidByUid(int $uid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $queryBuilder->select('pid');
        $queryBuilder->from('fe_users');
        $queryBuilder->where($queryBuilder->expr()->eq('uid', $uid));
        $res = $queryBuilder->execute();
        $pid = $res->fetch()['pid'];

        return (int)$pid;
    }

    /**
     * Check if user has an email adress set
     *
     * @param int $uid The uid of the user for which to check
     *
     * @return bool True if an email is set
     */
    public function hasEmailAdressSet(int $uid): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $queryBuilder->select('email');
        $queryBuilder->from('fe_users');
        $queryBuilder->where($queryBuilder->expr()->eq('uid', $uid));
        $res = $queryBuilder->execute();
        $email = $res->fetch()['email'];
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($email===false) {
            return false;
        }
        return true;
    }

    /**
     * Get the value of tx_eidlogin_disablepwlogin for given frontend user
     *
     * @param int $uid The uid of the user for which to get tx_eidlogin_disablepwlogin
     *
     * @return int $value The value of tx_eidlogin_disablepwlogin, null if non has been found
     */
    public function getDisablePwLogin(int $uid): ?int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $queryBuilder->select('tx_eidlogin_disablepwlogin');
        $queryBuilder->from('fe_users');
        $queryBuilder->where($queryBuilder->expr()->eq('uid', $uid));
        $res = $queryBuilder->execute();

        return $res->fetch()['tx_eidlogin_disablepwlogin'];
    }

    /**
     * Set the value of tx_eidlogin_disablepwlogin for given frontend user
     *
     * @param int $uid The uid of the user for which to set tx_eidlogin_disablepwlogin
     * @param int $value The value for to set tx_eidlogin_disablepwlogin
     */
    public function setDisablePwLogin(int $uid, int $value): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $queryBuilder->update('fe_users');
        $queryBuilder->where($queryBuilder->expr()->eq('uid', $uid));
        $queryBuilder->set('tx_eidlogin_disablepwlogin', (string)$value);
        $queryBuilder->execute();
    }

    /**
     * Reset the value of tx_eidlogin_disablepwlogin for all frontend users
     */
    public function resetAllDisablePwLogins(): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $queryBuilder->update('fe_users');
        $queryBuilder->where($queryBuilder->expr()->eq('tx_eidlogin_disablepwlogin', $queryBuilder->createNamedParameter('1')));
        $queryBuilder->set('tx_eidlogin_disablepwlogin', '0');
        $queryBuilder->execute();
    }
}
