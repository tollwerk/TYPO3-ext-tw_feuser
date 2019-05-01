<?php

/**
 * NDF
 *
 * @category   typo3conf
 * @package    typo3conf\ext
 * @subpackage typo3conf\ext\tw_user\Classes\Domain\Finisher
 * @author     Joschi Kuphal <joschi@tollwerk.de> / @jkphl
 * @copyright  Copyright © 2019 Joschi Kuphal <joschi@tollwerk.de> / @jkphl
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

namespace Tollwerk\TwUser\Domain\Finisher;

use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;

/**
 * Extended redirect finisher
 *
 * @package    typo3conf\ext
 * @subpackage typo3conf\ext\tw_user\Classes\Domain\Finisher
 */
class RedirectFinisher extends \TYPO3\CMS\Form\Domain\Finishers\RedirectFinisher
{
    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal()
    {
        $redirectUri = $this->parseOption('uri');
        if (!$redirectUri) {
            parent::executeInternal();

            return;
        }

        $formRuntime    = $this->finisherContext->getFormRuntime();
        $this->request  = $formRuntime->getRequest();
        $this->response = $formRuntime->getResponse();
        $delay          = (int)$this->parseOption('delay');
        $statusCode     = (int)$this->parseOption('statusCode');

        $this->finisherContext->cancel();
        $redirectUri = $this->getTypoScriptFrontendController()->cObj->typoLink_URL(['parameter' => $redirectUri]);
        try {
            $this->redirectToUri($redirectUri, $delay, $statusCode);
        } catch (StopActionException $e) {
            $this->response->send();
            exit;
        }
    }
}
