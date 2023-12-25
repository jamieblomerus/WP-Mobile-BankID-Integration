<?php
namespace Mobile_BankID_Integration;

use Dimafe6\BankID\Service\BankIDService;

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
	 * @var BankIDService|null
	 */
	private BankIDService $bankid_service;

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
		if ( get_option( 'mobile_bankid_integration_endpoint' ) && get_option( 'mobile_bankid_integration_certificate' ) && get_option( 'mobile_bankid_integration_password' ) ) {
			$this->create_bankid_service();
			do_action( 'mobile_bankid_integration_init' );
		}
	}

	/**
	 * Create BankIDService object.
	 *
	 * @return void
	 */
	private function create_bankid_service() {
		$this->bankid_service = new BankIDService(
			get_option( 'mobile_bankid_integration_endpoint' ),
			$_SERVER['REMOTE_ADDR'], // phpcs:ignore -- Does always exist and isn't user input.
			array(
				'verify' => false,
				'cert'   => array( get_option( 'mobile_bankid_integration_certificate' ), get_option( 'mobile_bankid_integration_password' ) ),
			)
		);
	}

	/**
	 * Get BankIDService object.
	 *
	 * @return BankIDService
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

		$response = $this->bankid_service->getAuthResponse();
		// Save the response in DB.
		$this->saveAuthResponseToDB( $response->orderRef, $response ); // phpcs:ignore -- We cannot modify $orderRef to snake_case.
		return array(
			'orderRef'       => $response->orderRef, // phpcs:ignore -- We cannot modify $orderRef to snake_case.
			'autoStartToken' => $response->autoStartToken, // phpcs:ignore -- We cannot modify $autoStartToken to snake_case.
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
			'response'     => $this->convert_json_order_response_to_array( $response->response ),
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
				'response'     => $this->convert_order_response_to_json( $response ),
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
	 * Get user ID from personal number.
	 *
	 * @param string $personal_number Personal number (12 digits, no hyphen).
	 * @return int|false
	 */
	public function getUserIdFromPersonalNumber( $personal_number ) {
		// Get user by personal number from User Meta.
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
	 * Set personal number for user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $personal_number Personal number (12 digits, no hyphen).
	 * @return void
	 */
	public function setPersonalNumberForUser( $user_id, $personal_number ) {
		// Check if user already has a personal number.
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
	 * It shall be a custom PHP SESSION.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function createAuthCookie( $user_id ) {
		// START SESSION.
		if ( ! session_id() ) {
			session_start();
		}
		$personal_number = get_user_meta( $user_id, 'mobile_bankid_integration_personal_number', true );
		if ( ! $personal_number ) {
			return;
		}
		$auth_cookie                                       = array(
			'user_id'         => $user_id,
			'personal_number' => $personal_number,
			'time_created'    => time(),
		);
		$_SESSION['mobile_bankid_integration_auth_cookie'] = $auth_cookie;
	}

	/**
	 * Verify the authentication cookie.
	 *
	 * @return bool
	 */
	public function verifyAuthCookie() {
		// START SESSION.
		if ( ! session_id() ) {
			session_start();
		}
		if ( ! isset( $_SESSION['mobile_bankid_integration_auth_cookie'] ) ) {
			return false;
		}
		$auth_cookie = $_SESSION['mobile_bankid_integration_auth_cookie']; // phpcs:ignore -- $_SESSION is not user input.
		if ( ! isset( $auth_cookie['user_id'] ) || ! isset( $auth_cookie['personal_number'] ) || ! isset( $auth_cookie['time_created'] ) ) {
			return false;
		}
		$user_id         = $auth_cookie['user_id'];
		$personal_number = $auth_cookie['personal_number'];
		$time_created    = $auth_cookie['time_created'];
		// Check if user is same.
		if ( get_current_user_id() !== $user_id ) {
			return false;
		}
		// Check if personal number is correct.
		if ( get_user_meta( $user_id, 'mobile_bankid_integration_personal_number', true ) !== $personal_number ) {
			return false;
		}
		// Check if time created is not older than 24 hours.
		if ( $time_created < time() - 86400 ) {
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
		if ( ! session_id() ) {
			session_start();
		}
		try {
			unset( $_SESSION['mobile_bankid_integration_auth_cookie'] );
		} catch ( \Throwable $th ) { // phpcs:ignore
			// Do nothing.
		}
	}

	/**
	 * Convert Dimafe6\BankID\OrderResponse to json.
	 *
	 * @param Dimafe6\BankID\OrderResponse $order_response Order response.
	 * @return array
	 * @since 1.0.1
	 */
	private function convert_order_response_to_json( $order_response ): string {
		// Make sure that $order_response is an instance of Dimafe6\BankID\Model\OrderResponse.
		if ( ! $order_response instanceof \Dimafe6\BankID\Model\OrderResponse ) {
			return array();
		}
		$array = array(
			'orderRef'       => $order_response->orderRef, // phpcs:ignore -- We shall not modify $orderRef to snake_case.
			'autoStartToken' => $order_response->autoStartToken // phpcs:ignore -- We shall not modify $autoStartToken to snake_case.
		);
		// If property qrStartToken exists, add it to the array.
		if ( property_exists( $order_response, 'qrStartToken' ) ) {
			$array['qrStartToken'] = $order_response->qrStartToken; // phpcs:ignore -- We shall not modify $qrStartToken to snake_case.
		}
		// If property qrStartSecret exists, add it to the array.
		if ( property_exists( $order_response, 'qrStartSecret' ) ) {
			$array['qrStartSecret'] = $order_response->qrStartSecret; // phpcs:ignore -- We shall not modify $qrStartSecret to snake_case.
		}

		$json = wp_json_encode( $array );

		return $json ? $json : '{}';
	}

	/**
	 * Convert JSON OrderResponse to array after checking if it is valid.
	 *
	 * @param string $json JSON OrderResponse.
	 * @throws \Exception If JSON or data is not valid.
	 * @return array
	 * @since 1.0.1
	 */
	private function convert_json_order_response_to_array( $json ): array {
		// Check each property in the JSON OrderResponse against [0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}
		// If any of the properties is not valid, return an empty array.
		$json = json_decode( $json, true );
		if ( ! is_array( $json ) ) {
			throw new \Exception( 'Invalid JSON' );
		}
		foreach ( $json as $key => $value ) {
			if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $value ) ) {
				throw new \Exception( 'Data is not valid' );
			}
		}
		return $json;
	}
}
