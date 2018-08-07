<?php

class cConveadTracker {

  static $MODULE_ID = 'platina.conveadtracker';

  /*  колбек страницы товара */
  static function productView($arResult, $user_id = false) {
    if ($arResult['ID'] != '') $arResult['PRODUCT_ID'] = $arResult['ID'];

    if (class_exists('DataManager')) return true;

    if (!CModule::includeModule('catalog')) return true;

    global $APPLICATION;
    global $USER;

    $visitor_uid = false;
    if (!$user_id and $USER) $user_id = $USER->GetID();

    $visitor_info = false;
    if ($user_id && $visitor_info = self::getVisitorInfo($user_id)) $visitor_uid = (int)$user_id;

    $guest_uid = self::getUid($visitor_uid);
    if (!$tracker = self::getTracker(false, $guest_uid, $visitor_uid, $visitor_info)) return true;

    $product_id = $arResult['PRODUCT_ID'];
    $arProduct = CCatalogProduct::GetByIDEx($product_id);
    if ($arProduct && strpos($APPLICATION->GetCurPage(), $arProduct['DETAIL_PAGE_URL']) !== false)
    {
      if (
        (function_exists('CCatalogSku::IsExistOffers') and CCatalogSku::IsExistOffers($variant_id)) // Deprecated from v15.0.2
        or
        ($tmp = CCatalogSKU::getExistOffers(array($product_id)) and !empty($tmp[$product_id])) // Avalible from v15.0.2
      )
      {
        $arOffers = CIBlockPriceTools::GetOffersArray(array('IBLOCK_ID' => $arProduct['IBLOCK_ID']), array($product_id), array(), array('ID', 'ACTIVE'), array(), 1, array(), null);
        foreach ($arOffers as $offer)
        {
          if ($offer['ACTIVE'] == 'Y')
          {
            $product_id = $offer['ID'];
            break;
          }
        }
      }

      $_SESSION['CONVEAD_PRODUCT_ID'] = $product_id;
      $_SESSION['CONVEAD_PRODUCT_NAME'] = $arProduct['NAME'];
      $_SESSION['CONVEAD_PRODUCT_URL'] = self::getDetailPageUrl($arProduct);
      if ($_SESSION['LAST_VIEW_ID'] == $product_id) return true;
      else
      {
        $_SESSION['LAST_VIEW_ID'] = $product_id;
        return true;
      }
    }
  }

  /* колбек обновления товаров корзины для новых версий */
  static function newEventUpdateCart($basket) {
    // проверяем, что не включена поддержка старых событий
    if (COption::GetOptionString('sale', 'expiration_processing_events') == 'Y') return true;
    $items = self::getItemsByProperty(array(
          'FUSER_ID' => $basket->getFUserId(),
          'LID' => SITE_ID,
          'ORDER_ID' => 'NULL',
          'DELAY' => 'N',
          'CAN_BUY' => 'Y'
        )
    );
    self::sendUpdateCart($items);
    return true;
  }

  /* колбек обновления количества товаров корзины для новых версий */
  static function newEventSetQtyCart($basketItem, $field, $value) {
    // проверяем, что не включена поддержка старых событий
    if (COption::GetOptionString('sale', 'expiration_processing_events') == 'Y') return true;
    if ($field == 'QUANTITY' and isset($_REQUEST['action']) and $_REQUEST['action'] == 'recalculate') {
      $items = self::getItemsByProperty(array(
          'FUSER_ID' => $basketItem->getCollection()->getFUserId(),
          'LID' => SITE_ID,
          'ORDER_ID' => 'NULL',
          'DELAY' => 'N',
          'CAN_BUY' => 'Y'
        )
      );
      // исправляем данные состава заказа т.к. они передаются до их обновления
      foreach($items as $k=>$item) if ($item['product_id'] == $basketItem->getProductId()) $items[$k]['qnt'] = $value;
      self::sendUpdateCart($items);
    }
    return true;
  }

  /* колбек обновления корзины */
  static function updateCart($id, $arFields = false) {
    if (COption::GetOptionString('sale', 'expiration_processing_events') == 'N') return true;
    if (!CModule::includeModule('catalog') || !class_exists('CCatalogSku')) return true;
    if ($arFields && !isset($arFields['PRODUCT_ID']) && !isset($arFields['DELAY'])) return true;
    if ($arFields && isset($arFields['ORDER_ID'])) return true; // покупка
    $basket = CSaleBasket::GetByID($id);
    $items = self::getItemsByProperty(array(
        'FUSER_ID' => $basket['FUSER_ID'],
        'LID' => SITE_ID,
        'ORDER_ID' => 'NULL',
        'DELAY' => 'N',
        'CAN_BUY' => 'Y'
      )
    );
    self::sendUpdateCart($items);
    return true;
  }
  
  /* колбек покупки и изменения статуса заказа для новых версий */
  static function newEventOrderChange($event) {
    $order = $event->getParameter("ENTITY");
    $is_new = $event->getParameter("IS_NEW");
    $order_data = self::getOrderDataFromObject($order);
    if (!$order_data) return true;
    if ($is_new) {
      self::sendPurchase($order_data->order_id);
      return true;
    }
    if ($order_data->cancelled == 'Y') {
      self::orderDelete($order_data->lid, $order_data->order_id);
      return true;
    }
    if (!($tracker = self::getTracker($order_data->lid))) return true;
    $tracker->webHookOrderUpdate($order_data->order_id, $order_data->state, $order_data->revenue, $order_data->items);
    return true;
  }

  /* колбек покупки и изменения заказа для старых версий */
  static function order($order_id, $fuserID, $order, $is_new = null) {
    $order_data = self::getOrderData($order_id);
    if (!$order_data) return true;
    if ($is_new === null || $is_new === true) {
      self::sendPurchase($order_data->order_id);
      return true;
    }
    if (!($tracker = self::getTracker($order_data->lid))) return true;
    $tracker->webHookOrderUpdate($order_data->order_id, $order_data->state, $order_data->revenue, $order_data->items);
    return true;
  }

  /* колбек покупки в один клик */
  static function orderOneClick($order_id, $order, $params) {
    if (\Bitrix\Main\Loader::includeModule('platina.conveadtracker') && class_exists('\cConveadTracker') && is_callable(array('\cConveadTracker', 'order'))) {
      self::sendPurchase($order_id);
    }
    return true;
  }

  /* колбек изменения статуса заказа */
  static function orderSetState($order_id, $state) {
    if (!($tracker = self::getTracker(SITE_ID))) return true;
    $state = self::switchState($state);
    $tracker->webHookOrderUpdate($order_id, $state);
    return true;
  }

  /* колбек удаления заказа */
  static function orderDelete($site_id, $order_id) {
    if (!($tracker = self::getTracker($site_id))) return;
    return $tracker->webHookOrderUpdate($order_id, 'cancelled');
  }

  /* устаревший колбек события link */
  static function view() {
    return true;
  }

  /*  колбек для вставки основного кода widget.js */
  static function head() {
    if (!self::getCurlUri()) return true;

    if (!($app_key = self::getAppKey())) return false;

    global $APPLICATION, $USER;

    $visitor_info = false;
    $visitor_uid = false;
    if ($USER and $USER->GetID() and $visitor_info = self::getVisitorInfo($USER->GetID())) $visitor_uid = $USER->GetID();
    $guest_uid = self::getUid($visitor_uid);

    if (CHTMLPagesCache::IsOn())
    {
      $frame = new \Bitrix\Main\Page\FrameHelper('platina_conveadtracker');
      $frame->begin();
      $actionType = \Bitrix\Main\Context::getCurrent()->getServer()->get('HTTP_BX_ACTION_TYPE');
      /*if ($actionType == 'get_dynamic') */echo self::getHeadScript($app_key);
      $frame->beginStub();
      $frame->end();
    }
    else
    {
      global $APPLICATION;
      $APPLICATION->AddHeadString(self::getHeadScript($app_key), false, true);
    }

    @session_start();
    $_SESSION['VIEWED_PRODUCT']=0;
    unset($_SESSION['VIEWED_ENABLE']);

    return true;
  }

  /* --- приватные методы --- */

  private static function sendPurchase($order_id) {
    $order_data = self::getOrderData($order_id);
    if (!$order_data) return false;
    
    /* блокировать повторную отправку заказа */
    if (isset($_SESSION['cnv_old_order']) && $_SESSION['cnv_old_order'] == $order_id) return false;
    $_SESSION['cnv_old_order'] = $order_id;
    
    unset($_SESSION['cnv_old_cart']);
    $visitor_info = $order_data->uid ? self::getVisitorInfo($order_data->uid) : array();
    if ($phone_name = self::getPhoneCode() and !empty($_POST[$phone_name])) $visitor_info['phone'] = $_POST[$phone_name];
    if (!$tracker = self::getTracker($order_data->lid, self::getUid($order_data->uid), $order_data->uid, $visitor_info)) return true;
    if (empty($order_data->items)) return false;
    return $tracker->eventOrder($order_data->order_id, $order_data->revenue, $order_data->items, $order_data->state);
  }

  /* получение информации о зарегистрированном пользователе */
  private static function getVisitorInfo($id) {
    if ($usr = CUser::GetByID($id)) {
      $user = $usr->Fetch();

      $visitor_info = array();
      $user['NAME'] && $visitor_info['first_name'] = $user['NAME'];
      $user['LAST_NAME'] && $visitor_info['last_name'] = $user['LAST_NAME'];
      $user['EMAIL'] && $visitor_info['email'] = $user['EMAIL'];
      $user['PERSONAL_PHONE'] && $visitor_info['phone'] = $user['PERSONAL_PHONE'];
      
      if ($user['PERSONAL_BIRTHDAY']) {
        $dates = array_reverse(explode('.', $user['PERSONAL_BIRTHDAY']));
        $visitor_info['date_of_birth'] = implode('-', $dates);
      }
      
      $user['PERSONAL_GENDER'] && $visitor_info['gender'] = ($user['PERSONAL_GENDER'] == 'M' ? 'male' : 'female');

      if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/helper/ConveadHelper.php'))
      {
        include_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/helper/ConveadHelper.php';
        if (method_exists('ConveadHelper', 'GetAddInfo')) $visitor_info = ConveadHelper::GetAddInfo($id,$visitor_info,$user);
      }

      return $visitor_info;
    } else return array();
  }

  /* вставка widget.js */
  private static function getHeadScript($app_key) {
    if (!$app_key) return;

    global $USER;
    global $APPLICATION;

    if (self::startsWith($APPLICATION->GetCurUri(), '/bitrix/admin/')) return;

    $visitor_info = array();
    $visitor_uid = false;
    if ($USER and $USER->GetID() and $visitor_info = self::getVisitorInfo($USER->GetID())) $visitor_uid = $USER->GetID();

    $guest_uid = self::getUid($visitor_uid);

    if (isset($_SESSION['CONVEAD_PRODUCT_ID'])) {
      $product_id = $_SESSION['CONVEAD_PRODUCT_ID'];
      $name = htmlentities($_SESSION['CONVEAD_PRODUCT_NAME'], ENT_QUOTES);
      $url = $_SESSION['CONVEAD_PRODUCT_URL'];

      unset($_SESSION['CONVEAD_PRODUCT_ID']);
      
      $js_view_product = "convead('event', 'view_product', {product_id: '{$product_id}', product_name: '{$name}', product_url: '{$url}'});";
    }
    else $js_view_product = '';
    
    $json_a = array();
    foreach($visitor_info as $key=>$value) $json_a[] = $key.':"'.htmlspecialchars($value, ENT_QUOTES, SITE_CHARSET).'"';

    $js = "
<!-- Convead Widget -->
<script>
  window.ConveadSettings = {
    " . ($visitor_uid ? "visitor_uid: '$visitor_uid'," : '') . "
    " . ($visitor_info ? "visitor_info: {".implode(', ', $json_a)."}," : '') . "
    app_key: '$app_key'
  };

  (function(w,d,c){w[c]=w[c]||function(){(w[c].q=w[c].q||[]).push(arguments)};var ts = (+new Date()/86400000|0)*86400;var s = d.createElement('script');s.type = 'text/javascript';s.async = true;s.charset = 'utf-8';s.src = 'https://tracker.convead.io/widgets/'+ts+'/widget-$app_key.js';var x = d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s, x);})(window,document,'convead');
  $js_view_product
</script>
<!-- /Convead Widget -->";

    return $js;
  }

  /* получение объекта заказа */
  private static function getOrderDataFromObject($order) {
    $id = $order->getField('ID');
    $items = self::getItemsByOrderId($id);
    $ret = new stdClass();
    $ret->order_id = $id;
    $ret->revenue = $order->getField('PRICE');
    $ret->items = $items;
    $ret->lid = $order->getField('LID');
    $ret->uid = $order->getField('USER_ID');
    $ret->cancelled = $order->getField('CANCELED');
    $ret->state = self::switchState($order->getField('STATUS_ID'));
    return $ret;
  }

  /* получение объекта заказа */
  private static function getOrderData($order_id) {
    $order = CSaleOrder::GetByID(intval($order_id));
    if (!$order['ID']) return false;
    $items = self::getItemsByOrderId($order['ID']);

    /* сделана пкупка в один клик, но состав заказа отсутствует */
    if (count($items) == 0 and !empty($_REQUEST['ELEMENT_ID']) and $pr_price = CCatalogProduct::GetOptimalPrice($_REQUEST['ELEMENT_ID'])) {
      $items = array(
        array(
          'product_id' => $_REQUEST['ELEMENT_ID'],
          'qnt' => 1,
          'price' => $pr_price['DISCOUNT_PRICE']
        )
      );
    }

    $ret = new stdClass();
    $ret->order_id = $order['ID'];
    $ret->revenue = $order['PRICE'] - (isset($order['PRICE_DELIVERY']) ? $order['PRICE_DELIVERY'] : 0);
    $ret->items = $items;
    $ret->lid = $order['LID'];
    $ret->uid = $order['USER_ID'];
    $ret->cancelled = $order['CANCELED'];
    $ret->state = self::switchState($order['STATUS_ID']);
    return $ret;
  }

  /* замена статусов на предустановленные */
  private static function switchState($state) {
    switch ($state) {
      case 'N':
        $state = 'new';
        break;
    }
    return $state;
  }

  private static function sendUpdateCart($items = array()) {
    global $USER;
    $user_id = false;
    if($USER and $USER->GetID()) $user_id = $USER->GetID();

    $visitor_uid = false;
    $visitor_info = false;
    $visitor_info = self::getVisitorInfo($user_id);
    if ($visitor_info || $user_id !== FALSE) $visitor_uid = $user_id;

    $guest_uid = self::getUid($visitor_uid);

    if (!$guest_uid and !$visitor_uid) return false;

    // исключить повторный вызов события
    if (isset($_SESSION['cnv_old_cart']) and $_SESSION['cnv_old_cart'] == $items) return true;
    $_SESSION['cnv_old_cart'] = $items;

    if ($tracker = self::getTracker(false, $guest_uid, $visitor_uid, $visitor_info)) return $tracker->eventUpdateCart($items);
    else return false;
  }

  private static function getItemsByOrderId($order_id) {
    $property = array(
      'ORDER_ID' => $order_id,
      'CAN_BUY' => 'Y'
    );
    $orders = CSaleBasket::GetList(array(), $property, false, false, array());
    $items = array();
    while ($order = $orders->Fetch()) {
      $item['product_id'] = $order['PRODUCT_ID'];
      $item['qnt'] = $order['QUANTITY'];
      $item['price'] = $order['PRICE'];
      $items[] = $item;
    }
    return $items;
  }

  private static function getItemsByProperty($property) {
    $dbBasketItems = CSaleBasket::GetList(array(), $property, false, false, array(
     'ID',
     'PRODUCT_ID',
     'QUANTITY',
     'PRICE',
     'DISCOUNT_PRICE',
     'WEIGHT'
    ));

    $allSum = 0;
    $allWeight = 0;
    $arItems = array();
    while ($arBasketItems = $dbBasketItems->Fetch()) {   
      $allSum += ($arItem["PRICE"] * $arItem["QUANTITY"]);
      $allWeight += ($arItem["WEIGHT"] * $arItem["QUANTITY"]);
      $arItems[] = $arBasketItems;
    } 
    $arOrder = array(
      'SITE_ID' => SITE_ID,
      'USER_ID' => $GLOBALS["USER"]->GetID(),
      'ORDER_PRICE' => $allSum,
      'ORDER_WEIGHT' => $allWeight,
      'BASKET_ITEMS' => $arItems
    );
    $arOptions = array(
      'COUNT_DISCOUNT_4_ALL_QUANTITY' => 'Y',
    );
    $arErrors = array();
    CSaleDiscount::DoProcessOrder($arOrder, $arOptions, $arErrors);
    
    $items = array();
    foreach ($arOrder['BASKET_ITEMS'] as $orderItem) {
      $items[] = array(
      'product_id' => $orderItem['PRODUCT_ID'],
      'qnt' => $orderItem['QUANTITY'],
      'price' => $orderItem['PRICE']
      );
    }
    return $items;
  }

  private static function getUid($visitor_uid) {
    return (!$visitor_uid and !empty($_COOKIE['convead_guest_uid'])) ? $_COOKIE['convead_guest_uid'] : false;
  }

  private static function getCurlUri() {
    global $APPLICATION;
    $url = $APPLICATION->GetCurUri();
    if (self::endsWith($url, 'ajax.php?UPDATE_STATE') or (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') or self::startsWith($url, '/bitrix/') or self::startsWith($url, '/ajax/') or self::startsWith($url, '/admin/') or !empty($_SERVER['HTTP_BX_AJAX'])) return false;
    else return $url;
  }

  private static function getPhoneCode() {
    if ($phone = COption::GetOptionString(self::$MODULE_ID, 'phone_code_'.SITE_ID, '')) return $phone;
    elseif ($single_phone = COption::GetOptionString(self::$MODULE_ID, 'phone_code', '')) return $single_phone;
    else return false;
  }

  private static function getAppKey($site_id = false) {
    if ($site_id === false) $site_id = SITE_ID;
    if ($app_key = COption::GetOptionString(self::$MODULE_ID, 'tracker_code_'.$site_id, '')) return $app_key;
    elseif ($single_app_key = COption::GetOptionString(self::$MODULE_ID, 'tracker_code', '')) return $single_app_key;
    else return false;
  }

  private static function getTracker($site_id = false, $guest_uid = false, $visitor_uid = false, $visitor_info = false) {
    if (!($app_key = self::getAppKey($site_id))) return false;
    $tracker = new ConveadTracker($app_key, self::getDomain(), $guest_uid, $visitor_uid, $visitor_info, false, self::getDomain());
    $tracker->charset = SITE_CHARSET;
    return $tracker;
  }

  private static function getDomain() {
    return $_SERVER['SERVER_NAME'];
  }

  private static function getDetailPageUrl($page) {
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