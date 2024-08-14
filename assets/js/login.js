if (typeof jQuery === 'undefined') {
    throw new Error('This JavaScript requires jQuery');
}

var orderRef = null;
var bankidRefreshId = null;

function initializeLoginPage(autoStartToken) {
    const loginButtonContainer = document.getElementById("bankid-login-button").parentElement;
    const bankIdLoginContainer = document.getElementById('bankid-login-container');

    const statusElement = document.getElementById('bankid-status');
    statusElement.textContent = mobile_bankid_integration_login_localization.qr_instructions;

    const cancelButton = document.getElementById('cancel_bankid');
    cancelButton.onclick = cancelBankIdLogin;

    const openBankidButton = document.getElementById('open_bankid');
    openBankidButton.href = `https://app.bankid.com/?autostarttoken=${autoStartToken}&redirect=null`;

    loginButtonContainer.after(bankIdLoginContainer);
    loginButtonContainer.style.display = 'none';

    bankIdLoginContainer.style.display = 'block';

    document.getElementById('login').classList.add('bankid-login');
}

function handleStatus() {
    if (orderRef === null || document.getElementById('bankid-qr-code').style.display == 'none') {
        return;
    }

    // Send REST API request to get QR code status
    jQuery.ajax({
        url: `${mobile_bankid_integration_rest_api}/status?orderRef=${orderRef}`,
        type: 'GET',
        dataType: 'json',
        success: function (data) {
            if (data.qr !== null) {
                document.getElementById('bankid-qr-code').src = data.qr;
            }

            if ( data.status === 'failed' && data.hintCode === 'startFailed' ) {
                identify(
                    (data) => {
                        orderRef = data.orderRef;
                        const open_on_this_device = document.getElementById('open_bankid');
                        open_on_this_device.href = `https://app.bankid.com/?autostarttoken=${data.autoStartToken}&redirect=null`;
                    }
                );
                return;
            }

            switch (data.status) {
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

            if (data.hintCode !== null) {
                handleHintCode(data.hintCode);
            }
        },
        error: function () {
            displayErrorMessage(mobile_bankid_integration_login_localization.something_went_wrong);
            console.log("Something went wrong. Debug info:\n\n", data);
        }
    });
}

function handleHintCode(hintCode) {
    const statusElement = document.getElementById('bankid-status');
    switch (hintCode) {
        case 'userCancel':
            statusElement.innerHTML = mobile_bankid_integration_login_localization.hintcode_userCancel;
            break;
        case 'userSign':
            statusElement.innerHTML = mobile_bankid_integration_login_localization.hintcode_userSign;
            break;
        case 'startFailed':
            statusElement.innerHTML = mobile_bankid_integration_login_localization.hintcode_startFailed;
            break;
        case 'certificateErr':
            statusElement.innerHTML = mobile_bankid_integration_login_localization.hintcode_certificateErr;
            break;
        default:
            statusElement.innerHTML = mobile_bankid_integration_login_localization.qr_instructions;
            break;
    }
}

function displayErrorMessage(message) {
    const statusElement = document.getElementById('bankid-status');
    statusElement.innerHTML = message;
    document.getElementById('bankid-qr-code').src = '';
    document.getElementById('bankid-qr-code-container').style.display = 'none';
    document.getElementById('open_bankid').style.display = 'none';
    document.getElementById('bankid-login-container').classList.add('error');
    orderRef = null;
    clearInterval(bankidRefreshId);
}

function completeLogin() {
    orderRef = null;
    document.getElementById('bankid-status').innerHTML = mobile_bankid_integration_login_localization.status_complete;
    document.getElementById('bankid-qr-code').src = '';
    document.getElementById('bankid-qr-code-container').style.display = 'none';
    document.getElementById('open_bankid').style.display = 'none';
    window.location.href = mobile_bankid_integration_redirect_url;
    clearInterval(bankidRefreshId);
}

function cancelBankIdLogin() {
    const loginButtonContainer = document.getElementById("bankid-login-button").parentElement;
    const bankIdLoginContainer = document.getElementById("bankid-login-container");
    bankIdLoginContainer.style.display = 'none';
    loginButtonContainer.style.display = 'block';

    // Close accordions
    jQuery('#bankid-login-container button.accordion-button').removeClass('active');
    jQuery('#bankid-login-container button.accordion-button').attr('aria-expanded', 'false');
    jQuery('#bankid-login-container button.accordion-button').next().slideUp();

    document.getElementById('login').classList.remove('bankid-login');
    clearInterval(bankidRefreshId);
}

function identify(callback) {
    jQuery.ajax({
        url: `${mobile_bankid_integration_rest_api}/identify`,
        type: 'POST',
        dataType: 'json',
        success: function (data) {
            if (data.orderRef === null) 
                throw new Error('Order reference is null');
            callback(data);
        },
        error: function () {
            console.log("Something went wrong with BankID identify request.");
        }
    });
}

jQuery(document).ready(function () {
    jQuery('#bankid-login-button').on('click', function (event) {
        event.preventDefault();
        identify(
            (data) => {
                initializeLoginPage(data.autoStartToken);
                orderRef = data.orderRef;
                bankidRefreshId = setInterval(handleStatus, 1000);
            }
        );
    });

    jQuery('#bankid-login-container button.accordion-button').on('click', function (event) {
        event.preventDefault();
        jQuery(this).toggleClass('active');
        jQuery(this).attr('aria-expanded', jQuery(this).attr('aria-expanded') === 'true' ? 'false' : 'true');
        jQuery(this).next().slideToggle();
    } );

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