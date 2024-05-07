<?php
namespace Mobile_BankID_Integration\Settings;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

new API();

/**
 * This class handles the API endpoints for the settings page and setup wizard.
 */
class API {

	/**
	 * Class constructor that adds the API endpoints.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register API endpoints.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'mobile-bankid-integration/v1/settings',
			'/configuration',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'configuration' ),
				'permission_callback' => array( $this, 'have_rights' ),
			)
		);
		register_rest_route(
			'mobile-bankid-integration/v1/settings',
			'/autoconfiguretestenv',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'auto_configure_test_env' ),
				'permission_callback' => array( $this, 'have_rights' ),
			)
		);
		register_rest_route(
			'mobile-bankid-integration/v1/settings',
			'/setup_settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'setup_settings' ),
				'permission_callback' => array( $this, 'have_rights' ),
			)
		);
		register_rest_route(
			'mobile-bankid-integration/v1/settings',
			'/settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'settings' ),
				'permission_callback' => array( $this, 'have_rights' ),
			)
		);
	}

	/**
	 * Check if the current user has rights to access the API.
	 *
	 * @return boolean
	 */
	public function have_rights() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Handle configuration request.
	 *
	 * @return bool|\WP_Error
	 */
	public function configuration() {
		// Get params.
		$certificate = isset( $_POST['certificate'] ) ? sanitize_text_field( wp_unslash( $_POST['certificate'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification
		$password    = isset( $_POST['password'] ) ? $_POST['password'] : null; // phpcs:ignore

		// Check that submitted certificate is valid and exists.
		if ( ! isset( $certificate ) || ! preg_match( '/^\/([A-z0-9-_+]+\/)*([A-z0-9]+\.(pem))$/', $certificate ) ) {
			return new \WP_Error( 'invalid_certificate', esc_html__( 'Certificate is not valid.', 'mobile-bankid-integration' ), array( 'status' => 400 ) );
		}
		if ( ! file_exists( $certificate ) ) {
			return new \WP_Error( 'certificate_does_not_exist', esc_html__( 'Certificate does not exist on specified path.', 'mobile-bankid-integration' ), array( 'status' => 400 ) );
		}

		// Check that password is not empty and is longer than 12 characters.
		if ( empty( $password ) ) {
			return new \WP_Error( 'empty_password', esc_html__( 'Password is empty.', 'mobile-bankid-integration' ), array( 'status' => 400 ) );
		}
		if ( strlen( $password ) < 12 && 'qwerty123' !== $password ) {
			return new \WP_Error( 'short_password', esc_html__( 'Password must be longer than 12 characters.', 'mobile-bankid-integration' ), array( 'status' => 400 ) );
		}

		// Check that password contains atleast 4 letters and 1 number, per BankID docs.
		if ( preg_match_all( '/[A-z]/', $password ) < 4 || preg_match_all( '/[0-9]/', $password ) < 1 ) {
			return new \WP_Error( 'password_format', esc_html__( 'Password must have atleast 4 letters and 1 number.', 'mobile-bankid-integration' ), array( 'status' => 400 ) );
		}

		// TODO: Check that password is valid, test it against the certificate.

		// Update the WP options.
		update_option( 'mobile_bankid_integration_env', 'production' );
		update_option( 'mobile_bankid_integration_certificate', $certificate );
		update_option( 'mobile_bankid_integration_password', $password );

		return true;
	}

	/**
	 * Handle auto configure test env request.
	 *
	 * Automatically configures the plugin to use the test environment as described in the BankID docs.
	 *
	 * @see https://www.bankid.com/en/utvecklare/test/skaffa-testbankid
	 * @return bool|\WP_Error
	 */
	public function auto_configure_test_env() {
		// Update the WP option.
		update_option( 'mobile_bankid_integration_env', 'test' );
		update_option( 'mobile_bankid_integration_certificate', 'test-env' );
		update_option( 'mobile_bankid_integration_password', 'test-env' );

		return true;
	}

	/**
	 * Handle setup settings request.
	 *
	 * @return bool|\WP_Error
	 */
	public function setup_settings() {
		// Get params.
		$wplogin      = isset( $_POST['wplogin'] ) ? sanitize_text_field( wp_unslash( $_POST['wplogin'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification
		$registration = isset( $_POST['registration'] ) ? sanitize_text_field( wp_unslash( $_POST['registration'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification

		// Make sure that the submitted values are valid.
		if ( ! isset( $wplogin ) || ! in_array( $wplogin, array( 'as_alternative', 'hide' ), true ) ) {
			return new \WP_Error( 'invalid_wplogin', esc_html__( 'Invalid value for wplogin.', 'mobile-bankid-integration' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $registration, array( 'yes', 'no' ), true ) ) {
			return new \WP_Error( 'invalid_registration', esc_html__( 'Invalid value for registration.', 'mobile-bankid-integration' ), array( 'status' => 400 ) );
		}

		// Update the WP options.
		update_option( 'mobile_bankid_integration_wplogin', $wplogin );
		update_option( 'mobile_bankid_integration_registration', $registration );
		return true;
	}

	/**
	 * Handle settings request.
	 *
	 * @return bool|\WP_Error
	 */
	public function settings() {
		// Get params.
		$wplogin      = isset( $_POST['wplogin'] ) ? sanitize_text_field( wp_unslash( $_POST['wplogin'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification
		$registration = isset( $_POST['registration'] ) ? sanitize_text_field( wp_unslash( $_POST['registration'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification
		$terms        = isset( $_POST['terms'] ) ? sanitize_text_field( wp_unslash( $_POST['terms'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! in_array( $wplogin, array( 'as_alternative', 'hide' ), true ) ) {
			return new \WP_Error( 'invalid_wplogin', esc_html__( 'Invalid value for wplogin.', 'mobile-bankid-integration' ), array( 'status' => 400 ) );
		}

		if ( ! in_array( $registration, array( 'yes', 'no' ), true ) ) {
			return new \WP_Error( 'invalid_registration', esc_html__( 'Invalid value for registration.', 'mobile-bankid-integration' ), array( 'status' => 400 ) );
		}

		// Update the WP options.
		update_option( 'mobile_bankid_integration_wplogin', $wplogin );
		update_option( 'mobile_bankid_integration_registration', $registration );
		update_option( 'mobile_bankid_integration_terms', $terms );
		return true;
	}
}
