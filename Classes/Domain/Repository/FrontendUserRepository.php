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

namespace Tollwerk\TwUser\Domain\Repository;


use Tollwerk\TwUser\Domain\Model\FrontendUser;

class FrontendUserRepository extends \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
{
    /**
     * @param string $username
     * @param bool $ignoreEnableFields
     *
     * @return null|FrontendUser
     */
    public function findOneByUsername(string $username, bool $ignoreEnableFields = false): ?FrontendUser
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false)->setIgnoreEnableFields($ignoreEnableFields)->setEnableFieldsToBeIgnored(['disabled', 'starttime', 'endtime']);
        $constraints = [
            $query->equals('username', $username),
        ];
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult $result */
        $result = $query->matching($query->logicalAnd($constraints))->execute();
        return $result->count() ? $result->getFirst() : null;
    }

    /**
     * @param $registrationCode
     *
     * @return null|FrontendUser
     */
    public function findOneByRegistrationCode($registrationCode)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false)->setIgnoreEnableFields(true)->setEnableFieldsToBeIgnored(['disabled', 'starttime', 'endtime']);
        $constraints = [
            $query->equals('registrationCode', $registrationCode),
        ];
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult $result */
        $result = $query->matching($query->logicalAnd($constraints))->execute();
        return $result->count() ? $result->getFirst() : null;
    }
}
