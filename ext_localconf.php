<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

call_user_func(
    function() {
        // Configure plugins
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Tollwerk.TwGeo',
            'FeuserRegistration',
            ['FrontendUser' => 'registration'],
            ['FrontendUser' => 'registration']
        );
    }
);

