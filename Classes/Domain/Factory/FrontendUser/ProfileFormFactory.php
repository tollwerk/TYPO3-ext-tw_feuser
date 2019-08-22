<?php
/**
 * Ziereis Relaunch
 *
 * @category   Tollwerk
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Domain\Factory\FrontendUser
 * @author     Klaus Fiedler <klaus@tollwerk.de> / @jkphl
 * @copyright  Copyright © 2019 Klaus Fiedler <klaus@tollwerk.de>
 * @license    http://opensource.org/licenses/MIT The MIT License (MIT)
 */

/***********************************************************************************
 *  The MIT License (MIT)
 *
 *  Copyright © 2019 Klaus Fiedler <klaus@tollwerk.de>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of
 *  this software and associated documentation files (the "Software"), to deal in
 *  the Software without restriction, including without limitation the rights to
 *  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 *  the Software, and to permit persons to whom the Software is furnished to do so,
 *  subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 *  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 *  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 ***********************************************************************************/

namespace Tollwerk\TwUser\Domain\Factory\FrontendUser;


use Tollwerk\TwBase\Domain\Validator\UniqueObjectValidator;
use Tollwerk\TwUser\Controller\FrontendUserController;
use Tollwerk\TwUser\Domain\Factory\AbstractFormFactory;
use Tollwerk\TwUser\Domain\Finisher\FrontendUser\ProfileFinisher;
use Tollwerk\TwUser\Domain\Finisher\FrontendUser\RegistrationFinisher;
use Tollwerk\TwUser\Domain\Model\FrontendUser;
use Tollwerk\TwUser\Domain\Repository\FrontendUserRepository;
use Tollwerk\TwUser\Hook\ProfileFormHook;
use Tollwerk\TwUser\Hook\RegistrationFormHook;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

class ProfileFormFactory extends AbstractFormFactory
{
    protected $identifier = 'frontendUserProfile';

    /**
     * Build a form definition, depending on some configuration.
     *
     * The configuration array is factory-specific; for example a YAML or JSON factory
     * could retrieve the path to the YAML / JSON file via the configuration array.
     *
     * @param array $configuration  factory-specific configuration array
     * @param string $prototypeName The name of the "PrototypeName" to use; it is factory-specific to implement this.
     *
     * @return FormDefinition
     * @throws Exception
     * @throws \TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException
     * @throws \TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException
     * @throws \TYPO3\CMS\Form\Domain\Model\Exception\FinisherPresetNotFoundException
     */
    public function build(array $configuration, string $prototypeName = null): FormDefinition
    {
        // Get the frontend user
        if (!empty($configuration['frontendUser'])) {
            /** @var FrontendUser $frontendUser */
            $frontendUser = ($configuration['frontendUser'] instanceof FrontendUser) ? $configuration['frontendUser'] : null;
        } else {
            $frontendUser = $GLOBALS['TSFE']->fe_user->user ? $this->objectManager->get(FrontendUserRepository::class)->findByUid($GLOBALS['TSFE']->fe_user->user['uid']) : null;
        }
        if (!$frontendUser) {
            throw new Exception(
                sprintf('The given frontendUser must be an instance of %s', [FrontendUser::class]),
                1566480373
            );
        }

        // Create form
        /** @var FormDefinition $form */
        $form = parent::build($configuration, $prototypeName);
        $form->setRenderingOption('controllerAction', 'profile');
        $form->setRenderingOption('submitButtonLabel', $this->translate('feuser.profile.form.submit'));
        $form->setRenderingOption('elementClassAttribute', 'UserProfile__form Form');

        // Create page and form fields
        $page = $form->createPage('profile');

        // Name row
        $nameRow = $page->createElement('nameRow', 'GridRow');
        $givenNameField = $nameRow->createElement('givenName', 'Text');
        $givenNameField->setLabeL($this->translate('feuser.profile.form.givenName'));
        $givenNameField->setProperty('fluidAdditionalAttributes', ['placeholder' => $this->translate('feuser.profile.form.givenName.placeholder')]);
        $givenNameField->setDefaultValue($frontendUser->getFirstName());
        $familyNameField = $nameRow->createElement('familyName', 'Text');
        $familyNameField->setLabeL($this->translate('feuser.profile.form.familyName'));
        $familyNameField->setProperty('fluidAdditionalAttributes', ['placeholder' => $this->translate('feuser.profile.form.familyName.placeholder')]);
        $familyNameField->setDefaultValue($frontendUser->getLastName());

        // Add finishers
        $profileFiniser = $this->objectManager->get(ProfileFinisher::class);
        $form->addFinisher($profileFiniser);
//        $form->createFinisher('Redirect', [
//            'pageUid' => (!empty($this->settings['feuser']['profile']['confirmPid'])) ? $this->settings['feuser']['profile']['confirmPid'] : $GLOBALS['TSFE']->id,
//            'additionalParameters' => 'tx_twuser_feuserprofile[status]='.FrontendUserController::REGISTRATION_SUBMITTED
//        ]);

        // Hook for manipulating the form definition
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tw_user']['profileForm'] ?? [] as $className) {
            /** @var ProfileFormHook $_procObj */
            $_procObj = GeneralUtility::makeInstance($className);
            if (!($_procObj instanceof ProfileFormHook)) {
                throw new Exception(
                    sprintf('The registered class %s for hook [ext/tw_user][profileForm] does not implement the ProfileFormHook interface', $className),
                    1556279202
                );
            }
            $_procObj->profileFormHook($form);
        }

        // Return everything
        $this->triggerFormBuildingFinished($form);
        return $form;
    }
}
