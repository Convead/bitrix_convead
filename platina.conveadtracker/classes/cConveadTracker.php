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
        $user["PERSONAL_GENDER"] && $visitor_info["gender"] = ($user["PERSONAL_GENDER"] == "M" ? "male" : "female");

        if(file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/include/helper/ConveadHelper.php"))
          {
            include_once $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/include/helper/ConveadHelper.php";
            if(method_exists('ConveadHelper', 'GetAddInfo'))
              {
                $visitor_info = ConveadHelper::GetAddInfo($id,$visitor_info,$user);
              }
          }

        return $visitor_info;
      } else {
        return false;
      }
    }

    static function getUid($visitor_uid) {
      if ($visitor_uid) return false;
      else return $_COOKIE["convead_guest_uid"];
    }

    static function productView($arResult, $user_id = false)
    {

        if ($arResult["ID"] != "")
          $arResult["PRODUCT_ID"] = $arResult["ID"];

        if (class_exists("DataManager"))
          return false;

        if (self::contains($_SERVER["HTTP_USER_AGENT"], "facebook.com"))
        {
            return;
        }


        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if (!$api_key)
          return;

        global $APPLICATION;
        global $USER;

        $visitor_uid = false;
        if (!$user_id)
          $user_id = $USER->GetID();

        $visitor_info = false;
        if ($user_id && $visitor_info = self::getVisitorInfo($user_id))
        {
            $visitor_uid = (int)$user_id;
        }
        $guest_uid = self::getUid($visitor_uid);
        $tracker = new ConveadTracker($api_key, SITE_SERVER_NAME, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);

        $arProduct = CCatalogProduct::GetByIDEx($arResult["PRODUCT_ID"]);
        if ($arProduct && strpos($APPLICATION->GetCurPage(), $arProduct["DETAIL_PAGE_URL"]) !== false)
        {
            if (CCatalogSku::IsExistOffers($arResult["PRODUCT_ID"]))
              {
                $arOffers =
                   CIBlockPriceTools::GetOffersArray(array("IBLOCK_ID" => $arProduct["IBLOCK_ID"]), array($arResult["PRODUCT_ID"]), array(), array(
                         "ID",
                         "ACTIVE"
                      )

                   );
                foreach ($arOffers as $array)
                  {
                    if ($array["ACTIVE"] == "Y")
                      {
                        $arResult["PRODUCT_ID"] = $array["ID"];
                        break;
                      }
                  }
              }

            $_SESSION["CONVEAD_PRODUCT_ID"] = $arResult["PRODUCT_ID"];
            $_SESSION["CONVEAD_PRODUCT_NAME"] = str_replace("'", '&#039;', $arProduct["NAME"]);
            $_SESSION["CONVEAD_PRODUCT_URL"] = "http://" . SITE_SERVER_NAME . $arProduct["DETAIL_PAGE_URL"];

            $product_id = $arResult["PRODUCT_ID"];
            $product_name = $arProduct["NAME"];
            $product_url = "http://" . SITE_SERVER_NAME . $arProduct["DETAIL_PAGE_URL"];

            if ($_SESSION["LAST_VIEW_ID"] == $arResult["PRODUCT_ID"])
              return false;
            else
              {
                $_SESSION["LAST_VIEW_ID"] = $arResult["PRODUCT_ID"];
                return true;
              }
            //$result = $tracker->eventProductView($product_id, $product_name, $product_url);

            return true;
        }
    }

    static function addToCart($arFields) {
      return true;
      $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
      if (!$api_key)
        return;

      $visitor_uid = false;
      $visitor_info = false;
      if ($arFields["FUSER_ID"] && $arFields["FUSER_ID"] && $visitor_info = self::getVisitorInfo($arFields["FUSER_ID"])) {
        $visitor_uid = $arFields["FUSER_ID"];
      }
      $guest_uid = self::getUid($visitor_uid);

      $tracker = new ConveadTracker($api_key, SITE_SERVER_NAME, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);

      $product_id = $arFields["PRODUCT_ID"];
      $qnt = $arFields["QUANTITY"];
      $product_name = $arFields["NAME"];
      $product_url = "http://" . SITE_SERVER_NAME . $arFields["DETAIL_PAGE_URL"];
      $price = $arFields["PRICE"];

      $result = $tracker->eventAddToCart($product_id, $qnt, $price, $product_name, $product_url);

      return true;
    }

    static function updateCart($id, $arFields = false) {

      if(!class_exists("CCatalogSku"))
        return false;

      $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
      if (!$api_key)
        return;

      if ($arFields && !isset($arFields["PRODUCT_ID"]) && !isset($arFields["DELAY"])) {//just viewving
        return;
      }

      if ($arFields && isset($arFields["ORDER_ID"])) {// purchasing
        return;
      }



      $basket = CSaleBasket::GetByID($id);

      $user_id = $basket["FUSER_ID"];
      $items = array();
      $orders = CSaleBasket::GetList(
         array(), array(
         "FUSER_ID" => $basket["FUSER_ID"],
         //"LID" => SITE_ID,
         "ORDER_ID" => "NULL"
      ), false, false, array()
      );
      $i = 0;
      while ($order = $orders->Fetch()) {
        if (!$arFields) {//deleting
          if ($order["ID"] == $id)
            continue;
        }

        $item["product_id"] = $order["PRODUCT_ID"];

        $item["qnt"] = $order["QUANTITY"];
        $item["price"] = $order["PRICE"];
        $items[$i . ""] = $item;
        $i++;
      }

      global $USER;
      $user_id = false;
      if($USER->GetID())
        $user_id = $USER->GetID();

      $visitor_uid = false;
      $visitor_info = false;
      $visitor_info = self::getVisitorInfo($user_id);
      if ($visitor_info || $user_id !== FALSE) {
        $visitor_uid = $user_id;
      }
      $guest_uid = self::getUid($visitor_uid);

      $tracker = new ConveadTracker($api_key, SITE_SERVER_NAME, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);

      $result = $tracker->eventUpdateCart($items);

      return true;
    }

    static function removeFromCart($id) {
      return true;
      $arFields = CSaleBasket::GetByID($id);

      $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
      if (!$api_key)
        return;

      $visitor_uid = false;
      $visitor_info = false;
      if ($arFields["FUSER_ID"] && $arFields["FUSER_ID"] && $visitor_info = self::getVisitorInfo($arFields["FUSER_ID"])) {
        $visitor_uid = $arFields["FUSER_ID"];
      }
      $guest_uid = self::getUid($visitor_uid);
      $tracker = new ConveadTracker($api_key, SITE_SERVER_NAME, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);

      $product_id = $arFields["PRODUCT_ID"];
      $qnt = $arFields["QUANTITY"];
      $product_name = $arFields["NAME"];
      $product_url = "http://" . SITE_SERVER_NAME . $arFields["DETAIL_PAGE_URL"];
      $price = $arFields["PRICE"];

      $result = $tracker->eventRemoveFromCart($product_id, $qnt);

      return true;
    }

    static function order($ID, $fuserID, $strLang, $arDiscounts)
      {
        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if (!$api_key)
          return true;
        $arOrder = CSaleOrder::GetByID(intval($ID));
        if ($arOrder["ID"] > 0)
          {

            $TimeUpdate = strtotime($arOrder["DATE_UPDATE"]);
            $TimeAdd = strtotime($arOrder["DATE_INSERT"]);
            if ($TimeUpdate - $TimeAdd <= 60)
              {
                $visitor_uid = false;
                $visitor_info = false;
                if ($arOrder["USER_ID"] && $arOrder["USER_ID"] &&
                   $visitor_info = self::getVisitorInfo($arOrder["USER_ID"])
                )
                  {
                    $visitor_uid = $arOrder["USER_ID"];
                  }
                $guest_uid = self::getUid($visitor_uid);
                $phone_name = COption::GetOptionString(self::$MODULE_ID, "phone_code", '');

                if ($phone_name && isset($_POST[$phone_name]))
                  {
                    $visitor_info["phone"] = $_POST[$phone_name];
                  }

                $tracker = new ConveadTracker($api_key, SITE_SERVER_NAME, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);

                $items = array();
                $orders = CSaleBasket::GetList(array(), array(
                   "ORDER_ID" => $arOrder["ID"]
                ), false, false, array());
                $i = 0;
                while ($order = $orders->Fetch())
                  {
                    $item["product_id"] =  $order["PRODUCT_ID"];
                    $item["qnt"] = $order["QUANTITY"];
                    $item["price"] = $order["PRICE"];
                    $items[$i . ""] = $item;
                    $i++;
                  }
                if (!empty($items))
                  {
                    $price = $arOrder["PRICE"] - (isset($arOrder["PRICE_DELIVERY"]) ? $arOrder["PRICE_DELIVERY"] : 0);
                    $result = $tracker->eventOrder($ID, $price, $items);
                  }
              }
          }
        return true;
      }

    static function view() {
      return true;
      $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
      if (!$api_key)
        return;

      global $USER;
      global $APPLICATION;

      $visitor_info = false;
      $visitor_uid = false;
      if ($USER->GetID() && $USER->GetID() > 0 && $visitor_info = self::getVisitorInfo($USER->GetID())) {
        $visitor_uid = $USER->GetID();
      }
      $guest_uid = self::getUid($visitor_uid);
      $title = $APPLICATION->GetTitle();
      $url = $APPLICATION->GetCurUri();
      if (self::endsWith($url, "ajax.php?UPDATE_STATE")) {
        return;
      } elseif (self::startsWith($url, "/bitrix/admin/")) {
        return;
      } elseif (self::startsWith($url, "/admin/")) {
        return;
      } elseif (self::contains($url, "/bitrix/tools")) {
        return;
      } elseif (self::contains($url, "bitrix/tools/autosave.php?bxsender=core_autosave")) {
        return;
      }

      $tracker = new ConveadTracker($api_key, SITE_SERVER_NAME, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);

      $result = $tracker->view($url, $title);

      return true;
    }

    static function HeadScript($api_key)
    {
        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if (!$api_key)
          return;

        global $USER;
        global $APPLICATION;

        $url = $APPLICATION->GetCurUri();
        if (self::startsWith($url, "/bitrix/admin/")) {
          return;
        }

        $visitor_info = false;
        $visitor_uid = false;
        if ($USER && $USER->GetID() && $USER->GetID() > 0 && $visitor_info = self::getVisitorInfo($USER->GetID())) {
          $visitor_uid = $USER->GetID();
        }
        $guest_uid = self::getUid($visitor_uid);
        $vi = "";
        if ($visitor_info) {
          foreach ($visitor_info as $key => $val) {
            $vi.="\n" . $key . ": '" . $val . "',";
          }

          $vi = substr($vi, 1, strlen($vi) - 2);
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
                    
                    (function(w,d,c){w[c]=w[c]||function(){(w[c].q=w[c].q||[]).push(arguments)};var ts = (+new Date()/86400000|0)*86400;var s = d.createElement('script');s.type = 'text/javascript';s.async = true;s.src = 'http://tracker.convead.io/widgets/'+ts+'/widget-$api_key.js';var x = d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s, x);})(window,document,'convead');
                    </script>
                    <!-- /Convead Widget -->";
        if(isset($_SESSION["CONVEAD_PRODUCT_ID"])){

          $head1 = "<!-- Convead view product -->
                    <script>
                    var callback = function(event) { 
                      convead('event', 'view_product', {
                            product_id: ".$_SESSION["CONVEAD_PRODUCT_ID"].",
                            product_name: '".$_SESSION["CONVEAD_PRODUCT_NAME"]."',
                            product_url: '".$_SESSION["CONVEAD_PRODUCT_URL"]."'
                          });
                        
                    };

                    callback(\"onreadystatechange\");
                    
                    </script>
                    <!-- /Convead view product -->";

          $_SESSION["CONVEAD_PRODUCT_ID"] = null;
          $_SESSION["CONVEAD_PRODUCT_NAME"] = null;
          $_SESSION["CONVEAD_PRODUCT_URL"] = null;
          unset($_SESSION["CONVEAD_PRODUCT_ID"]);
          unset($_SESSION["CONVEAD_PRODUCT_NAME"]);
          unset($_SESSION["CONVEAD_PRODUCT_URL"]);
        }
        return $head.$head1;
    }

    static function head()
      {
        $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
        if (!$api_key)
          return;
        global $APPLICATION,$USER;
        $url = $APPLICATION->GetCurUri();
        if (self::endsWith($url, "ajax.php?UPDATE_STATE")) {
          return;
        } elseif (self::startsWith($url, "/bitrix/admin/")) {
          return;
        } elseif (self::startsWith($url, "/admin/")) {
          return;
        } elseif (self::contains($url, "/bitrix/tools")) {
          return;
        } elseif (self::contains($url, "bitrix/tools/autosave.php?bxsender=core_autosave")) {
          return;
        }

        $visitor_info = false;
        $visitor_uid = false;
        if ($USER && $USER->GetID() && $USER->GetID() > 0 && $visitor_info = self::getVisitorInfo($USER->GetID())) {
          $visitor_uid = $USER->GetID();
        }
        $guest_uid = self::getUid($visitor_uid);


        if (CHTMLPagesCache::IsOn())
        {
            $frame = new \Bitrix\Main\Page\FrameHelper("platina_conveadtracker");
            $frame->begin();
            $actionType = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_ACTION_TYPE");
            if (true/*$actionType == "get_dynamic"*/)
            {
                echo self::HeadScript($api_key);
            }
            $frame->beginStub();
            $frame->end();
        }
        else
        {
            global $APPLICATION;
            $APPLICATION->AddHeadString(self::HeadScript($api_key), false, true);
        }

        @session_start();
        $_SESSION["VIEWED_PRODUCT"]=0;
        unset($_SESSION["VIEWED_ENABLE"]);

        return true;
    }

    static function productViewCustom($id, $arFields) {

      if($arFields["PRODUCT_ID"])
        $arResult["PRODUCT_ID"] = $arFields["PRODUCT_ID"];
      else if($id["PRODUCT_ID"])
        $arResult["PRODUCT_ID"] = $id["PRODUCT_ID"];
      else
        return true;

      if (self::contains($_SERVER["HTTP_USER_AGENT"], "facebook.com")) {
        return;
      }

      $api_key = COption::GetOptionString(self::$MODULE_ID, "tracker_code", '');
      if (!$api_key)
        return;

      global $APPLICATION;
      global $USER;

      $visitor_uid = false;
      if(!$user_id)
        $user_id = $USER->GetID();

      $visitor_info = false;
      if ($user_id && $visitor_info = self::getVisitorInfo($user_id)) {
        $visitor_uid = (int) $user_id;
      }
      $guest_uid = self::getUid($visitor_uid);
      $tracker = new ConveadTracker($api_key, SITE_SERVER_NAME, $guest_uid, $visitor_uid, $visitor_info, false, SITE_SERVER_NAME);

      $arProduct = CCatalogProduct::GetByIDEx($arResult["PRODUCT_ID"]);

      $product_id = $arResult["PRODUCT_ID"];
      $product_name = str_replace("'", '&#039;', $arProduct["NAME"]);
      $product_url = "http://" . SITE_SERVER_NAME . $arProduct["DETAIL_PAGE_URL"];

      $result = $tracker->eventProductView($product_id, $product_name, $product_url);

      return true;
    }

    private static function startsWith($haystack, $needle) {
      return $needle === "" || strpos($haystack, $needle) === 0;
    }

    private static function endsWith($haystack, $needle) {
      return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

    private static function contains($haystack, $needle) {
      return $needle === "" || strpos($haystack, $needle) !== false;
    }

  }
