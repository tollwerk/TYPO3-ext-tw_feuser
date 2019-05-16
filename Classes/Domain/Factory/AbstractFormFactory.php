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

namespace Tollwerk\TwUser\Domain\Factory;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

/**
 * Abstract form factory
 *
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Domain\Factory
 */
abstract class AbstractFormFactory extends \TYPO3\CMS\Form\Domain\Factory\AbstractFormFactory
{
    /**
     * Form identifier
     *
     * @var string
     */
    protected $identifier = 'form';

    /**
     * Settings
     *
     * @var array
     */
    protected $settings = [];

    /**
     * Object manager
     *
     * @var ObjectManager
     */
    protected $objectManager = null;

    /**
     * Form definition class
     *
     * @var string
     */
    protected $formDefinition = FormDefinition::class;

    /**
     * Call LocalizationUtility::translate with the given $key.
     *
     * @param string $key
     * @param array $arguments
     *
     * @return string   Returns the $key if no translation found.
     */
    protected function translate(string $key, array $arguments = []): string
    {
        return LocalizationUtility::translate($key, 'TwUser', $arguments) ?: $key;
    }

    /**
     * AbstractFormFactory constructor.
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->settings      = $this->objectManager
            ->get(ConfigurationManager::class)
            ->getConfiguration(
                ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
                'TwUser'
            );
    }

    /**
     * Return a form field label
     *
     * @param string $key Localization key
     *
     * @return string
     */
    protected function label(string $key): string
    {
        return LocalizationUtility::translate('form.') ?? $key;
    }

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
     * @throws PrototypeNotFoundException
     * @api
     */
    public function build(array $configuration, string $prototypeName = null): FormDefinition
    {
        // Basic configuration
        $prototypeName          = $prototypeName ?? 'standard';
        $configurationService   = $this->objectManager->get(ConfigurationService::class);
        $prototypeConfiguration = $configurationService->getPrototypeConfiguration($prototypeName);

        // Create form definition
        /** @var \TYPO3\CMS\Form\Domain\Model\FormDefinition $form */
        $form = $this->objectManager->get($this->formDefinition, $this->identifier, $prototypeConfiguration);

        return $form;
    }
}
