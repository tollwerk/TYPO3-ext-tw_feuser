<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function() {
        // Add static TypoScript files
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
            'tw_user',
            'Configuration/TypoScript',
            'tollwerk User Tools'
        );

        // Register plugins
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Tollwerk.TwUser',
            'FeuserRegistration',
            'LLL:EXT:tw_user/Resources/Private/Language/locallang_db.xlf:plugin.feuser.registration',
            'EXT:tw_user/Resources/Public/Icons/Backend/FrontendUser.svg'
        );
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Tollwerk.TwUser',
            'FeuserProfile',
            'LLL:EXT:tw_user/Resources/Private/Language/locallang_db.xlf:plugin.feuser.profile',
            'EXT:tw_user/Resources/Public/Icons/Backend/FrontendUser.svg'
        );
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Tollwerk.TwUser',
            'FeuserPassword',
            'LLL:EXT:tw_user/Resources/Private/Language/locallang_db.xlf:plugin.feuser.password',
            'EXT:tw_user/Resources/Public/Icons/Backend/FrontendUser.svg'
        );
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Tollwerk.TwUser',
            'Debug',
            'LLL:EXT:tw_user/Resources/Private/Language/locallang_db.xlf:plugin.debug',
            'EXT:tw_user/Resources/Public/Icons/Backend/Bug.svg'
        );

        // Register flexforms for plugins
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['twuser_feuserregistration'] = 'pi_flexform';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('twuser_feuserregistration','FILE:EXT:tw_user/Configuration/FlexForm/FeuserRegistration.xml');
    }
);
