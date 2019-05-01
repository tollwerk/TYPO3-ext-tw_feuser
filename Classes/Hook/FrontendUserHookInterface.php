<?php
/**
 * NDF
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
 *  Copyright © 2019 Joschi Kuphal <joschi@tollwerk.de>
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

use Tollwerk\TwUser\Domain\Model\FrontendUser;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

/**
 * FrontendUserHookInterface
 *
 * Contains all hooks for FrontendUser related actions like registration.
 *
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Hook
 */
interface FrontendUserHookInterface
{
    /**
     * frontendUserRegistration
     *
     * Gets called inside Controller/FrontendUser->registrationAction() and can be used to manipulate all parameters
     * before rendering the registration form or handling the different statuses. With help of the passed in $controller
     * you can also forward or redirect to other controller actions.
     *
     * @param string|null $status
     * @param array $passthrough
     * @param array|null $form
     *
     * @return mixed
     */
    public function frontendUserRegistration(string &$status = null, array &$passthrough = null, array &$form = null);

    /**
     * frontendUserConfirmRegistration
     *
     * Gets called after trying to find a FrontendUser for the given confirmation code
     * but before changing anything on that record.
     *
     * @param string $code                    The confirmation code
     * @param FrontendUser|null $frontendUser The found FrontendUser for the confirmation code
     *
     * @return mixed|void
     */
    public function frontendUserConfirmRegistration(string &$code, FrontendUser $frontendUser = null): void;

    /**
     * Finalize the user registration form
     *
     * Gets called when building the FrontendUser registration form.
     * Can be used to manipulate the FormDefinition, for example, to add more form fields, pages, validators etc.
     *
     * @param FormDefinition $form Form definition
     * @param array $configuration Form factory configuration
     */
    public function frontendUserRegistrationForm(FormDefinition $form, array $configuration): void;

    /**
     * Finalize the user profile form
     *
     * Gets called when building the FrontendUser profile form.
     * Can be used to manipulate the FormDefinition, for example, to add more form fields, pages, validators etc.
     *
     * @param FormDefinition $form Form definition
     * @param array $configuration Form factory configuration
     */
    public function frontendUserProfileForm(FormDefinition $form, array $configuration): void;
}
