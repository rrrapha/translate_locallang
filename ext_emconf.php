<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Translate',
    'description' => 'Editor for locallang.xlf files',
    'category' => 'module',
    'version' => '2.6.2',
    'state' => 'stable',
    'uploadfolder' => 0,
    'clearCacheOnLoad' => 1,
    'author' => 'Raphael Graf',
    'author_email' => 'r@undefined.ch',
    'author_company' => 'undefined',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-10.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
