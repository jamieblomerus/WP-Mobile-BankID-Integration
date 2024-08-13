if (typeof jQuery === 'undefined') {
    throw new Error('This JavaScript requires jQuery');
}

var orderRef = null;
var bankidRefreshId = null;

function initializeLoginPage(autoStartToken) {
    const loginButtonContainer = document.getElementById("bankid-login-button").parentElement;

    const bankIdLoginContainer = document.createElement('form');
    bankIdLoginContainer.id = 'bankid-login-container';

    // Create and append elements to the loginButtonContainer
    const titleElement = document.createElement('h2');
    titleElement.textContent = mobile_bankid_integration_login_localization.title;

    const statusElement = document.createElement('p');
    statusElement.id = 'bankid-status';
    statusElement.textContent = mobile_bankid_integration_login_localization.qr_instructions;

    const qrCodeContainer = document.createElement('div');
    qrCodeContainer.id = 'bankid-qr-code-container';

    const qrCodeElement = document.createElement('img');
    qrCodeElement.id = 'bankid-qr-code';
    qrCodeElement.src = '';
    qrCodeElement.alt = mobile_bankid_integration_login_localization.qr_alt;
    qrCodeContainer.appendChild(qrCodeElement);

    const lineBreak1 = document.createElement('br');
    const lineBreak2 = document.createElement('br');

    const cancelButton = document.createElement('a');
    cancelButton.href = '#';
    cancelButton.className = 'button wp-element-button';
    cancelButton.onclick = cancelBankIdLogin;
    cancelButton.textContent = mobile_bankid_integration_login_localization.cancel;

    const openBankidButton = document.createElement('a');
    openBankidButton.style.marginLeft = '5px';
    openBankidButton.target = '_blank';
    openBankidButton.id = 'open_bankid';
    openBankidButton.href = `https://app.bankid.com/?autostarttoken=${autoStartToken}&redirect=null`;
    openBankidButton.className = 'button wp-element-button';
    openBankidButton.textContent = mobile_bankid_integration_login_localization.open_on_this_device;

    // Clear the container and append the new elements
    bankIdLoginContainer.appendChild(titleElement);
    bankIdLoginContainer.appendChild(statusElement);
    bankIdLoginContainer.appendChild(qrCodeContainer);
    bankIdLoginContainer.appendChild(lineBreak1);
    bankIdLoginContainer.appendChild(lineBreak2);
    bankIdLoginContainer.appendChild(cancelButton);
    bankIdLoginContainer.appendChild(openBankidButton);

    // Sibling to loginButtonContainer
    loginButtonContainer.after(bankIdLoginContainer);

    // Hide the login button
    loginButtonContainer.style.display = 'none';
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
    bankIdLoginContainer.remove();
    loginButtonContainer.style.display = 'block';
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
});