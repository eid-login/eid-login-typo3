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
namespace Ecsec\Eidlogin\Util;

use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Class Typo3VersionUtil
 */
class Typo3VersionUtil
{
    /**
     * Check for TYPO3 Version 10
     *
     * @return bool $isVersion10 True if TYPO3 is version 10
     */
    public static function isVersion10(): bool
    {
        if (VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getCurrentTypo3Version()) < 11000000) {
            return true;
        }
        return false;
    }
}
