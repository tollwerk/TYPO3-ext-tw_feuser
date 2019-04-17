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

namespace Tollwerk\TwUser\Utility;

use Tollwerk\TwUser\Domain\Model\FrontendUser;
use Tollwerk\TwUser\Domain\Repository\FrontendUserGroupRepository;
use Tollwerk\TwUser\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class FrontendUserUtility implements SingletonInterface
{
    /** @var ObjectManager */
    protected $objectManager = null;

    /** @var FrontendUserGroupRepository */
    protected $frontendUserGroupRepository = null;

    /** @var FrontendUserRepository */
    protected $frontendUserRepository = null;

    /** @var PersistenceManager */
    protected $persistenceManager = null;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @param string $email
     * @param int $uid
     *
     * @return string
     */
    protected function createRegistrationCode(string $email, int $uid)
    {
        return md5($email . $uid . time());
    }

    /**
     * @param FrontendUserRepository $frontenUserRepository
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);
        $this->frontendUserGroupRepository = $this->objectManager->get(FrontendUserGroupRepository::class);
        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $this->settings = $this->objectManager->get(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TwUser');
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function createFrontendUser(string $email): bool
    {
        // Check if user already exists. If not, create one.
        $frontendUser = $this->frontendUserRepository->findOneByUsername($email, true);
        if (!$frontendUser) {
            // Create frontend user object
            /** @var FrontendUser $frontendUser */
            $frontendUser = $this->objectManager->get(FrontendUser::class);
            $frontendUser->setUsername($email);
            $frontendUser->setEmail($email);
            $frontendUser->setPid($this->settings['feuser']['registration']['pid']);
            $frontendUser->setDisabled(true);

            // Add FrontendUserGroup
            $userGroup = $this->frontendUserGroupRepository->findByUid($this->settings['feuser']['registration']['groupUid']);
            if ($userGroup) {
                $frontendUser->addUsergroup($userGroup);
            }

            // Create and set password
            $password = GeneralUtility::makeInstance(PasswordUtility::class)->createPassword();
            $frontendUser->setPassword(GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('FE')->getHashedPassword($password));
            $this->frontendUserRepository->add($frontendUser);
            $this->persistenceManager->persistAll();
        }

        // Set registration confirmation code and update user
        $frontendUser->setRegistrationCode($this->createRegistrationCode($frontendUser->getEmail(), $frontendUser->getUid()));
        $this->frontendUserRepository->update($frontendUser);
        $this->persistenceManager->persistAll();

        // Send confirmation email



        die();
        return false;
    }
}
