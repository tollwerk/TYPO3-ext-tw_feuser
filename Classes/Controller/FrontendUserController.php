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
use Tollwerk\TwUser\Hook\FrontendUserHookInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Frontend User Controller
 *
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Controller
 */
class FrontendUserController extends ActionController
{
    /**
     * Status submitted
     *
     * @var string
     */
    const REGISTRATION_SUBMITTED = 'submitted';
    /**
     * Status success
     *
     * @var string
     */
    const REGISTRATION_CONFIRMATION_SUCCESS = 'success';
    /**
     * Status error
     *
     * @var striung
     */
    const REGISTRATION_CONFIRMATION_ERROR = 'error';

    /**
     * Frontend user repository
     *
     * @var FrontendUserRepository
     */
    protected $frontendUserRepository = null;

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
     * Render the registration form
     *
     * @param string $status The registration status
     * @param array $form    The submitted form data
     *
     * @throws Exception
     */
    public function registrationAction(string $status = null, array $form = null)
    {
        $passthrough = $form['orc']['passthrough'] ?? [];

        // Hook for frontend user registration action
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tw_user']['frontendUserRegistration'] ?? [] as $className) {
            $_procObj = GeneralUtility::makeInstance($className);
            if (!($_procObj instanceof FrontendUserHookInterface)) {
                throw new Exception('The registered class '.$className.' for hook [ext/tw_user][frontendUserRegistration] does not implement the FrontendUserHookInterface',
                    1556279202);
            }
            $_procObj->frontendUserRegistration($status, $passthrough, $form);
        }

        // Add as status message
        switch ($status) {
            case self::REGISTRATION_SUBMITTED:
                $this->addFlashMessage(
                    LocalizationUtility::translate('feuser.registration.status.submitted.message', 'TwUser'),
                    LocalizationUtility::translate('feuser.registration.status.submitted.title', 'TwUser'),
                    FlashMessage::OK);
                break;
            case self::REGISTRATION_CONFIRMATION_SUCCESS:
                $this->addFlashMessage(
                    LocalizationUtility::translate('feuser.registration.status.success.message', 'TwUser'),
                    LocalizationUtility::translate('feuser.registration.status.success.title', 'TwUser'),
                    FlashMessage::OK);
                break;
            case self::REGISTRATION_CONFIRMATION_ERROR:
                $this->addFlashMessage(
                    LocalizationUtility::translate('feuser.registration.status.error.message', 'TwUser'),
                    LocalizationUtility::translate('feuser.registration.status.error.title', 'TwUser'),
                    FlashMessage::OK);
                break;
        }

        $this->view->assign('registrationStatus', $status);
    }

    /**
     * Render the confirmation page
     *
     * @param string $code
     *
     * @throws Exception
     * @throws StopActionException
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function confirmRegistrationAction(string $code)
    {
        $frontendUser = $this->frontendUserRepository->findOneByRegistrationCode($code);

        // Hook for frontend user registration action
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tw_user']['frontendUserConfirmRegistration'] ?? [] as $className) {
            $_procObj = GeneralUtility::makeInstance($className);
            if (!($_procObj instanceof FrontendUserHookInterface)) {
                throw new Exception('The registered class '.$className.' for hook [ext/tw_user][frontendUserConfirmRegistration] does not implement the FrontendUserHookInterface',
                    1556280888);
            }
            $_procObj->frontendUserConfirmRegistration($code, $frontendUser);
        }

        if ($frontendUser) {
            $frontendUser->setDisabled(false);
            $this->frontendUserRepository->update($frontendUser);
            $this->objectManager->get(PersistenceManager::class)->persistAll();
            $this->forward('registration', null, null, [
                'status' => self::REGISTRATION_CONFIRMATION_SUCCESS
            ]);
        }

        $this->forward('registration', null, null, [
            'status' => self::REGISTRATION_CONFIRMATION_ERROR
        ]);
    }
}
