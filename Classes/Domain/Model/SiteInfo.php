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
namespace Ecsec\Eidlogin\Domain\Model;

use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Entitiy holding info about the eID-Login setup of an site
 */
class SiteInfo
{

    /**
     * The site of itself
     * @var Site
     */
    private $site;
    /**
     * The id of the page holding the saml template
     * @var int
     */
    private $samlPageId;
    /**
     * The ids of the page holding the user records for the site as array
     * @var array<string>
     */
    private $userPageIds = [];
    /**
     * If the eidlogin_login fe plugin is setup
     * @var bool
     */
    private $setupLogin;
    /**
     * If the eidlogin_settings fe plugin is setup
     * @var bool
     */
    private $setupSettings;
    /**
     * If the site has eID-Login settings already
     * @var bool
     */
    private $configured;

    /**
     * Create a SiteInfo for a given Site
     *
     * @param Site $site The site to create the info for.
     */
    public function __construct(Site $site)
    {
        $this->site=$site;
        $this->setupLogin=false;
        $this->setupSettings=false;
        $this->configured=false;
    }

    /**
     * Gets the site
     *
     * @return Site
     */
    public function getSite(): Site
    {
        return $this->site;
    }

    /**
     * Sets the samlPageId
     *
     * @param int $samlPageId
     */
    public function setSamlPageId(int $samlPageId): void
    {
        $this->samlPageId = $samlPageId;
    }

    /**
     * Gets the samlPageId
     *
     * @return int
     */
    public function getSamlPageId(): ?int
    {
        return $this->samlPageId;
    }

    /**
     * Sets the userPageId
     *
     * @param array<string> $userPageIds
     */
    public function setUserPageIds(array $userPageIds): void
    {
        $this->userPageIds = $userPageIds;
    }

    /**
     * Gets the userPageIds
     *
     * @return array<string>
     */
    public function getUserPageIds(): ?array
    {
        return $this->userPageIds;
    }

    /**
     * Sets the value of setupLogin
     *
     * @param bool $setupLogin
     */
    public function setSetupLogin(bool $setupLogin): void
    {
        $this->setupLogin = $setupLogin;
    }

    /**
     * Gets the value of setupLogin
     *
     * @return bool
     */
    public function getSetupLogin(): bool
    {
        return $this->setupLogin;
    }

    /**
     * Sets the value of setupSettings
     *
     * @param bool $setupSettings
     */
    public function setSetupSettings(bool $setupSettings): void
    {
        $this->setupSettings = $setupSettings;
    }

    /**
     * Gets the value of setupSettings
     *
     * @return bool
     */
    public function getSetupSettings(): bool
    {
        return $this->setupSettings;
    }

    /**
     * Sets the value of configured
     *
     * @param bool $configured
     */
    public function setConfigured(bool $configured): void
    {
        $this->configured = $configured;
    }

    /**
     * Gets the value of configured
     *
     * @return bool
     */
    public function getConfigured(): bool
    {
        return $this->configured;
    }
}
