.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============


.. _what-it-does:

What does it do?
================

The Translate backend module is an editor for locallang.xlf files (Resources/Private/Language/locallang*.xlf). 
Additionally, the translation files can be exported as CSV (Excel) file.

Features:

- Side by side editing of multiple languages.

- Changing/adding/deleting of label keys.

- CDATA support.

- XLIFF 1.2 and 2.0 support. Both versions are read, the version used for writing
  is configurable (:ref:`xliffVersion <configuration-xliffversion>`).

- CSV import/export function.

- Configurable restrictions for non-admin backend users.

- Search for labels across all extensions.

- Automatic translation via MyMemory (experimental)
