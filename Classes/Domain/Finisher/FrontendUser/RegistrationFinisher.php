<?php
/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2019 Klaus Fiedler <klaus@tollwerk.de>, tollwerk® GmbH
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace Tollwerk\TwUser\Domain\Finisher\FrontendUser;

use Tollwerk\TwUser\Hook\RegistrationFinisherHook;
use Tollwerk\TwUser\Utility\FrontendUserUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Form\Domain\Finishers\RedirectFinisher;

/**
 * Class RegistrationFinisher
 * @package Tollwerk\TwUser\Domain\Finisher\FrontendUser
 */
class RegistrationFinisher extends RedirectFinisher
{
    public function executeInternal()
    {
        // Get some objects and properties
        $formRuntime = $this->finisherContext->getFormRuntime();
        $frontendUserProperties = ['email' => $formRuntime->getElementValue('email')];

        // Hook for manipulating the $frontendUserProperties array which is used to set FrontendUser properties
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tw_user']['registrationFinisher'] ?? [] as $className) {
            $_procObj = GeneralUtility::makeInstance($className);
            if (!($_procObj instanceof RegistrationFinisherHook)) {
                throw new Exception(
                    sprintf('The registered class %s for hook [ext/tw_user][registrationFinisher] does not implement the RegistrationFinisherHook interface', $className),
                    1561469421
                );
            }
            $_procObj->registrationFinisherHook($formRuntime, $frontendUserProperties);
        }

        $frontendUserUtility = GeneralUtility::makeInstance(FrontendUserUtility::class);
        $frontendUserUtility->createFrontendUser($frontendUserProperties);
    }
}
