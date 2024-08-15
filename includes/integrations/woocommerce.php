<?php // phpcs:ignore
namespace Mobile_BankID_Integration\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use Mobile_BankID_Integration\Core;
use Personnummer\Personnummer;

new Settings();
new Login();
new Checkout();

/**
 * This class handles the WooCommerce integration settings.
 */
final class Settings {
	/**
	 * Class constructor that adds the settings to the WooCommerce settings page.
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_sections_advanced', array( $this, 'add_settings_section' ) );
		add_filter( 'woocommerce_get_settings_advanced', array( $this, 'add_settings' ), 10, 2 );
	}

	/**
	 * Add settings section to WooCommerce settings page.
	 *
	 * @param array $sections Array of sections.
	 * @return array
	 */
	public function add_settings_section( $sections ) {
		$sections['mobile_bankid_integration'] = __( 'Mobile BankID Integration', 'mobile-bankid-integration' );
		return $sections;
	}

	/**
	 * Add settings to WooCommerce settings page.
	 *
	 * @param array  $settings Array of settings.
	 * @param string $current_section Current section.
	 * @return array
	 */
	public function add_settings( $settings, $current_section ) {
		if ( 'mobile_bankid_integration' === $current_section ) {
			$settings_mobile_bankid_integration   = array();
			$settings_mobile_bankid_integration[] = array(
				'name' => __( 'Mobile BankID Integration', 'mobile-bankid-integration' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'mobile_bankid_integration',
			);
			/* Login using BankID */
			$settings_mobile_bankid_integration[] = array(
				'name'    => __( 'Login using BankID', 'mobile-bankid-integration' ),
				'desc'    => __( 'Let customers login using Mobile BankID on My Account page.', 'mobile-bankid-integration' ),
				'id'      => 'mobile_bankid_integration_woocommerce_login',
				'type'    => 'checkbox',
				'css'     => 'min-width:300px;',
				'default' => 'no',
			);
			/* Require customer to be logged in with BankID at checkout */
			$settings_mobile_bankid_integration[] = array(
				'name'    => __( 'Require users to be authenticated through Mobile BankID at checkout', 'mobile-bankid-integration' ),
				'desc'    => __( 'Require customer to be logged in with BankID at checkout. This helps to follow the law regarding sale of age-restricted products.', 'mobile-bankid-integration' ),
				'id'      => 'mobile_bankid_integration_woocommerce_checkout_require_bankid',
				'type'    => 'checkbox',
				'css'     => 'min-width:300px;',
				'default' => 'no',
			);
			/* Require customer to be over a certain age at checkout */
			$settings_mobile_bankid_integration[] = array(
				'name'    => __( 'Require users to be over a certain age at checkout (0 to disable)', 'mobile-bankid-integration' ),
				'desc'    => __( 'Require customers to be over a certain age at checkout. This helps to follow the law regarding sale of age-restricted products.<br>This requires that users are forced to sign in with BankID at checkout.', 'mobile-bankid-integration' ),
				'id'      => 'mobile_bankid_integration_woocommerce_age_check',
				'type'    => 'number',
				'css'     => 'min-width:300px;',
				'default' => '0',
			);

			$settings_mobile_bankid_integration[] = array(
				'type' => 'sectionend',
				'id'   => 'wcslider',
			);
			return $settings_mobile_bankid_integration;
		} else {
			return $settings;
		}
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
		if ( get_option( 'mobile_bankid_integration_woocommerce_login' ) === 'yes' && ( get_option( 'mobile_bankid_integration_certificate' ) && get_option( 'mobile_bankid_integration_password' ) && get_option( 'mobile_bankid_integration_env' ) ) ) {
			add_action(
				'woocommerce_login_form_end',
				function () {
					$this->login_button( '/my-account' );
					$this->terms( 0.9 );
				}
			);
			add_action( 'woocommerce_login_form_end', function () {
				$this->login_container( 'div' );
			} );
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

		if ( get_option( 'mobile_bankid_integration_woocommerce_checkout_require_bankid' ) !== 'yes' ) {
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
		if ( Core::$instance->verifyAuthCookie() && get_option( 'mobile_bankid_integration_woocommerce_age_check', 0 ) <= 0 ) {
			return;
		} elseif ( Core::$instance->verifyAuthCookie() ) {
			if ( $this->age_check() ) {
				return;
			} else {
				// Translators: Age.
				wc_add_notice( sprintf( __( 'You must be over %s years old to make an order.', 'mobile-bankid-integration' ), get_option( 'mobile_bankid_integration_woocommerce_age_check' ) ), 'error' );
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
	 * Check if user is over a certain age.
	 *
	 * @return bool
	 */
	public function age_check(): bool {
		$age = get_option( 'mobile_bankid_integration_woocommerce_age_check', 0 );

		/**
		 * Filter the age check.
		 * 
		 * @param int $age Age.
		 * @since 1.3
		 */
		$age = apply_filters( 'mobile_bankid_integration_age_check', $age );

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
		if ( Core::$instance->verifyAuthCookie() ) {
			if ( $this->age_check() ) {
				return;
			} else {
				// Translators: Age.
				$errors->add( 'bankid_error', sprintf( __( 'You must be over %s years old to make an order.', 'mobile-bankid-integration' ), get_option( 'mobile_bankid_integration_woocommerce_age_check' ) ) );
				return;
			}
		}
		$errors->add( 'bankid_error', __( 'You must be authenticated through Mobile BankID to make an order.', 'mobile-bankid-integration' ) );
	}
}