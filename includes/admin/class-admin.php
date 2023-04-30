<?php
namespace Webbstart\WP_BankID;

new Admin;

class Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'register_page'));
    }

    public function register_page() {
        add_menu_page(
            __( 'WP BankID by Webbstart', 'wp-bankid' ),
            __( 'WP BankID by Webbstart', 'wp-bankid' ),
            'manage_options',
            'wp-bankid',
            [$this, 'page'],
            'dashicons-id',
            99
        );
    }

    public function page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $current_tab = isset($_GET['tab']) ? $_GET['tab'] : null;
        $tabs = [
            'settings' => __('Settings', 'wp-bankid'),
            'logs' => __('Logs', 'wp-bankid'),
        ];
        if (!isset($current_tab) || !array_key_exists($current_tab, $tabs)) {
            $current_tab = 'settings';
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab => $name) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-bankid&tab=' . $tab)); ?>" class="nav-tab <?php echo $current_tab == $tab ? 'nav-tab-active' : ''; ?>"><?php echo $name; ?></a>
                <?php endforeach; ?>
            </nav>
            <?php
            call_user_func([$this, 'page_' . $current_tab]);
            ?>
        </div>
        <?php
    }

    public function page_settings() {
        echo "Settings";
    }

    public function page_logs() {
        echo "Logs";
    }
}