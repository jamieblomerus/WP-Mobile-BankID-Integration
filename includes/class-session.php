<?php
namespace Mobile_BankID_Integration;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * This class provides an alternative to PHP sessions, because they are not available on all servers and can cause problems.
 *
 * @since 1.1.0
 */
class Session {
	/**
	 * Any errors that occur on instantiation.
	 *
	 * @var \WP_Error|null
	 */
	private \WP_Error|null $error = null;

	/**
	 * The user ID.
	 *
	 * @var int
	 */
	public int $user_id;

	/**
	 * The personal identity number.
	 *
	 * @var string
	 */
	public string $personal_number;

	/**
	 * The time the session was created.
	 *
	 * @var int
	 */
	public int $time_created;

	/**
	 * The session secret.
	 *
	 * @var string
	 */
	protected static string $session_secret;

	/**
	 * Class constructor that starts the session.
	 *
	 * @param int         $user_id The user ID.
	 * @param string|null $personal_number The personal identity number.
	 * @param int|null    $time_created The time the session was created.
	 * @return void|\WP_Error
	 */
	public function __construct( int $user_id, $personal_number = null, $time_created = null ) {
		if ( ! isset( self::$session_secret ) ) {
			self::$session_secret = self::get_secret();
		}

		if ( ! self::$session_secret ) {
			$this->error = new \WP_Error( 'no_session_secret', __( 'No session secret found.', 'mobile-bankid-integration' ) );
			return;
		}

		if ( ! isset( $personal_number ) ) {
			$personal_number = get_user_meta( $user_id, 'mobile_bankid_integration_personal_number', true );
		}
		if ( ! $personal_number ) {
			$this->error = new \WP_Error( 'no_personal_number', __( 'No personal identity number found.', 'mobile-bankid-integration' ) );
		}
		$this->user_id         = $user_id;
		$this->personal_number = $personal_number;
		$this->time_created    = $time_created ?? time();
		$this->save();
	}

	/**
	 * Get whether the session could be started correctly.
	 *
	 * Should always be checked before using the session.
	 *
	 * @return true|\WP_Error
	 */
	public function is_ok() {
		if ( isset( $this->error ) ) {
			return $this->error;
		}
		return true;
	}

	/**
	 * Warn admin if session secret is not set.
	 *
	 * @return void
	 */
	public static function admin_notice() {
		if ( defined( 'MOBILE_BANKID_INTEGRATION_SESSION_SECRET' ) || get_option( 'mobile_bankid_integration_session_secret' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Mobile BankID Integration: No session secret found. This can cause problems with the plugin. Please reinstall the plugin or contact support.', 'mobile-bankid-integration' ) . '</p></div>';
	}

	/**
	 * Create session secret.
	 *
	 * @return void
	 */
	public static function install() {
		// Include the WordPress filesystem API functions.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		// Check if a secret already exists.
		if ( defined( 'MOBILE_BANKID_INTEGRATION_SESSION_SECRET' ) || get_option( 'mobile_bankid_integration_session_secret' ) ) {
			return;
		}

		$filesystem = new \WP_Filesystem_Direct( true );

		$secret = wp_generate_password( 64, true, true );

		// Write to wp-config.php if possible, otherwise write to database.
		if ( $filesystem->is_writable( ABSPATH . 'wp-config.php' ) ) {
			// Add an encrption secret to wp-config.php.
			$secret = "define('MOBILE_BANKID_INTEGRATION_SESSION_SECRET', '" . $secret . "'); // Only change this if you know what you're doing!";

			$config_path = ABSPATH . 'wp-config.php';
			$config      = $filesystem->get_contents( $config_path );

			if ( false === $config ) {
				return;
			}

			$config = str_replace( "/* That's all, stop editing! Happy publishing. */", $secret . "\n/* That's all, stop editing! Happy publishing. */", $config );

			if ( false === $filesystem->put_contents( $config_path, $config ) ) {
				return;
			}
		} else {
			// Add an encryption secret to database.
			update_option( 'mobile_bankid_integration_session_secret', $secret );
		}

		// Deregister action.
		remove_action( 'admin_notices', array( 'Mobile_BankID_Integration\Session', 'admin_notice' ) );
	}

	/**
	 * Remove session secret.
	 *
	 * @return void
	 */
	public static function uninstall() {
		// Include the WordPress filesystem API functions.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		// Check if a secret exists.
		if ( ! defined( 'MOBILE_BANKID_INTEGRATION_SESSION_SECRET' ) && ! get_option( 'mobile_bankid_integration_session_secret' ) ) {
			return;
		}

		if ( defined( 'MOBILE_BANKID_INTEGRATION_SESSION_SECRET' ) ) {
			$filesystem = new \WP_Filesystem_Direct( true );

			if ( ! $filesystem->is_writable( ABSPATH . 'wp-config.php' ) ) {
				return;
			}

			// Remove encryption secret from wp-config.php.
			$config_path = ABSPATH . 'wp-config.php';
			$config      = $filesystem->get_contents( $config_path );

			$config = str_replace( "define('MOBILE_BANKID_INTEGRATION_SESSION_SECRET', '" . MOBILE_BANKID_INTEGRATION_SESSION_SECRET . "'); // Only change this if you know what you're doing!\n", '', $config );

			$filesystem->put_contents( $config_path, $config );
		}

		if ( get_option( 'mobile_bankid_integration_session_secret' ) ) {
			// Remove encryption secret from database.
			delete_option( 'mobile_bankid_integration_session_secret' );
		}
	}

	/**
	 * __set() magic method to prevent setting of properties.
	 *
	 * @param string $name The name of the property.
	 * @param mixed  $value The value of the property.
	 * @throws \WP_Error If trying to set a property.
	 * @return void
	 */
	public function __set( $name, $value ) {
		throw new \WP_Error( 'cannot_modify_session', __( 'Sessions shall not be modified directly.', 'mobile-bankid-integration' ) ); // phpcs:ignore -- Message will be escaped when displayed, double escaping is a security risk.
	}

	/**
	 * Get the session secret.
	 *
	 * @return string|false The session secret or false if not found.
	 */
	private static function get_secret() {
		if ( defined( 'MOBILE_BANKID_INTEGRATION_SESSION_SECRET' ) ) {
			return MOBILE_BANKID_INTEGRATION_SESSION_SECRET;
		} else {
			return get_option( 'mobile_bankid_integration_session_secret' );
		}
	}

	/**
	 * Save the session to a cookie.
	 *
	 * @return void
	 */
	private function save() {
		$session = array(
			'user_id'         => $this->user_id,
			'personal_number' => $this->personal_number,
			'time_created'    => $this->time_created,
		);
		$session = wp_json_encode( $session );
		$session = base64_encode( $session ); // phpcs:ignore

		$session = openssl_encrypt( $session, 'aes-256-cbc', self::$session_secret, 0, substr( self::$session_secret, 0, 16 ) );

		setcookie( 'mobile_bankid_integration_session', $session, 0, '/', '', is_ssl(), true );
	}

	/**
	 * Load the session from a cookie.
	 *
	 * @return false|Session The session or false if not found.
	 */
	public static function load() {
		if ( ! isset( $_COOKIE['mobile_bankid_integration_session'] ) ) {
			return false;
		}

		// Check if session secret is set.
		if ( ! isset( self::$session_secret ) ) {
			self::$session_secret = self::get_secret();
		}

		$session = $_COOKIE['mobile_bankid_integration_session']; // phpcs:ignore -- The data is handled securely by the openssl functions.

		$session = openssl_decrypt( $session, 'aes-256-cbc', self::$session_secret, 0, substr( self::$session_secret, 0, 16 ) );

		// Check if decryption failed.
		if ( false === $session ) {
			return false;
		}

		$session = base64_decode( $session ); // phpcs:ignore

		$session = json_decode( $session );

		if ( ! isset( $session->user_id ) || ! isset( $session->personal_number ) || ! isset( $session->time_created ) ) {
			return false;
		}

		// Check if user exists.
		if ( get_user_by( 'id', $session->user_id ) === false ) {
			return false;
		}

		// Check if session is older than 24 hours.
		if ( $session->time_created < time() - 86400 ) {
			return false;
		}

		$session = new Session( $session->user_id, $session->personal_number, $session->time_created );

		return $session;
	}

	/**
	 * Destroy the session.
	 *
	 * @return void
	 */
	public function destroy() {
		// Delete cookie.
		setcookie( 'mobile_bankid_integration_session', '', time() - 3600, '/', '', is_ssl(), true );
	}
}
