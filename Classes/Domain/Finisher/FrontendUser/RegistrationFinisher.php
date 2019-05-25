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

use Tollwerk\TwUser\Utility\FrontendUserUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Finishers\RedirectFinisher;

/**
 * Registration Finisher
 *
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Domain\Finisher\FrontendUser
 */
class RegistrationFinisher extends RedirectFinisher
{
    /**
     * Executes this finisher
     *
     * @see AbstractFinisher::execute()
     */
    public function executeInternal()
    {
        // Get some objects and properties
        $formRuntime         = $this->finisherContext->getFormRuntime();
        $frontendUserUtility = GeneralUtility::makeInstance(FrontendUserUtility::class);
        $passthrough         = null;
        $configuration       = $formRuntime->getElementValue('orc');

        if (strlen($configuration)) {
            $configuration = json_decode($configuration);
            if (is_object($configuration) && isset($configuration->passthrough)) {
                $passthrough = $configuration->passthrough;
            }
        }

        $additionalProperties = [];
        $firstName = $formRuntime->getElementValue('firstName');
        if($firstName){
            $additionalProperties['first_name'] = $firstName;
        }
        $lastName = $formRuntime->getElementValue('lastName');
        if($firstName){
            $additionalProperties['last_name'] = $lastName;
        }

        $password = $formRuntime->getElementValue('password');
        if($password){
            $additionalProperties['password'] = $password;
        }

        // Create FrontendUser
        $frontendUserUtility->createFrontendUser($formRuntime->getElementValue('email'), $additionalProperties, $passthrough);
    }
}
