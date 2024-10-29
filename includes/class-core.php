<?php // phpcs:ignore
namespace Mobile_BankID_Integration;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use LJSystem\BankID\BankID;

new Core();

/**
 * Core of the plugin.
 * It is responsible for all interactions with the BankID API and authentication of users.
 */
class Core {

	/**
	 * Static variable that holds the instance of the class and make sure that there is only one instance at a time.
	 *
	 * @var Core|null
	 */
	public static Core|null $instance = null;

	/**
	 * BankIDService object.
	 *
	 * @var BankID|null
	 */
	private BankID $bankid_service;

	/**
	 * Class constructor that sets static $instance variable and adds actions.
	 */
	public function __construct() {
		if ( isset( self::$instance ) ) {
			return;
		}
		self::$instance = $this;
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_logout', array( $this, 'deleteAuthCookie' ) );
	}

	/**
	 * Create BankIDService object and do action mobile_bankid_integration_init if required options are set.
	 *
	 * @return void
	 */
	public function init() {
		if ( get_option( 'mobile_bankid_integration_env' ) && get_option( 'mobile_bankid_integration_certificate' ) && get_option( 'mobile_bankid_integration_password' ) ) {
			$this->create_bankid_service();

			/**
			 * Fires when the plugin is initialized and the BankID service has been created.
			 * 
			 * @since 1.0.0
			 */
			do_action( 'mobile_bankid_integration_init' );
		}
	}

	/**
	 * Create BankIDService object.
	 *
	 * @return void
	 */
	private function create_bankid_service() {
		if ( 'test' === get_option( 'mobile_bankid_integration_env' ) ) {
			$this->bankid_service = new BankID(
				BankID::ENVIRONMENT_TEST,
				MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'assets/certs/test.pem',
				MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'assets/certs/test_cacert.cer',
				null,
				'qwerty123'
			);
		} else {
			$this->bankid_service = new BankID(
				BankID::ENVIRONMENT_PRODUCTION,
				get_option( 'mobile_bankid_integration_certificate' ),
				MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'assets/certs/prod_cacert.cer',
				null,
				get_option( 'mobile_bankid_integration_password' )
			);
		}
	}

	/**
	 * Get BankIDService object.
	 *
	 * @return BankID
	 */
	public function get_bankid_service() {
		return $this->bankid_service;
	}

	/**
	 * Creating new identification order.
	 *
	 * @return array
	 */
	public function identify() {
		if ( ! isset( $this->bankid_service ) ) {
			$this->create_bankid_service();
		}

		$response = $this->bankid_service->authenticate( $_SERVER['REMOTE_ADDR'] ); // phpcs:ignore
		// Save the response in DB.
		$this->saveAuthResponseToDB( $response->getOrderRef(), $response->getBody() );
		return array(
			'orderRef'       => $response->getOrderRef(),
			'autoStartToken' => $response->getAutoStartToken(),
		);
	}

	/**
	 * Read the auth_response from DB.
	 *
	 * @param string $order_ref BankID order reference.
	 * @return array|null
	 */
	public function getAuthResponseFromDB( $order_ref ) {
		global $wpdb;
		$response = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}mobile_bankid_integration_auth_responses WHERE orderRef = %s",
				$order_ref
			)
		);
		if ( ! $response ) {
			return null;
		}
		return array(
			'time_created' => $response->time_created,
			'response'     => json_decode( $response->response, true ),
			'orderRef'     => $response->orderRef, // phpcs:ignore -- We shall not modify $orderRef to snake_case.
		);
	}

	/**
	 * Save the auth_response to DB.
	 *
	 * @param string $orderRef BankID order reference.
	 * @param array  $response BankID response.
	 * @return void
	 */
	private function saveAuthResponseToDB( $orderRef, $response ) { // phpcs:ignore -- We shall not modify $orderRef to snake_case.
		global $wpdb;
		$table_name = $wpdb->prefix . 'mobile_bankid_integration_auth_responses';
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$table_name,
			array(
				'time_created' => time(),
				'response'     => wp_json_encode( $response ),
				'orderRef'     => $orderRef, // phpcs:ignore -- We shall not modify $orderRef to snake_case.
			)
		);
	}

	/**
	 * Delete the auth_response from DB.
	 *
	 * @param string $orderRef BankID order reference.
	 * @return void
	 */
	public function deleteAuthResponseFromDB( $orderRef ) { // phpcs:ignore -- We shall not modify $orderRef to snake_case.
		global $wpdb;
		$table_name = $wpdb->prefix . 'mobile_bankid_integration_auth_responses';
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_name,
			array(
				'orderRef' => $orderRef, // phpcs:ignore -- We shall not modify $orderRef to snake_case.
			)
		);
	}

	/**
	 * Get user ID from personal identity number.
	 *
	 * @param string $personal_number Personal identity number (12 digits, no hyphen).
	 * @return int|false
	 */
	public function getUserIdFromPersonalNumber( $personal_number ) {
		// Get user by personal identity number from User Meta.
		$user_query = new \WP_User_Query(
			array(
				'meta_key'   => 'mobile_bankid_integration_personal_number',
				'meta_value' => $personal_number,
			)
		);
		$users      = $user_query->get_results();
		if ( count( $users ) > 0 && count( $users ) < 2 ) {
			return $users[0]->ID;
		}
		return false;
	}

	/**
	 * Set personal identity number for user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $personal_number Personal identity number (12 digits, no hyphen).
	 * @return void
	 */
	public function setPersonalNumberForUser( $user_id, $personal_number ) {
		// Check if user already has a personal identity number.
		if ( $this->getUserIdFromPersonalNumber( $personal_number ) !== false ) {
			return;
		}

		update_user_meta( $user_id, 'mobile_bankid_integration_personal_number', $personal_number );
	}

	/**
	 * Authentication cookies are used to verify the identity of a user who logs in to the site.
	 *
	 * Authentication cookies are set when a user logs in to the site, and are used to verify the identity of a user who logs in to the site.
	 * They are a guarantee that the user signed in to the site using Mobile BankID.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function createAuthCookie( $user_id ) {
		// Check that user exists.
		if ( ! get_user_by( 'id', $user_id ) ) {
			return;
		}
		// START SESSION.
		new Session( $user_id );
	}

	/**
	 * Verify the authentication cookie.
	 *
	 * @return bool
	 */
	public function verifyAuthCookie() {
		// START SESSION.
		$session = Session::load();

		if ( ! $session ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete the authentication cookie.
	 *
	 * @return void
	 */
	public function deleteAuthCookie() {
		// START SESSION.
		$session = Session::load();

		if ( ! $session ) {
			return;
		}

		$session->destroy();
	}
}
