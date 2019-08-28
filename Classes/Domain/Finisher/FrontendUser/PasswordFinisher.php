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
use Tollwerk\TwUser\Domain\Factory\FrontendUser\PasswordFormFactory;
use Tollwerk\TwUser\Domain\Model\FrontendUser;
use Tollwerk\TwUser\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Finishers\RedirectFinisher;

/**
 * PasswordFinisher
 *
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Domain\Finisher\FrontendUser
 */
class PasswordFinisher extends RedirectFinisher
{
    /**
     * @var array
     */
    protected $defaultOptions = [
        'frontendUserUid' => null
    ];

    public function executeInternal()
    {
        // Get the  properties we want to set
        $formRuntime = $this->finisherContext->getFormRuntime();

        // Get the frontend user we want to update
        $frontendUserUid = $this->options['frontendUserUid'] ?: ($GLOBALS['TSFE']->fe_user->user ? $GLOBALS['TSFE']->fe_user->user['uid'] : null);
        /** @var FrontendUser $frontendUser */
        $frontendUser = $frontendUserUid ? $this->objectManager->get(FrontendUserRepository::class)->findByUid($frontendUserUid) : null;

        // Set the new password
        if($frontendUser){
            $frontendUser->setPassword(GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('FE')->getHashedPassword($formRuntime->getElementValue('password')));
            $this->objectManager->get(FrontendUserRepository::class)->update($frontendUser);
            $this->objectManager->get(PersistenceManager::class)->persistAll();
            $updateStatus = FrontendUserController::CHANGE_PASSWORD_SUCCESS;
        } else {
            $updateStatus = FrontendUserController::CHANGE_PASSWORD_ERROR;
        }

        // Redirect
        $this->request = $formRuntime->getRequest();
        $this->response = $formRuntime->getResponse();
        $this->uriBuilder = $this->objectManager->get(UriBuilder::class);
        $this->uriBuilder->setRequest($this->request);
        $uri = $this->uriBuilder->reset()->uriFor(
            'password',
            [
                'status' => $updateStatus
            ]
        );
        $this->finisherContext->cancel();
        $this->redirectToUri($uri);
    }
}