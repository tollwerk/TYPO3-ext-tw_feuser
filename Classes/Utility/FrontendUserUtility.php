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

use Tollwerk\TwBase\Utility\EmailUtility;
use Tollwerk\TwBase\Utility\StandaloneRenderer;
use Tollwerk\TwUser\Domain\Model\FrontendUser;
use Tollwerk\TwUser\Domain\Repository\FrontendUserGroupRepository;
use Tollwerk\TwUser\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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
     * @param FrontendUser $frontendUser
     *
     * @return string
     *
     */
    protected function createRegistrationCode(FrontendUser $frontendUser)
    {
        return md5($frontendUser->getEmail() . $frontendUser->getUid() . time());
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
     * Create a new, disabled frontend user
     * and send double-opt-in mail
     *
     * @param array $properties
     *
     * @return bool
     */
    public function createFrontendUser(array $properties = []): bool
    {
        if (empty($properties['email'])) {
            throw new \InvalidArgumentException('No email or username found given in properties. Can not create a new user without those two values', 1561466312);
        }

        // Get email. Remove from $properties afterwards for automatic processing of all other values
        $email = $properties['email'];
        unset($properties['email']);

        // Get username. Remove from $properties afterwards for automatic processing of all other values
        if (!empty($properties['username'])) {
            $username = $properties['username'];
            unset($properties['username']);
        } else {
            $username = $email;
        }

        // Check if user already exists. If not, create one.
        $frontendUser = $this->frontendUserRepository->findOneByUsername($username, true);
        if (!$frontendUser) {
            // Create frontend user object
            /** @var FrontendUser $frontendUser */
            $frontendUser = $this->objectManager->get(FrontendUser::class);
        }

        // Set basic properties
        $frontendUser->setUsername($username);
        $frontendUser->setEmail($email);
        $frontendUser->setPid($this->settings['feuser']['registration']['pid']);
        $frontendUser->setDisabled(true);

        // Add FrontendUserGroup
        $userGroup = $this->frontendUserGroupRepository->findByUid($this->settings['feuser']['registration']['groupUid']);
        if ($userGroup) {
            $frontendUser->addUsergroup($userGroup);
        }

        // Automatically process all values left inside $properties
        foreach ($properties as $propertyName => $propertyValue) {
            $method = 'set' . ucfirst($propertyName);
            if (!is_callable([$frontendUser, $method])) {
                continue;
            }
            $frontendUser->{$method}($propertyValue);
        }

        // Create and/or set password
        if (isset($properties['password'])) {
            $password = $properties['password'];
            unset($properties['password']);
        } else {
            $password = GeneralUtility::makeInstance(PasswordUtility::class)->createPassword();
        }
        $frontendUser->setPassword(GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('FE')->getHashedPassword($password));
        $this->frontendUserRepository->add($frontendUser);

        // Set registration confirmation code and update user
        $frontendUser->setRegistrationCode($this->createRegistrationCode($frontendUser));
        $this->frontendUserRepository->update($frontendUser);
        $this->persistenceManager->persistAll();

        // Send confirmation email
        $this->sendConfirmationEmail($frontendUser, $password);
        return true;
    }

    /**
     * Send registration confirmation email to user
     *
     * @param FrontendUser $frontendUser
     * @param string $password
     * @return int
     */
    public function sendConfirmationEmail(FrontendUser $frontendUser, string $password): int
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $confirmationUri = $uriBuilder
            ->reset()
            ->setTargetPageUid($this->settings['feuser']['registration']['pluginPid'])
            ->setCreateAbsoluteUri(true)
            ->uriFor(
                'confirmRegistration',
                [
                    'code' => $frontendUser->getRegistrationCode(),
                ],
                'FrontendUser',
                'TwUser',
                'FeuserRegistration'
            );

        /** @var EmailUtility $emailUtility */
        $emailUtility = $this->objectManager->get(EmailUtility::class, $this->settings['email']['senderName'], $this->settings['email']['senderAddress']);
        $emailBody = $this->createConfirmationEmailBody($frontendUser, $password, $confirmationUri);
        return $emailUtility->send(
            [$frontendUser->getEmail()],
            LocalizationUtility::translate('feuser.registration.email.subject', 'TwUser'),
            $emailBody['html'],
            $emailBody['plaintext']
        );
    }

    /**
     * Create the email body
     * for the registration confirmation email
     *
     * @param FrontendUser $frontendUser
     * @param string $password
     * @param string $confirmationUri
     * @return array
     */
    public function createConfirmationEmailBody(FrontendUser $frontendUser, string $password, string $confirmationUri): array
    {
        $standaloneRenderer = $this->objectManager->get(StandaloneRenderer::class);
        return [
            'html' => $standaloneRenderer->render(
                'Email/FrontendUser/Registration',
                [
                    'confirmationUri' => $confirmationUri,
                    'username' => $frontendUser->getUsername(),
                    'password' => $password,
                    'user' => $frontendUser,
                ],
                'html',
                'Html'
            ),
            'plaintext' => $standaloneRenderer->render(
                'Email/FrontendUser/Registration',
                [
                    'confirmationUri' => $confirmationUri,
                    'username' => $frontendUser->getUsername(),
                    'password' => $password,
                    'user' => $frontendUser,
                ],
                'html',
                'Plaintext'
            ),
        ];
    }

    /**
     * Update a frontend user
     *
     * @param FrontendUser|null $frontendUser
     * @param array $properties
     *
     * @return bool
     */
    public function updateFrontendUser(FrontendUser $frontendUser = null, array $properties = []): bool
    {
        if (!$frontendUser) {
            return false;
        }

        // Automatically process all values inside $properties
        foreach ($properties as $propertyName => $propertyValue) {
            $method = 'set' . ucfirst($propertyName);
            if (!is_callable([$frontendUser, $method])) {
                continue;
            }
            $frontendUser->{$method}($propertyValue);
        }

        $this->frontendUserRepository->update($frontendUser);
        $this->persistenceManager->persistAll();
        return true;
    }

    /**
     * @return FrontendUser|null
     */
    public function getCurrentFrontendUser(): ?FrontendUser
    {
        if (!$GLOBALS['TSFE']->fe_user->user) {
            return null;
        }
        $frontendUser = $this->frontendUserRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        return $frontendUser ?: null;
    }

    /**
     * Automatically login an a FrontendUser
     *
     * @param int $frontendUserId
     * @throws \ReflectionException
     */
    public static function autoLogin(int $frontendUserId): void
    {
        $GLOBALS['TSFE']->fe_user->checkPid = 0;
        $userRecord = $GLOBALS['TSFE']->fe_user->getRawUserByUid($frontendUserId);
        $GLOBALS['TSFE']->fe_user->createUserSession($userRecord);
        $setSessionCookieMethod = new \ReflectionMethod($GLOBALS['TSFE']->fe_user, 'setSessionCookie');
        $setSessionCookieMethod->setAccessible(true);
        $setSessionCookieMethod->invoke($GLOBALS['TSFE']->fe_user);
    }
}
