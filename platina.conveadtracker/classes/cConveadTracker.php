<?php

class cConveadTracker {

  static $MODULE_ID = 'platina.conveadtracker';

  static function getVisitorInfo($id) {
    if ($usr = CUser::GetByID($id)) {
      $user = $usr->Fetch();

      $visitor_info = array();
      $user['NAME'] && $visitor_info['first_name'] = $user['NAME'];
      $user['LAST_NAME'] && $visitor_info['last_name'] = $user['LAST_NAME'];
      $user['EMAIL'] && $visitor_info['email'] = $user['EMAIL'];
      $user['PERSONAL_PHONE'] && $visitor_info['phone'] = $user['PERSONAL_PHONE'];
      $user['PERSONAL_BIRTHDAY'] && $visitor_info['date_of_birth'] = date('Y-m-d', $user['PERSONAL_BIRTHDAY']);
      $user['PERSONAL_GENDER'] && $visitor_info['gender'] = ($user['PERSONAL_GENDER'] == 'M' ? 'male' : 'female');

      if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/helper/ConveadHelper.php'))
      {
        include_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/helper/ConveadHelper.php';
        if (method_exists('ConveadHelper', 'GetAddInfo')) $visitor_info = ConveadHelper::GetAddInfo($id,$visitor_info,$user);
      }

      return $visitor_info;
    } else return false;
  }

  static function productView($arResult, $user_id = false) {
    if ($arResult['ID'] != '') $arResult['PRODUCT_ID'] = $arResult['ID'];

    if (class_exists('DataManager')) return false;

    if (self::contains($_SERVER['HTTP_USER_AGENT'], 'facebook.com')) return;

    if (!CModule::includeModule('catalog')) return;

    global $APPLICATION;
    global $USER;

    $visitor_uid = false;
    if (!$user_id) $user_id = $USER->GetID();

    $visitor_info = false;
    if ($user_id && $visitor_info = self::getVisitorInfo($user_id)) $visitor_uid = (int)$user_id;

    $guest_uid = self::getUid($visitor_uid);
    if (!$tracker = self::getTracker(false, $guest_uid, $visitor_uid, $visitor_info)) return true;

    $arProduct = CCatalogProduct::GetByIDEx($arResult['PRODUCT_ID']);
    if ($arProduct && strpos($APPLICATION->GetCurPage(), $arProduct['DETAIL_PAGE_URL']) !== false)
    {
      if (CCatalogSku::IsExistOffers($arResult['PRODUCT_ID']))
      {
        $arOffers =
          CIBlockPriceTools::GetOffersArray(array('IBLOCK_ID' => $arProduct['IBLOCK_ID']), array($arResult['PRODUCT_ID']), array(), array(
              'ID',
              'ACTIVE'
            )
          );
        foreach ($arOffers as $array)
        {
          if ($array['ACTIVE'] == 'Y')
          {
            $arResult['PRODUCT_ID'] = $array['ID'];
            break;
          }
        }
      }

      $product_id = $arResult['PRODUCT_ID'];
      $product_name = $arProduct['NAME'];
      $product_url = self::getDelautPageUrl($arProduct);

      $_SESSION['CONVEAD_PRODUCT_ID'] = $arResult['PRODUCT_ID'];
      $_SESSION['CONVEAD_PRODUCT_NAME'] = str_replace('"', '&#039;', $arProduct['NAME']);
      $_SESSION['CONVEAD_PRODUCT_URL'] = $product_url;

      if ($_SESSION['LAST_VIEW_ID'] == $arResult['PRODUCT_ID']) return false;
      else
      {
        $_SESSION['LAST_VIEW_ID'] = $arResult['PRODUCT_ID'];
        return true;
      }

      return true;
    }
  }

  static function newEventUpdateCart($basket) {
    if (COption::GetOptionString('sale', 'expiration_processing_events') == 'Y') return true;

    $items = self::getItemsByProperty(array(
          'FUSER_ID' => $basket->getFUserId(),
          //'LID' => SITE_ID,
          'ORDER_ID' => 'NULL',
          'DELAY' => 'N',
          'CAN_BUY' => 'Y'
        )
    );

    return self::sendUpdateCart($items);
  }

  static function newEventSetQtyCart($basketItem, $field, $value) {
    if (COption::GetOptionString('sale', 'expiration_processing_events') == 'Y') return true;

    if ($field == 'QUANTITY' and isset($_REQUEST['action']) and $_REQUEST['action'] == 'recalculate') {
      $items = self::getItemsByProperty(array(
          'FUSER_ID' => $basketItem->getCollection()->getFUserId(),
          //'LID' => SITE_ID,
          'ORDER_ID' => 'NULL',
          'DELAY' => 'N',
          'CAN_BUY' => 'Y'
        )
      );

      # fix receive data before updating
      foreach($items as $k=>$item) if ($item['product_id'] == $basketItem->getProductId()) $items[$k]['qnt'] = $value;

      return self::sendUpdateCart($items);
    }
  }

  static function updateCart($id, $arFields = false) {
    if (COption::GetOptionString('sale', 'expiration_processing_events') == 'N') return;

    if (!CModule::includeModule('catalog') || !class_exists('CCatalogSku')) return false;

    if ($arFields && !isset($arFields['PRODUCT_ID']) && !isset($arFields['DELAY'])) return;//just viewving
      
    if ($arFields && isset($arFields['ORDER_ID'])) return;// purchasing

    $basket = CSaleBasket::GetByID($id);

    $items = self::getItemsByProperty(array(
        'FUSER_ID' => $basket['FUSER_ID'],
        //'LID' => SITE_ID,
        'ORDER_ID' => 'NULL',
        'DELAY' => 'N',
        'CAN_BUY' => 'Y'
      ), $id, $arFields
    );

    return self::sendUpdateCart($items);
  }
  
  static function newEventOrder($order) {
    $order_id = intval($order->getField('ID'));
    $is_new = $order->isNew();
    $order = CSaleOrder::GetByID($order_id);
    $state = $order['STATUS_ID'];
    $site_id = $order['LID'];
    
    if ($is_new) return self::order($order_id);
    
    if ($order['CANCELED'] == 'Y') return self::orderDelete($site_id, $order_id);
    
    return self::orderSetState($site_id, $order_id, $state);
  }

  static function order($ID, $fuserID, $strLang, $arDiscounts) {
    $order_id = intval($ID);
    self::purchase($order_id);
  }

  static function orderSetState($site_id, $order_id, $state) {
    if (!($api = self::getApi($site_id))) return;
  switch ($state) {
      case 'N':
        $state = 'new';
        break;
      case 'P':
        $state = 'paid';
        break;
      case 'F':
        $state = 'shipped';
        break;
    }
    return $api->orderUpdate($order_id, $state);
  }

  static function orderDelete($site_id, $order_id) {
    if (!($api = self::getApi($site_id))) return;
    return $api->orderUpdate($order_id, 'cancelled');
  }

  static function purchase($order_id) {
    $order = CSaleOrder::GetByID(intval($order_id));

    if ($order['ID'] > 0)
    {
      $visitor_uid = false;
      $visitor_info = false;
      if ($order['USER_ID'] && $order['USER_ID'] &&
         $visitor_info = self::getVisitorInfo($order['USER_ID'])
      )
      
      $visitor_uid = $order['USER_ID'];
      $guest_uid = self::getUid($visitor_uid);
      $phone_name = self::getPhoneCode();

      if ($phone_name && !empty($_POST[$phone_name])) $visitor_info['phone'] = $_POST[$phone_name];

      if (!$tracker = self::getTracker($order['LID'], $guest_uid, $visitor_uid, $visitor_info)) return true;

      $items = self::getItemsByProperty(array(
         'ORDER_ID' => $order['ID'],
         'CAN_BUY' => 'Y'
        )
      );

      if (!empty($items))
      {
        unset($_SESSION['cnv_old_cart']);
        $price = $order['PRICE'] - (isset($order['PRICE_DELIVERY']) ? $order['PRICE_DELIVERY'] : 0);
        
        
        $result = $tracker->eventOrder($ID, $price, $items);
      }
    }
    return true;
  }

  static function view() {
    return true;

    global $USER;
    global $APPLICATION;

    $visitor_info = false;
    $visitor_uid = false;
    if ($USER->GetID() && $USER->GetID() > 0 && $visitor_info = self::getVisitorInfo($USER->GetID())) $visitor_uid = $USER->GetID();

    $guest_uid = self::getUid($visitor_uid);
    $title = $APPLICATION->GetTitle();
    if ($url = self::getCurlUri()) return true;

    if (!$tracker = self::getTracker(false, $guest_uid, $visitor_uid, $visitor_info)) return true;

    $result = $tracker->view($url, $title);

    return true;
  }

  static function HeadScript($api_key) {
    if (!($api_key = self::getAppKey())) return;

    global $USER;
    global $APPLICATION;

    $url = $APPLICATION->GetCurUri();
    if (self::startsWith($url, '/bitrix/admin/')) return;

    $visitor_info = false;
    $visitor_uid = false;
    if ($USER && $USER->GetID() && $USER->GetID() > 0 && $visitor_info = self::getVisitorInfo($USER->GetID())) $visitor_uid = $USER->GetID();

    $guest_uid = self::getUid($visitor_uid);

    if (isset($_SESSION['CONVEAD_PRODUCT_ID'])) {
      $js_view_product = "convead('event', 'view_product', {product_id: '".$_SESSION['CONVEAD_PRODUCT_ID']."', product_name: '".$_SESSION['CONVEAD_PRODUCT_NAME']."', product_url: '".$_SESSION['CONVEAD_PRODUCT_URL']."'});";
      $_SESSION['CONVEAD_PRODUCT_ID'] = null;
      unset($_SESSION['CONVEAD_PRODUCT_ID']);
    }
    else $js_view_product = '';

    $js = "
<!-- Convead Widget -->
<script>
  window.ConveadSettings = {
    " . ($visitor_uid ? "visitor_uid: '$visitor_uid'," : '') . "
    visitor_info: " . ($visitor_info ? json_encode($visitor_info) : '') . ",
    app_key: '$api_key'
  };

  (function(w,d,c){w[c]=w[c]||function(){(w[c].q=w[c].q||[]).push(arguments)};var ts = (+new Date()/86400000|0)*86400;var s = d.createElement('script');s.type = 'text/javascript';s.async = true;s.charset = 'utf-8';s.src = 'https://tracker.convead.io/widgets/'+ts+'/widget-$api_key.js';var x = d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s, x);})(window,document,'convead');
  $js_view_product
</script>
<!-- /Convead Widget -->";

    return $js;
  }

  static function head() {
    if (!self::getCurlUri()) return true;

    if (!($api_key = self::getAppKey())) return false;

    global $APPLICATION, $USER;

    $visitor_info = false;
    $visitor_uid = false;
    if ($USER && $USER->GetID() && $USER->GetID() > 0 && $visitor_info = self::getVisitorInfo($USER->GetID())) $visitor_uid = $USER->GetID();
    $guest_uid = self::getUid($visitor_uid);

    if (CHTMLPagesCache::IsOn())
    {
      $frame = new \Bitrix\Main\Page\FrameHelper('platina_conveadtracker');
      $frame->begin();
      $actionType = \Bitrix\Main\Context::getCurrent()->getServer()->get('HTTP_BX_ACTION_TYPE');
      /*if ($actionType == 'get_dynamic') */echo self::HeadScript($api_key);
      $frame->beginStub();
      $frame->end();
    }
    else
    {
      global $APPLICATION;
      $APPLICATION->AddHeadString(self::HeadScript($api_key), false, true);
    }

    @session_start();
    $_SESSION['VIEWED_PRODUCT']=0;
    unset($_SESSION['VIEWED_ENABLE']);

    return true;
  }

  static function productViewCustom($id, $arFields) {
    if ($arFields['PRODUCT_ID'])
      $arResult['PRODUCT_ID'] = $arFields['PRODUCT_ID'];
    else if($id['PRODUCT_ID'])
      $arResult['PRODUCT_ID'] = $id['PRODUCT_ID'];
    else
      return true;

    if (self::contains($_SERVER['HTTP_USER_AGENT'], 'facebook.com')) return;

    if (!CModule::includeModule('catalog')) return;

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
    if (!$tracker = self::getTracker(false, $guest_uid, $visitor_uid, $visitor_info)) return true;

    $arProduct = CCatalogProduct::GetByIDEx($arResult['PRODUCT_ID']);

    $product_id = $arResult['PRODUCT_ID'];
    $product_name = str_replace('"', '&#039;', $arProduct['NAME']);
    $product_url = 'http://' . self::getDomain() . $arProduct['DETAIL_PAGE_URL'];

    $result = $tracker->eventProductView($product_id, $product_name, $product_url);

    return true;
  }

  private function sendUpdateCart($items = array()) {
    global $USER;
    $user_id = false;
    if($USER->GetID()) $user_id = $USER->GetID();

    $visitor_uid = false;
    $visitor_info = false;
    $visitor_info = self::getVisitorInfo($user_id);
    if ($visitor_info || $user_id !== FALSE) $visitor_uid = $user_id;

    $guest_uid = self::getUid($visitor_uid);

    # fix double events
    if (isset($_SESSION['cnv_old_cart']) and $_SESSION['cnv_old_cart'] == $items) return true;
    $_SESSION['cnv_old_cart'] = $items;

    if ($tracker = self::getTracker(false, $guest_uid, $visitor_uid, $visitor_info)) return $tracker->eventUpdateCart($items);
    else return false;
  }

  private function getItemsByProperty($property, $id = false, $arFields = true) {
    $items = array();
    $orders = CSaleBasket::GetList(array(), $property, false, false, array());
    while ($order = $orders->Fetch()) {
      if (!$arFields) {//deleting
        if ($order['ID'] == $id) continue;
      }
      $item['product_id'] = $order['PRODUCT_ID'];
      $item['qnt'] = $order['QUANTITY'];
      $item['price'] = $order['PRICE'];
      $items[] = $item;
    }
    return $items;
  }

  private static function getUid($visitor_uid) {
    if ($visitor_uid) return false;
    if (!empty($_COOKIE['convead_guest_uid'])) return $_COOKIE['convead_guest_uid'];
    return false;
  }

  private static function getCurlUri() {
    global $APPLICATION;
    $url = $APPLICATION->GetCurUri();
    if (self::endsWith($url, 'ajax.php?UPDATE_STATE') or (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') or self::startsWith($url, '/bitrix/admin/') or self::startsWith($url, '/ajax/') or self::startsWith($url, '/admin/') or self::contains($url, '/bitrix/tools') or self::contains($url, 'bitrix/tools/autosave.php?bxsender=core_autosave') or !empty($_SERVER['HTTP_BX_AJAX'])) return false;
    else return $url;
  }

  private static function getPhoneCode() {
    if ($phone = COption::GetOptionString(self::$MODULE_ID, 'phone_code_'.SITE_ID, '')) return $phone;
    elseif ($single_phone = COption::GetOptionString(self::$MODULE_ID, 'phone_code', '')) return $single_phone;
    else return false;
  }

  private static function getToken() {
    $token = COption::GetOptionString(self::$MODULE_ID, 'access_token_code', '');
    return $token;
  }

  private static function getAppKey($site_id = false) {
    if ($site_id === false) $site_id = SITE_ID;
    if ($api_key = COption::GetOptionString(self::$MODULE_ID, 'tracker_code_'.$site_id, '')) return $api_key;
    elseif ($single_api_key = COption::GetOptionString(self::$MODULE_ID, 'tracker_code', '')) return $single_api_key;
    else return false;
  }

  private static function getTracker($site_id = false, $guest_uid = false, $visitor_uid = false, $visitor_info = false) {
    if (!($api_key = self::getAppKey($site_id))) return false;
    $tracker = new ConveadTracker($api_key, self::getDomain(), $guest_uid, $visitor_uid, $visitor_info, false, self::getDomain());
    $tracker->charset = SITE_CHARSET;
    return $tracker;
  }

  private static function getApi($site_id)
  {
    if (!($api_key = self::getAppKey($site_id)) || !($access_token = self::getToken())) return false;
    $api = new ConveadApi($access_token, $api_key);
    return $api;
  }

  private static function getDomain() {
    return $_SERVER['SERVER_NAME'];
  }

  private static function getDelautPageUrl($page) {
    return 'http://' . self::getDomain() . $page['DETAIL_PAGE_URL'];
  }

  private static function startsWith($haystack, $needle) {
    return $needle === '' || strpos($haystack, $needle) === 0;
  }

  private static function endsWith($haystack, $needle) {
    return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
  }

  private static function contains($haystack, $needle) {
    return $needle === '' || strpos($haystack, $needle) !== false;
  }

}
