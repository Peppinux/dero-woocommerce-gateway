# DERO Payment Gateway for WooCommerce

Start accepting secure and private payments with [DERO](https://dero.io/) on your WooCommerce shop.

| :zap: This project was developed back in 2019 for DERO Atlantis. As such, it's not going to be compatible with the latest DERO-HE STARGATE release. |
|-----------------------------------------|

## Requirements
* A web server with PHP and [Wordpress](https://wordpress.org/download/) installed are needed for WooCommerce. (Plugin tested on PHP version 7 and Wordpress 5.2.2)
* WooCommerce plugin for Wordpress installed. (Plugin tested on WooCommerce 3.1.6)
* A running istance of the [dero-wallet-cli](https://github.com/deroproject/derosuite/releases) for receiving payments.
  * Wallet needs to expose its RPC server by running it with the `--rpc-server` flag.
  * `--rpc-login=username:password` flag should be added for security reasons.
  * Using the previous flag is __strongly advised__ (not by the plugin, but by common sense) especially if the wallet is running on a different machine (consequently with a open port) from the web server.

## Complete Installation Guide
* Read [installation_guide.pdf](https://github.com/Peppinux/dero-woocommerce-gateway/blob/master/installation_guide.pdf) for in-depth instructions.

## Quick Installation Guide
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

## DERO Accepted Here Badge
Use shortcode `[dero-accepted-here]` to display the badge on your pages.

![DERO Accepted Here Badge](https://i.imgur.com/2eAdksP.png)

## How it works
* After order checkout: 
  * A random Payment ID and associated Integrated Address are generated through Wallet RPC.
  * Order total gets converted to DERO thorugh CoinGecko API and discount (if present) gets applied to the new DERO total.
  * Said generated variables and other details are stored in a MySQL database through wpdb for following payment processing.
  * Integrated Address, total amount of DERO to pay, amount of time left to pay and other useful details are shown to the customer in the order summary page.
* Once every minute on the server (through Wordpress CRON Job):
    * List of pending orders paid with DERO gets fetched from the database through wpdb.
    * List of incoming payments with the same Payment ID gets fetched through Wallet RPC.
    * If payment isn't received in time, order status is set to failed.
    * If the amount transfered isn't enough to cover the total of the order, order status is set to failed and the owner of the shop has to refund the customer.

## Credits
- [DERO](https://dero.io/) for providing the blockchain.
- [CoinGecko API](https://www.coingecko.com/api) for price conversion.
- [WooCommerce Payment Gateway API](https://docs.woocommerce.com/document/payment-gateway-api/).
- [Monero Gateway for WooCommerce](https://github.com/monero-integrations/monerowp) by [Monero Integrations](https://github.com/monero-integrations) for the inspiration.
- [clipboard.js](https://clipboardjs.com/)
- [QRCode.js](https://davidshimjs.github.io/qrcodejs/)
- [Bitcoin Accepted Here Logo PSD by Thomas Schmall](https://www.oxpal.com/bitcoin-accepted-here-logos.html) for providing the footprint for the "DERO Accepted Here" badge.

## Donations
**DERO**: dERoWuoU8wthSaUCRJN6HuFpfuaQKnPftTfXoBbi7CaLVTFCGdYrPN5iZTWNcqiZkbEVqaGduHt6C2CagHV2SPQd9VU1UknF1N

## Screenshots

### Admin - Dashboard Settings
![Settings](https://i.imgur.com/eDANcx8.png)

### Customer - Place order
![Place order](https://i.imgur.com/TMNb3DB.png)

### Customer - Order details
![Order details](https://i.imgur.com/Zb4RIkt.png)
