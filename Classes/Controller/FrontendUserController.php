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
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
                    FlashMessage::OK);                break;
            case self::REGISTRATION_CONFIRMATION_ERROR:
                $this->addFlashMessage(
                    LocalizationUtility::translate('feuser.registration.status.error.message', 'TwUser'),
                    LocalizationUtility::translate('feuser.registration.status.error.title', 'TwUser'),
                    FlashMessage::OK);                break;
        }

        $this->view->assign('registrationStatus', $status);
    }
}
