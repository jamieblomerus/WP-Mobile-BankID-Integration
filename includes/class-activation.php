<?php
namespace Mobile_BankID_Integration;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

new Activation();

/**
 * This class is responsible for activation and deactivation of the plugin.
 */
class Activation {

	/**
	 * Class constructor that adds activation and deactivation hooks.
	 */
	public function __construct() {
		register_activation_hook( MOBILE_BANKID_INTEGRATION_PLUGIN_FILE, array( $this, 'activation' ) );
		register_deactivation_hook( MOBILE_BANKID_INTEGRATION_PLUGIN_FILE, array( $this, 'deactivation' ) );
	}

	/**
	 * Create DB table for storing auth responses.
	 *
	 * @return void
	 */
	public function activation() {
		$this->checkrequirements();
		// Create DB table for storing auth responses.
		global $wpdb;
		$table_name      = $wpdb->prefix . 'mobile_bankid_integration_auth_responses';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time_created bigint NOT NULL,
            response text NOT NULL,
            orderRef text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Create session secret.
		Session::install();
	}

	/**
	 * Delete DB table for storing auth responses on deactivation.
	 *
	 * @return void
	 */
	public function deactivation() {
		// Delete DB table.
		global $wpdb;
		$table_name = $wpdb->prefix . 'mobile_bankid_integration_auth_responses';
		$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );

		// Delete session secret.
		Session::uninstall();
	}

	/**
	 * Check if plugin requirements are met.
	 *
	 * @return void
	 */
	private function checkrequirements() {
		// Check if PHP version is 7.4 or higher.
		if ( version_compare( PHP_VERSION, '7.4.0' ) < 0 ) {
			wp_die( esc_html__( 'PHP version 7.4 or higher is required for this plugin to work.', 'mobile-bankid-integration' ) );
		}
		// Check if curl is installed.
		if ( ! function_exists( 'curl_version' ) ) {
			wp_die( esc_html__( 'cURL is required for this plugin to work.', 'mobile-bankid-integration' ) );
		}
	}
}
