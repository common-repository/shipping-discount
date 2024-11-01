<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/aliterm
 * @since             1.0.0
 * @package           Shipping-Discount
 *
 * @wordpress-plugin
 * Plugin Name:       Shipping Discount
 * Plugin URI:        https://github.com/aliterm/shipping-discount
 * Description:       Simple yet flexible shipping discount for WooCommerce.
 * Version:           1.2.1
 * Author:            Ali
 * Author URI:        https://www.situsali.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       shipping-discount
 * Domain Path:       /languages
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 6.1.1
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'SHIPPING_DISCOUNT_VERSION', '1.2.1' );
define( 'SHIPPING_DISCOUNT_FILE', __FILE__ );
define( 'SHIPPING_DISCOUNT_PATH', plugin_dir_path( SHIPPING_DISCOUNT_FILE ) );
define( 'SHIPPING_DISCOUNT_URL', plugin_dir_url( SHIPPING_DISCOUNT_FILE ) );

require_once SHIPPING_DISCOUNT_PATH . 'includes/helpers.php';

if ( function_exists( 'shipping_discount_autoload' ) ) {
	spl_autoload_register( 'shipping_discount_autoload' );
}

if ( shipping_discount_is_plugin_active( 'woocommerce/woocommerce.php' ) && class_exists( 'ShippingDiscount' ) ) {
	ShippingDiscount::get_instance();
}
