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
use Tollwerk\TwUser\Domain\Model\FrontendUserGroup;
use Tollwerk\TwUser\Domain\Repository\FrontendUserGroupRepository;
use Tollwerk\TwUser\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
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
                                                                 ->getConfiguration(
                                                                     ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                                                                     'TwUser'
                                                                 );
    }

    /**
     * Create a frontend user
     *
     * @param string $email               Email address
     * @param array $additionalProperties Additional Properties
     * @param mixed $passthrough          Paramaters to pass through
     *
     * @return bool Success
     * @throws IllegalObjectTypeException
     * @throws InvalidPasswordHashException
     * @throws Swift_SwiftException
     * @throws UnknownObjectException
     */
    public function createFrontendUser(string $email, array $additionalProperties = [], $passthrough = null): bool
    {
        // Check if user already exists. If not, create one.
        $frontendUser = $this->frontendUserRepository->findOneByUsername($email, true);
        if (!$frontendUser) {
            $frontendUser = $this->objectManager->get(FrontendUser::class);

            // Start by adding the additional properties
            foreach ($additionalProperties as $property => $value) {
                $propertySetter = 'set'.ucfirst(GeneralUtility::underscoredToUpperCamelCase($property));
                if (is_callable([$frontendUser, $propertySetter])) {
                    $frontendUser->$propertySetter($value);
                }
            }

            // Set standard properties
            $frontendUser->setUsername($email);
            $frontendUser->setEmail($email);
            $frontendUser->setPid($this->settings['feuser']['registration']['pid']);
            $frontendUser->setDisabled(true);

            // Add the default user group
            $userGroupId = intval($this->settings['feuser']['registration']['groupUid']);
            if ($userGroupId) {
                $userGroup = $this->frontendUserGroupRepository->findByUid($userGroupId);
                if ($userGroup instanceof FrontendUserGroup) {
                    $frontendUser->addUsergroup($userGroup);
                }
            }

            // Create and set (new) password
            $password = GeneralUtility::makeInstance(PasswordUtility::class)->createPassword();
            $frontendUser->setPassword(
                GeneralUtility::makeInstance(PasswordHashFactory::class)
                              ->getDefaultHashInstance('FE')
                              ->getHashedPassword($password)
            );

            // Persist the new user
            $this->frontendUserRepository->add($frontendUser);
            $this->persistenceManager->persistAll();
        } else {
            $password = 'UNKNOWN';
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
        return $this->sendConfirmationMessage($frontendUser, $password, $parameters);
    }

    /**
     * Send a confirmation message
     *
     * @param FrontendUser $frontendUser Frontend user
     * @param string $password           Unencrypted password
     * @param array $parameters          URL parameters
     *
     * @return bool Success
     * @throws Swift_SwiftException
     */
    protected function sendConfirmationMessage(FrontendUser $frontendUser, string $password, array $parameters): bool
    {
        $frontendUserName = trim($frontendUser->getFirstName().' '.$frontendUser->getLastName());
        $recipient        = strlen($frontendUserName) ? [$frontendUser->getEmail() => $frontendUserName] : [$frontendUser->getEmail()];
        $uriBuilder       = $this->objectManager->get(UriBuilder::class)
                                                ->reset()
                                                ->setTargetPageUid($this->settings['feuser']['registration']['pluginPid'])
                                                ->setCreateAbsoluteUri(true);
        if (intval($this->settings['feuser']['registration']['pluginCid'])) {
            $uriBuilder->setSection('c'.$this->settings['feuser']['registration']['pluginCid']);
        }
        $confirmationUri = $uriBuilder->uriFor(
            'confirmRegistration',
            $parameters,
            'FrontendUser',
            'TwUser',
            'FeuserRegistration'
        );

        $standaloneRenderer = $this->objectManager->get(StandaloneRenderer::class);
        $emailUtility       = $this->objectManager->get(
            EmailUtility::class,
            $this->settings['email']['senderName'],
            $this->settings['email']['senderAddress']
        );

        return !!$emailUtility->send(
            $recipient,
            LocalizationUtility::translate('feuser.registration.email.subject', 'TwUser'),
            $standaloneRenderer->render(
                'Email/FrontendUser/Registration',
                [
                    'user'            => $frontendUser,
                    'confirmationUri' => $confirmationUri,
                    'password'        => $password,
                ],
                'html',
                'Html'
            ),
            $standaloneRenderer->render(
                'Email/FrontendUser/Registration',
                [
                    'user'            => $frontendUser,
                    'confirmationUri' => $confirmationUri,
                    'password'        => $password,
                ],
                'html',
                'Plaintext'
            )
        );
    }

    /**
     * Update the current frontend user
     *
     * @param array $values Values
     *
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     */
    public function updateFrontendUser(array $values): void
    {
        $frontendUser = $this->frontendUserRepository->findByIdentifier($GLOBALS['TSFE']->fe_user->user['uid']);
        if ($frontendUser instanceof FrontendUser) {
            foreach ($values as $property => $value) {
                if (($property == 'image') && !($value instanceof ObjectStorage)) {
                    $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
                    $images        = $objectManager->get(ObjectStorage::class);
                    switch (true) {
                        case ($value instanceof FileReference):
                            $images->attach($value);
                            break;
                        case ($value instanceof \TYPO3\CMS\Core\Resource\FileReference):
                            $extbaseImage = new FileReference();
                            $extbaseImage->setOriginalResource($value);
                            $images->attach($extbaseImage);
                            break;
                        case (!empty(GeneralUtility::_GP('delete_image')));
                            break;
                        default:
                            continue 2;
                            break;
                    }
                    $value = $images;
                }
                $propertySetter = 'set'.ucfirst($property);
                if (is_callable([$frontendUser, $propertySetter])) {
                    $frontendUser->$propertySetter($value);
                }
            }
            $this->frontendUserRepository->update($frontendUser);
            $this->persistenceManager->persistAll();
        }
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
