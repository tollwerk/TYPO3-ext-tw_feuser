<?php
/**
 * Ziereis Relaunch
 *
 * @category   Tollwerk
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Domain\Finisher\FrontendUser
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

namespace Tollwerk\TwUser\Domain\Finisher\FrontendUser;


use Tollwerk\TwUser\Controller\FrontendUserController;
use Tollwerk\TwUser\Domain\Model\FrontendUser;
use Tollwerk\TwUser\Domain\Repository\FrontendUserRepository;
use Tollwerk\TwUser\Hook\ProfileFinisherHook;
use Tollwerk\TwUser\Utility\FrontendUserUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Finishers\RedirectFinisher;

class ProfileFinisher extends RedirectFinisher
{
    /**
     * @var array
     */
    protected $defaultOptions = [
        'frontendUserUid' => null
    ];

    /**
     * @return string|void|null
     * @throws Exception
     */
    public function executeInternal()
    {
        // Get the  properties we want to set
        $formRuntime = $this->finisherContext->getFormRuntime();
        $frontendUserProperties = [
            'firstName' => $formRuntime->getElementValue('givenName'),
            'lastName' => $formRuntime->getElementValue('familyName'),
        ];

        // Get the frontend user we want to update
        $frontendUserUid = $this->options['frontendUserUid'] ?: ($GLOBALS['TSFE']->fe_user->user ? $GLOBALS['TSFE']->fe_user->user['uid'] : null);
        /** @var FrontendUser $frontendUser */
        $frontendUser = $frontendUserUid ? $this->objectManager->get(FrontendUserRepository::class)->findByUid($frontendUserUid) : null;

        // Hook for manipulating the $frontendUserProperties array which is used to set FrontendUser properties
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tw_user']['profileFinisher'] ?? [] as $className) {
            /** @var ProfileFinisherHook $_procObj */
            $_procObj = GeneralUtility::makeInstance($className);
            if (!($_procObj instanceof ProfileFinisherHook)) {
                throw new Exception(
                    sprintf('The registered class %s for hook [ext/tw_user][profileFinisher] does not implement the ProfileFinisherHook interface', $className),
                    1561469421
                );
            }
            $_procObj->profileFinisherFinisherHook($formRuntime, $frontendUserProperties);
        }

        // Update the frontend user
        $frontendUserUtility = GeneralUtility::makeInstance(FrontendUserUtility::class);
        $updateStatus = $frontendUserUtility->updateFrontendUser($frontendUser, $frontendUserProperties) ? FrontendUserController::PROFILE_UPDATE_SUCCESS : FrontendUserController::PROFILE_UPDATE_ERROR;

        // Redirect
        $settings = $this->objectManager->get(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TwUser');
        $formRuntime = $this->finisherContext->getFormRuntime();
        $this->request = $formRuntime->getRequest();
        $this->response = $formRuntime->getResponse();
        $this->uriBuilder = $this->objectManager->get(UriBuilder::class);
        $this->uriBuilder->setRequest($this->request);

        $uri = $this->uriBuilder->reset()->uriFor(
            'profile',
            [
                'status' => $updateStatus
            ]
        );
        $this->finisherContext->cancel();
        $this->redirectToUri($uri);
    }
}