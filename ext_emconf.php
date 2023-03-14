<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Translate',
    'description' => 'Backend Module for creating and editing of language files (locallang.xlf).',
    'category' => 'module',
    'version' => '2.8.5',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'author' => 'Raphael Graf',
    'author_email' => 'r@undefined.ch',
    'author_company' => 'undefined',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
