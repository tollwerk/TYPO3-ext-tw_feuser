<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

call_user_func(
    function() {
        // Register fluid ViewHelper namespace
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['user'] = ['Tollwerk\\TwUser\\ViewHelpers'];

        // Configure plugins
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Tollwerk.TwUser',
            'FeuserRegistration',
            ['FrontendUser' => 'registration, confirmRegistration'],
            ['FrontendUser' => 'registration, confirmRegistration']
        );
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Tollwerk.TwUser',
            'FeuserProfile',
            ['FrontendUser' => 'profile'],
            ['FrontendUser' => 'profile']
        );
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Tollwerk.TwUser',
            'FeuserPassword',
            ['FrontendUser' => 'password'],
            ['FrontendUser' => 'password']
        );

        // Exclude parameters from cacheHash calculation
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] =  'tx_twuser_feuserregistration[code]';
    }
);

