<?php

/**
 * ����� ��� ������ � �������� convead.io
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
     * @param type $visitor_info ��������� � ����������� �������� �������� (��� ��������� ������������) ���������� ����:
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
        require __DIR__ . '/Browser.php';
        $this->brovser = new Browser();
        $this->api_key = $api_key;
        $this->guest_uid = $guest_uid;
        $this->visitor_info = $visitor_info;
        $this->visitor_uid = $visitor_uid;
        $this->referer = $referer;
        $this->url = $url;
    }

    private function getDefaultPost() {
        $post = array();
        $post["app_key"] = $this->api_key;
        $post["guest_uid"] = $this->guest_uid;
        $this->visitor_uid && $post["visitor_uid"] = $this->visitor_uid;
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
     * @param type $product_id ID ������ � �������� (����� ��, ��� � XML-���� ������.������/Google Merchant)
     * @param type $product_name ������������ ������
     * @param type $product_url ���������� URL ������
     */
    public function eventProductView($product_id, $product_name = false, $product_url = false) {
        $post = $this->getDefaultPost();
        $post["type"] = "view_product";
        $post["properties"]["product_id"] = $product_id;
        $product_name && $post["properties"]["product_name"] = $product_name;
        $product_url && $post["properties"]["product_url"] = $product_url;

        $post = json_encode($post);
        
        if ($this->brovser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->brovser->error;
    }

    /**
     * 
     * @param type $product_id - ID ������ � �������� (����� ��, ��� � XML-���� ������.������/Google Merchant)
     * @param type $qnt ���������� ��. ������������ ������
     * @param type $product_name ������������ ������
     * @param type $product_url ���������� URL ������
     * @param type $price ��������� 1 ��. ������������ ������
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

        $post = json_encode($post);
        
        if ($this->brovser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->brovser->error;
    }

    /**
     * 
     * @param type $product_id ID ������ � �������� (����� ��, ��� � XML-���� ������.������/Google Merchant)
     * @param type $qnt ���������� ��. ������������ ������
     * @return boolean
     */
    public function eventRemoveFromCart($product_id, $qnt) {
        $post = $this->getDefaultPost();
        $post["type"] = "remove_from_cart";
        $post["properties"]["product_id"] = $product_id;
        $post["properties"]["qnt"] = $qnt;

        $post = json_encode($post);

        if ($this->brovser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->brovser->error;
    }

    /**
     * 
     * @param type $order_id - ID ������ � ��������-��������
     * @param type $revenue - ����� ����� ������
     * @param type $order_array JSON-��������� ����:
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

        $post = json_encode($post);


        if ($this->brovser->get($this->api_page, $post) === true)
            return true;
        else
            return $this->brovser->error;
    }

}
