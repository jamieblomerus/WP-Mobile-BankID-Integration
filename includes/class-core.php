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
}