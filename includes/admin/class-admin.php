<?php
namespace Webbstart\WP_BankID;

new Admin;

class Admin {
    private static array $tabs = [];
    public function __construct() {
        add_action('admin_menu', array($this, 'register_page'));

        // Register tabs
        self::add_tab(__('Settings', 'wp-bankid'), 'settings', [$this, 'page_settings']);
        self::add_tab(__('Integrations', 'wp-bankid'), 'integrations', [$this, 'page_integrations']);
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

    public static function add_tab(string $display_name, string $slug, callable $callback): void {
        if (array_key_exists($slug, self::$tabs)) {
            throw new \Exception("Tab with that slug already exists.");
        }

        self::$tabs[$slug] = [
            'display_name' => $display_name,
            'callback' => $callback
        ];
    }

    public static function remove_tab(string $slug) {
        if (!array_key_exists($slug, self::$tabs)) {
            throw new \Exception("Tab with that slug does not exist.");
        }

        unset(self::$tabs[$slug]);
    }

    public function page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $current_tab = isset($_GET['tab']) ? $_GET['tab'] : null;
        if (!isset($current_tab) || !array_key_exists($current_tab, self::$tabs)) {
            $current_tab = array_key_first(self::$tabs);
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <nav class="nav-tab-wrapper">
                <?php foreach (self::$tabs as $tab => $content) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-bankid&tab=' . $tab)); ?>" class="nav-tab <?php echo $current_tab == $tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($content['display_name']); ?></a>
                <?php endforeach; ?>
            </nav>
            <br>
            <?php
            call_user_func(self::$tabs[$current_tab]['callback']);
            ?>
        </div>
        <?php
    }

    public function page_settings() {
        echo "Settings";
    }

    public function page_integrations() {
        // Make grid of integrations to be in wp-admin with styling
        ?>
        <div class="wp-bankid-integrations">
            <div class="wp-bankid-integration">
                <div class="wp-bankid-integration__logo">
                    <img src="<?php echo esc_url(WP_BANKID_PLUGIN_URL . 'assets/images/woocommerce.svg'); ?>" alt="WooCommerce">
                </div>
                <div class="wp-bankid-integration__content">
                    <h2 class="wp-bankid-integration__title">WooCommerce</h2>
                    <p class="wp-bankid-integration__description">WooCommerce is the most popular e-commerce platform for WordPress. With WP BankID by Webbstart you can perform age checks using BankID.</p>
                    <?php if (is_plugin_active('woocommerce/woocommerce.php')) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=bankid')); ?>" class="button button-primary"><?php esc_html_e('Go to settings', 'wp-bankid'); ?></a>
                    <?php else : 
                        // Generate _wpnonce
                        $nonce = wp_create_nonce('install-plugin_woocommerce');
                        ?>
                        <a href="<?php echo esc_url(admin_url("update.php?action=install-plugin&plugin=woocommerce&_wpnonce=$nonce")); ?>" class="button button-primary"><?php esc_html_e('Install WooCommerce', 'wp-bankid'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- More coming soon -->
            <div class="wp-bankid-integration coming-soon">
                <div class="wp-bankid-integration__content">
                    <h2 class="wp-bankid-integration__title">More coming soon</h2>
                    <p class="wp-bankid-integration__description">We are working on more integrations. If you have any suggestions, please let us know.</p>
                </div>
            </div>
        </div>
        <style>
            .wp-bankid-integrations {
                display: flex;
                flex-wrap: wrap;
                margin-left: 5px;
            }
            .wp-bankid-integration {
                display: flex;
                flex-direction: column;
                background: #fff;
                border: 1px solid #e5e5e5;
                border-radius: 4px;
                padding: 20px;
                max-width: 300px;
            }
            .wp-bankid-integration__logo {
                display: flex;
                justify-content: center;
                align-items: center;
                margin-bottom: 20px;
            }
            .wp-bankid-integration__logo img {
                max-width: 100%;
                height: 50px;
            }
            .wp-bankid-integration__title {
                margin-top: 0;
            }
            .wp-bankid-integration__description {
                margin-bottom: 20px;
            }
            .coming-soon {
                background: #e5e5e5;
                display: flex;
                justify-content: center;
                align-items: center;
            }
        </style>
        <?php
    }
}