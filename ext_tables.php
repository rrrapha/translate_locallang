<?php

defined('TYPO3') or die();

(function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'TranslateLocallang',
        'tools',
        'm1',
        '',
        [
            \Undefined\TranslateLocallang\Controller\ModuleController::class => 'list, save, exportCsv, importCsv, createFile, deleteFile, search'
        ],
        [
            'access' => 'user,group',
            'iconIdentifier' => 'module-translate',
            'labels' => 'LLL:EXT:translate_locallang/Resources/Private/Language/locallang_mod.xlf',
        ]
    );
})();
