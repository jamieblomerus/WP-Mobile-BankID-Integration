<?php
namespace Webbstart\WP_BankID\Settings;

new UserSettings;

class UserSettings {
    function __construct() {
        add_action('show_user_profile', array($this, 'showPersonalNumber'));
        add_action('edit_user_profile', array($this, 'showPersonalNumber'));
        add_action('personal_options_update', array($this, 'savePersonalNumber'));
        add_action('edit_user_profile_update', array($this, 'savePersonalNumber'));
    }
    public function showPersonalNumber($user) {
        ?>
        <h3><? esc_html_e('BankID Authentication', 'wp-bankid') ?></h3>
        <table class="form-table">
        <tr>
        <th><label for="personal_number"><? esc_html_e('Personal number (12 digits, no hyphen)', 'wp-bankid') ?></label></th>
        <td>
        <input type="text" name="personal_number" id="personal_number" <? if (!current_user_can('administrator')) { echo "disabled"; } ?> value="<? echo esc_attr(get_user_meta($user->ID, 'wp_bankid_personal_number', true)) ?>" placeholder="<? /* translators: Placeholder personal number. */ esc_attr_e('YYYYMMDDXXXX', 'wp-bankid') ?>" class="regular-text" />
        </td>
        </tr>
        </table>
        <?php
    }
    public function savePersonalNumber($user_id) {
        if (current_user_can('administrator')) {
            // Check if personal number is valid and save it if it is.
            if (preg_match('/^[0-9]{12}$/', $_POST['personal_number'])) {
                // Check if user with this personal number already exists.
                $check = get_users(array(
                    'meta_key' => 'wp_bankid_personal_number',
                    'meta_value' => $_POST['personal_number'],
                    'number' => 1,
                    'count_total' => false,
                ));
                if ($check && $check[0]->ID != $user_id) {
                    add_action('user_profile_update_errors', array($this, 'personalNumberUpdateErrorAlreadyExists'), 10, 3);
                    return;
                }

                update_user_meta($user_id, 'wp_bankid_personal_number', $_POST['personal_number']);
            } elseif (strlen($_POST['personal_number']) < 1) {
                try {
                    delete_user_meta($user_id, 'personal_number');
                } catch (Exception $e) {
                    // Do nothing.
                }
            } else {
                add_action('user_profile_update_errors', array($this, 'personalNumberUpdateErrorInvalid'), 10, 3);
                return;
            }
        }
    }
    public function personalNumberUpdateErrorAlreadyExists($errors, $update, $user) {
        $errors->add('personal_number', esc_html__('User with this personal number already exists.', 'wp-bankid'));
    }
    public function personalNumberUpdateErrorInvalid($errors, $update, $user) {
        $errors->add('personal_number', esc_html__('Personal number is not valid.', 'wp-bankid'));
    }
}