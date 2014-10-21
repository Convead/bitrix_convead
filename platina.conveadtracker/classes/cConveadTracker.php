<?php

class cConveadTracker {

    static $MODULE_ID = "platina.conveadtracker";

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
        if (!$api_key)
            return;

        global $APPLICATION;
        $guest_uid = self::getUid();
        $visitor_uid = false;
        $visitor_info = false;
        if ($user_id && $visitor_info = self::getVisitorInfo($user_id)) {
            $visitor_uid = (int) $user_id;
        }

        $tracker = new ConveadTracker($api_key, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);

        $product_id = $arResult["ID"];
        $product_name = $arResult["NAME"];
        $product_url = "http://" . SITE_SERVER_NAME . $APPLICATION->GetCurPage();

        $result = $tracker->eventProductView($product_id, $product_name, $product_url);

        return true;
    }

    static function addToCart($arFields) {
        return true;
        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if (!$api_key)
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

    static function updateCart($id, $arFields = false) {
        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if (!$api_key)
            return;


        $basket = CSaleBasket::GetByID($id);
        $user_id = $basket["FUSER_ID"];
        $items = array();
        $orders = CSaleBasket::GetList(
                        array(), array(
                    "FUSER_ID" => $basket["FUSER_ID"],
                    "LID" => SITE_ID,
                    "ORDER_ID" => "NULL"
                        ), false, false, array()
        );
        $i = 0;
        while ($order = $orders->Fetch()) {
            if (!$arFields) {//deleting
                if ($order["ID"] == $id)
                    continue;
            }
            $arProd = CCatalogSku::GetProductInfo($order["PRODUCT_ID"]);
            $item["id"] = $arProd["ID"];
            $item["qnt"] = $order["QUANTITY"];
            $item["price"] = $order["PRICE"];
            $items[$i . ""] = $item;
            $i++;
        }


        $guest_uid = self::getUid();
        $visitor_uid = false;
        $visitor_info = false;
        if ($arFields && $user_id && $visitor_info = self::getVisitorInfo($user_id)) {
            $visitor_uid = $user_id;
        }

        $tracker = new ConveadTracker($api_key, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);


        $result = $tracker->eventUpdateCart($items);

        return true;
    }

    static function removeFromCart($id) {
        return true;
        $arFields = CSaleBasket::GetByID($id);

        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if (!$api_key)
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

    static function order($arFields) {
        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if (!$api_key)
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
                    "USER_ID" => $arFields["USER_ID"],
                    "LID" => SITE_ID,
                    "ORDER_ID" => "NULL"
                        ), false, false, array()
        );
        $i = 0;
        while ($order = $orders->Fetch()) {
            $arProd = CCatalogSku::GetProductInfo($order["PRODUCT_ID"]);
            $item["id"] = $arProd["ID"];
            $item["qnt"] = $order["QUANTITY"];
            $item["price"] = $order["PRICE"];
            $items[$i . ""] = $item;
            $i++;
        }

        $price = $arFields["PRICE"];

        $max_order = CSaleOrder::GetList(array("ID" => "DESC"), array(), false, false, array())->Fetch();
        $order_id = (isset($max_order["ID"]) ? $max_order["ID"] : 0) + 1;
        $result = $tracker->eventOrder($order_id, $price, $items);

        return true;
    }

    static function view() {
        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if (!$api_key)
            return;

        global $USER;
        global $APPLICATION;




        $guest_uid = self::getUid();
        $visitor_info = false;
        $visitor_uid = false;
        if ($USER->GetID() && $USER->GetID() > 0 && $visitor_info = self::getVisitorInfo($USER->GetID())) {
            $visitor_uid = $USER->GetID();
        }

        $title = $APPLICATION->GetTitle();
        $url = $APPLICATION->GetCurUri();
        if (self::endsWith($url, "ajax.php?UPDATE_STATE")) {
            return;
        }
        if(self::startsWith($url, "/bitrix/admin/")){
            return;
        }
        $tracker = new ConveadTracker($api_key, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);

        $result = $tracker->view($url, $title);



        return true;
    }

    static function head() {
        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if (!$api_key)
            return;

        global $USER;
        global $APPLICATION;

        $url = $APPLICATION->GetCurUri();
        if(self::startsWith($url, "/bitrix/admin/")){
            return;
        }

        $guest_uid = self::getUid();
        $visitor_info = false;
        $visitor_uid = false;
        if ($USER && $USER->GetID() && $USER->GetID() > 0 && $visitor_info = self::getVisitorInfo($USER->GetID())) {
            $visitor_uid = $USER->GetID();
        }

        $vi = "";
        if ($visitor_info) {
            foreach ($visitor_info as $key => $val) {
                $vi.="\n" . $key . ": '" . $val . "',";
            }
            
            $vi = substr($vi, 1, strlen($vi)-2);
        }

        $head = "<!-- Convead Widget -->
                    <script>
                    window.ConveadSettings = {
                        /* Use only [0-9a-z-] characters for visitor uid!*/
                        " . ($visitor_uid ? "visitor_uid: '$visitor_uid'," : "") . "
                        visitor_info: {
                            $vi
                        }, 
                        app_key: \"$api_key\"

                        /* For more information on widget configuration please see:
                           http://convead.uservoice.com/knowledgebase/articles/344831-how-to-embed-a-tracking-code-into-your-websites
                        */
                    };

                    (function(w,d,c){w[c]=w[c]||function(){(w[c].q=w[c].q||[]).push(arguments)};var ts = (+new Date()/86400000|0)*86400;var s = d.createElement('script');s.type = 'text/javascript';s.async = true;s.src = 'http://tracker.staging.convead.io/widgets/'+ts+'/widget-$api_key.js';var x = d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s, x);})(window,document,'convead');
                    </script>
                    <!-- /Convead Widget -->";
        $APPLICATION->AddHeadString($head, true);



        return true;
    }

    private static function startsWith($haystack, $needle) {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    private static function endsWith($haystack, $needle) {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

}
