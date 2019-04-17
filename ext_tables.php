<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function() {
        // Add static TypoScript files
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            'tw_user',
            'Configuration/TypoScript/Static',
            'tollwerk User Tools'
        );

        // Register plugins
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Tollwerk.TwUser',
            'feuserRegistration',
            'LLL:EXT:tw_user/Resources/Private/Language/locallang_db.xlf:plugin.feuser.registration',
            'EXT:tw_user/Resources/Public/Icons/Backend/FrontendUser.svg'
        );

        // Register flexforms for plugins
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['twuser_feuserregistration'] = 'pi_flexform';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('twuser_feuserregistration','FILE:EXT:tw_user/Configuration/FlexForm/FeuserRegistration.xml');
    }
);
