<?php
/**
 * User
 *
 * @category   typo3conf
 * @package    typo3conf\ext
 * @subpackage typo3conf\ext\tw_user\Classes\Hook
 * @author     Klaus Fiedler <klaus@tollwerk.de> / @jkphl
 * @copyright  Copyright © 2019 Klaus Fiedler <klaus@tollwerk.de>
 * @license    http://opensource.org/licenses/MIT The MIT License (MIT)
 */

/***********************************************************************************
 *  The MIT License (MIT)
 *
 *  Copyright © 2020 Joschi Kuphal <joschi@tollwerk.de>
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

namespace Tollwerk\TwUser\Hook;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Mvc\ProcessingRule;
use TYPO3\CMS\Form\Service\TranslationService;

/**
 * Form hook
 *
 * @package Tollwerk\TwUser\Hook
 */
class FormElementHooks
{
    /**
     * This hook is invoked by the FormRuntime for each form element
     * **after** a form page was submitted but **before** values are
     * property-mapped, validated and pushed within the FormRuntime's `FormState`.
     *
     * @param FormRuntime         $formRuntime
     * @param RenderableInterface $renderable
     * @param mixed               $elementValue     submitted value of the element *before post processing*
     * @param array               $requestArguments submitted raw request values
     *
     * @return mixed
     * @see FormRuntime::mapAndValidate()
     * @internal
     */
    public function afterSubmit(
        FormRuntime $formRuntime,
        RenderableInterface $renderable,
        $elementValue,
        array $requestArguments = []
    ) {
        if ($renderable->getType() === 'AdvancedPassword') {
            if ($elementValue['password'] !== $elementValue['confirmation']) {
                $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
                /** @var ProcessingRule $processingRule */
                $processingRule = $renderable->getRootForm()->getProcessingRule($renderable->getIdentifier());
                $processingRule->getProcessingMessages()->addError(
                    $objectManager->get(
                        Error::class,
                        TranslationService::getInstance()->translate('validation.error.1556283177', null,
                            'EXT:form/Resources/Private/Language/locallang.xlf'),
                        1556283177
                    )
                );
                foreach ($processingRule->getValidators() as $validator) {
                    $processingRule->removeValidator($validator);
                }

                return '';
            }
            $elementValue = $elementValue['password'];
        }

        return $elementValue;
    }
}
