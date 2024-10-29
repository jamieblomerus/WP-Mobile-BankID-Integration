<?php
namespace Mobile_BankID_Integration\WP_Login;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use Mobile_BankID_Integration\Core;
use chillerlan\QRCode\QRCode;

new API();

/**
 * This class handles the API endpoints for the login page.
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
	public function register_routes(): void {
		register_rest_route(
			'mobile-bankid-integration/v1/login',
			'/identify',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'identify' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'mobile-bankid-integration/v1/login',
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'status' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle identify request.
	 *
	 * @return array
	 */
	public function identify() {
		$instance = Core::$instance;
		$response = $instance->identify();
		return $response;
	}

	/**
	 * Handle status request.
	 *
	 * @return array|\WP_Error
	 */
	public function status() {
		$instance = Core::$instance;

		if ( ! isset( $_GET['orderRef'] ) || preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $_GET['orderRef'] ) !== 1 ) { // phpcs:ignore
			return new \WP_Error( 'no_orderRef', 'No orderRef provided or invalid format.', array( 'status' => 400 ) );
		}

		$order_ref = $_GET['orderRef']; // phpcs:ignore -- We have checked that it is set and valid.
		$db_row    = $instance->getAuthResponseFromDB( $order_ref );
		if ( ! isset( $db_row ) ) {
			return new \WP_Error( 'no_orderRef', 'No orderRef found in DB.', array( 'status' => 400 ) );
		}
		$auth_response   = $db_row['response'];
		$time            = time();
		$time_since_auth = $time - $db_row['time_created'];

		$status = $instance->get_bankid_service()->collect( $_GET['orderRef'] ); // phpcs:ignore

		$status = $status->getBody();

		if ( 'failed' === $status['status'] ) {
			$instance->deleteAuthResponseFromDB( $order_ref );
			$return = array(
				'qr'              => null,
				'orderRef'        => $order_ref,
				'time_since_auth' => $time_since_auth,
				'status'          => 'failed',
				'hintCode'        => $status['hintCode'], // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			);
			return $return;
		}

		if ( 'complete' === $status['status'] ) {
			$instance->deleteAuthResponseFromDB( $order_ref );
			$create_user = $this->sign_in_as_user_from_bankid( $status['completionData']['user']['personalNumber'], $status['completionData']['user']['givenName'], $status['completionData']['user']['surname'] );
			if ( false === $create_user ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				return array(
					'qr'              => null,
					'orderRef'        => $order_ref,
					'time_since_auth' => $time_since_auth,
					'status'          => 'complete_no_user',
				);
			}
			return array(
				'qr'              => null,
				'orderRef'        => $order_ref,
				'time_since_auth' => $time_since_auth,
				'status'          => 'complete',
			);
		}

		$qr      = new QRCode();
		$qr_code = $qr->render( 'bankid.' . $auth_response['qrStartToken'] . '.' . $time_since_auth . '.' . hash_hmac( 'sha256', $time_since_auth, $auth_response['qrStartSecret'] ) );
		return array(
			'qr'              => $qr_code,
			'orderRef'        => $order_ref,
			'time_since_auth' => $time_since_auth,
			'status'          => $status['status'],
			'hintCode'        => $status['hintCode'] ?? '', // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		);
	}

	/**
	 * Sign in user from BankID or create user if it does not exist and registration is enabled.
	 *
	 * @param string $personal_number Personal identity number of user.
	 * @param string $fname First name as returned from BankID API.
	 * @param string $lname Last name as returned from BankID API.
	 * @return bool|WP_User
	 */
	private function sign_in_as_user_from_bankid( $personal_number, $fname, $lname ) {
		// Get user by personal identity number from DB.
		$user_id = Core::$instance->getUserIdFromPersonalNumber( $personal_number );

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			if ( get_option( 'mobile_bankid_integration_registration' ) !== 'yes' ) {
				return false;
			}

			// Create user.
			$user_id = wp_create_user( $this->random_username(), wp_generate_password() );
			$user    = get_user_by( 'id', $user_id );
			// Set user name.
			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => $fname . ' ' . $lname,
					'first_name'   => $fname,
					'last_name'    => $lname,
				)
			);

			// Set user personal identity number.
			Core::$instance->setPersonalNumberForUser( $user_id, $personal_number );
		} else {
			$user_id = $user->ID;
		}
		wp_clear_auth_cookie();
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );
		Core::$instance->createAuthCookie( $user_id );
		do_action( 'wp_login', $personal_number, $user );

		/**
		 * Fires after a user has successfully logged in via BankID.
		 * 
		 * @param WP_User $user WP_User object.
		 */
		do_action( 'mobile_bankid_integration_login_success', $user );

		return $user;
	}

	/**
	 * Generate random username.
	 *
	 * @return string
	 */
	private function random_username() {
		$user_exists = 1;
		do {
			$rnd_str     = sprintf( '%06d', wp_rand( 1, 999999 ) );
			$user_exists = username_exists( 'user_' . $rnd_str );
		} while ( $user_exists > 0 );

		$username = 'user_' . $rnd_str;

		/**
		 * Filter the username generated for new users.
		 *
		 * @param string $username Username.
		 * @since 1.3
		 */
		$username = apply_filters( 'mobile_bankid_integration_new_user_username', $username );

		return $username;
	}
}
