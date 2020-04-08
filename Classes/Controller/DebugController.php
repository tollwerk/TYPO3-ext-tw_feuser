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

namespace Tollwerk\TwUser\Controller;

use Tollwerk\TwUser\Domain\Repository\FrontendUserRepository;
use Tollwerk\TwUser\Hook\ConfirmRegistrationHook;
use Tollwerk\TwUser\Utility\FrontendUserUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Tollwerk\TwBase\Utility\LocalizationUtility;

class DebugController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var FrontendUserRepository
     */
    protected $frontendUserRepository = null;

    /**
     * @var FrontendUserUtility
     */
    protected $frontendUserUtility = null;

    /**
     * Inject the frontendUser repository
     *
     * @param FrontendUserRepository $frontendUserRepository
     */
    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository): void
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    /**
     * Inject the frontendUserUtility
     *
     * @param FrontendUserUtility $frontendUserUtility
     */
    public function injectFrontendUserUtililty(FrontendUserUtility $frontendUserUtility): void
    {
        $this->frontendUserUtility = $frontendUserUtility;
    }

    /**
     * Starting point for all debug actions
     *
     * @param string $statusMessage
     */
    public function indexAction(string $statusMessage = null)
    {
        $this->view->assignMultiple([
            'frontendUser' => $this->frontendUserRepository->findByUidNoRestrictions(82),
            'statusMessage' => $statusMessage,
        ]);
    }

    /**
     * Send confirmation email to debug feuser
     */
    public function confirmationEmailAction()
    {
        $this->frontendUserUtility->sendConfirmationEmail(
            $this->frontendUserRepository->findByUidNoRestrictions($this->settings['debug']['feuserUid']),
            '1234567890xyz'
        );

        $this->forward(
            'index',
            null,
            null,
            [
                'statusMessage' => 'Registration confirmation email sent!'
            ]
        );
    }


}
