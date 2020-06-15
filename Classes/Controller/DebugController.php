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

use Tollwerk\TwUser\Domain\Model\FrontendUser;
use Tollwerk\TwUser\Domain\Repository\FrontendUserRepository;
use Tollwerk\TwUser\Utility\AdminUtility;
use Tollwerk\TwUser\Utility\FrontendUserUtility;

/**
 * Class DebugController
 * @package Tollwerk\TwUser\Controller
 */
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
     * @var AdminUtility
     */
    protected $adminUtility = null;

    /**
     * @var FrontendUser
     */
    protected $frontendUser = null;

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
     * Inject the AdminUtility
     *
     * @param AdminUtility $adminUtility
     */
    public function injectAdminUtility(AdminUtility $adminUtility)
    {
        $this->adminUtility = $adminUtility;
    }

    /**
     * Initializes the controller before invoking an action method.
     *
     * Override this method to solve tasks which all actions have in
     * common.
     */
    public function initializeAction()
    {
        $this->frontendUser = $this->frontendUserRepository->findByUidNoRestrictions($this->settings['debug']['feuserUid']);
    }

    /**
     * Starting point for all debug actions
     *
     * @param string $statusMessage
     * @param string $html
     */
    public function indexAction(string $statusMessage = null, string $html = null)
    {
        $this->view->assignMultiple([
            'frontendUser' => $this->frontendUser,
            'statusMessage' => $statusMessage,
            'html' => $html,
        ]);
    }

    /**
     * Send confirmation email to debug feuser
     */
    public function sendConfirmationEmailAction()
    {
        $emailSuccess = $this->frontendUserUtility->sendConfirmationEmail(
            $this->frontendUser,
            '1234567890xyz'
        );

        $this->forward(
            'index',
            null,
            null,
            [
                'statusMessage' => 'Send confirmation email: ' . $emailSuccess ? 'Success': 'Error'
            ]
        );
    }

    /**
     * Show content of confirmation email
     */
    public function showConfirmationEmailAction(string $type = 'html')
    {
        $emailBody = $this->frontendUserUtility->createConfirmationEmailBody(
            $this->frontendUser,
            '0123456789xyz',
            '#'
        );

        $this->forward(
            'index',
            null,
            null,
            [
                'statusMessage' => 'Showing confirmation email: ' . $type,
                'html' => array_key_exists($type, $emailBody) ? $emailBody[$type] : null
            ]
        );
    }

    /**
     * Send admin email notification upon user registration
     */
    public function sendRegistrationAdminEmailAction()
    {
        $emailSuccess = $this->adminUtility->sendRegistrationAdminEmail($this->frontendUser);
        $this->forward(
            'index',
            null,
            null,
            [
                'statusMessage' => 'Send registration admin email: ' . ($emailSuccess ? 'Success': 'Error')
            ]
        );
    }

    /**
     * Show admin email notification upon user registration
     */
    public function showRegistrationAdminEmailAction()
    {
        $this->forward(
            'index',
            null,
            null,
            [
                'statusMessage' => 'Showing registration admin email',
                'html' => $this->adminUtility->createRegistrationAdminEmailBody($this->frontendUser)
            ]
        );
    }


}
