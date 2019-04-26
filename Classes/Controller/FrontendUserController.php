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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Tollwerk\TwUser\Hook\FrontendUserHookInterface;

class FrontendUserController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    const REGISTRATION_SUBMITTED = 'submitted';
    const REGISTRATION_CONFIRMATION_SUCCESS = 'success';
    const REGISTRATION_CONFIRMATION_ERROR = 'error';

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
     * @param string $code
     */
    public function confirmRegistrationAction(string $code)
    {
        $frontendUser = $this->frontendUserRepository->findOneByRegistrationCode($code);

        // Hook for frontend user registration action
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tw_user']['frontendUserConfirmRegistration'] ?? [] as $className) {
            $_procObj = GeneralUtility::makeInstance($className);
            if (!($_procObj instanceof FrontendUserHookInterface)) {
                throw new Exception('The registered class '.$className.' for hook [ext/tw_user][frontendUserConfirmRegistration] does not implement the FrontendUserHookInterface', 1556280888);
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

    /**
     * @param string $status     The registration status
     * @param array $passthrough See \Tollwerk\TwUser\Domain\Factory\AbstractFormFactory
     * @param array $form        The submitted form data
     */
    public function registrationAction(string $status = null, array $passthrough = null, array $form = null)
    {
        // Hook for frontend user registration action
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tw_user']['frontendUserRegistration'] ?? [] as $className) {
            $_procObj = GeneralUtility::makeInstance($className);
            if (!($_procObj instanceof FrontendUserHookInterface)) {
                throw new Exception('The registered class '.$className.' for hook [ext/tw_user][frontendUserRegistration] does not implement the FrontendUserHookInterface', 1556279202);
            }
            $_procObj->frontendUserRegistration($status, $passthrough, $form);
        }

        $passthrough = $passthrough ?? (!empty($form['passthrough']) ? $form['passthrough'] : []);
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

        $this->view->assignMultiple([
            'registrationStatus' => $status,
            'passthrough' => $passthrough,
        ]);
    }
}
