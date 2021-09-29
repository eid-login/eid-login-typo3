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
namespace Ecsec\Eidlogin\Service;

use Ecsec\Eidlogin\Domain\Repository\EidRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class EidFeAuthService
 */
class EidFeAuthService extends AbstractAuthenticationService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var EidRepository */
    private $eidRepository;

    public function __construct(
    ) {
        $this->eidRepository = GeneralUtility::makeInstance(EidRepository::class);
    }

    /**
     * If this function return a valid user it will be authenticated in authUser below.
     * ATTENTION: Side effect due to TYPO3 AuthenticationService architecture:
     * Expects the eid of the user to login in $_POST['user']!
     *
     * @return ?array<mixed> $user
     */
    public function getUser(): ?array
    {
        $this->logger->debug('EidFeAuthService - getUser');
        // set in the EidService as $_POST['user'];
        $authData = $this->login['uname'];
        // we decode and try to fetch the original record
        $authData = json_decode($authData);
        if (is_null($authData)) {
            return null;
        }
        $res = $this->eidRepository->findByPidAndEidvalue($authData->pid, $authData->eid);
        if (count($res) !== 1) {
            return null;
        }
        // fetch the user matching the frontend user uid from the eid record
        // based on TYPO3\CMS\Core\Authentication\AbstractAuthenticationService->fetchUserRecord
        $query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->db_user['table']);
        $query->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $constraints = array_filter([
            QueryHelper::stripLogicalOperatorPrefix($this->db_user['enable_clause']),
        ]);
        array_unshift(
            $constraints,
            $query->expr()->eq(
                'uid',
                $query->createNamedParameter($res[0]->getFeuid(), \PDO::PARAM_STR)
            )
        );
        $user = $query->select('*')
            ->from($this->db_user['table'])
            ->where(...$constraints)
            ->execute()
            ->fetch();

        if (!$user) {
            return null;
        }

        return $user;
    }

    /**
     * This will only be called if getUser above returned a valid user record
     *
     * @param array<mixed> $user
     */
    public function authUser(array $user): int
    {
        $this->logger->debug('EidFeAuthService - authUser');
        // 200 = authenticated
        if (array_key_exists('uid', $user)) {
            return 200;
        }
        // 0 = not authenticated
        return 0;
    }
}
