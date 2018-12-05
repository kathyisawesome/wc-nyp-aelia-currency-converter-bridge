# WC Name Your Price + Aelia Currency Converter Bridge

This plugin adds compatibility between WooCommerce Name Your Price and Aelia Currency Converter.

## Installation

To install WC Name Your Price + Aelia Currency Converter Bridge:

1. Download the extension from [Github](https://github.com/helgatheviking/wc-nyp-aelia-currency-converter-bridge/archive/master.zip)

2. In your WordPress admin, go to Plugins > Add New and then click on the "Upload" tab

3. Click the "Choose File" button, select the zip file you just downloaded to your computer and then click "Install Now"

4. After installation has completed you can activate the plugin right away or you can activate the 'WC Name Your Price + Aelia Currency Converter Bridge' extension through the 'Plugins' menu in WordPress at any time

## How to Use

Just activate and go. 

## How to Use As A Template for Establishing Compatibility with Other Multicurrency Plugins

Refer to the `convert_price()` method. It's a wrapper function for converting a price from one currency to the other. Specifically you'll need to modify this function [here](https://github.com/helgatheviking/wc-nyp-aelia-currency-converter-bridge/blob/master/wc-name-your-price-aelia.php#L130) to use your Multicurrency plugin's own conversion logic. The bridge plugin _should_ help take care of suggested, minimum, maximum, and cart prices, but it will all depend on how exactly your multicurrency plugin behaves. 

## Important

This is proof of concept and not officially supported in any way.


