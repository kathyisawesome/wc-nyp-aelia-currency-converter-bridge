<?php
/**
 * Plugin Name: WC Name Your Price + Aelia Currency Converter Bridge
 * Plugin URI:  http://github.com/helgatheviking/wc-nyp-aelia-currency-converter-bridge
 * Description: A bridge plugin to add compatibility between Name Your Price and Aelia Currency Converter
 * Version:     0.2.0
 * Author:      Kathy Darling
 * Author URI:  http://www.kathyisawesome.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wc_nyp_aelia_cc
 * Domain Path: /languages
 * Requires at least: 4.8.0
 * Tested up to: 4.9.x
 * WC requires at least: 3.0.0
 * WC tested up to: 3.3.0   
 */

/**
 * Copyright: © 2017 Kathy Darling.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */


/**
 * The Main WC_NYP_Aelia_CC class
 **/
if ( ! class_exists( 'WC_NYP_Aelia_CC' ) ) :

class WC_NYP_Aelia_CC {

	/**
	 * Initialize plugin.
	 *
	 * @static
	 * @return null
	 * @since 0.1.0
	 */
	public static function init() {
		add_action( 'woocommerce_add_cart_item', array( __CLASS__, 'add_initial_currency' ) );
		add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'convert_cart_currency' ), 20, 3 );
		add_filter( 'woocommerce_raw_suggested_price', array( __CLASS__, 'convert_nyp_prices' ), 10, 2 );
		add_filter( 'woocommerce_raw_minimum_price', array( __CLASS__, 'convert_nyp_prices' ), 10, 2 );
		add_filter( 'woocommerce_raw_maximum_price', array( __CLASS__, 'convert_nyp_prices' ), 10, 2 );
	}

	/**
	 * Store the inintial currency when item is added.
	 *
	 * @static
	 * @param array $cart_item
	 * @return array
	 * @since 0.1.0
	 */
	public static function add_initial_currency( $cart_item ) {

		$nyp_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

		if ( WC_Name_Your_Price_Helpers::is_nyp( $nyp_id ) ) {
			$cart_item['nyp_currency'] = get_woocommerce_currency();
		}

		return $cart_item;
	}

	/**
	 * Switch the cart price when currency changes.
	 *
	 * @static
	 * @param array $cart_item
	 * @return array
	 * @since 0.1.0
	 */
	public static function convert_cart_currency( $cart_item, $key, $values ) {

		// If the currency changed, convert the price entered by the customer into the active currency
		if ( isset( $cart_item['nyp'] ) && isset( $cart_item['nyp_currency'] ) && $cart_item['nyp_currency'] != get_woocommerce_currency() ) {
			/* This filter allows to call a conversion while still maintaining a loose coupling. It accepts a minimum of three arguments:
			 * - Value to convert
			 * - source currency
			 * - destination currency
			 * It returns the original converted to the destination currency
			 */
			$new_price = apply_filters( 'wc_aelia_cs_convert', $cart_item['nyp'], $cart_item['nyp_currency'], get_woocommerce_currency() );
			$cart_item['data']->set_price( $new_price );
		}
		
		return $cart_item;
	}

	/**
	 * User Aelia to convert the suggested and min prices.
	 *
	 * @static
	 * @param array $cart_item
	 * @return array
	 * @since 0.1.0
	 */
	public static function convert_nyp_prices( $price, $product ) {
			/* This filter allows to call a conversion while still maintaining a loose coupling. It accepts a minimum of three arguments:
			 * - Value to convert
			 * - source currency
			 * - destination currency
			 * It returns the original converted to the destination currency
			 */
		return apply_filters( 'wc_aelia_cs_convert', $price, get_option( 'woocommerce_currency' ), get_woocommerce_currency() );
	}


} //end class: do not remove or there will be no more guacamole for you

endif; // end class_exists check

// Launch the whole plugin.
add_action( 'wc_name_your_price_loaded', array( 'WC_NYP_Aelia_CC', 'init' ) );