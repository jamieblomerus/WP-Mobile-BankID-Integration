if (typeof jQuery === 'undefined') {
    throw new Error('Unofficial Mobile BankID Integration requires jQuery on the login page.');
}

let orderRef = null;
let bankidRefreshId = null;

jQuery(document).ready(function () {
    function initializeLoginPage(autoStartToken) {
        const loginButtonContainer = jQuery("#bankid-login-button").parent().parent();
        const bankIdLoginContainer = jQuery('#bankid-login-container');

        const statusElement = jQuery('#bankid-status');
        statusElement.text(mobile_bankid_integration_login_localization.qr_instructions);

        const cancelButton = jQuery('#cancel_bankid');
        cancelButton.on('click', cancelBankIdLogin);

        const openBankidButton = jQuery('#open_bankid');
        openBankidButton.attr('href', `https://app.bankid.com/?autostarttoken=${autoStartToken}&redirect=null`);

        loginButtonContainer.after(bankIdLoginContainer);
        loginButtonContainer.hide();

        jQuery('h2').not('#bankid-login-h2').addClass('bankid-login-hidden');

        bankIdLoginContainer.show();

        jQuery('#login').addClass('bankid-login');
    }

    async function handleStatus() {
        if (orderRef === null) {
            return;
        }

        try {
            const response = await fetch(`${mobile_bankid_integration_rest_api}/status?orderRef=${orderRef}`);
            const data = await response.json();

            if (data.qr !== null) {
                jQuery('#bankid-qr-code').attr('src', data.qr);

                if (! jQuery('#bankid-qr-code-loading').hasClass('hidden')) {
                    jQuery('#bankid-qr-code-loading').addClass('hidden');
                }
            }

            if (data.status === 'failed' && data.hintCode === 'startFailed') {
                identify((data) => {
                    orderRef = data.orderRef;
                    const open_on_this_device = jQuery('#open_bankid');
                    open_on_this_device.attr('href', `https://app.bankid.com/?autostarttoken=${data.autoStartToken}&redirect=null`);
                });
                return;
            }

            handleStatusSwitch(data.status);
            if (data.hintCode !== null) {
                handleHintCode(data.hintCode);
            }

            updateQRCodeTimeLeft(data.time_since_auth);
        } catch (error) {
            displayErrorMessage(mobile_bankid_integration_login_localization.something_went_wrong);
            console.error("Something went wrong. Debug info:\n\n", error);
        }
    }

    function handleStatusSwitch(status) {
        switch (status) {
            case 'expired':
                displayErrorMessage(mobile_bankid_integration_login_localization.status_expired);
                break;
            case 'complete':
                completeLogin();
                break;
            case 'complete_no_user':
                displayErrorMessage(mobile_bankid_integration_login_localization.status_complete_no_user);
                break;
            case 'failed':
                displayErrorMessage(mobile_bankid_integration_login_localization.status_failed);
                break;
        }
    }

    function updateQRCodeTimeLeft(timeSinceAuth) {
        const timeLeft = 30 - timeSinceAuth;
        const timeLeftPercentage = (timeLeft / 30) * 100;
        jQuery('#bankid-qr-code-timeleft').css('width', `${timeLeftPercentage}%`);
    }

    function handleHintCode(hintCode) {
        const statusElement = jQuery('#bankid-status');
        const hintMessages = {
            'userCancel': mobile_bankid_integration_login_localization.hintcode_userCancel,
            'userSign': mobile_bankid_integration_login_localization.hintcode_userSign,
            'startFailed': mobile_bankid_integration_login_localization.hintcode_startFailed,
            'certificateErr': mobile_bankid_integration_login_localization.hintcode_certificateErr,
            'default': mobile_bankid_integration_login_localization.qr_instructions
        };
        statusElement.html(hintMessages[hintCode] || hintMessages['default']);
    }

    function displayErrorMessage(message) {
        const statusElement = jQuery('#bankid-status');
        statusElement.html(message);
        jQuery('#bankid-qr-code').attr('src', '');
        jQuery('#bankid-qr-code-container').hide();
        jQuery('#open_bankid').hide();
        jQuery('#bankid-login-container').addClass('error');
        orderRef = null;
        clearInterval(bankidRefreshId);
    }

    function completeLogin() {
        orderRef = null;
        jQuery('#bankid-status').html(mobile_bankid_integration_login_localization.status_complete);
        jQuery('#bankid-qr-code').attr('src', '');
        jQuery('#bankid-qr-code-container').hide();
        jQuery('#open_bankid').hide();
        window.location.href = mobile_bankid_integration_redirect_url;
        clearInterval(bankidRefreshId);
    }

    function cancelBankIdLogin() {
        const loginButtonContainer = jQuery("#bankid-login-button").parent().parent();
        const bankIdLoginContainer = jQuery("#bankid-login-container");
        bankIdLoginContainer.hide();
        loginButtonContainer.show();

        // Close accordions
        jQuery('#bankid-login-container button.accordion-button').removeClass('active');
        jQuery('#bankid-login-container button.accordion-button').attr('aria-expanded', 'false');
        jQuery('#bankid-login-container button.accordion-button').next().slideUp();

        // Show loading spinner
        jQuery('#bankid-qr-code-loading').removeClass('hidden');

        // Unhide qr code container
        jQuery('#bankid-qr-code-container').show();

        jQuery('#login').removeClass('bankid-login');
        jQuery('.bankid-login-hidden').removeClass('bankid-login-hidden');
        clearInterval(bankidRefreshId);
    }

    async function identify(callback) {
        try {
            const response = await fetch(`${mobile_bankid_integration_rest_api}/identify`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            const data = await response.json();
            if (data.orderRef === null) {
                throw new Error('Order reference is null');
            }
            callback(data);
        } catch (error) {
            console.error("Something went wrong with BankID identify request.", error);
        }
    }

    jQuery('#bankid-login-button').on('click', function (event) {
        event.preventDefault();
        identify((data) => {
            initializeLoginPage(data.autoStartToken);
            orderRef = data.orderRef;
            bankidRefreshId = setInterval(handleStatus, 1000);
        });
    });

    jQuery('#bankid-login-container button.accordion-button').on('click', function (event) {
        event.preventDefault();
        jQuery(this).toggleClass('active');
        jQuery(this).attr('aria-expanded', jQuery(this).attr('aria-expanded') === 'true' ? 'false' : 'true');
        jQuery(this).next().slideToggle();
    });

    jQuery('#bankid-qr-code-container').on('click', function (event) {
        jQuery(this).toggleClass('full-screen');
        jQuery(this).attr('aria-expanded', jQuery(this).attr('aria-expanded') === 'true' ? 'false' : 'true');
        jQuery(this).attr('aria-label', jQuery(this).attr('aria-label') === mobile_bankid_integration_login_localization.qr_click_to_enlarge ? mobile_bankid_integration_login_localization.qr_click_to_shrink : mobile_bankid_integration_login_localization.qr_click_to_enlarge);
        if (jQuery(this).hasClass('full-screen')) {
            jQuery('#login').after(this);
            jQuery('#login').hide();
            jQuery('#bankid-terms').hide();
        } else {
            jQuery('#bankid-status').after(this);
            jQuery('#login').show();
            jQuery('#bankid-terms').show();
        }
    });
});