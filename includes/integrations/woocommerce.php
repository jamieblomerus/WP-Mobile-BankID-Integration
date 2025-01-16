<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
namespace Mobile_BankID_Integration\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use Mobile_BankID_Integration\Core;
use Personnummer\Personnummer;

add_filter(
	'woocommerce_integrations',
	function ( $integrations ) {
		$integrations[] = 'Mobile_BankID_Integration\Integrations\WooCommerce\Settings';
		return $integrations;
	}
);
new Login();
new Checkout();
new Product();

/**
 * This class provides the settings for the WooCommerce integration.
 */
final class Settings extends \WC_Integration { // phpcs:ignore
	/**
	 * Class constructor that adds the settings to the WooCommerce settings page.
	 */
	public function __construct() {
		$this->id = 'mobile-bankid-integration';
		// Translators: WooCommerce integration title.
		$this->method_title = __( 'Mobile BankID', 'mobile-bankid-integration' );
		// Translators: WooCommerce integration description.
		$this->method_description = __( 'Let customers login and verify their age using Mobile BankID.', 'mobile-bankid-integration' );

		// If old settings exist, migrate them.
		$this->migrate_settings();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialize integration settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'login'                           => array(
				'title'       => __( 'Login using BankID', 'mobile-bankid-integration' ),
				'label'       => __( 'Let customers login using Mobile BankID on My Account page.', 'mobile-bankid-integration' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'my_account_show_personal_number' => array(
				'title'       => __( 'Show personal number field on My Account page', 'mobile-bankid-integration' ),
				'label'       => __( 'Show personal number field on My Account page.', 'mobile-bankid-integration' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'checkout_require_bankid'         => array(
				'title'       => __( 'Require users to be authenticated through Mobile BankID at checkout', 'mobile-bankid-integration' ),
				'label'       => __( 'Require customer to be logged in with BankID at checkout. This helps to follow the law regarding sale of age-restricted products.', 'mobile-bankid-integration' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'age_check'                       => array(
				'title'       => __( 'Require users to be over a certain age at checkout (0 to disable)', 'mobile-bankid-integration' ),
				'label'       => __( 'Require customers to be over a certain age at checkout. This helps to follow the law regarding sale of age-restricted products.<br>This requires that users are forced to sign in with BankID at checkout.', 'mobile-bankid-integration' ),
				'type'        => 'number',
				'description' => '',
				'default'     => '0',
			),
		);
	}

	/**
	 * Migrate old settings (prior to 1.5) to new settings.
	 *
	 * @return void
	 */
	private function migrate_settings() {
		$options  = array(
			'mobile_bankid_integration_woocommerce_login' => 'login',
			'mobile_bankid_integration_woocommerce_checkout_require_bankid' => 'checkout_require_bankid',
			'mobile_bankid_integration_woocommerce_age_check' => 'age_check',
		);
		$settings = get_option( 'woocommerce_mobile-bankid-integration_settings', array() );

		foreach ( $options as $old => $new ) {
			$option = get_option( $old, null );
			if ( null !== $option ) {
				$settings[ $new ] = $option;
			}
		}

		update_option( 'woocommerce_mobile-bankid-integration_settings', $settings );

		foreach ( $options as $old => $new ) {
			delete_option( $old );
		}
	}

	/**
	 * Get the settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$settings = get_option(
			'woocommerce_mobile-bankid-integration_settings',
			array(
				'login'                           => 'no',
				'my_account_show_personal_number' => 'no',
				'checkout_require_bankid'         => 'no',
				'age_check'                       => 0,
			)
		);
		return $settings;
	}
}

/**
 * This class provides the login button on the WooCommerce login page.
 */
class Login extends \Mobile_BankID_Integration\WP_Login\Login { // phpcs:ignore

	/**
	 * Class constructor that adds the login button to the login page if the plugin is configured to do so.
	 */
	public function __construct() {
		$settings = Settings::get_settings();
		if ( 'yes' === $settings['login'] && ( get_option( 'mobile_bankid_integration_certificate' ) && get_option( 'mobile_bankid_integration_password' ) && get_option( 'mobile_bankid_integration_env' ) ) ) {
			add_action(
				'woocommerce_login_form_end',
				function () {
					$this->login_button( wc_get_page_permalink( 'myaccount' ) );
					$this->terms( 0.9 );
				}
			);
			add_action(
				'woocommerce_login_form_end',
				function () {
					$this->login_container( 'div' );
				}
			);
		}
	}
}

/**
 * This class provides the checkout block on the WooCommerce checkout page.
 */
class Checkout { // phpcs:ignore

	/**
	 * Class constructor that adds the checkout block to the checkout page if the plugin is configured to do so.
	 */
	public function __construct() {
		if ( ! get_option( 'mobile_bankid_integration_certificate' ) || ! get_option( 'mobile_bankid_integration_password' ) || ! get_option( 'mobile_bankid_integration_env' ) ) {
			return;
		}
		add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'checkout_block' ), 10 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate' ), 10, 2 );
	}

	/**
	 * Add checkout block to checkout page.
	 *
	 * @return void
	 */
	public function checkout_block() {
		$settings = Settings::get_settings();
		if ( 'yes' !== $settings['checkout_require_bankid'] && $this->cart_age_limit() <= 0 ) {
			return;
		}

		if ( Core::$instance->verifyAuthCookie() && $this->cart_age_check() ) {
			return;
		} elseif ( Core::$instance->verifyAuthCookie() ) {
			if ( $this->cart_age_check() ) {
				return;
			} elseif ( $this->cart_age_limit() === $this->store_age_limit() ) {
					// Translators: Age.
					wc_add_notice( sprintf( __( 'You must be over %s years old to make an order.', 'mobile-bankid-integration' ), $this->store_age_limit() ), 'error' );
					return;
			} else {
				// Translators: Age.
				wc_add_notice( sprintf( __( 'Your cart contains products that require you to be over %s years old to make an order.', 'mobile-bankid-integration' ), $this->cart_age_limit() ), 'error' );
				return;
			}
		}

		?>
		<div id="bankid-checkout-block">
			<div class="wc-block-components-notice-banner is-warning" style="display:block;" role="alert">
				<h2><?php esc_html_e( 'Mobile BankID Authentication required', 'mobile-bankid-integration' ); ?></h3>
				<p><?php esc_html_e( 'This site requires you to be authenticated through Mobile BankID to make an order.', 'mobile-bankid-integration' ); ?></p>

				<p><a href="#" id="bankid-login-button" class="button wp-element-button" style="text-align: center;"><?php esc_html_e( 'Login with BankID', 'mobile-bankid-integration' ); ?></a></p>
				<p><?php esc_html_e( 'If you do not have Mobile BankID, you can download it from your bank.', 'mobile-bankid-integration' ); ?></p>
				<noscript>
					<p><?php esc_html_e( 'This feature requires JavaScript. Please enable it.', 'mobile-bankid-integration' ); ?></p>
					<style>#bankid-login-button { display: none; height: 0; margin: 0; }</style>
				</noscript>
				<?php
				// Load scripts.
				$login = new \Mobile_BankID_Integration\WP_Login\Login();
				$login->load_scripts( '/checkout' );
				?>
			</div>
			<?php
			$login->login_container( 'div', array( 'wc-block-components-notice-banner', 'is-info' ) );
			?>
		</div>
		<?php
		echo wp_kses( apply_filters( 'mobile_bankid_integration_checkout_block_style', '<style>#bankid-checkout-block h2 { font-size: 1.5em; }</style>' ), array( 'style' => array() ) );
	}

	/**
	 * Get store-wide age limit.
	 *
	 * @return int
	 */
	private function store_age_limit(): int {
		$settings  = Settings::get_settings();
		$age_limit = (int) $settings['age_check'];

		/**
		 * Filter the store-wide age limit.
		 *
		 * @param int $age_limit Age limit.
		 * @since 1.5
		 */
		$age_limit = apply_filters( 'mobile_bankid_integration_woocommerce_store_age_limit', $age_limit );

		return $age_limit;
	}

	/**
	 * Get age limit for cart. When multiple products are in the cart, the highest age limit is used.
	 *
	 * Default age limit is the store-wide age limit.
	 *
	 * @return int
	 */
	private function cart_age_limit(): int {
		$age  = $this->store_age_limit();
		$cart = WC()->cart->get_cart();
		foreach ( $cart as $item ) {
			$product_id  = $item['product_id'];
			$product_age = get_post_meta( $product_id, 'mobile_bankid_integration_woocommerce_age_check', true );
			if ( $product_age > $age ) {
				$age = $product_age;
			}
		}

		/**
		 * Filter the age limit for the cart.
		 *
		 * @param int $age Age.
		 * @since 1.5
		 */
		$age = apply_filters( 'mobile_bankid_integration_woocommerce_cart_age_limit', $age, $cart );

		return $age;
	}

	/**
	 * Check if user is over the age limit for the cart products and store-wide age limit.
	 *
	 * @return bool
	 */
	public function cart_age_check(): bool {
		$age = $this->cart_age_limit();
		return $this->age_check( $age );
	}

	/**
	 * Check if user is over a certain age.
	 *
	 * @param int|null $age Age.
	 * @return bool
	 */
	public function age_check( ?int $age = null ): bool {

		if ( is_null( $age ) ) {
			$age = $this->store_age_limit();

			/**
			 * Filter the age check.
			 *
			 * @param int $age Age.
			 * @since 1.3
			 */
			$age = apply_filters( 'mobile_bankid_integration_age_check', $age );
		}

		if ( $age <= 0 ) {
			return true;
		}
		$session = \Mobile_BankID_Integration\Session::load();
		if ( ! $session ) {
			return false;
		}
		$personal_number = $session->personal_number;
		if ( ! $personal_number ) {
			return false;
		}
		$userage = ( new Personnummer( $personal_number ) )->getAge();
		if ( $userage < $age ) {
			return false;
		}
		return true;
	}

	/**
	 * Validate checkout.
	 *
	 * @param array  $data WooCommerce data.
	 * @param object $errors WooCommerce errors.
	 * @return void
	 */
	public function validate( $data, $errors ) {
		$settings = Settings::get_settings();
		if ( 'yes' !== $settings['checkout_require_bankid'] && $this->cart_age_limit() <= 0 ) {
			return;
		}

		if ( Core::$instance->verifyAuthCookie() ) {
			if ( $this->cart_age_check() ) {
				return;
			} elseif ( $this->cart_age_limit() === $this->store_age_limit() ) {
					// Translators: Age.
					$errors->add( 'bankid_error', sprintf( __( 'You must be over %s years old to make an order.', 'mobile-bankid-integration' ), $this->store_age_limit() ) );
					return;
			} else {
				// Translators: Age.
				$errors->add( 'bankid_error', sprintf( __( 'Your cart contains products that require you to be over %s years old to make an order.', 'mobile-bankid-integration' ), $this->cart_age_limit() ) );
				return;
			}
		}
		$errors->add( 'bankid_error', __( 'You must be authenticated through Mobile BankID to make an order.', 'mobile-bankid-integration' ) );
	}
}

/**
 * This class provides the ability to age restrict individual products in WooCommerce.
 */
class Product { // phpcs:ignore

	/**
	 * Class constructor that adds the age restriction to the product page if the plugin is configured to do so.
	 */
	public function __construct() {
		if ( ! get_option( 'mobile_bankid_integration_certificate' ) || ! get_option( 'mobile_bankid_integration_password' ) || ! get_option( 'mobile_bankid_integration_env' ) ) {
			return;
		}
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'product_age_check_setting' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'product_age_check_save' ) );
		add_action( 'woocommerce_product_meta_end', array( $this, 'product_display_age_limit' ) );
	}

	/**
	 * Add age restriction setting to product page.
	 *
	 * @return void
	 */
	public function product_age_check_setting() {
		woocommerce_wp_text_input(
			array(
				'id'                => 'mobile_bankid_integration_woocommerce_age_check',
				'label'             => __( 'Age restriction', 'mobile-bankid-integration' ),
				'description'       => __( 'Require users to identify themselves with Mobile BankID and be over a certain age to purchase this product.', 'mobile-bankid-integration' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'min'  => 0,
					'step' => 1,
					'max'  => 130,
				),
			)
		);
	}

	/**
	 * Save age restriction setting to product.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function product_age_check_save( $post_id ) {
		$age = isset( $_POST['mobile_bankid_integration_woocommerce_age_check'] ) ? wc_clean( wp_unslash( $_POST['mobile_bankid_integration_woocommerce_age_check'] ) ) : 0; // phpcs:ignore -- WP handles nonces and wc_clean handles sanitization.
		update_post_meta( $post_id, 'mobile_bankid_integration_woocommerce_age_check', $age );
	}

	/**
	 * Display age limit on product page.
	 *
	 * @return void
	 */
	public function product_display_age_limit() {
		global $product;

		/**
		 * Filter to disable age limit display on product page.
		 *
		 * @param bool $display_age_limit Whether to display the age limit on the product page.
		 * @since 1.5
		 */
		$display_age_limit = apply_filters( 'mobile_bankid_integration_woocommerce_product_display_age_limit', true );
		if ( ! $display_age_limit ) {
			return;
		}

		$age = get_post_meta( $product->get_id(), 'mobile_bankid_integration_woocommerce_age_check', true );
		if ( $age > 0 ) {
			// Translators: Age.
			$message = sprintf( _nx( 'Age restriction: %s year (verification required via Mobile BankID)', 'Age restriction: %s years (verification required via Mobile BankID)', $age, 'Product page meta section', 'mobile-bankid-integration' ), $age );

			/**
			 * Filter the age limit message displayed on the product page.
			 *
			 * @param string $message Age limit message.
			 * @param int    $age Age limit.
			 * @param object $product Product object.
			 * @since 1.5
			 */
			$message = apply_filters( 'mobile_bankid_integration_woocommerce_product_age_limit_message', $message, $age, $product );

			echo '<span class="age-restriction">' . esc_html( $message ) . '</span>';
		}
	}
}