<?php
namespace Mobile_BankID_Integration;

new Admin;

class Admin {
    private static array $tabs = [];
    public function __construct() {
        add_action('admin_init', [$this, 'redirect_to_setup_if_incomplete']);
        add_action('admin_menu', array($this, 'register_page'));

        // Register tabs
        self::add_tab(__('Settings', 'mobile-bankid-integration'), 'settings', [$this, 'page_settings']);
        self::add_tab(__('Integrations', 'mobile-bankid-integration'), 'integrations', [$this, 'page_integrations']);
    }

    public function register_page() {
        add_menu_page(
            __( 'Mobile BankID Integration', 'mobile-bankid-integration' ),
            __( 'Mobile BankID Integration', 'mobile-bankid-integration' ),
            'manage_options',
            'mobile-bankid-integration',
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

    function redirect_to_setup_if_incomplete() {
        if (get_admin_page_parent() == 'mobile-bankid-integration') {
            if (!(get_option('mobile_bankid_integration_certificate') && get_option('mobile_bankid_integration_password') && get_option('mobile_bankid_integration_endpoint'))) {
                // Redirect to setup wizard
                wp_redirect(home_url().'/wp-admin/admin.php?page=mobile-bankid-integration-setup');
                exit();
            }
        }
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
                    <a href="<?php echo esc_url(admin_url('admin.php?page=mobile-bankid-integration&tab=' . $tab)); ?>" class="nav-tab <?php echo $current_tab == $tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($content['display_name']); ?></a>
                <?php endforeach; ?>
                <!-- Setup link --->
                <a href="<?php echo esc_url(admin_url('admin.php?page=mobile-bankid-integration-setup')); ?>" class="nav-tab" style="float:right"><?php echo esc_html(__('Run setup wizard again', 'mobile-bankid-integration')); ?></a>
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
    <h2><?php esc_html_e('Basic configuration', 'mobile-bankid-integration'); ?></h2>
    <p class="description"><?php esc_html_e('These settings can only be changed by running the setup wizard again.', 'mobile-bankid-integration'); ?></p>
    <div class="form-group">
        <label for="mobile-bankid-integration-endpoint"><?php esc_html_e('API Endpoint', 'mobile-bankid-integration'); ?></label>
        <input type="text" name="mobile-bankid-integration-endpoint" id="mobile-bankid-integration-endpoint" disabled readonly value="<?php echo esc_url(get_option('mobile_bankid_integration_endpoint')); ?>">
    </div>
    <div class="form-group">
        <label for="mobile-bankid-integration-certificate"><?php esc_html_e('Certificate location (absolute path)', 'mobile-bankid-integration'); ?></label>
        <input type="text" name="mobile-bankid-integration-certificate" id="mobile-bankid-integration-certificate" disabled readonly value="<?php echo esc_attr(get_option('mobile_bankid_integration_certificate')); ?>">
    </div>
    <div class="form-group">
        <label for="mobile-bankid-integration-password"><?php esc_html_e('Certificate password', 'mobile-bankid-integration'); ?></label>
        <input type="password" name="mobile-bankid-integration-password" id="mobile-bankid-integration-password" autocomplete="off" disabled readonly value="<?php if (get_option('mobile_bankid_integration_password')) { echo "********"; } ?>">
    </div>

    <h2><?php esc_html_e('Login page', 'mobile-bankid-integration'); ?></h2>
    <div class="form-group">
        <label for="mobile-bankid-integration-wplogin"><?php esc_html_e('Show BankID on login page', 'mobile-bankid-integration'); ?></label>
        <select name="mobile-bankid-integration-wplogin" id="mobile-bankid-integration-wplogin">
            <option value="as_alternative" <?php if (get_option('mobile_bankid_integration_wplogin') == "as_alternative") { echo 'selected'; } ?>><?php esc_html_e('Show as alternative to traditional login', 'mobile-bankid-integration'); ?></option>
            <option value="hide" <?php if (get_option('mobile_bankid_integration_wplogin') == "hide") { echo 'selected'; } ?>><?php esc_html_e('Do not show at all', 'mobile-bankid-integration'); ?></option>
        </select>
    </div><br>
    <div class="form-group">
        <label for="mobile-bankid-integration-registration"><?php esc_html_e('Allow registration with BankID', 'mobile-bankid-integration'); ?></label>
        <select name="mobile-bankid-integration-registration" id="mobile-bankid-integration-registration">
            <option value="yes" <?php if (get_option('mobile_bankid_integration_registration') == "yes") { echo 'selected'; } ?>><?php esc_html_e('Yes', 'mobile-bankid-integration'); ?></option>
            <option value="no" <?php if (get_option('mobile_bankid_integration_registration') == "no") { echo 'selected'; } ?>><?php esc_html_e('No', 'mobile-bankid-integration'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('This setting does not affect, nor is affected by, the native "Allow registration" setting.', 'mobile-bankid-integration'); ?></p>
    </div>
    <div class="form-group">
        <label for="mobile-bankid-integration-terms"><?php esc_html_e('Terms to show with login (Supports HTML)', 'mobile-bankid-integration'); ?></label>
        <textarea name="mobile-bankid-integration-terms" id="mobile-bankid-integration-terms" rows="5"><?php echo stripslashes(esc_html(get_option('mobile_bankid_integration_terms', _("By logging in using Mobile BankID you agree to our Terms of Service and Privacy Policy.")))); ?></textarea>
        <p class="description"><?php esc_html_e('Following HTML elements are supported: a, br, em, strong and i. All others will be escaped.', 'mobile-bankid-integration'); ?></p>
    </div>
</form>
<button class="button button-primary" onclick="settingsSubmit()" id="mobile-bankid-integration-save"><?php esc_html_e('Save changes', 'mobile-bankid-integration'); ?></button>
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
    .form-group input[type="password"],
    .form-group textarea {
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
        resize: none;
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
        document.getElementById("mobile-bankid-integration-save").innerHTML = "<?php esc_html_e('Saving...', 'mobile-bankid-integration'); ?>";
        document.getElementById("mobile-bankid-integration-save").disabled = true;
        var wplogin = document.getElementById("mobile-bankid-integration-wplogin").value;
        var registration = document.getElementById("mobile-bankid-integration-registration").value;
        var terms = document.getElementById("mobile-bankid-integration-terms").value;
        
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "<?php echo esc_url(rest_url('mobile-bankid-integration/v1/settings')). '/settings'; ?>", true);
        xhr.setRequestHeader("X-WP-Nonce", "<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>");

        xhr.onload = function() {
            if (this.status == 200) {
                document.getElementById("mobile-bankid-integration-save").innerHTML = "<?php esc_html_e('Saved!', 'mobile-bankid-integration'); ?>";
                setTimeout(function() {
                    document.getElementById("mobile-bankid-integration-save").innerHTML = "<?php esc_html_e('Save changes', 'mobile-bankid-integration'); ?>";
                    document.getElementById("mobile-bankid-integration-save").disabled = false;
                }, 2000);
            } else {
                response = JSON.parse(this.responseText);
                alert(mobile_bankid_integration_setup_localization.configuration_failed + response['message']);
            }
        }

        formdata = new FormData();
        formdata.append("wplogin", wplogin);
        formdata.append("registration", registration);
        formdata.append("terms", terms);

        xhr.send(formdata);
    }
</script>
        <?php
    }

    private function page_integrations() {
        ?>
        <div class="mobile-bankid-integration-integrations">
            <div class="mobile-bankid-integration-integration">
                <div class="mobile-bankid-integration-integration__logo">
                    <img src="<?php echo esc_url(MOBILE_BANKID_INTEGRATION_PLUGIN_URL . 'assets/images/woocommerce.svg'); ?>" alt="WooCommerce">
                </div>
                <div class="mobile-bankid-integration-integration__content">
                    <h2 class="mobile-bankid-integration-integration__title">WooCommerce</h2>
                    <p class="mobile-bankid-integration-integration__description">WooCommerce is the most popular e-commerce platform for WordPress. With Mobile BankID Integration you can perform age checks using Mobile BankID.</p>
                    <?php if (is_plugin_active('woocommerce/woocommerce.php')) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=advanced&section=mobile_bankid_integration')); ?>" class="button button-primary"><?php esc_html_e('Go to settings', 'mobile-bankid-integration'); ?></a>
                    <?php elseif (file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php')):
                        // Generate _wpnonce
                        $nonce = wp_create_nonce('activate-plugin_woocommerce/woocommerce.php');
                        ?>
                        <a href="<?php echo esc_url(admin_url("plugins.php?action=activate&plugin=woocommerce/woocommerce.php&_wpnonce=$nonce")); ?>" class="button button-primary"><?php esc_html_e('Activate WooCommerce', 'mobile-bankid-integration'); ?></a>
                    <?php else : 
                        // Generate _wpnonce
                        $nonce = wp_create_nonce('install-plugin_woocommerce');
                        ?>
                        <a href="<?php echo esc_url(admin_url("update.php?action=install-plugin&plugin=woocommerce&_wpnonce=$nonce")); ?>" class="button button-primary"><?php esc_html_e('Install WooCommerce', 'mobile-bankid-integration'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- More coming soon -->
            <div class="mobile-bankid-integration-integration coming-soon">
                <div class="mobile-bankid-integration-integration__content">
                    <h2 class="mobile-bankid-integration-integration__title">More coming soon</h2>
                    <p class="mobile-bankid-integration-integration__description">We are working on more integrations. If you have any suggestions, please let us know.</p>
                </div>
            </div>
        </div>
        <style>
            .mobile-bankid-integration-integrations {
                display: flex;
                flex-wrap: wrap;
                margin-left: 5px;
            }
            .mobile-bankid-integration-integration {
                display: flex;
                flex-direction: column;
                background: #fff;
                border: 1px solid #e5e5e5;
                border-radius: 4px;
                padding: 20px;
                max-width: 300px;
            }
            .mobile-bankid-integration-integration__logo {
                display: flex;
                justify-content: center;
                align-items: center;
                margin-bottom: 20px;
            }
            .mobile-bankid-integration-integration__logo img {
                max-width: 100%;
                height: 50px;
            }
            .mobile-bankid-integration-integration__title {
                margin-top: 0;
            }
            .mobile-bankid-integration-integration__description {
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