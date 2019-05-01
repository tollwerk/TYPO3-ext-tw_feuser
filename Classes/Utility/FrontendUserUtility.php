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

use Swift_SwiftException;
use Tollwerk\TwBase\Utility\EmailUtility;
use Tollwerk\TwBase\Utility\StandaloneRenderer;
use Tollwerk\TwUser\Domain\Model\FrontendUser;
use Tollwerk\TwUser\Domain\Repository\FrontendUserGroupRepository;
use Tollwerk\TwUser\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Frontend User Utility
 *
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Utility
 */
class FrontendUserUtility implements SingletonInterface
{
    /**
     * Object manager
     *
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * Frontend user group repository
     *
     * @var FrontendUserGroupRepository
     */
    protected $frontendUserGroupRepository = null;

    /**
     * Frontend user repository
     *
     * @var FrontendUserRepository
     */
    protected $frontendUserRepository = null;

    /**
     * Persistence manager
     *
     * @var PersistenceManager
     */
    protected $persistenceManager = null;

    /**
     * Settings
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Constructor
     *
     * @throws InvalidConfigurationTypeException
     */
    public function __construct()
    {
        $this->objectManager               = GeneralUtility::makeInstance(ObjectManager::class);
        $this->frontendUserRepository      = $this->objectManager->get(FrontendUserRepository::class);
        $this->frontendUserGroupRepository = $this->objectManager->get(FrontendUserGroupRepository::class);
        $this->persistenceManager          = $this->objectManager->get(PersistenceManager::class);
        $this->settings                    = $this->objectManager->get(ConfigurationManager::class)
                                                                 ->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                                                                     'TwUser');
    }

    /**
     * Create a frontend user
     *
     * @param string $email      Email address
     * @param mixed $passthrough Paramaters to pass through
     *
     * @return bool Success
     * @throws Swift_SwiftException
     * @throws InvalidPasswordHashException
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function createFrontendUser(string $email, $passthrough = null): bool
    {
        // Check if user already exists. If not, create one.
        $frontendUser = $this->frontendUserRepository->findOneByUsername($email, true);
        if (!$frontendUser) {
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
            $frontendUser->setPassword(
                GeneralUtility::makeInstance(PasswordHashFactory::class)
                              ->getDefaultHashInstance('FE')->getHashedPassword($password)
            );
            $this->frontendUserRepository->add($frontendUser);
            $this->persistenceManager->persistAll();
        }

        // Set registration confirmation code and update user
        $frontendUser->setRegistrationCode($this->createRegistrationCode($frontendUser));
        $this->frontendUserRepository->update($frontendUser);
        $this->persistenceManager->persistAll();

        // Prepare the registration parameters
        $parameters = ['code' => $frontendUser->getRegistrationCode()];
        if ($passthrough) {
            $parameters['passthrough'] = $passthrough;
        }

        // Send confirmation email
        $uriBuilder         = $this->objectManager->get(UriBuilder::class);
        $confirmationUri    = $uriBuilder
            ->reset()
            ->setTargetPageUid($this->settings['feuser']['registration']['pluginPid'])
            ->setCreateAbsoluteUri(true)
            ->uriFor('confirmRegistration', $parameters, 'FrontendUser', 'TwUser', 'FeuserRegistration');
        $standaloneRenderer = $this->objectManager->get(StandaloneRenderer::class);
        $emailUtility       = $this->objectManager->get(
            EmailUtility::class,
            $this->settings['email']['senderName'],
            $this->settings['email']['senderAddress']
        );
        $emailUtility->send(
            [$frontendUser->getEmail()],
            LocalizationUtility::translate('feuser.registration.email.subject', 'TwUser'),
            $standaloneRenderer->render(
                'Email/FrontendUser/Registration',
                [
                    'confirmationUri' => $confirmationUri,
                    'username'        => $frontendUser->getUsername(),
                    'password'        => $password,
                ],
                'html',
                'Html'
            ),
            $standaloneRenderer->render(
                'Email/FrontendUser/Registration',
                [
                    'confirmationUri' => $confirmationUri,
                    'username'        => $frontendUser->getUsername(),
                    'password'        => $password,
                ],
                'html',
                'Plaintext'
            )
        );

        return true;
    }

    /**
     * Create a random registration code
     *
     * @param FrontendUser $frontendUser Frontend user
     *
     * @return string Registration code
     */
    protected function createRegistrationCode(FrontendUser $frontendUser)
    {
        return md5($frontendUser->getEmail().$frontendUser->getUid().time());
    }

    /**
     * Automatically login a user
     *
     * @param int $frontendUserId Frontend User ID
     *
     * @throws \ReflectionException
     * @see https://forge.typo3.org/issues/62194
     */
    public static function userAutoLogin($frontendUserId)
    {
        $GLOBALS['TSFE']->fe_user->checkPid = 0;
        $userRecord                         = $GLOBALS['TSFE']->fe_user->getRawUserByUid($frontendUserId);
        $GLOBALS['TSFE']->fe_user->createUserSession($userRecord);
        $setSessionCookieMethod = new \ReflectionMethod($GLOBALS['TSFE']->fe_user, 'setSessionCookie');
        $setSessionCookieMethod->setAccessible(true);
        $setSessionCookieMethod->invoke($GLOBALS['TSFE']->fe_user);
    }
}
