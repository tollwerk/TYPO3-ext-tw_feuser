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

namespace Tollwerk\TwUser\Domain\Model;

/**
 * Extended Frontend User
 *
 * @package    Tollwerk\TwUser
 * @subpackage Tollwerk\TwUser\Domain\Model
 */
class FrontendUser extends \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
{
    /**
     * User is disabled
     *
     * @var bool
     */
    protected $disabled = true;

    /**
     * Registration code
     *
     * @var string
     */
    protected $registrationCode = '';

    /**
     * Extbase type
     *
     * @var string
     */
    protected $txExtbaseType;

    /**
     * Return whether the user is disabled
     *
     * @return bool Disabled
     */
    public function getDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Set whether the user is disabled
     *
     * @param bool $disabled Disabled
     */
    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    /**
     * Return the registration code
     *
     * @return string Registration code
     */
    public function getRegistrationCode(): string
    {
        return $this->registrationCode;
    }

    /**
     * Set the registration code
     *
     * @param string $registrationCode Registration code
     */
    public function setRegistrationCode(string $registrationCode): void
    {
        $this->registrationCode = $registrationCode;
    }

    /**
     * Return the Extbase type
     *
     * @return string Extbase type
     */
    public function getTxExtbaseType(): string
    {
        return $this->txExtbaseType;
    }

    /**
     * Set the Extbase type
     *
     * @param string $txExtbaseType Extbase type
     */
    public function setTxExtbaseType(string $txExtbaseType): void
    {
        $this->txExtbaseType = $txExtbaseType;
    }
}
