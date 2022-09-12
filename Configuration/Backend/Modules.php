<?php

use Undefined\TranslateLocallang\Controller\ModuleController;

return [
    'tools_TranslateLocallang' => [
        'parent' => 'tools',
        'position' => 'bottom',
        'access' => 'user',
        'workspaces' => '*',
        'path' => '/module/tools/translate',
        'iconIdentifier' => 'module-translate',
        'labels' => 'LLL:EXT:translate_locallang/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'TranslateLocallang',
        'controllerActions' => [
            ModuleController::class => [
                'list', 
                'save', 
                'exportCsv', 
                'importCsv', 
                'createFile', 
                'deleteFile', 
                'search',
            ],
        ],
    ],
];
