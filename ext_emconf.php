<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "tw_geo"
 *
 * https://github.com/tollwerk/TYPO3-ext-tw_geo
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'tollwerk User Tools',
    'description' => 'Frontend User Registration, Login etc.',
    'category' => 'misc',
    'author' => 'tollwerk GmbH',
    'author_email' => 'info@tollwerk.de',
    'state' => 'alpha',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'tw_base' => '2.3.0',
            'typo3' => '9.5.4',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
