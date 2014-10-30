<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
IncludeModuleLangFile(__FILE__);
if (class_exists('platina_conveadtracker')) {
    return;
}



class platina_conveadtracker extends CModule {

    var $MODULE_ID = 'platina.conveadtracker';
    var $MODULE_VERSION = '0.0.1';
    var $MODULE_VERSION_DATE = '2014-10-01 16:23:14';
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_GROUP_RIGHTS = 'N';
    var $PARTNER_NAME = "Platina";
    var $PARTNER_URI = "http://ptweb.ru/";

    function platina_conveadtracker() {
        $arModuleVersion = array();

        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path . "/version.php");

        $this->MODULE_ID = 'platina.conveadtracker';
        $this->PARTNER_NAME = "Platina";
        $this->PARTNER_URI = "http://ptweb.ru/";

        $this->MODULE_NAME = GetMessage("convead_tracker.MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("convead_tracker.MODULE_DESCRIPTION");;
        $this->MODULE_VERSION = $arModuleVersion["VERSION"] || $this->MODULE_VERSION;
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"] || $this->MODULE_VERSION_DATE;
    }

    public function DoInstall() {
        global $APPLICATION;
        RegisterModule($this->MODULE_ID);
        RegisterModuleDependences("sale", "OnBasketAdd", $this->MODULE_ID, "cConveadTracker", "updateCart", "100");
        RegisterModuleDependences("sale", "OnBeforeBasketDelete", $this->MODULE_ID, "cConveadTracker", "updateCart", "100");
        RegisterModuleDependences("sale", "OnBeforeOrderAdd", $this->MODULE_ID, "cConveadTracker", "order", "100");
        RegisterModuleDependences("main", "OnAfterEpilog", $this->MODULE_ID, "cConveadTracker", "view", "100");
        RegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "cConveadTracker", "head", "100");
        RegisterModuleDependences("sale", "OnBasketUpdate", $this->MODULE_ID, "cConveadTracker", "updateCart", "100");
        
        $this->InstallFiles();
        $this->InstallDB();
    }

    public function DoUninstall() {
        global $APPLICATION;
        UnRegisterModuleDependences("sale", "OnBasketAdd", $this->MODULE_ID, "cConveadTracker", "updateCart");
        UnRegisterModuleDependences("sale", "OnBeforeBasketDelete", $this->MODULE_ID, "cConveadTracker", "updateCart");
        UnRegisterModuleDependences("sale", "OnBeforeOrderAdd", $this->MODULE_ID, "cConveadTracker", "order");
        UnRegisterModuleDependences("main", "OnAfterEpilog", $this->MODULE_ID, "cConveadTracker", "view");
        UnRegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "cConveadTracker", "head");
        UnRegisterModuleDependences("sale", "OnBasketUpdate", $this->MODULE_ID, "cConveadTracker", "updateCart");
        $this->UnInstallFiles();
        UnRegisterModule($this->MODULE_ID);
    }

    function InstallFiles() {
        if (GetFileExtension(
                        $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/catalog/templates/.default/bitrix/catalog.element/.default/component_epilog.php"
                )) {
            CopyDirFiles(
                    $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/catalog/templates/.default/bitrix/catalog.element/.default/component_epilog.php", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/catalog/templates/.default/bitrix/catalog.element/.default/component_epilog_back.php", true
            );
        }
        CopyDirFiles(
                $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/platina.conveadtracker/install/components/bitrix/catalog/templates/.default/bitrix/catalog.element/.default/component_epilog.php", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/catalog/templates/.default/bitrix/catalog.element/.default/component_epilog.php", true
        );
        return true;
    }

    function InstallDB () {
        
        return true;
    }

    function UnInstallFiles() {
        DeleteDirFilesEx(
                $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/catalog/templates/.default/bitrix/catalog.element/.default/component_epilog.php"
        );
        
        if (GetFileExtension(
                        $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/catalog/templates/.default/bitrix/catalog.element/.default/component_epilog_back.php"
                )) {
            CopyDirFiles(
                    $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/catalog/templates/.default/bitrix/catalog.element/.default/component_epilog_back.php", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/catalog/templates/.default/bitrix/catalog.element/.default/component_epilog.php", true
            );
        }
        return true;
    }

}

?>