<?php

/**
 * Класс для работы с сервисом convead.io
 */
class ConveadTracker {

    private $brovser;
    private $api_key;
    private $guest_uid;
    private $visitor_info = false;
    private $visitor_uid = false;
    private $referer = false;
    private $api_page = "http://tracker.convead.io/watch/event";
    private $url = false;

    /**
     * 
     * @param type $api_key
     * @param type $guest_uid
     * @param type $visitor_uid
     * @param type $visitor_info структура с параметрами текущего визитора (все параметры опциональные) следующего вида:
      {
      first_name: 'Name',
      last_name: 'Surname',
      email: 'email',
      phone: '1-111-11-11-11',
      date_of_birth: '1984-06-16',
      gender: 'male',
      language: 'ru',
      custom_field_1: 'custom value 1',
      custom_field_2: 'custom value 2',
      ...
      }
     * @param type $referer
     */
    public function __construct($api_key, $guest_uid, $visitor_uid = false, $visitor_info = false, $referer = false, $url = false) {
        if (!class_exists('Browser')) {
            require __DIR__ . '/Browser.php';
        }

        $this->brovser = new Browser();
        $this->api_key = $api_key;
        $this->guest_uid = $guest_uid;
        $this->visitor_info = $visitor_info;
        if (!$visitor_uid)
            $this->visitor_uid = "";
        else
            $this->visitor_uid = $visitor_uid;
        $this->referer = $referer;
        $this->url = $url;
    }

    private function getDefaultPost() {
        $post = array();
        $post["app_key"] = $this->api_key;
        $post["guest_uid"] = $this->guest_uid;
        $post["visitor_uid"] = $this->visitor_uid;
        $this->referrer && $post["referrer"] = $this->referrer;
        $this->visitor_info && $post["visitor_info"] = $this->visitor_info;
        if ($this->url) {
            $post["url"] = "http://" . $this->url;
            $post["domain"] = $this->url;
            $post["host"] = $this->url;
            $post["path"] = "http://" . $this->url;
        }
        return $post;
    }

    /**
     * 
     * @param type $product_id ID товара в магазине (такой же, как в XML-фиде Яндекс.Маркет/Google Merchant)
     * @param type $product_name наименование товара
     * @param type $product_url постоянный URL товара
     */
    public function eventProductView($product_id, $product_name = false, $product_url = false) {
        $post = $this->getDefaultPost();
        $post["type"] = "view_product";
        $post["properties"]["product_id"] = $product_id;
        $product_name && $post["properties"]["product_name"] = $product_name;
        $product_url && $post["properties"]["product_url"] = $product_url;
        error_reporting(E_ALL);
        $post = $this->json_encode($post);
        $this->putLog($post);
        if ($this->brovser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->brovser->error;
    }

    /**
     * 
     * @param type $product_id - ID товара в магазине (такой же, как в XML-фиде Яндекс.Маркет/Google Merchant)
     * @param type $qnt количество ед. добавляемого товара
     * @param type $product_name наименование товара
     * @param type $product_url постоянный URL товара
     * @param type $price стоимость 1 ед. добавляемого товара
     * @return boolean
     */
    public function eventAddToCart($product_id, $qnt, $product_name = false, $product_url = false, $price = false) {
        $post = $this->getDefaultPost();
        $post["type"] = "add_to_cart";
        $post["properties"]["product_id"] = $product_id;
        $post["properties"]["qnt"] = $qnt;
        $product_name && $post["properties"]["product_name"] = $product_name;
        $product_url && $post["properties"]["product_url"] = $product_url;
        $price && $post["properties"]["price"] = $price;

        $post = $this->json_encode($post);
        $this->putLog($post);
        if ($this->brovser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->brovser->error;
    }

    /**
     * 
     * @param type $product_id ID товара в магазине (такой же, как в XML-фиде Яндекс.Маркет/Google Merchant)
     * @param type $qnt количество ед. добавляемого товара
     * @return boolean
     */
    public function eventRemoveFromCart($product_id, $qnt) {
        $post = $this->getDefaultPost();
        $post["type"] = "remove_from_cart";
        $post["properties"]["product_id"] = $product_id;
        $post["properties"]["qnt"] = $qnt;

        $post = $this->json_encode($post);
        $this->putLog($post);
        if ($this->brovser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->brovser->error;
    }

    /**
     * 
     * @param type $order_id - ID заказа в интернет-магазине
     * @param type $revenue - общая сумма заказа
     * @param type $order_array JSON-структура вида:
      [
      {id: <product_id>, qnt: <product_count>, price: <product_price>},
      {...}
      ]
     * @return boolean
     */
    public function eventOrder($order_id, $revenue = false, $order_array = false) {
        $post = $this->getDefaultPost();
        $post["type"] = "purchase";
        $properties = array();
        $properties["order_id"] = $order_id;

        $revenue && $properties["revenue"] = $revenue;
        $order_array && $properties["items"] = $order_array;

        $post["properties"] = $properties;
        //unset($post["domain"]);
        unset($post["url"]);
        unset($post["host"]);
        unset($post["path"]);
        $post = $this->json_encode($post);
        $this->putLog($post);

        if ($this->brovser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->brovser->error;
    }

    public function eventUpdateCart($order_array) {
        $post = $this->getDefaultPost();
        $post["type"] = "update_cart";
        $properties = array();

        $properties["items"] = $order_array;

        $post["properties"] = $properties;

        $post = $this->json_encode($post);
        $this->putLog($post);

        if ($this->brovser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->brovser->error;
    }

    public function view($url, $title) {
        $post = $this->getDefaultPost();
        $post["type"] = "link";
        $post["title"] = $title;
        $post["url"] = "http://" . $this->url . $url;
        $post["path"] = $url;

        $post = $this->json_encode($post);

        $this->putLog($post);

        if ($this->brovser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->brovser->error;
    }

    private function putLog($message) {
        $message = "\n" . date("Y.m.d H:i:s") . $message;
        $filename = dirname(__FILE__) . "/log.log";
        file_put_contents($filename, $message, FILE_APPEND);
    }

    private function json_encode($text) {
        if (LANG_CHARSET == "windows-1251") {
            return json_encode($this->json_fix($text));
        } else {
            return json_encode($text);
        }
    }

    private function json_fix($data) {
        # Process arrays
        if (is_array($data)) {
            $new = array();
            foreach ($data as $k => $v) {
                $new[$this->json_fix($k)] = $this->json_fix($v);
            }
            $data = $new;
        }
        # Process objects
        else if (is_object($data)) {
            $datas = get_object_vars($data);
            foreach ($datas as $m => $v) {
                $data->$m = $this->json_fix($v);
            }
        }
        # Process strings
        else if (is_string($data)) {
            $data = iconv('cp1251', 'utf-8', $data);
        }
        return $data;
    }

}
