.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

Configuration Reference
=======================

.. _configuration-extconf:

Extension Configuration
-----------------------

All configuration is done in the extension manager (extConf). 

Configuration Options
^^^^^^^^^^^^^^^^^^^^^

.. t3-field-list-table::
 :header-rows: 1

 - :Property:
      Option
   :Description:
      Description
   :Default:
      Default value

 - :Property:
      defaultLangKey
   :Description:
      Default language key
   :Default:
      en

 - :Property:
      langKeys
   :Description:
      Translation language keys
   :Default:
      de,fr,it

 - :Property:
      useL10n 
   :Description:
      Save translations to 'labels' or 'l10n' folder instead of 'typo3conf/ext/'
   :Default:
      0

 - :Property:
      debug
   :Description:
      Show debug output
   :Default:
      0

 - :Property:
      sysLog
   :Description:
      Log write operations
   :Default:
      0

 - :Property:
      allowedExts
   :Description:
      Allowed extensions for non-admin users (comma separated, empty=all)
   :Default:
      [empty]

 - :Property:
      allowedFiles
   :Description:
      Allowed filenames for non-admin users (comma separated, empty=all)
   :Default:
      [empty]

 - :Property:
      modifyDefaultLang
   :Description:
      Allow non-admin users to modify default language
   :Default:
      0

 - :Property:
      modifyKeys
   :Description:
      Allow non-admin users to modify keys and sorting (implies modifyDefaultLang)
   :Default:
      0
