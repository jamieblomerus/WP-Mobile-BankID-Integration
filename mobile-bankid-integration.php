<?php // PHPCS:Ignore
/*
Plugin Name: Mobile BankID Integration
Description: A plugin that allows you to integrate Mobile BankID with your WordPress site.
Version: Indev
Author: Jamie Blomerus
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define constants.
define( 'MOBILE_BANKID_INTEGRATION_VERSION', 'Indev' );
define( 'MOBILE_BANKID_INTEGRATION_PLUGIN_FILE', __FILE__ );
define( 'MOBILE_BANKID_INTEGRATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOBILE_BANKID_INTEGRATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! class_exists( 'mobile_bankid_integration' ) ) {

	/**
	 * Main plugin class.
	 */
	class Mobile_Bankid_Integration {

		/**
		 * Class constructor.
		 */
		public function __construct() {
			// Check if composer autoload exists.
			if ( ! file_exists( MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
				wp_die( wp_kses_post( __( '<strong>Mobile BankID Integration</strong> requires its dependencies to be installed. These are included in all releases, but if you are using the development version, you need to run <code>composer install</code> in the plugin directory.', 'mobile-bankid-integration' ) ) );
			}

			// Composer autoload.
			require_once MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'vendor/autoload.php';

			// Load plugin files.
			$this->load_plugin_files();

			// Add link in plugin list.
			add_filter( 'plugin_action_links_' . plugin_basename( MOBILE_BANKID_INTEGRATION_PLUGIN_FILE ), array( $this, 'plugin_list_link' ) );
		}

		/**
		 * Load plugin files.
		 */
		private function load_plugin_files() {
			require_once MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'includes/class-core.php';
			require_once MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'includes/class-activation.php';
			require_once MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'includes/settings/class-setup.php';
			require_once MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'includes/settings/class-api.php';
			require_once MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'includes/settings/class-user.php';
			require_once MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'includes/wp-login/class-api.php';
			require_once MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'includes/wp-login/class-login.php';
			require_once MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'includes/admin/class-admin.php';
			require_once MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'includes/integrations/load.php';
		}

		/**
		 * Add link to settings page in plugin list.
		 *
		 * @param array $links Array of links.
		 * @return array
		 */
		public function plugin_list_link( $links ) {
			$setup_link = '<a href="admin.php?page=mobile-bankid-integration">' . esc_html__( 'Settings', 'mobile-bankid-integration' ) . '</a>';
			array_unshift( $links, $setup_link );
			return $links;
		}
	}
	new Mobile_Bankid_Integration();
}
