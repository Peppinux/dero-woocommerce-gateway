<?php
/**
 * Plugin Name: DERO WooCommerce Gateway
 * Plugin URI:  https://github.com/Peppinux/dero-woocommerce-gateway
 * Description: DERO Payment Gateway for WooCommerce
 * Version:     1.0.0
 * Author:      Peppinux
 * Author URI:  https://github.com/Peppinux
 * License:     MIT
 */

defined('ABSPATH') || exit;

define('DERO_GATEWAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DERO_GATEWAY_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', 'dero_gateway_init');

require_once(DERO_GATEWAY_PLUGIN_DIR . '/lib/dero-wallet-rpc.php');
require_once(DERO_GATEWAY_PLUGIN_DIR . '/lib/coingecko-api.php');

require_once(DERO_GATEWAY_PLUGIN_DIR . '/lib/util/format-time.php');

function dero_gateway_init() {
    if(!class_exists('WC_Payment_Gateway'))
        return;

    class DERO_Gateway extends WC_Payment_Gateway {
        function __construct() {
            $this->id = 'dero_gateway';
            $this->icon = apply_filters('woocommerce_gateway_icon', DERO_GATEWAY_PLUGIN_URL . '/assets/icon.png');
            $this->has_fields = false;
            $this->method_title = __('DERO Gateway', 'dero_gateway');
            $this->method_description = __('DERO Payment Gateway for WooCommerce', 'dero_gateway');
            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title');
		    $this->description = $this->get_option('description');
            $this->discount = $this->get_option('discount');
            $this->order_valid_time = $this->get_option('order_valid_time');
            $this->confirmations = $this->get_option('confirmations');
            $this->wallet_host = $this->get_option('wallet_host');
            $this->wallet_port = $this->get_option('wallet_port');
            $this->wallet_login_required = $this->get_option('wallet_login_required');
            $this->wallet_username = $this->get_option('wallet_username');
            $this->wallet_password = $this->get_option('wallet_password');

            DERO_Wallet_RPC::setup($this->wallet_host, $this->wallet_port, $this->wallet_login_required, $this->wallet_username, $this->wallet_password);

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'dero_gateway'),
                    'type' => 'checkbox',
                    'label' => __('Enable DERO Gateway Payment', 'dero_gateway'),
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => __('Title', 'dero_gateway'),
                    'type' => 'text',
                    'description' => __('Payment title which the user sees during checkout.', 'dero_gateway'),
                    'default' => __('DERO Payment Gateway', 'dero_gateway'),
                    'desc_tip' => true
                ),
                'description' => array(
                    'title' => __('Description', 'dero_gateway'),
                    'type' => 'text',
                    'description' => __('Payment description which the user sees during checkout.', 'dero_gateway'),
                    'default' => __('Pay securely and privately using DERO. Payment details will be provided after checkout.', 'dero_gateway'),
                    'desc_tip' => true
                ),
                'discount' => array(
                    'title' => __('Discount for using DERO', 'dero_gateway'),
                    'type' => __('number'),
                    'description' => __('Enter a percentage discount (e.g., 5 for 5%. Whole numbers only.) or leave this field empty if you do not wish to provide a discount.', 'dero_gateway'),
                    'default' => '0',
                    'desc_tip' => __('Discount for making a payment with DERO', 'dero_gateway')
                ),
                'order_valid_time' => array(
                    'title' => __('Order valid time', 'dero_gateway'),
                    'type' => __('number'),
                    'description' => __('Enter the number of seconds that the funds must be received in after the order is placed (e.g., 3600 = 1 hour).', 'dero_gateway'),
                    'default' => '3600',
                    'desc_tip' => __('Amount of time the funds must be received in after the order is placed.', 'dero_gateway')
                ),
                'confirmations' => array(
                    'title' => __('Number of confirmations', 'dero_gateway'),
                    'type' => __('number'),
                    'description' => __('Enter the number of confirmations that the transaction must have to be valid. Each confirmation should take around 12 seconds. 10 confirmations = 2 minutes.', 'dero_gateway'),
                    'default' => '10',
                    'desc_tip' => __('Number of confirmations that the transaction must have to be valid.', 'dero_gateway')
                ),
                'wallet_host' => array(
                    'title' => __('DERO Wallet RPC Hostname/IP Address', 'dero_gateway'),
                    'type' => 'text',
                    'description' => __('Wallet RPC host used to connect to the wallet in order to verify transactions.', 'dero_gateway'),
                    'default' => __('127.0.0.1', 'dero_gateway'),
                    'desc_tip' => true
                ),
                'wallet_port' => array(
                    'title' => __('DERO Wallet RPC Port', 'dero_gateway'),
                    'type' => __('number'),
                    'description' => __('Wallet RPC port used to connect to the wallet in order to verify transactions.', 'dero_gateway'),
                    'default' => 20209,
                    'desc_tip' => true
                ),
                'wallet_login_required' => array(
                    'title' => __('Wallet RPC requires Login', 'dero_gateway'),
                    'type' => 'checkbox',
                    'label' => __('Enable Wallet RPC Login', 'dero_gateway'),
                    'default' => 'no'
                ),
                'wallet_username' => array(
                    'title' => __('DERO Wallet RPC Username', 'dero_gateway'),
                    'type' => 'text',
                    'description' => __('Optional. Enter username only if wallet requires RPC Login and previous checkbox is checked.', 'dero_gateway'),
                    'default' => __('', 'dero_gateway'),
                    'desc_tip' => __('Wallet RPC username used to connect to the wallet in order to verify transactions.', 'dero_gateway')
                ),
                'wallet_password' => array(
                    'title' => __('DERO Wallet RPC Password', 'dero_gateway'),
                    'type' => 'text',
                    'description' => __('Optional. Enter password only if wallet requires RPC Login and previous checkbox is checked.', 'dero_gateway'),
                    'default' => __('', 'dero_gateway'),
                    'desc_tip' => __('Wallet RPC password used to connect to the wallet in order to verify transactions.', 'dero_gateway')
                )
            );
        }

        public function process_payment($order_id) {
            global $woocommerce;
            $order = new WC_Order($order_id);

            $current_height = DERO_Wallet_RPC::get_height();
            if(!is_int($current_height) || !($current_height >= 0)) {
                $error_message = 'Could not get chain height.';
                wc_add_notice(__('DERO Payment Gateway error: ', 'dero_gateway') . $error_message, 'error');
                return;
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'dero_gateway_payments';

            $payment_id = '';
            do {
                $payment_id = bin2hex(openssl_random_pseudo_bytes(32));
                $query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE payment_id=%s", $payment_id);
                $payment_id_found = $wpdb->get_var($query);
            } while($payment_id_found);

            $integrated_address = DERO_Wallet_RPC::make_integrated_address($payment_id);
            if(!is_string($integrated_address) || strlen($integrated_address) != 142) {
                $error_message = 'Could not make integrated address.';
                wc_add_notice(__('DERO Payment Gateway error: ', 'dero_gateway') . $error_message, 'error');
                return;
            }

            $currency = $order->get_currency();
            $supported_currencies = CoinGecko_API::get_supported_currencies();
            $exchange_rate = 0;
            if(in_array(strtolower($currency), $supported_currencies) || in_array(strtoupper($currency), $supported_currencies))
                $exchange_rate = CoinGecko_API::get_dero_exchange_rate($currency);
            else {
                $error_message = 'Currency ' . $currency . ' not supported by CoinGecko API.';
                wc_add_notice(__('DERO Payment Gateway error: ', 'dero_gateway') . $error_message, 'error');
                return;
            }

            $fiat_total = $order->get_total();
            $dero_total = $fiat_total / $exchange_rate;
            $discount = 0;
            if($this->discount > 0 && $this->discount <= 100) {
                $discount = $this->discount;
                $dero_total = $dero_total - ($dero_total * $discount / 100);
            }

            $prepared_statement_params = array(
                $order_id,
                $payment_id,
                $integrated_address,
                $currency,
                $exchange_rate,
                $fiat_total,
                $discount,
                $dero_total,
                $current_height
            );
            $query = $wpdb->prepare("INSERT INTO $table_name (order_id, payment_id, integrated_address, currency, exchange_rate, fiat_total, discount_percentage, dero_total, height_at_creation) VALUES (%d, %s, %s, %s, %f, %f, %d, %f, %d)", $prepared_statement_params);
            $wpdb->query($query);

            $wpdb->query("UPDATE $table_name SET status='on-hold', creation_time=NOW() WHERE order_id=$order_id"); // Separate query to re-set status when re-paying existing order.
            $order->update_status('on-hold', __('Awaiting DERO payment', 'dero_gateway'));
            $order->reduce_order_stock();
            $woocommerce->cart->empty_cart();

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }

        public function thankyou_page($order_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'dero_gateway_payments';
            $query = $wpdb->prepare("SELECT integrated_address, currency, exchange_rate, fiat_total, discount_percentage, dero_total, status, received_payment_txid, TIMESTAMPDIFF(SECOND, creation_time, NOW()) as seconds_passed FROM $table_name WHERE order_id=%d", $order_id);
            $result = $wpdb->get_results($query)[0];

            $status = ucfirst($result->status);
            if($result->status == 'on-hold' || $result->status == 'pending') {
                $remaining_seconds = $this->order_valid_time - $result->seconds_passed;
                $time_format = format_time($remaining_seconds);
                if($remaining_seconds > 0)
                    $status = "Awaiting payment. Your order will expire in <i>$time_format</i> if payment is not received.";
                else
                    $status = "Your order is about to expire. Place another one to complete your purchase.";
            } else if($result->status == 'expired')
                $status = 'Expired. Place another order to complete your purchase.';

            $discount_section = "";
            if((int)$result->discount_percentage > 0)
                $discount_section = "<li class='woocommerce-order-overview__order order'>
                                        Discount for paying with DERO: <strong>$result->discount_percentage%</strong>
                                    </li>";

            $integrated_address_section = "";
            if($result->status == 'on-hold' || $result->status == 'pending')
                $integrated_address_section = "<li class='woocommerce-order-overview__order order'>
                                                    Pay to (integrated address): <strong>$result->integrated_address</strong>
                                                </li>";

            $txid_section = "";
            if($result->received_payment_txid != null)
                $txid_section = "<li class='woocommerce-order-overview__order order'>
                                    Payment TXID: <strong>$result->received_payment_txid</strong>
                                </li>";

            $instructions = "<section class='woocommerce-order-details'>
                                <h2 class='woocommerce-order-details__title'>DERO Payment Details</h2>
                                <ul class='woocommerce-order-overview woocommerce-thankyou-order-details order_details'>
                                    <li class='woocommerce-order-overview__order order'>
                                        Status: <strong>$status</strong>
                                    </li>
                                    <li class='woocommerce-order-overview__order order'>
                                        Fiat total: <strong>$result->fiat_total $result->currency</strong>
                                    </li>
                                    <li class='woocommerce-order-overview__order order'>
                                        Exchange rate: <strong>1 DERO = $result->exchange_rate $result->currency</strong>
                                    </li>
                                    $discount_section
                                    <li class='woocommerce-order-overview__order order'>
                                        Total: <strong>$result->dero_total DERO</strong>
                                    </li>
                                    $integrated_address_section
                                    $txid_section
                                </ul>
                            </section>";
            echo wp_kses_post(wpautop(wptexturize($instructions)));
        }

        public function email_instructions($order, $sent_to_admin, $plain_text = false) {
            if(!$sent_to_admin && $order->get_payment_method() === $this->id && $order->has_status('on-hold')) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'dero_gateway_payments';
                $query = $wpdb->prepare("SELECT integrated_address, dero_total, TIMESTAMPDIFF(SECOND, creation_time, NOW()) as seconds_passed FROM $table_name WHERE order_id=%d", $order->get_id());
                $result = $wpdb->get_results($query)[0];

                $remaining_seconds = $this->order_valid_time - $result->seconds_passed;
                $time_format = format_time($remaining_seconds);

                echo wp_kses_post(wpautop(wptexturize('You need to send <strong>' . $result->dero_total . ' DERO</strong> to the following address within <strong>' . $time_format . '</strong> to complete your purchase.<br>DERO Payment Integrated Address: <strong>' . $result->integrated_address . '</strong><br>More details can be found in the order page.' . PHP_EOL)));
            }
        }
    }

    function dero_gateway($methods) {
        $methods[] = 'DERO_Gateway'; 
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'dero_gateway');
}

function dero_cron_add_one_minute($schedules) {
    $schedules['one_minute'] = array(
        'interval' => 60,
        'display' => __('Once every minute')
    );
    return $schedules;
}
add_filter('cron_schedules', 'dero_cron_add_one_minute');

function check_dero_payments() {
	global $wpdb;
    $table_name = $wpdb->prefix . 'dero_gateway_payments';
    $results = $wpdb->get_results("SELECT order_id, payment_id, dero_total, height_at_creation, TIMESTAMPDIFF(SECOND, creation_time, NOW()) as seconds_passed FROM $table_name WHERE status='on-hold' OR status='pending'");

    global $woocommerce;
    $wc_gateways = new WC_Payment_Gateways();
    $dero_gateway = $wc_gateways->get_available_payment_gateways()['dero_gateway'];
    $order_valid_time = $dero_gateway->get_option('order_valid_time');
    $required_confirmations = $dero_gateway->get_option('confirmations');

    foreach($results as $result) {
        $order = new WC_Order($result->order_id);

        if($result->seconds_passed > $order_valid_time) {
            if($order->get_status() == "on-hold" || $order->get_status() == "pending")
                $order->update_status('failed', __('Payment not received in time', 'dero_gateway'));

            $query = $wpdb->prepare("UPDATE $table_name SET status=%s WHERE order_id=%d", array('expired', $result->order_id));
            $wpdb->query($query);
        } else {
            $payments = DERO_Wallet_RPC::get_bulk_payments($result->payment_id, $result->height_at_creation);
            if(payments != null) {
                $dero_total = $result->dero_total * pow(10, 12); // Convert DERO got from the db from float to int in order to make it comparable to the value returned by wallet RPC.
                $payment = null;
                foreach($payments as $p) {
                    if(json_encode($p['amount']) >= $dero_total) {
                        $payment = $p;
                        break;
                    }
                }
                if(payment != null) {
                    $payment_confirmations = DERO_Wallet_RPC::get_height() - $result->height_at_creation;

                    if($payment_confirmations >= $required_confirmations)
                        $order->payment_complete();
                } else {
                    if($order->get_status() == "on-hold" || $order->get_status() == "pending")
                        $order->update_status('failed', __('Amount of DERO sent not matching with order total.', 'dero_gateway'));
                }

                $tx_hash = ($payment != null) ? $payment['tx_hash'] : null;
                $query = $wpdb->prepare("UPDATE $table_name SET status=%s, received_payment_txid=%s WHERE order_id=%d", array($order->get_status(), $tx_hash, $result->order_id));
                $wpdb->query($query);
            }
        }
    }
}
add_action('check_dero_payments_cron', 'check_dero_payments');

function dero_activate() {
    global $wpdb;
    require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'dero_gateway_payments';
    if($wpdb->get_var("show tables like $table_name") != $table_name) {
        $sql = "CREATE TABLE $table_name (
                    order_id BIGINT UNSIGNED NOT NULL,
                    payment_id VARCHAR(128) NOT NULL,
                    integrated_address VARCHAR(256) NOT NULL,
                    currency VARCHAR(8) NOT NULL,
                    exchange_rate DOUBLE UNSIGNED NOT NULL,
                    fiat_total DOUBLE UNSIGNED NOT NULL,
                    discount_percentage TINYINT UNSIGNED NOT NULL,
                    dero_total DOUBLE UNSIGNED NOT NULL,
                    status VARCHAR(16) NOT NULL,
                    height_at_creation BIGINT NOT NULL,
                    creation_time TIMESTAMP NOT NULL DEFAULT NOW(),
                    received_payment_txid VARCHAR(256) NULL DEFAULT NULL,
                    PRIMARY KEY (order_id)
               ) $charset_collate;";
        dbDelta($sql);
    }

    if(!wp_next_scheduled('check_dero_payments_cron'))
        wp_schedule_event(time(), 'one_minute', 'check_dero_payments_cron');
}
register_activation_hook(__FILE__, 'dero_activate');

function dero_deactivate() {
    wp_clear_scheduled_hook('check_dero_payments_cron');
}
register_deactivation_hook(__FILE__, 'dero_deactivate');
?>
