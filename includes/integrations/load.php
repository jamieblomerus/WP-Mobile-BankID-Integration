<?php
add_action('init', function() {
    if (is_plugin_active('woocommerce/woocommerce.php')) {
        include_once WP_BANKID_PLUGIN_DIR . 'includes/integrations/woocommerce.php';
    }
});