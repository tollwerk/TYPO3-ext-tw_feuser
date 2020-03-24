<?php
/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2020 Klaus Fiedler <klaus@tollwerk.de>, tollwerk® GmbH
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

namespace Tollwerk\TwUser\ViewHelpers;


use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Class InfoViewHelper
 * Returns $GLOBALS['TSFE']->fe_user->user
 *
 * @package Tollwerk\TwUser\ViewHelpers
 */
class GetViewHelper extends AbstractViewHelper
{
    /**
     * Render
     *
     * @param array $arguments Arguments
     * @param \Closure $renderChildrenClosure Children rendering closure
     * @param RenderingContextInterface $renderingContext Rendering context
     * @return mixed|string Output
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        return $GLOBALS['TSFE']->fe_user->user;
    }

}
