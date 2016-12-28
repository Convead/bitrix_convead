<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
IncludeModuleLangFile(__FILE__);
if (class_exists('platina_conveadtracker')) {
    return;
}

class platina_conveadtracker extends CModule {

    var $MODULE_ID = "platina.conveadtracker";
    function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__."/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = GetMessage("convead_tracker.MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("convead_tracker.MODULE_DESCRIPTION");

        $this->PARTNER_NAME = GetMessage("convead_tracker.PARTNER_NAME");
        $this->PARTNER_URI = GetMessage("convead_tracker.PARTNER_URI");
    }

    public function DoInstall() {
        RegisterModule($this->MODULE_ID);
        RegisterModuleDependences("sale", "OnBasketAdd", $this->MODULE_ID, "cConveadTracker", "updateCart", "100");
        RegisterModuleDependences("sale", "OnBeforeBasketDelete", $this->MODULE_ID, "cConveadTracker", "updateCart", "100");
        RegisterModuleDependences("sale", "OnSaleComponentOrderComplete", $this->MODULE_ID, "cConveadTracker", "order", "100");
        RegisterModuleDependences("main", "OnAfterEpilog", $this->MODULE_ID, "cConveadTracker", "view", "100");
        RegisterModuleDependences("main", "OnEpilog", $this->MODULE_ID, "cConveadTracker", "head", "100");
        RegisterModuleDependences("sale", "OnBasketUpdate", $this->MODULE_ID, "cConveadTracker", "updateCart", "100");
        RegisterModuleDependences("sale", "OnBeforeViewedAdd", $this->MODULE_ID, "cConveadTracker", "productView", "100");
        RegisterModuleDependences("sale", "OnSaleStatusOrder", $this->MODULE_ID, "cConveadTracker", "orderSetState", "100");
        
        RegisterModuleDependences("sale", "OnSaleBasketSaved", $this->MODULE_ID, "cConveadTracker", "newEventUpdateCart", "100");
        RegisterModuleDependences("sale", "OnSaleBasketItemSetField", $this->MODULE_ID, "cConveadTracker", "newEventSetQtyCart", "100");
        RegisterModuleDependences("sale", "OnSaleOrderSaved", $this->MODULE_ID, "cConveadTracker", "newEventOrder", "100");
    
        $this->InstallFiles();
        $this->InstallDB();
    }

    public function DoUninstall() {
        UnRegisterModuleDependences("sale", "OnBasketAdd", $this->MODULE_ID, "cConveadTracker", "updateCart");
        UnRegisterModuleDependences("sale", "OnBeforeBasketDelete", $this->MODULE_ID, "cConveadTracker", "updateCart");
        UnRegisterModuleDependences("sale", "OnSaleComponentOrderComplete", $this->MODULE_ID, "cConveadTracker", "order");
        UnRegisterModuleDependences("main", "OnAfterEpilog", $this->MODULE_ID, "cConveadTracker", "view");
        UnRegisterModuleDependences("main", "OnEpilog", $this->MODULE_ID, "cConveadTracker", "head");
        UnRegisterModuleDependences("sale", "OnBasketUpdate", $this->MODULE_ID, "cConveadTracker", "updateCart");
        UnRegisterModuleDependences("sale", "OnBeforeViewedAdd", $this->MODULE_ID, "cConveadTracker", "productView");
        UnRegisterModuleDependences("sale", "OnSaleStatusOrder", $this->MODULE_ID, "cConveadTracker", "orderSetState");
        
        UnRegisterModuleDependences("sale", "OnSaleBasketSaved", $this->MODULE_ID, "cConveadTracker", "newEventUpdateCart"); 
        UnRegisterModuleDependences("sale", "OnSaleBasketItemSetField", $this->MODULE_ID, "cConveadTracker", "newEventSetQtyCart");
        UnRegisterModuleDependences("sale", "OnSaleOrderSaved", $this->MODULE_ID, "cConveadTracker", "newEventOrder"); 
        
        $this->UnInstallFiles();
        UnRegisterModule($this->MODULE_ID);
    }

    function InstallFiles() {
        
        return true;
    }

    function InstallDB () {
        
        return true;
    }

    function UnInstallFiles() {
        
        return true;
    }

}

?>