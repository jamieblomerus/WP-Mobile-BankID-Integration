<?php
namespace Mobile_BankID_Integration\Settings;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

new Setup();

/**
 * This class handles the setup wizard.
 */
class Setup {
	/**
	 * Class constructor that adds the wizard page.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'wizard' ) );
	}

	/**
	 * Register the wizard page.
	 *
	 * @return void
	 */
	public function wizard() {
		// Register the wizard page as submenu of a non-existent page.
		add_submenu_page(
			'non-existent-page-slug',
			esc_html__( 'Mobile BankID Integration Setup', 'mobile-bankid-integration' ),
			esc_html__( 'Mobile BankID Integration Setup', 'mobile-bankid-integration' ),
			'manage_options',
			'mobile-bankid-integration-setup',
			array( $this, 'wizard_page' )
		);
	}

	/**
	 * Load the wizard page.
	 *
	 * @return void
	 */
	public function wizard_page() {
		// Check if the user is allowed to access this page.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		// Register styles and scripts.
		$this->register_scripts();
		// Load the setup page.
		require_once MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'includes/settings/views/setup.php';
	}

	/**
	 * Register styles and scripts.
	 *
	 * @return void
	 */
	public function register_scripts() {
		// Register styles.
		wp_register_style( 'mobile-bankid-integration-setup', MOBILE_BANKID_INTEGRATION_PLUGIN_URL . 'assets/css/setup.css', array(), MOBILE_BANKID_INTEGRATION_VERSION );
		wp_enqueue_style( 'mobile-bankid-integration-setup' );
		// Register scripts.
		wp_register_script( 'mobile-bankid-integration-setup', MOBILE_BANKID_INTEGRATION_PLUGIN_URL . 'assets/js/setup.js', array(), MOBILE_BANKID_INTEGRATION_VERSION, true );
		wp_enqueue_script( 'mobile-bankid-integration-setup' );
		wp_localize_script(
			'mobile-bankid-integration-setup',
			'mobile_bankid_integration_setup_localization',
			array(
				'confirmation_abort_text'   => esc_html__( 'Press "Abort" below to abort this operation.', 'mobile-bankid-integration' ),
				'testenv_confirmation_text' => esc_html__( 'Are you sure you want to auto-configure the plugin for the test enviroment? This is not considered safe as test BankIDs are open for registration by anyone, without any kind of identification. DO NOT USE TEST ENVIRONMENT ON A PRODUCTION SITE.', 'mobile-bankid-integration' ),
				'testenv_autoconfig_failed' => __( 'Autoconfigure test environment failed. Please try again or configure manually.', 'mobile-bankid-integration' ),
				'configuration_failed'      => __( 'Configuration failed. The following error was reported by the server: ', 'mobile-bankid-integration' ),
				// Manual configuration form validation.
				'endpoint_required'         => __( 'API Endpoint is required.', 'mobile-bankid-integration' ),
				'certificate_required'      => __( 'Path to .p12 certificate is required.', 'mobile-bankid-integration' ),
				'password_required'         => __( 'Password for the .p12 certificate is required.', 'mobile-bankid-integration' ),
			)
		);
		wp_add_inline_script( 'mobile-bankid-integration-setup', 'var mobile_bankid_integration_rest_api = "' . rest_url( 'mobile-bankid-integration/v1/settings' ) . '"; var mobile_bankid_integration_rest_api_nonce = "' . wp_create_nonce( 'wp_rest' ) . '";', 'before' );
	}
}
