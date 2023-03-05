<?php
// Setup view on activation.

$step = isset($_GET['step']) ? $_GET['step'] : 1;
$step = intval($step);
?>
<div id="wizard-modal">
    <div id="wizard-modal-content">
        <div id="wizard-modal-content-inner">
            <h2><?php _e('Are you sure?', 'wp-bankid'); ?></h2>
            <p id="wizard-modal-confirmation-text"></p>
        </div>
        <hr>
        <div id="wizard-modal-footer">
            <button class="button button-primary" id="wizard-modal-abort"><?php _e('Abort', 'wp-bankid'); ?></button>
            <button class="button button-secondary" onclick="confirmconfirmation()" id="wizard-modal-confirm"><?php _e('Confirm', 'wp-bankid'); ?></button>
        </div>
    </div>
</div>
<div class="wizard">
    <div class="steps">
        <ol>
            <li <?php if ($step > 1) {?>class="done"<?} else {?>class="active"<?} ?>>
                <span class="title">Welcome</span>
            </li>
            <li <?php if ($step > 2) {?>class="done"<?} elseif ($step == 2) {?>class="active"<?} ?>>
                <span class="title">Configuration</span>
            </li>
            <li <?php if ($step > 3) {?>class="done"<?} elseif ($step == 3) {?>class="active"<?} ?>>
                <span class="title">Settings</span>
            </li>
            <li <?php if ($step == 4) {?>class="active"<?} ?>>
                <span class="title">Finish</span>
            </li>
        </ol>
    </div><br>

    <div id="wizard-content" step="<? echo esc_attr($step); ?>">
        <?php
        switch ($step) {
            case 1:
                include_once 'setup_welcome.php';
                break;
            case 2:
                include_once 'setup_configuration.php';
                break;
            case 3:
                include_once 'setup_settings.php';
                break;
            case 4:
                include_once 'setup_finish.php';
                break;
            default:
                include_once 'setup_welcome.php';
                break;
        }
        ?>
    </div>
    <p class="footer-info"><?php esc_html_e('WP BankID version: ', 'wp-bankid'); ?> <?php echo esc_html(WP_BANKID_VERSION); ?></p>
</div>