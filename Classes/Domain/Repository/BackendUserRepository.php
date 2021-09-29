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
 * Class BackendUserRepository
 */
class BackendUserRepository
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
     * Get the admin mail adresses
     *
     * @return array<string> $emailadresses
     */
    public function getAdminEmailAdresses(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder->select('email');
        $queryBuilder->from('be_users');
        $queryBuilder->where($queryBuilder->expr()->neq('email', $queryBuilder->createNamedParameter('')));
        $queryBuilder->andWhere($queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter('1')));
        $res = $queryBuilder->execute();
        $emailadresses = [];
        foreach ($res->fetchAll() as $row) {
            $emailadresses[] = $row['email'];
        }

        return $emailadresses;
    }
}
