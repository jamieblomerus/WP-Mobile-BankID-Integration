<?php // phpcs:ignore Squiz.Commenting.FileComment.Missing
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			return;
		}
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			include_once MOBILE_BANKID_INTEGRATION_PLUGIN_DIR . 'includes/integrations/woocommerce.php';
		}
	}
);
