<?php
/**
 * The file that defines the core plugin classes
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/aliterm
 * @since      1.0.0
 *
 * @package    ShippingDiscount
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\Jetpack\Constants;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    ShippingDiscount
 * @subpackage shipping-discount/includes
 * @author     Ali <situsali.id@gmail.com>
 */
class ShippingDiscount {

	/**
	 * Hold an instance of the class
	 *
	 * @var ShippingDiscount
	 */
	private static $instance = null;

	/**
	 * The object is created from within the class itself
	 * only if the class has no instance.
	 *
	 * @return ShippingDiscount
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new ShippingDiscount();
		}

		return self::$instance;
	}
	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		$this->load_all_hooks();
	}

	/** Load all Hooks */
	public function load_all_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'woocommerce_coupon_discount_types', array( $this, 'custom_coupon_type' ), 10 );
		add_action( 'woocommerce_coupon_options', array( $this, 'add_coupon_text_field' ) );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'save_coupon_text_field' ), 10, 2 );
		add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'validate_custom_coupon' ), 10, 3 );
		add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'custom_cart_totals_coupon' ), 30, 3 );
		add_filter( 'woocommerce_package_rates', array( $this, 'override_shipping_price' ), 10 );
		add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'override_shipping_label' ), 10, 2 );
		add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'custom_cart_totals_coupon' ), 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'display_order_data_in_admin' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backend_assets' ), 20 );
		add_filter( 'wc_sc_percent_discount_types', array( $this, 'wc_sc_percentage_override' ), 10, 1 );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'shipping-discount', false, basename( SHIPPING_DISCOUNT_PATH ) . '/languages' );
	}

	/** Custom Coupon Type
	 *
	 * @param array $discount_types Discount Types.
	 * @return array
	 */
	public function custom_coupon_type( $discount_types ) {
		$discount_types['shipping_discount'] = __( 'Shipping Discount', 'shipping-discount' );
		return $discount_types;
	}

	/** Add Coupon Text Field */
	public function add_coupon_text_field() {
		woocommerce_wp_select(
			array(
				'id'          => 'shipping_discount_type',
				'label'       => __( 'Shipping Discount Type', 'shipping-discount' ),
				'placeholder' => '',
				'description' => __( 'Assign a shipping coupon', 'shipping-discount' ),
				'desc_tip'    => true,
				'options'     => array(
					'free'       => __( 'Free shipping', 'shipping-discount' ),
					'fixed'      => __( 'Fixed amount', 'shipping-discount' ),
					'percentage' => __( 'Percentage', 'shipping-discount' ),
				),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => 'shipping_discount_max_amount',
				'label'       => __( 'Maximum amount', 'shipping-discount' ),
				'placeholder' => '',
				'description' => __( 'Maximum value of the coupon', 'woocommerce' ),
				'desc_tip'    => true,
			)
		);
	}

	/**
	 * Save meta box data.
	 *
	 * @param int       $post_id
	 * @param WC_COUPON $coupon
	 */
	//phpcs:ignore
	public function save_coupon_text_field( $post_id, $coupon ) {

		//phpcs:ignore
		if ( ! isset( $_POST['shipping_discount_type'] ) ) {
			return;
		}

		$type       = wc_clean( $_POST['shipping_discount_type'] ); //phpcs:ignore
		$max_amount = wc_clean( $_POST['shipping_discount_max_amount'] ); //phpcs:ignore

		$coupon->update_meta_data( 'shipping_discount_type', $type );
		$coupon->update_meta_data( 'shipping_discount_max_amount', $max_amount );
		$coupon->save();
	}

	/** Validate Coupon
	 *
	 * @param boolean    $valid Valid.
	 * @param WC_PRODUCT $product Product.
	 * @param WC_COUPON  $coupon Coupon.
	 */
	//phpcs:ignore
	public function validate_custom_coupon( $valid, $product, $coupon ) {
		if ( ! $coupon->is_type( array( 'shipping_discount' ) ) ) {
			return $valid;
		}
	}

	/** Custom Display Coupon in Cart
	 *
	 * @param string    $coupon_html Coupon HTML.
	 * @param WC_COUPON $coupon Coupon.
	 * @return string
	 */
	public function custom_cart_totals_coupon( $coupon_html, $coupon ) {
		if ( 'shipping_discount' === $coupon->get_discount_type() ) {
			$shipping_discount_type = $coupon->get_meta( 'shipping_discount_type' );

			switch ( $shipping_discount_type ) {
				case 'fixed':
					$shipping_amount = wc_price( $coupon->get_amount() );
					break;
				case 'percentage':
					$shipping_amount = '<span class="woocommerce-Price-amount amount">' . $coupon->get_amount() . '%</span>';
					break;
				default:
					$shipping_amount = '';
			}

			$coupon_html = $shipping_amount . ' <a href="' . esc_url( add_query_arg( 'remove_coupon', rawurlencode( $coupon->get_code() ), Constants::is_defined( 'WOOCOMMERCE_CHECKOUT' ) ? wc_get_checkout_url() : wc_get_cart_url() ) ) . '" class="woocommerce-remove-coupon" data-coupon="' . esc_attr( $coupon->get_code() ) . '">' . __( '[Remove]', 'woocommerce' ) . '</a>';
		}

		return $coupon_html;
	}

	/** Override Shipping Price
	 *
	 * @param object $rates Rates.
	 * @return object
	 */
	public function override_shipping_price( $rates ) {

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return $rates;
		}

		$coupons         = WC()->cart->get_applied_coupons();
		$shipping_coupon = false;

		foreach ( $coupons as $coupon ) {
			$coupon                 = new WC_Coupon( $coupon );
			$shipping_discount_type = $coupon->get_meta( 'shipping_discount_type' );
			$shipping_coupon        = false;

			if ( 'shipping_discount' === $coupon->get_discount_type() ) {
				$shipping_coupon = true;
				break;
			}
		}

		if ( false === $shipping_coupon ) {
			return $rates;
		}

		foreach ( $rates as $rate ) {

			$shipping_before_discount = (int) $rate->cost;
			$coupon_amount            = (int) $coupon->get_amount();

			$max_amount = $coupon->get_meta( 'shipping_discount_max_amount' );
			if ( ! is_numeric( $max_amount ) && $max_amount < 1 ) {
				$max_amount = 0;
			}

			$rate->add_meta_data(
				'_shipping_discount',
				array(
					'before_discount' => $shipping_before_discount,
					'discount_type'   => $shipping_discount_type,
					'discount_amount' => $coupon_amount,
				)
			);

			switch ( $shipping_discount_type ) {
				case 'free':
					$cost = 0;
					break;
				case 'fixed':
					$cost = max( $shipping_before_discount - $coupon_amount, 0 );
					break;
				case 'percentage':
					$coupon_amount_percentage = ( $coupon_amount / 100 ) * $shipping_before_discount;
					if ( ( $max_amount > 0 ) && ( $coupon_amount_percentage > $max_amount ) ) {
						$coupon_amount_percentage = $max_amount;
					}
					$cost = max( $shipping_before_discount - $coupon_amount_percentage, 0 );
					break;
			}

			$rate->cost = $cost;
		}

		return $rates;
	}

	/** Override Shipping Label
	 *
	 * @param string $label Label.
	 * @param object $method Method.
	 */
	public function override_shipping_label( $label, $method ) {
		$coupons         = WC()->cart->get_applied_coupons();
		$shipping_coupon = false;

		foreach ( $coupons as $coupon ) {
			$coupon = new WC_Coupon( $coupon );

			if ( 'shipping_discount' === $coupon->get_discount_type() ) {
				$shipping_coupon = true;
				break;
			}
		}

		if ( false === $shipping_coupon ) {
			return $label;
		}

		$meta = $method->get_meta_data();

		$meta_shipping_discount = $meta['_shipping_discount'];
		if ( ! is_array( $meta_shipping_discount ) ) {
			return $label;
		}

		$shipping_before_discount = $meta_shipping_discount['before_discount'] ?? '';
		if ( empty( $shipping_before_discount ) ) {
			return $label;
		}

		$label_array = explode( ':', $label );

		if ( count( $label_array ) > 1 ) {
			array_pop( $label_array );
		}

		$label_str = implode( ':', $label_array );

		if ( ':' !== substr( $label_str, -1 ) ) {
			$label_str .= ': ';
		}

		if ( (int) $shipping_before_discount !== (int) $method->cost ) {
			$label = $label_str . '<del>' . wc_price( $shipping_before_discount ) . '</del> <ins>' . wc_price( $method->cost ) . '</ins>';
		}

		return $label;
	}

	/** Display Order Data in Admin Panel
	 *
	 * @param object $order Order.
	 */
	public function display_order_data_in_admin( $order ) {
		$shipping_discount = false;
		foreach ( $order->get_coupon_codes() as $coupon_code ) {
			$coupon = new WC_Coupon( $coupon_code );

			if ( 'shipping_discount' === $coupon->get_discount_type() ) {
				$shipping_discount = true;
				break;
			}
		}

		if ( false === $shipping_discount ) {
			return;
		}

		$meta_shipping_discount = false;
		foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {

			$meta_shipping_discount = $shipping_item->get_meta( '_shipping_discount' );
			if ( is_array( $meta_shipping_discount ) ) {
				$shipping_before_discount = (int) $meta_shipping_discount['before_discount'];
				$shipping_discount_type   = $meta_shipping_discount['discount_type'];
				$shipping_discount_amount = $meta_shipping_discount['discount_amount'];
				$shipping_after_discount  = (int) $shipping_item->get_total();
				if ( 'percentage' === $shipping_discount_type ) {
					$shipping_discount_amount .= '%';
				}
			}
		}

		?>
		<?php if ( is_array( $meta_shipping_discount ) ) : ?>
			<div class="order_data_column form-field-wide">
				<h3><?php esc_attr_e( 'Shipping Discount Details', 'shipping-discount' ); ?></h3>
				<p class="form-field form-field-wide wc-customer-user">
					<?php esc_attr_e( 'Before Discount', 'shipping-discount' ); ?>
					<span style="float: right"><?php echo wp_kses_post( wc_price( $shipping_before_discount ) ); ?></span>
				</p>
				<p class="form-field form-field-wide wc-customer-user">
					<?php esc_attr_e( 'After Discount', 'shipping-discount' ); ?>
					<span style="float: right"><?php echo wp_kses_post( wc_price( $shipping_after_discount ) ); ?></span>
				</p>
				<p class="form-field form-field-wide wc-customer-user">
					<?php esc_attr_e( 'Discount Type', 'shipping-discount' ); ?>
					<span style="float: right"><?php echo esc_html( strtoupper( $shipping_discount_type ) ); ?></span>
				</p>
				<p class="form-field form-field-wide wc-customer-user">
					<?php esc_attr_e( 'Discount Amount', 'shipping-discount' ); ?>
					<span style="float: right"><?php echo esc_html( strtoupper( $shipping_discount_amount ) ); ?></span>
				</p>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Enqueue backend scripts.
	 *
	 * @since 1.0.0
	 * @param string $hook Passed screen ID in admin area.
	 */
	public function enqueue_backend_assets( $hook ) {

		$hooks = array(
			'edit.php',
			'post.php',
			'post-new.php',
		);

		if ( ( ! is_admin() ) || ( ! in_array( $hook, $hooks, true ) ) ) {
			return;
		}

		// Define the scripts URL.
		$js_url = SHIPPING_DISCOUNT_URL . 'assets/shipping-discount.js';

		wp_enqueue_script(
			'shipping_discount', // Give the script a unique ID.
			$js_url, // Define the path to the JS file.
			array( 'jquery' ), // Define dependencies.
			shipping_discount_get_plugin_data( 'Version' ), // Define a version (optional).
			true // Specify whether to put in footer (leave this true).
		);
	}

	/** Override WooCommerce Percentage Label Format
	 *
	 * @return array
	 */
	public function wc_sc_percentage_override() {
		return array( 'percent_product', 'percent', 'shipping_discount' );
	}
}
