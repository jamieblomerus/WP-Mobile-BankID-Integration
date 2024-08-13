if (typeof jQuery === 'undefined') {
    throw new Error('This JavaScript requires jQuery');
}

var orderRef = null;
var bankidRefreshId = null;

function initializeLoginPage(autoStartToken) {
    const loginButtonContainer = document.getElementById("bankid-login-button").parentElement;
    loginButtonContainer.innerHTML = `
        <h2>${mobile_bankid_integration_login_localization.title}</h2>
        <p id="bankid-status">${mobile_bankid_integration_login_localization.qr_instructions}</p>
        <img id="bankid-qr-code" src="" alt="${mobile_bankid_integration_login_localization.qr_alt}" />
        <br><br>
        <a href="#" class="button wp-element-button" onclick="window.location.reload();">
            ${mobile_bankid_integration_login_localization.cancel}
        </a>
        <a style="margin-left: 5px;" target="_blank" id="open_bankid" href="https://app.bankid.com/?autostarttoken=${autoStartToken}&redirect=null" class="button wp-element-button">
            ${mobile_bankid_integration_login_localization.open_on_this_device}
        </a>`;
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
        success: function(data) {
            if (data.qr !== null) {
                document.getElementById('bankid-qr-code').src = data.qr;
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
        error: function() {
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
    document.getElementById('bankid-qr-code').style.display = 'none';
    document.getElementById('open_bankid').style.display = 'none';
    orderRef = null;
    clearInterval(bankidRefreshId);
}

function completeLogin() {
    orderRef = null;
    document.getElementById('bankid-status').innerHTML = mobile_bankid_integration_login_localization.status_complete;
    document.getElementById('bankid-qr-code').src = '';
    document.getElementById('bankid-qr-code').style.display = 'none';
    document.getElementById('open_bankid').style.display = 'none';
    window.location.href = mobile_bankid_integration_redirect_url;
    clearInterval(bankidRefreshId);
}

jQuery(document).ready(function() {
    jQuery('#bankid-login-button').on('click', function( event ) {
        event.preventDefault();
        jQuery.ajax({
            url: `${mobile_bankid_integration_rest_api}/identify`,
            type: 'POST',
            dataType: 'json',
            success: function(data) {
                if (data.orderRef !== null) {
                    initializeLoginPage(data.autoStartToken);
                    orderRef = data.orderRef;
                    bankidRefreshId = setInterval(handleStatus, 1000);
                }
            },
            error: function() {
                console.log("Something went wrong with BankID identify request.");
            }
        });
    });
});