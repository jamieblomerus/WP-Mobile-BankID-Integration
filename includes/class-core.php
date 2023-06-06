<?php
namespace Webbstart\WP_BankID;

use Dimafe6\BankID\Service\BankIDService;

new Core;

class Core {
    public static Core|null $instance = null;
    private BankIDService $bankIDService;
    public function __construct() {
        if (isset(self::$instance)) {
            return;
        }
        self::$instance = $this;
        add_action('init', array($this, 'init'));
        add_action('wp_logout', array($this, 'deleteAuthCookie'));
    }
    public function init() {
        if (get_option('wp_bankid_endpoint') && get_option('wp_bankid_certificate') && get_option('wp_bankid_password')) {
            $this->createBankIDService();
            do_action('wp_bankid_init');
        }
    }
    private function createBankIDService() {
        $this->bankIDService = new BankIDService(
            get_option('wp_bankid_endpoint'),
            $_SERVER["REMOTE_ADDR"],
            [
                'verify' => false,
                'cert'   => [get_option('wp_bankid_certificate'), get_option('wp_bankid_password')],
            ]
        );
    }

    public function getBankIDService() {
        return $this->bankIDService;
    }

    public function identify() {
        if (!isset($this->bankIDService)) {
            $this->createBankIDService();
        }

        $response = $this->bankIDService->getAuthResponse();
        // Save the response in DB.
        $this->saveAuthResponseToDB($response->orderRef, $response);
        return [
            "orderRef" => $response->orderRef,
            "autoStartToken" => $response->autoStartToken
        ];
    }

    public function getAuthResponseFromDB($orderRef) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wp_bankid_auth_responses';
        $response = $wpdb->get_row("SELECT * FROM $table_name WHERE orderRef = '$orderRef'");
        if (!$response) {
            return null;
        }
        return [
            "time_created" => $response->time_created,
            "response" => unserialize($response->response),
            "orderRef" => $response->orderRef
        ];
    }

    private function saveAuthResponseToDB($orderRef, $response) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wp_bankid_auth_responses';
        $wpdb->insert(
            $table_name,
            array(
                'time_created' => time(),
                'response' => serialize($response),
                'orderRef' => $orderRef
            )
        );
    }

    public function deleteAuthResponseFromDB($orderRef) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wp_bankid_auth_responses';
        $wpdb->delete(
            $table_name,
            array(
                'orderRef' => $orderRef
            )
        );
    }

    public function getUserIdFromPersonalNumber($personal_number) {
        // Get user by personal number from User Meta.
        $user_query = new \WP_User_Query(array(
            'meta_key' => 'wp_bankid_personal_number',
            'meta_value' => $personal_number,
        ));
        $users = $user_query->get_results();
        if (count($users) > 0 && count($users) < 2) {
            return $users[0]->ID;
        }
        return false;
    }

    public function setPersonalNumberForUser($user_id, $personal_number) {
        // Check if user already has a personal number.
        if ($this->getUserIdFromPersonalNumber($personal_number) !== false) {
            return;
        }

        update_user_meta($user_id, 'wp_bankid_personal_number', $personal_number);
    }

    /**
     * Authentication cookies are used to verify the identity of a user who logs in to the site.
     * 
     * Authentication cookies are set when a user logs in to the site, and are used to verify the identity of a user who logs in to the site.
     * They are a guarantee that the user signed in to the site using Mobile BankID.
     * It shall be a custom PHP SESSION.
     */
    public function createAuthCookie($user_id) {
        // START SESSION
        if (!session_id()) {
            session_start();
        }
        $personal_number = get_user_meta($user_id, 'wp_bankid_personal_number', true);
        if (!$personal_number) {
            return;
        }
        $auth_cookie = [
            "user_id" => $user_id,
            "personal_number" => $personal_number,
            "time_created" => time()
        ];
        $_SESSION['wp_bankid_auth_cookie'] = $auth_cookie;
    }
    public function verifyAuthCookie() {
        // START SESSION
        if (!session_id()) {
            session_start();
        }
        if (!isset($_SESSION['wp_bankid_auth_cookie'])) {
            return false;
        }
        $auth_cookie = $_SESSION['wp_bankid_auth_cookie'];
        if (!isset($auth_cookie['user_id']) || !isset($auth_cookie['personal_number']) || !isset($auth_cookie['time_created'])) {
            return false;
        }
        $user_id = $auth_cookie['user_id'];
        $personal_number = $auth_cookie['personal_number'];
        $time_created = $auth_cookie['time_created'];
        // Check if user is same.
        if (get_current_user_id() !== $user_id) {
            return false;
        }
        // Check if personal number is correct.
        if (get_user_meta($user_id, 'wp_bankid_personal_number', true) !== $personal_number) {
            return false;
        }
        // Check if time created is not older than 24 hours.
        if ($time_created < time() - 86400) {
            return false;
        }
        return true;
    }
    public function deleteAuthCookie() {
        // START SESSION
        if (!session_id()) {
            session_start();
        }
        try {
            unset($_SESSION['wp_bankid_auth_cookie']);
        } catch (\Throwable $th) {
        }
    }
}