# DERO Payment Gateway for WooCommerce

Start accepting secure and private payments with [DERO](https://dero.io/) on your WooCommerce shop.

## Requirements
* A web server with PHP and [Wordpress](https://wordpress.org/download/) installed are needed for WooCommerce. (Plugin tested on PHP version 7 and Wordpress 5.2.2)
* WooCommerce plugin for Wordpress installed. (Plugin tested on WooCommerce 3.1.6)
* A running istance of the [dero-wallet-cli](https://github.com/deroproject/derosuite/releases) for receiving payments.
  * Wallet needs to expose its RPC server by running it with the `--rpc-server` flag.
  * `--rpc-login=username:password` flag should be added for security reasons.
  * Using the previous flag is __strongly advised__ (not by the plugin, but by common sense) especially if the wallet is running on a different machine (consequently with a open port) from the web server.

## How to install
* Download the plugin from the [releases page](https://github.com/Peppinux/dero-woocommerce-gateway/releases).
* Unzip it into the `wp-content/plugins` folder of your Wordpress folder.
* Activate the plugin in the `Plugins -> Installed Plugins` page of your Wordpress Dashboard.
* Set up and Enable the payment gateway in the `WooCommerce -> Settings -> Payments` page of your Wordpress Dashboard.

## Settings
* `Enable/Disable` - Enable or disable DERO Payment Gateway. (Default: disabled)
* `Title` - Payment title which the user sees during checkout. (Default: DERO Payment Gateway)
* `Descrition` - Payment description which the user sees during checkout. (Default: Pay securely and privately using DERO. Payment details will be provided after checkout.)
* `Discount` - Percentage discount for making a payment with DERO. (Default: 0 = no discount)
* `Order valid time` - Amount of time the funds must be received in after the order is placed. (Default: 3600 seconds = 1 hour)
* `Confirmations` - Number of confirmations that the transaction must have to be valid. (Default: 10 = around 2 minutes)
* `Wallet host` - Wallet RPC host used to connect to the wallet in order to verify transactions. (Default: 127.0.0.1)
* `Wallet port` - Wallet RPC port used to connect to the wallet. (Default: 20209)
* `Wallet login required` - Enable or disable whether wallet RPC needs to login. (Default: disabled)
* `Wallet username` - Wallet RPC username used to connect to the wallet. (Default: none)
* `Wallet password` - Wallet RPC password used to connect to the wallet. (Default: none)

## How it works
* After order checkout: 
  * A random Payment ID and associated Integrated Address are generated through Wallet RPC.
  * Order total gets converted to DERO thorugh CoinGecko API and discount (if present) gets applied to the new DERO total.
  * Said generated variables and other details are stored in a MySQL database through wpdb for following payment processing.
  * Integrated Address, total amount of DERO to pay, amount of time left to pay and other useful details are shown to the customer in the order summary page.
* Once every minute on the server (through Wordpress CRON Job):
    * List of pending orders paid with DERO gets fetched from the database through wpdb.
    * List of incoming transfers to the wallet gets fetched through Wallet RPC.
    * If the Payment ID of one of the pending orders matches with the Payment ID of one of the incoming transfers and the amount transfered isn't less than the order total, payment is received and order status gets updated.
    * If payment isn't received in time (Order valid time setting) order status is set to failed.
    * If the amount transfered isn't enough to cover the total of the order, order status is set to failed and the owner of the shop has to refund the customer.

## Credits
- [DERO](https://dero.io/) for providing the blockchain.
- [CoinGecko API](https://www.coingecko.com/api) for price conversion.
- [WooCommerce Payment Gateway API](https://docs.woocommerce.com/document/payment-gateway-api/).
- [Monero Gateway for WooCommerce](https://github.com/monero-integrations/monerowp) by [Monero Integrations](https://github.com/monero-integrations) for the inspiration.

## Donations
__DERO__: dERiY64hDfghSaUCRJN6HuFpfuaQKnPftTfXoBbi7CaLVTFCGdYrPN5iZTWNcqiZkbEVqaGduHt6C2CagHV2SPQdeUeDKueYirQhGykNLZwrug5FHzP2sSFcngdwaxGrX2Ne5GjGc8xrBt

__XMR__: 46LugwCgmfMd9puoXiRzfobaFPHr1do78d6rbxkT32bo2cHX8vu2YRpVnQbyE7LQXzeZLVYmpv2jsQvFs6QirBoAJ4fXEGM

__BTC__: 18RUHsdW3pC9NSJ9g2HeYvNRhbAqyGoeM6

__ETH__: 0x80C5540a865740495AF4569954459E1Bf4EF0dB4

## Screenshots

### Settings
![Settings](https://i.imgur.com/eDANcx8.png)

### Place order
![Place order](https://i.imgur.com/TMNb3DB.png)

### Order details
![Order details](https://i.imgur.com/745SXOe.png)
