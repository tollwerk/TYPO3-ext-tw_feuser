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


use Tollwerk\TwUser\Domain\Factory\AbstractFormFactory;
use Tollwerk\TwUser\Domain\Finisher\FrontendUser\PasswordFinisher;
use Tollwerk\TwUser\Domain\Validation\Validator\PasswordValidator;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

/**
 * PasswordFormFactory
 *
 * Change the user password
 *
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Domain\Factory\FrontendUser
 */
class PasswordFormFactory extends AbstractFormFactory
{
    protected $identifier = 'frontendUserPassword';

    /**
     * @param array $configuration
     * @param string|null $prototypeName
     *
     * @return FormDefinition
     */
    public function build(array $configuration, string $prototypeName = null): FormDefinition
    {
        $form = parent::build($configuration, $prototypeName);
        $form->setRenderingOption('controllerAction', 'password');
        $form->setRenderingOption('submitButtonLabel', $this->translate('feuser.password.form.submit'));
        $form->setRenderingOption('elementClassAttribute', 'UserPassword__form Form');

        // Create page and form fields
        $page = $form->createPage('password');
        $passwordValidator = $this->objectManager->get(PasswordValidator::class, [
            'minLength' => $this->settings['validation']['password']['minLength']
        ]);
        $passwordValidatorOptions = $passwordValidator->getOptions();
        $passwordField = $page->createElement('password', 'AdvancedPassword');
        $passwordField->setLabel($this->translate('feuser.password.form.password'));
        $passwordField->setProperty('fluidAdditionalAttributes', [
            'placeholder' => $this->translate('feuser.password.form.password.placeholder'),
            'minlength' => $passwordValidatorOptions['minLength'],
        ]);
        $passwordField->addValidator($passwordValidator);
        $passwordField->setProperty('confirmationLabel', $this->translate('feuser.password.form.confirmPassword'));

        // Add finishers
        $form->addFinisher($this->objectManager->get(PasswordFinisher::class));

        // Return everything
        $this->triggerFormBuildingFinished($form);
        return $form;
    }

}