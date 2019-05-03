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

namespace Tollwerk\TwUser\Domain\Factory\FrontendUser;

use Tollwerk\TwBase\Domain\Validator\UniqueObjectValidator;
use Tollwerk\TwUser\Controller\FrontendUserController;
use Tollwerk\TwUser\Domain\Factory\AbstractFormFactory;
use Tollwerk\TwUser\Domain\Finisher\FrontendUser\RegistrationFinisher;
use Tollwerk\TwUser\Domain\Finisher\RedirectFinisher;
use Tollwerk\TwUser\Hook\FrontendUserHookInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;
use TYPO3\CMS\Form\Domain\Model\Exception\FinisherPresetNotFoundException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Exception;

/**
 * Registration Form Factory
 *
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Domain\Factory\FrontendUser
 */
class RegistrationFormFactory extends AbstractFormFactory
{
    /**
     * Build a form definition, depending on some configuration.
     *
     * The configuration array is factory-specific; for example a YAML or JSON factory
     * could retrieve the path to the YAML / JSON file via the configuration array.
     *
     * @param array $configuration  factory-specific configuration array
     * @param string $prototypeName The name of the "PrototypeName" to use; it is factory-specific to implement this.
     *
     * @return FormDefinition a newly built form definition
     * @throws Exception
     * @throws PrototypeNotFoundException
     * @throws TypeDefinitionNotFoundException
     * @throws TypeDefinitionNotValidException
     * @throws FinisherPresetNotFoundException
     * @api
     */
    public function build(array $configuration, string $prototypeName = null): FormDefinition
    {
        // Create form
        $form = parent::build($configuration, $prototypeName);
        $form->setRenderingOption('controllerAction', 'registration');
        $form->setRenderingOption('submitButtonLabel', $this->translate('feuser.registration.form.submit'));
        $form->setRenderingOption('elementClassAttribute', 'UserRegistration__form Form');

        // Add custom action URL
        if (!empty($configuration['actionUri'])) {
            $form->setRenderingOption('actionUri', $configuration['actionUri']);
        }

        // Create page and form fields
        $page  = $form->createPage('registration');
        $email = $page->createElement('email', 'Email');
        $email->setLabel($this->translate('feuser.registration.form.email'));
        $email->setProperty('fluidAdditionalAttributes', [
            'placeholder' => $this->translate('feuser.registration.form.email.placeholder')
        ]);
        $email->addValidator($this->objectManager->get(NotEmptyValidator::class));
        if (empty($this->settings['debug']['enable'])) {
            $email->addValidator($this->objectManager->get(UniqueObjectValidator::class, [
                'table'     => 'fe_users',
                'fieldname' => 'username',
            ]));
        }

        // Add the override configuration as hidden parameter
        $orc = $page->createElement('orc', 'Hidden');
        $orc->setDefaultValue(json_encode(array_intersect_key(
            $configuration,
            ['actionUri' => true, 'passthrough' => true]
        )));

        // Add frontend user registration finisher
        $form->addFinisher($this->objectManager->get(RegistrationFinisher::class));

        // If no custom form action is given: Redirect to current page
        if (empty($configuration['actionUri'])) {
            $form->createFinisher('Redirect', [
                'pageUid'              => $GLOBALS['TSFE']->id,
                'additionalParameters' => 'tx_twuser_feuserregistration[status]='.FrontendUserController::REGISTRATION_SUBMITTED
            ]);
        } else {
            $redirectUrl                                   = parse_url($configuration['actionUri']);
            $redirectQuery                                 = empty($redirectUrl['query']) ? [] : $redirectUrl['query'];
            $redirectQuery['tx_twuser_feuserregistration'] = ['status' => FrontendUserController::REGISTRATION_SUBMITTED];
            $redirectUrl['query']                          = http_build_query($redirectQuery);
            $redirectUrl                                   = HttpUtility::buildUrl($redirectUrl);
            $redirectFinisher                              = $this->objectManager->get(RedirectFinisher::class);
            $redirectFinisher->setOption('uri', $redirectUrl);
            $form->addFinisher($redirectFinisher);
        }

        // Hook for manipulating the form before calling triggerFormBuildingFinished()
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tw_user']['frontendUserRegistrationForm'] ?? [] as $className) {
            $_procObj = GeneralUtility::makeInstance($className);
            if (!($_procObj instanceof FrontendUserHookInterface)) {
                throw new Exception('The registered class '.$className.' for hook [ext/tw_user][frontendUserRegistrationForm] does not implement the FrontendUserHookInterface',
                    1556283898);
            }
            $_procObj->frontendUserRegistrationForm($form, $configuration);
        }

        // Return everything
        $this->triggerFormBuildingFinished($form);

        return $form;
    }
}
