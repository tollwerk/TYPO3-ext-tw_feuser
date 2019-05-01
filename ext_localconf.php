<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

call_user_func(
    function() {
        // Configure plugins
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Tollwerk.TwUser',
            'FeuserRegistration',
            ['FrontendUser' => 'registration, confirmRegistration'],
            ['FrontendUser' => 'confirmRegistration']
        );

        // Exclude parameters from cacheHash calculation
        $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_twuser_feuserregistration[code]';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['user']      = ['Tollwerk\\TwUser\\ViewHelpers'];
    }
);

