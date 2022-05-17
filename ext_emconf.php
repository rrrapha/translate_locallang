<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Translate',
    'description' => 'Editor for locallang.xlf files',
    'category' => 'module',
    'version' => '2.8.1',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'author' => 'Raphael Graf',
    'author_email' => 'r@undefined.ch',
    'author_company' => 'undefined',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-11.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
