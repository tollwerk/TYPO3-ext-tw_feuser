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

use Tollwerk\TwUser\Domain\Factory\AbstractFormFactory;
use Tollwerk\TwUser\Domain\Finisher\FrontendUser\RegistrationFinisher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

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
     * @throws InvalidConfigurationTypeException
     * @throws PrototypeNotFoundException
     * @throws TypeDefinitionNotFoundException
     * @throws TypeDefinitionNotValidException
     * @throws FinisherPresetNotFoundException
     * @api
     */
    public function build(array $configuration, string $prototypeName = null): FormDefinition
    {
        // Create form
        /** @var FormDefinition $form */
        $form = parent::build($configuration, $prototypeName);
        $form->setRenderingOption('controllerAction', 'registration');
        $form->setRenderingOption('submitButtonLabel', $this->translate('feuser.registration.form.submit'));

        // Create page and form fields
        $page = $form->createPage('registration');

        $email = $page->createElement('email', 'Email');
        $email->setLabeL($this->translate('feuser.registration.form.email'));
        $email->setProperty('fluidAdditionalAttributes', ['placeholder' => $this->translate('feuser.registration.form.email.placeholder')]);
        $email->addValidator($this->objectManager->get(NotEmptyValidator::class));

        // Add finishers
        $form->addFinisher($this->objectManager->get(RegistrationFinisher::class));

        // Return everything
        $this->triggerFormBuildingFinished($form);
        return $form;
    }
}
