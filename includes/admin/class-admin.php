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
            wp_die("You do not have sufficient priviliges to see this page.");
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
                <!-- Setup link --->
                <a href="<?php echo esc_url(admin_url('admin.php?page=wp-bankid-setup')); ?>" class="nav-tab" style="float:right"><?php echo esc_html(__('Run setup wizard again', 'wp-bankid')); ?></a>
            </nav>
            <br>
            <?php
            call_user_func(self::$tabs[$current_tab]['callback']);
            ?>
        </div>
        <?php
    }

    private function page_settings() {
        ?>
<form autocomplete="off">
    <h2><?php esc_html_e('Basic configuration', 'wp-bankid'); ?></h2>
    <p class="description"><?php esc_html_e('These settings can only be changed by running the setup wizard again.', 'wp-bankid'); ?></p>
    <div class="form-group">
        <label for="wp-bankid-endpoint"><?php esc_html_e('API Endpoint', 'wp-bankid'); ?></label>
        <input type="text" name="wp-bankid-endpoint" id="wp-bankid-endpoint" disabled readonly value="<?php echo esc_url(get_option('wp_bankid_endpoint')); ?>">
    </div>
    <div class="form-group">
        <label for="wp-bankid-certificate"><?php esc_html_e('Certificate location (absolute path)', 'wp-bankid'); ?></label>
        <input type="text" name="wp-bankid-certificate" id="wp-bankid-certificate" disabled readonly value="<?php echo esc_attr(get_option('wp_bankid_certificate')); ?>">
    </div>
    <div class="form-group">
        <label for="wp-bankid-password"><?php esc_html_e('Certificate password', 'wp-bankid'); ?></label>
        <input type="password" name="wp-bankid-password" id="wp-bankid-password" autocomplete="off" disabled readonly value="<?php if (get_option('wp_bankid_password')) { echo "********"; } ?>">
    </div>

    <h2><?php esc_html_e('Login page', 'wp-bankid'); ?></h2>
    <div class="form-group">
        <label for="wp-bankid-wplogin"><?php esc_html_e('Show BankID on login page', 'wp-bankid'); ?></label>
        <select name="wp-bankid-wplogin" id="wp-bankid-wplogin">
            <option value="as_alternative" <?php if (get_option('wp_bankid_wplogin') == "as_alternative") { echo 'selected'; } ?>><?php esc_html_e('Show as alternative to traditional login', 'wp-bankid'); ?></option>
            <option value="hide" <?php if (get_option('wp_bankid_wplogin') == "hide") { echo 'selected'; } ?>><?php esc_html_e('Do not show at all', 'wp-bankid'); ?></option>
        </select>
    </div><br>
    <div class="form-group">
        <label for="wp-bankid-registration"><?php esc_html_e('Allow registration with BankID', 'wp-bankid'); ?></label>
        <select name="wp-bankid-registration" id="wp-bankid-registration">
            <option value="yes" <?php if (get_option('wp_bankid_registration') == "yes") { echo 'selected'; } ?>><?php esc_html_e('Yes', 'wp-bankid'); ?></option>
            <option value="no" <?php if (get_option('wp_bankid_registration') == "no") { echo 'selected'; } ?>><?php esc_html_e('No', 'wp-bankid'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('This setting does not affect, nor is affected by, the native "Allow registration" setting.', 'wp-bankid'); ?></p>
    </div>
</form>
<button class="button button-primary" onclick="settingsSubmit()" id="wp-bankid-save"><?php esc_html_e('Save changes', 'wp-bankid'); ?></button>
<style>
    form {
        width: fit-content;
    }
    form .description {
        /* Line break when description is too long */
        max-width: 500px;
        word-break: break-word;
    }
    .form-group {
        margin-bottom: 1rem;
        box-sizing: border-box;
        width: 100%;
    }
    .form-group label {
        font-weight: bold;
        display: block;
        margin-bottom: 0.5rem;
    }
    .form-group input[type="text"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 0.25rem;
        background-color: #fff;
        font-size: 1rem;
        line-height: 1.2;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    .form-group select {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 0.25rem;
        background-color: #fff;
        font-size: 1rem;
        line-height: 1.2;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
</style>
<script>
    function settingsSubmit() {
        document.getElementById("wp-bankid-save").innerHTML = "<?php esc_html_e('Saving...', 'wp-bankid'); ?>";
        document.getElementById("wp-bankid-save").disabled = true;
        var wplogin = document.getElementById("wp-bankid-wplogin").value;
        var registration = document.getElementById("wp-bankid-registration").value;
        
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "<?php echo esc_url(rest_url('wp-bankid/v1/settings')). '/settings'; ?>", true);
        xhr.setRequestHeader("X-WP-Nonce", "<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>");

        xhr.onload = function() {
            if (this.status == 200) {
                document.getElementById("wp-bankid-save").innerHTML = "<?php esc_html_e('Saved!', 'wp-bankid'); ?>";
                setTimeout(function() {
                    document.getElementById("wp-bankid-save").innerHTML = "<?php esc_html_e('Save changes', 'wp-bankid'); ?>";
                    document.getElementById("wp-bankid-save").disabled = false;
                }, 2000);
            } else {
                response = JSON.parse(this.responseText);
                alert(wp_bankid_setup_localization.configuration_failed + response['message']);
            }
        }

        formdata = new FormData();
        formdata.append("wplogin", wplogin);
        formdata.append("registration", registration);

        xhr.send(formdata);
    }
</script>
        <?php
    }

    private function page_integrations() {
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
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=advanced&section=wp_bankid')); ?>" class="button button-primary"><?php esc_html_e('Go to settings', 'wp-bankid'); ?></a>
                    <?php elseif (file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php')):
                        // Generate _wpnonce
                        $nonce = wp_create_nonce('activate-plugin_woocommerce/woocommerce.php');
                        ?>
                        <a href="<?php echo esc_url(admin_url("plugins.php?action=activate&plugin=woocommerce/woocommerce.php&_wpnonce=$nonce")); ?>" class="button button-primary"><?php esc_html_e('Activate WooCommerce', 'wp-bankid'); ?></a>
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