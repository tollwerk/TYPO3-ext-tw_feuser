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
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Finishers\RedirectFinisher;

/**
 * Profile Update Finisher
 *
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Domain\Finisher\FrontendUser
 */
class ProfileUpdateFinisher extends RedirectFinisher
{
    /**
     * Executes this finisher
     *
     * @see AbstractFinisher::execute()
     */
    public function executeInternal()
    {
        $formRuntime = $this->finisherContext->getFormRuntime();
        $formValues  = [];
        foreach ($formRuntime->getFormDefinition()->getRenderablesRecursively() as $renderable) {
            $renderableId                    = $renderable->getIdentifier();
            $renderableFormName              = GeneralUtility::underscoredToLowerCamelCase($renderableId);
            $formValues[$renderableFormName] = $formRuntime->getElementValue($renderableId);
        }

        $frontendUserUtility = GeneralUtility::makeInstance(FrontendUserUtility::class);
        $frontendUserUtility->updateFrontendUser(array_diff_key($formValues, ['profile' => true, 'orc' => true]));
    }
}
