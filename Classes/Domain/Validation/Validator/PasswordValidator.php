<?php
/**
 * Ziereis Relaunch
 *
 * @subpackage ${NAMESPACE}
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

namespace Tollwerk\TwUser\Domain\Validation\Validator;

use Tollwerk\TwBase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Error;

/**
 * PasswordValidator
 *
 * This validator checks a password against certain rules.
 * See the $supportedOptions.
 * TODO: Add more options like special characters etc.
 *
 * Please note: There will be NO validation error when the password is empty,
 * because there already is the NotEmptyValidator for that. TODO: Change that?
 *
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Domain\Validator
 */
class PasswordValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator
{
    /**
     * Supported options
     *
     * @var array
     */
    protected $supportedOptions = [
        'minLength' => [10, 'The minimum password length'],
    ];

    /**
     * Check if as given password is valid
     *
     * @param mixed $value
     */
    public function isValid($value): void
    {
        $strlen = mb_strlen($value, 'utf-8');
        if ($strlen < $this->options['minLength']) {
            $this->result->addError(
                new Error(
                    LocalizationUtility::translate('validator.password.minLength.error', 'TwUser', [ $this->options['minLength']]),
                    1566978961
                )
            );
        }
    }
}