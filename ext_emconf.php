<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Translate',
    'description' => 'Editor for locallang.xlf files',
    'category' => 'module',
    'version' => '2.8.3',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'author' => 'Raphael Graf',
    'author_email' => 'r@undefined.ch',
    'author_company' => 'undefined',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.0.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
