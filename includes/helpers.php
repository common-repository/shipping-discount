<?php
/**
 * The Helper
 *
 * @package Shipping-Discount
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'shipping_discount_autoload' ) ) :

	/**
	 * Class autoload
	 *
	 * @since 1.0.0
	 *
	 * @param string $class Class name.
	 *
	 * @return void
	 */
	function shipping_discount_autoload( $class ) {
		$class = strtolower( $class );

		if ( strpos( $class, 'shippingdiscount' ) !== 0 ) {
			return;
		}

		require_once SHIPPING_DISCOUNT_PATH . 'includes/classes/class-' . str_replace( '_', '-', $class ) . '.php';
	}

endif;


if ( ! function_exists( 'shipping_discount_is_plugin_active' ) ) :
	/**
	 * Check if plugin is active
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file Plugin file name.
	 */
	function shipping_discount_is_plugin_active( $plugin_file ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_file );
	}
endif;

if ( ! function_exists( 'shipping_discount_get_plugin_data' ) ) :
	/**
	 * Get plugin data
	 *
	 * @since 1.2.13
	 *
	 * @param string $selected Selected data key.
	 * @param string $selected_default Selected data key default value.
	 * @param bool   $markup If the returned data should have HTML markup applied.
	 * @param bool   $translate If the returned data should be translated.
	 *
	 * @return (string|array)
	 */
	function shipping_discount_get_plugin_data( $selected = null, $selected_default = '', $markup = false, $translate = true ) {
		static $plugin_data;

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_null( $plugin_data ) ) {
			$plugin_data = get_plugin_data( SHIPPING_DISCOUNT_FILE, $markup, $translate );
		}

		if ( ! is_null( $selected ) ) {
			return isset( $plugin_data[ $selected ] ) ? $plugin_data[ $selected ] : $selected_default;
		}

		return $plugin_data;
	}

endif;
