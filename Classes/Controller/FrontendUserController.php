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
use Tollwerk\TwUser\Hook\RegistrationFormHook;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use Tollwerk\TwBase\Utility\LocalizationUtility;

class FrontendUserController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    const REGISTRATION_SUBMITTED = 'submitted';
    const REGISTRATION_CONFIRMATION_SUCCESS = 'success';
    const REGISTRATION_CONFIRMATION_ERROR = 'error';
    const PROFILE_UPDATE_ERROR = 'error';
    const PROFILE_UPDATE_SUCCESS = 'success';

    /** @var FrontendUserRepository */
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
     * Process the registration double-opt-in confirmation code.
     * Enable the FrontendUser if everything is valid. Then,
     * forward or redirect to somehere else
     * with the confirmation status as parameter
     *
     * @param string $code
     */
    public function confirmRegistrationAction(string $code)
    {
        $frontendUser = $this->frontendUserRepository->findOneByRegistrationCode($code);


        if ($frontendUser) {
            $frontendUser->setDisabled(false);
            $this->frontendUserRepository->update($frontendUser);
            $this->objectManager->get(PersistenceManager::class)->persistAll();
            $status = self::REGISTRATION_CONFIRMATION_SUCCESS;
        } else {
            $status = self::REGISTRATION_CONFIRMATION_ERROR;
        }

        // Hook for manipulating the registration confirmation
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tw_user']['confirmRegistration'] ?? [] as $className) {
            $_procObj = GeneralUtility::makeInstance($className);
            if (!($_procObj instanceof ConfirmRegistrationHook)) {
                throw new Exception(
                    sprintf('The registered class %s for hook [ext/tw_user][confirmRegistration] does not implement the ConfirmRegistrationHook interface', $className),
                    1556279202
                );
            }
            $_procObj->confirmRegistrationHook($status);
        }

        $this->forward('registration', null, null, [
            'status' => $status
        ]);
    }

    /**
     * Register a new feuser.
     * The registration form get's rendered inside this view.
     *
     * @param string $status
     */
    public function registrationAction(string $status = null)
    {
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
                    FlashMessage::ERROR);
                break;
        }

        $this->view->assign('registrationStatus', $status);
    }

    /**
     * Show the user profile.
     * The user profile form get's rendered inside this view.
     *
     * @param string $status
     */
    public function profileAction(string $status = null)
    {
        switch ($status) {
            case self::PROFILE_UPDATE_SUCCESS:
                $this->addFlashMessage(
                    LocalizationUtility::translate('feuser.profile.status.update.success.message', 'TwUser'),
                    '',
                    FlashMessage::OK);
                break;
            case self::PROFILE_UPDATE_ERROR:
                $this->addFlashMessage(
                    LocalizationUtility::translate('feuser.profile.status.update.error.message', 'TwUser'),
                    '',
                    FlashMessage::ERROR);
                break;
        }

        $this->view->assign('status', $status);
    }
}
