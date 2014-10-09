<?php

class cConveadTracker {

    static $MODULE_ID = "platina.tracker";

    static function getVisitorInfo($id) {
        if ($usr = CUser::GetByID($id)) {
            $user = $usr->Fetch();
            $visitor_info = array();
            $user["NAME"] && $visitor_info["first_name"] = $user["NAME"];
            $user["LAST_NAME"] && $visitor_info["last_name"] = $user["LAST_NAME"];
            $user["EMAIL"] && $visitor_info["email"] = $user["EMAIL"];
            $user["PERSONAL_PHONE"] && $visitor_info["phone"] = $user["PERSONAL_PHONE"];
            $user["PERSONAL_BIRTHDAY"] && $visitor_info["date_of_birth"] = date('Y-m-d', $user["PERSONAL_BIRTHDAY"]);
            $user["PERSONAL_GENDER"] && $visitor_info["gender"] = ($user["PERSONAL_GENDER"] == "M" ? "male" : "femail");
            return $visitor_info;
        } else {
            return false;
        }
    }

    static function getUid() {
        return substr(md5($_SESSION["SESS_SESSION_ID"]), 1, 16);
    }
    
    static function productView($arResult, $user_id = false) { 
        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if(!$api_key)
            return;
        
        global $APPLICATION;
        $guest_uid = self::getUid();
        $visitor_uid = false;
        $visitor_info = false;
        if ($user_id && $visitor_info = self::getVisitorInfo($user_id)) {
            $visitor_uid = (int)$user_id;
        }

        $tracker = new ConveadTracker($api_key, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);

        $product_id = $arResult["ID"];
        $product_name = $arResult["NAME"];
        $product_url = "http://" . SITE_SERVER_NAME . $APPLICATION->GetCurPage();

        $result = $tracker->eventProductView($product_id, $product_name, $product_url);
        echo $result."-------LLLL";
        return true;
    }

    static function addToCart($arFields) {
        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if(!$api_key)
            return;
        $guest_uid = self::getUid();
        $visitor_uid = false;
        $visitor_info = false;
        if ($arFields["FUSER_ID"] && $arFields["FUSER_ID"] && $visitor_info = self::getVisitorInfo($arFields["FUSER_ID"])) {
            $visitor_uid = $arFields["FUSER_ID"];
        }

        $tracker = new ConveadTracker($api_key, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);

        $product_id = $arFields["PRODUCT_ID"];
        $qnt = $arFields["QUANTITY"];
        $product_name = $arFields["NAME"];
        $product_url = "http://" . SITE_SERVER_NAME . $arFields["DETAIL_PAGE_URL"];
        $price = $arFields["PRICE"];

        $result = $tracker->eventAddToCart($product_id, $qnt, $product_name, $product_url, $price);
        
        return true;
    }

    static function removeFromCart($id) {
        $arFields = CSaleBasket::GetByID($id);
        
        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if(!$api_key)
            return;
        $guest_uid = self::getUid();
        $visitor_uid = false;
        $visitor_info = false;
        if ($arFields["FUSER_ID"] && $arFields["FUSER_ID"] && $visitor_info = self::getVisitorInfo($arFields["FUSER_ID"])) {
            $visitor_uid = $arFields["FUSER_ID"];
        }

        $tracker = new ConveadTracker($api_key, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);

        $product_id = $arFields["PRODUCT_ID"];
        $qnt = $arFields["QUANTITY"];
        $product_name = $arFields["NAME"];
        $product_url = "http://" . SITE_SERVER_NAME . $arFields["DETAIL_PAGE_URL"];
        $price = $arFields["PRICE"];

        $result = $tracker->eventRemoveFromCart($product_id, $qnt);
       
        
        return true;
    }

    static function order($order_id, $arFields) {
        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if(!$api_key)
            return;
        $guest_uid = self::getUid();
        $visitor_uid = false;
        $visitor_info = false;
        if ($arFields["USER_ID"] && $arFields["USER_ID"] && $visitor_info = self::getVisitorInfo($arFields["USER_ID"])) {
            $visitor_uid = $arFields["USER_ID"];
        }
            
        $tracker = new ConveadTracker($api_key, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);
        
        $items = array();
        $orders = CSaleBasket::GetList(
                        array(), array(
                    "ORDER_ID" => $order_id
                        ), false, false, array()
        );
        $i = 0;
        while ($order = $orders->Fetch()) {
            
            $item["id"] = $order["PRODUCT_ID"];
            $item["qnt"] = $order["QUANTITY"];
            $item["price"] = $order["PRICE"];
            $items[$i.""] = $item;
            $i++;
        }

        $price = $arFields["PRICE"];

        $result = $tracker->eventOrder($order_id, $price, $items);
        
        return true;
    }

}
