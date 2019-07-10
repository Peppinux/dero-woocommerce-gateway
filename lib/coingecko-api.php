<?php
defined('ABSPATH') || exit;

require_once(DERO_GATEWAY_PLUGIN_DIR . '/lib/utils/admin-error.php');

class CoinGecko_API {
    private static $url = 'https://api.coingecko.com/api/v3/';

    private static function request($endpoint) {
        $ch = curl_init();

        if(!$ch)
            throw new RuntimeException('Could\'t initialize a cURL session');

        curl_setopt($ch, CURLOPT_URL, self::$url . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        
        $response = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($http_code != 200)
            admin_error('CoinGecko API HTTP Response Code: ' . $http_code);

        if(curl_errno($ch) > 0)
            admin_error('Failed to connect to CoinGecko API at ' . self::$url);

        curl_close($ch);

        return json_decode($response, true);
    }

    public static function get_supported_currencies() {
        return self::request('simple/supported_vs_currencies');
    }

    public static function get_dero_exchange_rate($currency) {
        return self::request('simple/price?ids=dero&vs_currencies=' . $currency)['dero'][strtolower($currency)];
    }
}
?>
