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

use Ecsec\Eidlogin\Domain\Model\SiteInfo;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Class SiteInfoService
 */
class SiteInfoService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const FE_PLUGIN_LOGIN = 'eidlogin_login';
    const FE_PLUGIN_SETTINGS = 'eidlogin_settings';

    /** @var SettingsService */
    private $settingsService;
    /** @var ConnectionPool */
    private $connectionPool;
    /** @var SiteFinder */
    private $siteFinder;
    /** @var Array<SiteInfo> */
    private $siteInfos;
    /** @var bool */
    private $initialized = false;

    /**
     * @param SettingsService $settingsService
     * @param ConnectionPool $connectionPool
     * @param SiteFinder $siteFinder
     */
    public function __construct(
        SettingsService $settingsService,
        ConnectionPool $connectionPool,
        SiteFinder $siteFinder
    ) {
        $this->settingsService = $settingsService;
        $this->connectionPool = $connectionPool;
        $this->siteFinder = $siteFinder;
    }

    /**
     * Get an object holding info about how a specific site is setup regarding eID-Login.
     *
     * @param int $pageId The pageid to get the site info for
     * @return SiteInfo The siteInfo object
     */
    public function getSiteInfoByPageId(int $pageId): SiteInfo
    {
        if (!$this->initialized) {
            $this->siteInfos = $this->createSiteInfosArray();
        }
        $site = $this->siteFinder->getSiteByPageId($pageId);

        return $this->siteInfos[$site->getRootPageId()];
    }

    /**
     * Get an array of all sites holding info about how they are setup regarding eID-Login.
     * Sites rootPageId is used as outer array keys.
     *
     * @return array<SiteInfo> An array of SiteInfo objects
     */
    public function getSiteInfos(): array
    {
        if (!$this->initialized) {
            $this->siteInfos = $this->createSiteInfosArray();
        }
        return $this->siteInfos;
    }

    /**
     * Return true if an configured site has been found.
     *
     * @return bool
     */
    public function configuredSitePresent(): bool
    {
        if (!$this->initialized) {
            $this->siteInfos = $this->createSiteInfosArray();
        }
        foreach ($this->siteInfos as $siteInfo) {
            if ($siteInfo->getSetupLogin() &&
                $siteInfo->getSetupSettings()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create an array of all sites holding info about how they are setup regarding eID-Login.
     *
     * @return array<SiteInfo> An array of SiteInfo objects
     */
    private function createSiteInfosArray()
    {
        $sites = $this->siteFinder->getAllSites();
        // no sites, no need to look further
        if (count($sites)===0) {
            return [];
        }
        $siteInfos = [];
        $samlPageIds = $this->getSamlPageIds();
        $loginPageIds = $this->getLoginPageIds();
        $settingsPageIds = $this->getSettingsPageIds();
        foreach ($sites as $site) {
            $siteInfo = new SiteInfo($site);
            $infoKey = $site->getRootPageId();
            foreach ($samlPageIds as $samlPageId) {
                try {
                    $rootPageId = $this->siteFinder->getSiteByPageId($samlPageId['pid'])->getRootPageId();
                    if ($infoKey===$rootPageId) {
                        $siteInfo->setSamlPageId($samlPageId['pid']);
                        $siteInfo->setConfigured($this->settingsService->settingsPresent($rootPageId));
                        break;
                    }
                } catch (\Exception $e) {
                    $this->logger->info('no site found at root page for samlPageId ' . $samlPageId['pid']);
                }
            }
            $userPageIdsLogin = null;
            foreach ($loginPageIds as $loginPageId) {
                try {
                    $rootPageId = $this->siteFinder->getSiteByPageId($loginPageId['pid'])->getRootPageId();
                    if ($infoKey===$rootPageId) {
                        $siteInfo->setSetupLogin(true);
                        $tmpUserPageIds = $this->getUserPageId($loginPageId['pid'], self::FE_PLUGIN_LOGIN);
                        if (is_null($userPageIdsLogin)) {
                            $userPageIdsLogin = $tmpUserPageIds;
                        } elseif ($userPageIdsLogin !== $tmpUserPageIds) {
                            $userPageIdsLogin = null;
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->info('no site found at root page for loginPageId ' . $loginPageId['pid']);
                }
            }
            $userPageIdsSettings = null;
            foreach ($settingsPageIds as $settingsPageId) {
                try {
                    $rootPageId = $this->siteFinder->getSiteByPageId($settingsPageId['pid'])->getRootPageId();
                    if ($infoKey===$rootPageId) {
                        $siteInfo->setSetupSettings(true);
                        $tmpUserPageIds = $this->getUserPageId($settingsPageId['pid'], self::FE_PLUGIN_SETTINGS);
                        if (is_null($userPageIdsSettings)) {
                            $userPageIdsSettings = $tmpUserPageIds;
                        } elseif ($userPageIdsSettings !== $tmpUserPageIds) {
                            $userPageIdsSettings = null;
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->info('no site found at root page for settingsPageId ' . $settingsPageId['pid']);
                }
            }
            if (!is_null($userPageIdsLogin) &&
                !is_null($userPageIdsSettings) &&
                $userPageIdsLogin === $userPageIdsSettings
                ) {
                $userPageIds = [];
                if (strpos($userPageIdsLogin, ',')) {
                    $userPageIds = explode(',', $userPageIdsLogin);
                } else {
                    $userPageIds[] = $userPageIdsLogin;
                }
                $siteInfo->setUserPageIds($userPageIds);
            }
            $siteInfos[$infoKey] = $siteInfo;
        }
        $this->initialized = true;

        return $siteInfos;
    }

    /**
     * Get the ID of pages containing the eidlogin TypoScript include template.
     *
     * @return array<mixed> The page ids
     *
     * @throws \Exception If the page ids could not be determined
     */
    private function getSamlPageIds(): array
    {
        $query = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $res = $query->select('pid')
            ->from('sys_template')
            ->where(
                $query->expr()->eq(
                    'include_static_file',
                    $query->createNamedParameter('EXT:eidlogin/Configuration/TypoScript', \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();

        return $res;
    }

    /**
     * Get the ID of pages containing the eidlogin_login fe plugin.
     *
     * @return array<mixed> The page ids
     *
     * @throws \Exception If the page ids could not be determined
     */
    private function getLoginPageIds(): array
    {
        $query = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $res = $query->select('pid')
            ->from('tt_content')
            ->where(
                $query->expr()->eq(
                    'list_type',
                    $query->createNamedParameter('eidlogin_login', \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();

        return $res;
    }

    /**
     * Get the ID of pages containing the eidlogin_settings fe plugin.
     * The query will check, if the page has access restrictions. (pages.fe_groups)
     *
     * @return array<mixed> The page ids
     *
     * @throws \Exception If the page ids could not be determined
     */
    private function getSettingsPageIds(): array
    {
        $query = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $res = $query->select('tt_content.pid')
            ->from('tt_content')
            ->join(
                'tt_content',
                'pages',
                'p',
                $query->expr()->eq('tt_content.pid', 'p.uid')
            )
            ->andWhere(
                $query->expr()->eq('tt_content.list_type', $query->createNamedParameter('eidlogin_settings', \PDO::PARAM_STR)),
                $query->expr()->neq('p.fe_group', '""')
            )
            ->execute()
            ->fetchAll();

        return $res;
    }
    /**
     * Get the ID of user page bound to the frontend plugin in the given site.
     *
     * @param int $pageId The page id the frontend plugin is part of
     * @param $fePluginType The type of frontend plugin to look for, must be self::FE_PLUGIN_LOGIN or self::FE_PLUGIN_SETTINGS
     *
     * @return string The user page ids If the value could not be specified (none or many)
     * @throws \Exception If invalid type of frontend plugin is given
     */
    private function getUserPageId(int $pageId, string $fePluginType): ?string
    {
        if ($fePluginType != self::FE_PLUGIN_LOGIN && $fePluginType != self::FE_PLUGIN_SETTINGS) {
            throw new \Exception('invalid frontend plugin type given');
        }
        $query = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $res = $query->select('tt_content.pages')
            ->from('tt_content')
            ->andWhere(
                $query->expr()->eq('tt_content.pid', $query->createNamedParameter($pageId, \PDO::PARAM_INT)),
                $query->expr()->eq('tt_content.list_type', $query->createNamedParameter($fePluginType, \PDO::PARAM_STR))
            )
            ->execute()
            ->fetchAll();
        $userPageId = null;
        foreach ($res as $row) {
            $tmpUserPageId = $row['pages'];
            if ($tmpUserPageId === '') {
                return null;
            }
            if (!is_null($userPageId) && $tmpUserPageId !== $userPageId) {
                return null;
            }
            $userPageId = $tmpUserPageId;
        }

        return $userPageId;
    }
}
