<?php

namespace Iberent;

class Client {

    /*
     * Iberent's production API URL
     */
    const PRODUCTION_BASE_URL = 'https://api.iberent.es';

    /*
     * Iberent's test API URL
     */
    const TEST_BASE_URL = 'https://api-test.iberent.es';

    /**
     * HTTP client object
     */
    private $client;

    /*
    * url to request
    */
    protected $url;

    /**
     * @param string $accessToken
     * @param string $origin
     * @param bool $production
     */
    public function __construct($access_token, $origin = '', $production = false)
    {
        $this->client = new \Http\Client(
            [
                'Authorization' => "$access_token",
                'Origin' => "$origin",
                'User-Agent' => 'iberent-php-api_client 0.3'
            ]
        );
        $this->url = $production ? self::PRODUCTION_BASE_URL : self::TEST_BASE_URL;
    }

    /**
     * @param array $conditions
     *
     * @return array
     */
    public function countPayments($conditions = [])
    {
        //$params = $this->extractQueryParameters($conditions);
        return $this->request('GET', 'v0/payment/count.json', $conditions);
    }

    /**
     * @param array $conditions
     *
     * @return array
     */
    public function openTransaction($reference = '', $transaction = [], $order = [])
    {
        $params = array_merge(
            $transaction,
            array(
                'callback_urls' => json_encode($transaction['callback_urls'])
            ),
            array(
                'reference' => $reference,
                'order' => array_merge(
                    $order,
                    array(
                        'reference' => $reference
                    )
                )
            )
        );
        return $this->request('POST', 'v0/transaction/open.json', $params);
    }

    /**
     * @param string $transaction_id
     *
     * @return array
     */
    public function getTransaction($transaction_id)
    {
        return $this->request('GET', "v0/transaction/$transaction_id.json");
    }

    /**
     * @param array $conditions
     *
     * @return array
     */
    public function getTransactions($conditions)
    {
        return $this->request('GET', "v0/transaction/list.json", $conditions);
    }

    /**
     * @param string $type
     * @param array $options
     * @param string $ticket
     *
     * @return array
     */
    public function createWidget($type, $options = [], $ticket = null)
    {
        $params = array_merge(
            $options,
            array(
                'type' => $type,
                'ticket' => $ticket,
            )
        );
        return $this->request('POST', "v0/widget/create.json", $params);
    }

    /**
     * @param string $order_id
     *
     * @return array
     */
    public function getOrder($order_id)
    {
        return $this->request('GET', "v0/order/$order_id.json");
    }

    /**
     * @param array $conditions
     *
     * @return array
     */
    public function getOrders($conditions)
    {
        return $this->request('GET', "v0/order/list.json", $conditions);
    }

    /**
     * @param string $user_id
     *
     * @return array
     */
    public function getUser($user_id)
    {
        return $this->request('GET', "v0/user/$user_id.json");
    }

    /**
     * @param string $account_id
     *
     * @return array
     */
    public function getAccount($account_id)
    {
        return $this->request('GET', "v0/account/$account_id.json");
    }

    /**
     * @param string $method
     * @param string $path
     * @param array $params
     * @param boolean $json
     *
     * @return array
     */
    private function request($method, $path, $params = [], $json = true){
        try {
            $response = $this->client->request($method, "$this->url/$path", $params);
            if ($json) {
                $response = json_decode($response, true);
            }
        } catch (\Exception $exception) {
            $response = $exception->getMessage();
        }
        return $response;
    }

}

namespace Http;

class Client {

    /**
     * HTTP headers
     */
    private $headers;

    /**
     * @param array $headers
     */
    public function __construct($headers = [])
    {
        $this->headers = [];
        foreach ($headers as $key => $value) {
            $this->headers[] = "$key: $value";
        }
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $params
     *
     * @return string
     */
    public function request($method, $url = '', $params = [])
    {
        array_walk_recursive($params, function (&$value) { $value = \Http\Encoding::fixUTF8($value);});
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_ENCODING => 'UTF-8',
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

}

class Encoding {

    /*
     * Active transliteration characters that cannot be represented in the target charset
     */
    const ICONV_TRANSLIT = "TRANSLIT";

    /*
     * Active ignore characters that cannot be represented in the target charset
     */
    const ICONV_IGNORE = "IGNORE";

    /*
     * Performs string conversion without iconv function
     */
    const WITHOUT_ICONV = "";

    /**
     * Converting table Windows-1252 to UTF-8
     */
    protected static $win1252_to_utf8 = array(
        128 => "\xe2\x82\xac",
        130 => "\xe2\x80\x9a",
        131 => "\xc6\x92",
        132 => "\xe2\x80\x9e",
        133 => "\xe2\x80\xa6",
        134 => "\xe2\x80\xa0",
        135 => "\xe2\x80\xa1",
        136 => "\xcb\x86",
        137 => "\xe2\x80\xb0",
        138 => "\xc5\xa0",
        139 => "\xe2\x80\xb9",
        140 => "\xc5\x92",
        142 => "\xc5\xbd",
        145 => "\xe2\x80\x98",
        146 => "\xe2\x80\x99",
        147 => "\xe2\x80\x9c",
        148 => "\xe2\x80\x9d",
        149 => "\xe2\x80\xa2",
        150 => "\xe2\x80\x93",
        151 => "\xe2\x80\x94",
        152 => "\xcb\x9c",
        153 => "\xe2\x84\xa2",
        154 => "\xc5\xa1",
        155 => "\xe2\x80\xba",
        156 => "\xc5\x93",
        158 => "\xc5\xbe",
        159 => "\xc5\xb8"
    );

    /**
     * Converting table broken UTF-8 to UTF-8
     */
    protected static $broken_utf8_to_utf8 = array(
        "\xc2\x80" => "\xe2\x82\xac",
        "\xc2\x82" => "\xe2\x80\x9a",
        "\xc2\x83" => "\xc6\x92",
        "\xc2\x84" => "\xe2\x80\x9e",
        "\xc2\x85" => "\xe2\x80\xa6",
        "\xc2\x86" => "\xe2\x80\xa0",
        "\xc2\x87" => "\xe2\x80\xa1",
        "\xc2\x88" => "\xcb\x86",
        "\xc2\x89" => "\xe2\x80\xb0",
        "\xc2\x8a" => "\xc5\xa0",
        "\xc2\x8b" => "\xe2\x80\xb9",
        "\xc2\x8c" => "\xc5\x92",
        "\xc2\x8e" => "\xc5\xbd",
        "\xc2\x91" => "\xe2\x80\x98",
        "\xc2\x92" => "\xe2\x80\x99",
        "\xc2\x93" => "\xe2\x80\x9c",
        "\xc2\x94" => "\xe2\x80\x9d",
        "\xc2\x95" => "\xe2\x80\xa2",
        "\xc2\x96" => "\xe2\x80\x93",
        "\xc2\x97" => "\xe2\x80\x94",
        "\xc2\x98" => "\xcb\x9c",
        "\xc2\x99" => "\xe2\x84\xa2",
        "\xc2\x9a" => "\xc5\xa1",
        "\xc2\x9b" => "\xe2\x80\xba",
        "\xc2\x9c" => "\xc5\x93",
        "\xc2\x9e" => "\xc5\xbe",
        "\xc2\x9f" => "\xc5\xb8"
    );

    /**
     * Converting table UTF-8 to Windows-1252
     */
    protected static $utf8_to_win1252 = array(
        "\xe2\x82\xac" => "\x80",
        "\xe2\x80\x9a" => "\x82",
        "\xc6\x92"     => "\x83",
        "\xe2\x80\x9e" => "\x84",
        "\xe2\x80\xa6" => "\x85",
        "\xe2\x80\xa0" => "\x86",
        "\xe2\x80\xa1" => "\x87",
        "\xcb\x86"     => "\x88",
        "\xe2\x80\xb0" => "\x89",
        "\xc5\xa0"     => "\x8a",
        "\xe2\x80\xb9" => "\x8b",
        "\xc5\x92"     => "\x8c",
        "\xc5\xbd"     => "\x8e",
        "\xe2\x80\x98" => "\x91",
        "\xe2\x80\x99" => "\x92",
        "\xe2\x80\x9c" => "\x93",
        "\xe2\x80\x9d" => "\x94",
        "\xe2\x80\xa2" => "\x95",
        "\xe2\x80\x93" => "\x96",
        "\xe2\x80\x94" => "\x97",
        "\xcb\x9c"     => "\x98",
        "\xe2\x84\xa2" => "\x99",
        "\xc5\xa1"     => "\x9a",
        "\xe2\x80\xba" => "\x9b",
        "\xc5\x93"     => "\x9c",
        "\xc5\xbe"     => "\x9e",
        "\xc5\xb8"     => "\x9f"
    );

    /**
     * Converting table UTF-8 to Windows-1252
     */
    static function toUTF8($data)
    {
        return mb_convert_encoding($data, 'Windows-1252', 'UTF-8');
        if(is_array($data)) {
            foreach($data as $key => $value) {
                $data[$key] = self::toUTF8($value);
            }
            return $data;
        }

        if(!is_string($data)) {
            return $data;
        }

        $max = self::strlen($data);

        $buf = "";
        for($i = 0; $i < $max; $i++){
                $c1 = $data[$i];
                if($c1>="\xc0"){ //Should be converted to UTF8, if it's not UTF8 already
                    $c2 = $i+1 >= $max? "\x00" : $data[$i+1];
                    $c3 = $i+2 >= $max? "\x00" : $data[$i+2];
                    $c4 = $i+3 >= $max? "\x00" : $data[$i+3];
                        if($c1 >= "\xc0" & $c1 <= "\xdf"){ //looks like 2 bytes UTF8
                                if($c2 >= "\x80" && $c2 <= "\xbf"){ //yeah, almost sure it's UTF8 already
                                        $buf .= $c1 . $c2;
                                        $i++;
                                } else { //not valid UTF8.    Convert it.
                                        $cc1 = (chr(ord($c1) / 64) | "\xc0");
                                        $cc2 = ($c1 & "\x3f") | "\x80";
                                        $buf .= $cc1 . $cc2;
                                }
                        } elseif($c1 >= "\xe0" & $c1 <= "\xef"){ //looks like 3 bytes UTF8
                                if($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf"){ //yeah, almost sure it's UTF8 already
                                        $buf .= $c1 . $c2 . $c3;
                                        $i = $i + 2;
                                } else { //not valid UTF8.    Convert it.
                                        $cc1 = (chr(ord($c1) / 64) | "\xc0");
                                        $cc2 = ($c1 & "\x3f") | "\x80";
                                        $buf .= $cc1 . $cc2;
                                }
                        } elseif($c1 >= "\xf0" & $c1 <= "\xf7"){ //looks like 4 bytes UTF8
                                if($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf" && $c4 >= "\x80" && $c4 <= "\xbf"){ //yeah, almost sure it's UTF8 already
                                        $buf .= $c1 . $c2 . $c3 . $c4;
                                        $i = $i + 3;
                                } else { //not valid UTF8.    Convert it.
                                        $cc1 = (chr(ord($c1) / 64) | "\xc0");
                                        $cc2 = ($c1 & "\x3f") | "\x80";
                                        $buf .= $cc1 . $cc2;
                                }
                        } else { //doesn't look like UTF8, but should be converted
                                        $cc1 = (chr(ord($c1) / 64) | "\xc0");
                                        $cc2 = (($c1 & "\x3f") | "\x80");
                                        $buf .= $cc1 . $cc2;
                        }
                } elseif(($c1 & "\xc0") === "\x80"){ // needs conversion
                            if(isset(self::$win1252_to_utf8[ord($c1)])) { //found in Windows-1252 special cases
                                    $buf .= self::$win1252_to_utf8[ord($c1)];
                            } else {
                                $cc1 = (chr(ord($c1) / 64) | "\xc0");
                                $cc2 = (($c1 & "\x3f") | "\x80");
                                $buf .= $cc1 . $cc2;
                            }
                } else { // it doesn't need conversion
                        $buf .= $c1;
                }
        }
        return $buf;
    }

    static function toWin1252($data, $option = self::WITHOUT_ICONV)
    {
        if(is_array($data)) {
            foreach($data as $key => $value) {
                $data[$key] = self::toWin1252($value, $option);
            }
            return $data;
        } elseif(is_string($data)) {
            return static::utf8_decode($data, $option);
        } else {
            return $data;
        }
    }

    static function toISO8859($data, $option = self::WITHOUT_ICONV)
    {
        return self::toWin1252($data, $option);
    }

    static function toLatin1($data, $option = self::WITHOUT_ICONV)
    {
        return self::toWin1252($data, $option);
    }

    static function fixUTF8($text, $option = self::WITHOUT_ICONV)
    {

        if(is_array($text)) {
            foreach($text as $k => $v) {
                $text[$k] = self::fixUTF8($v, $option);
            }
            return $text;
        }

        if(!is_string($text)) {
            return $text;
        }

        $last = "";
        while($last <> $text){
            $last = $text;
            $text = self::toUTF8(static::utf8_decode($text, $option));
        }
        $text = self::toUTF8(static::utf8_decode($text, $option));
        return $text;

    }

    static function UTF8FixWin1252Chars($text)
    {
        return str_replace(array_keys(self::$broken_utf8_to_utf8), array_values(self::$broken_utf8_to_utf8), $text);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    static function remove_bom($string = "")
    {
        if (substr($string, 0,3) === pack("CCC", 0xef, 0xbb, 0xbf)) {
            $string = substr($string, 3);
        }
        return $string;
    }

    /**
     * @param string $string
     *
     * @return int
     */
    protected static function strlen($string)
    {
        return (function_exists('mb_strlen') && ((int) ini_get('mbstring.func_overload')) & 2) ? mb_strlen($string, '8bit') : strlen($string);
    }

    public static function normalizeEncoding($charset)
    {
        $charset = preg_replace('/[^a-zA-Z0-9\s]/', '', strtoupper($charset));
        $equivalences = array(
            'ISO88591'    => 'ISO-8859-1',
            'ISO8859'     => 'ISO-8859-1',
            'ISO'         => 'ISO-8859-1',
            'LATIN1'      => 'ISO-8859-1',
            'LATIN'       => 'ISO-8859-1',
            'UTF8'        => 'UTF-8',
            'UTF'         => 'UTF-8',
            'WIN1252'     => 'ISO-8859-1',
            'WINDOWS1252' => 'ISO-8859-1'
        );
        if(empty($equivalences[$charset])){
            return 'UTF-8';
        }
        return $equivalences[$charset];
    }

    public static function encode($charset, $data)
    {
        if (self::normalizeEncoding($charset) === 'ISO-8859-1') return self::toLatin1($data);
        return self::toUTF8($data);
    }

    protected static function utf8_decode($data, $option = self::WITHOUT_ICONV)
    {
        if ($option == self::WITHOUT_ICONV || !function_exists('iconv')) {
            $o = utf8_decode(
                str_replace(array_keys(self::$utf8_to_win1252), array_values(self::$utf8_to_win1252), self::toUTF8($data))
            );
        } else {
            $o = iconv("UTF-8", "Windows-1252" . ($option === self::ICONV_TRANSLIT ? '//TRANSLIT' : ($option === self::ICONV_IGNORE ? '//IGNORE' : '')), $data);
        }
        return $o;
    }

}