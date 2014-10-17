<?php

/**
 * ����� ��� ������ � post ���������
 */
class Browser {

    protected $config = array();
    public $error = false;

    public function __initialize() {
        $this->resetConfig();
    }

    public function setopt($const, $val) {
        $this->settings[$const] = $val;
    }

    public function resetConfig() {
        $this->referer = false;
        $this->useragent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko/20100101 Firefox/32.0";
        $this->cookie = false;
        $this->userpwd = false;

        $this->timeout = 5;

        $this->proxy = false;
        $this->proxyuserpwd = false;

        $this->followlocation = false;
        $this->maxsize = 0;
        $this->maxredirs = 5;

        $this->encode = false;

        $this->settings = array();
    }

    public function postToString($post) {
        $result = "";
        $i = 0;
        foreach ($post as $varname => $varval) {
            $result .= ($i > 0 ? "&" : "") . urlencode($varname) . "=" . urlencode($varval);
            $i++;
        }

        return $result;
    }

    public function postEncode($post) {
        $result = array();
        foreach ($post as $varname => $varval) {
            $result[urlencode($varname)] = urlencode($varval);
        }

        return $result;
    }

    public function get($url, $post = false) {
        $curl = curl_init($url);


        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1:8888');

        if ($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        } else {
            curl_setopt($curl, CURLOPT_POST, false);
        }


        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8", "Accept:application/json, text/javascript, */*; q=0.01"));

        curl_exec($curl);

        $this->error = curl_error($curl);

        if ($this->error) {

            return $this->error;
        }

        curl_close($curl);

        return true;
    }

}
