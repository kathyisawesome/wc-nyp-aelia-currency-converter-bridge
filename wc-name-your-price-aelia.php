<?php
/**
 * Plugin Name: WC Name Your Price + Aelia Currency Converter Bridge
 * Plugin URI:  http://github.com/helgatheviking/wc-nyp-aelia-currency-converter-bridge
 * Description: A bridge plugin to add compatibility between Name Your Price and Aelia Currency Converter
 * Version:     0.3.0
 * Author:      Kathy Darling
 * Author URI:  http://www.kathyisawesome.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wc_nyp_aelia_cc
 * Domain Path: /languages
 * Requires at least: 4.8.0
 * Tested up to: 4.9.x
 * WC requires at least: 3.0.0
 * WC tested up to: 3.5.0   
 */

/**
 * Copyright: © 2018 Kathy Darling.
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
		
		$raw_price_tags = array(
			'woocommerce_raw_suggested_price',
			'woocommerce_raw_minimum_price',
			'woocommerce_raw_maximum_price',
		);

		/**
		 * Filter tags renamed in NYP 3+.
		 * Method is_nyp_gte exists in NYP 3+.
		 *
		 * @noinspection PhpUndefinedMethodInspection
		 */
		if (
			is_callable( array( 'WC_Name_Your_Price_Compatibility', 'is_nyp_gte' ) )
			&& WC_Name_Your_Price_Compatibility::is_nyp_gte( '3.0' )
		) {
			$raw_price_tags = array(
				'wc_nyp_raw_suggested_price',
				'wc_nyp_raw_minimum_price',
				'wc_nyp_raw_maximum_price',
			);
		}

		foreach ( $raw_price_tags as $tag ) {
			add_filter(
				$tag,
				array( __CLASS__, 'convert_price' )
			);
		}

		// Admin
		add_action( 'wc_nyp_options_pricing', array( __CLASS__, 'pricing_options' ), 100, 2 );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product_meta' ) );

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
			$new_price = self::convert_price( $cart_item['nyp'], $cart_item['nyp_currency'] );
			$cart_item['data']->set_price( $new_price );
		}
		
		return $cart_item;
	}

	/**
	 * Convert the suggested, min, and max prices.
	 *
	 * @static
	 * @param string $price
	 * @return array
	 * @since 0.1.0
	 * @deprecated 0.3.0
	 */
	public static function convert_nyp_prices( $price ) {
		_deprecated_function( __FUNCTION__, '0.3.0', 'WC_NYP_Aelia_CC::convert_price' );
		return self::convert_price( $price );
	}


	/**
	 * Wrapper function to convert a price from one currency to another.
	 *
	 * @static
	 * @param string $price
	 * @param string $from_currency
	 * @param string $to_currency
	 * 
	 * @return string
	 * @since 0.3.0
	 */
	public static function convert_price( $price, $from_currency = false, $to_currency = false ) {

		// Source currency.
		if( ! $from_currency ){
			$from_currency = get_option( 'woocommerce_currency' );
		}

		// Destination currency.
		if( ! $to_currency ){
			$to_currency = get_woocommerce_currency();
		}

		/* This filter allows to call a conversion while still maintaining a loose coupling. It accepts a minimum of three arguments:
		 * - Value to convert
		 * - source currency
		 * - destination currency
		 * It returns the original converted to the destination currency
		 */
		return apply_filters( 'wc_aelia_cs_convert', $price, $from_currency, $to_currency );
	}


    /*-----------------------------------------------------------------------------------*/
	/* Write Panel / metabox */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Add maximum inputs to product metabox
	 *
	 * @param  object WC_Product $product_object
	 * @param  bool $show_billing_period_options
	 * @param  mixed int|false $loop - for use in variations
	 * @return print HTML
	 * @since  0.4.0
	 */
	public static function pricing_options( $product_object, $show_billing_period_options = false, $loop = false ) {

		$currencies = apply_filters('wc_aelia_cs_enabled_currencies', array() );

		$default_currency = get_option( 'woocommerce_currency' );

		if( count( $currencies ) > 1 ) { ?>

			<div class="wc_aelia_cs_product_prices clearfix hide_if_subscription">

				<div class="product_currency_prices_header">
					<h3><?php esc_html_e( 'Price in specific currencies', 'wc_name_your_price'); ?></h3>

					<?php

					foreach( $currencies as $currency ) {

						if( $currency === $default_currency ) {
							continue;
						}

						// Suggested Price.
						woocommerce_wp_text_input( array(
							'id' => is_int( $loop ) ? "variation_suggested_price_currency[$currency][$loop]" : "_suggested_price_currency[$currency]",
							'class' => 'wc_input_price short',
							'wrapper_class' => is_int( $loop ) ? 'form-row form-row-first' : '',
							'placeholder' => __( 'Auto', 'wc_name_your_price' ),
							'label' => __( 'Suggested Price', 'wc_name_your_price') . ' ('. $currency .')' ,
							'desc_tip' => 'true',
							'description' => __( 'Price to pre-fill for customers.  Leave blank to not suggest a price.', 'wc_name_your_price' ),
							'data_type' => 'price',
							'value' => $product_object->get_meta( '_suggested_price_' . $currency, true )
						) );

						// Minimum Price.
						woocommerce_wp_text_input( array(
							'id' => is_int( $loop ) ? "variation_min_price_currency[$loop][$currency]" : "_min_price_currency[$currency]",
							'class' => 'wc_input_price short',
							'wrapper_class' => is_int( $loop ) ? 'form-row form-row-last' : '',
							'placeholder' => __( 'Auto', 'wc_name_your_price' ),
							'label' => __( 'Minimum Price', 'wc_name_your_price') . ' (' . $currency . ')',
							'desc_tip' => 'true',
							'description' =>  __( 'Lowest acceptable price for product. Leave blank to not enforce a minimum. Must be less than or equal to the set suggested price.', 'wc_name_your_price' ),
							'data_type' => 'price',
							'value' => $product_object->get_meta( '_min_price_' . $currency, true )
						) );

						// Maximum Price.
						woocommerce_wp_text_input( array(
							'id' => is_int( $loop ) ? "variation_maximum_price_currency[$loop][$currency]" : "_maximum_price_currency[$currency]",
							'class' => 'wc_input_price short',
							'wrapper_class' => is_int( $loop ) ? 'form-row form-row-first' : '',
							'placeholder' => __( 'Auto', 'wc_name_your_price' ),
							'label' => __( 'Maximum Price', 'wc_name_your_price') . ' (' . $currency . ')',
							'desc_tip' => 'true',
							'description' =>  __( 'Highest acceptable price for product. Leave blank to not enforce a maximum.', 'wc_name_your_price' ),
							'data_type' => 'price',
							'value' => $product_object->get_meta( '_maximum_price_' . $currency, true )
						) );

						echo '<hr>';

					}

					?>
				</div>
		</div>

	<?php }
	}

	/**
	 * Save extra meta info
	 *
	 * @param object $product
	 * @return void
	 * @since 1.0 (renamed in 2.0)
	 */
	public static function save_product_meta( $product ) {

		// Text Field - Amounts in each currency
		$suggested_prices = $_POST['_suggested_price_currency'];

		if( ! empty( $suggested_prices ) ) {

			// Save the fee amount for each currency
			foreach( $suggested_prices as $currency => $amount ) {
				$product->update_meta_data( '_suggested_price_' . $currency, wc_format_decimal( $amount ) );
			}
		
		}

		// Text Field - Amounts in each currency
		$minimum_prices = $_POST['_min_price_currency'];

		if( ! empty( $minimum_prices ) ) {

			// Save the fee amount for each currency
			foreach( $minimum_prices as $currency => $amount ) {
				$product->update_meta_data( '_min_price_' . $currency, wc_format_decimal( $amount ) );
			}
		
		}

		// Text Field - Amounts in each currency
		$maximum_prices = $_POST['_maximum_price_currency'];

		if( ! empty( $maximum_prices ) ) {

			// Save the fee amount for each currency
			foreach($maximum_prices as $currency => $amount) {
				$product->update_meta_data( '_maximum_price_' . $currency, wc_format_decimal( $amount ) );
			}
		
		}

	}


} //end class: do not remove or there will be no more guacamole for you

endif; // end class_exists check

// Launch the whole plugin.
add_action( 'plugins_loaded', array( 'WC_NYP_Aelia_CC', 'init' ) );