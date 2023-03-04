if (typeof jQuery === 'undefined') {
    throw new Error('This JavaScript requires jQuery');
}

var orderRef = null;
var bankidRefreshId = null;

jQuery(document).ready(function() {
    // Add event listener to login button
    jQuery('#bankid-login-button').on('click', function() {
        // Send REST API request to start BankID identification
        jQuery.ajax({
            url: wp_bankid_rest_api + '/identify',
            type: 'POST',
            dataType: 'json',
            success: function(data) {
                if (data.orderRef !== null) {
                    loginPage(data.autoStartToken);

                    // Save orderRef
                    orderRef = data.orderRef;

                    // Show QR code
                    bankidRefreshId = setInterval(status, 1000);
                }
            },
            error: function(data) {
                // Show error message
                console.log("Something went wrong with BankID identify request.");
            }
        });
    });
});

function loginPage(autoStartToken) {
    document.getElementById("bankid-login-button").parentElement.parentElement.innerHTML = '<h2>'+wp_bankid_login_localization.title+'</h2><p id="bankid-status">'+wp_bankid_login_localization.qr_instructions+'</p><img id="bankid-qr-code" src="" alt="'+wp_bankid_login_localization.qr_alt+'" /><br><br><a href="#" class="button" onclick="window.location.reload();">'+wp_bankid_login_localization.cancel+'</a><a style="margin-left: 5px;" target="_blank" id="open_bankid" href="https://app.bankid.com/?autostarttoken='+autoStartToken+'&redirect=null" class="button">'+wp_bankid_login_localization.open_on_this_device+'</a>';
}

function status() {
    if (orderRef === null || document.getElementById('bankid-qr-code').style.display == 'none') {
        return;
    }
    // Send REST API request to get QR code
    jQuery.ajax({
        url: wp_bankid_rest_api + '/status?orderRef=' + orderRef,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.qr !== null) {
                // Show QR code
                document.getElementById('bankid-qr-code').src = data.qr;
            }

            switch (data.status) {
                case 'expired':
                    // Show error message
                    showErrorMessage(wp_bankid_login_localization.status_expired);
                    clearInterval(bankidRefreshId);
                    break;
                case 'complete':
                    orderRef = null;
                    // Show success message
                    document.getElementById('bankid-status').innerHTML = wp_bankid_login_localization.status_complete;
                    document.getElementById('bankid-qr-code').src = '';
                    document.getElementById('bankid-qr-code').style.display = 'none';
                    document.getElementById('open_bankid').style.display = 'none';
                    // Redirect to my account page
                    window.location.href = wp_bankid_redirect_url;
                    clearInterval(bankidRefreshId);
                    break;
                case 'complete_no_user':
                    showErrorMessage(wp_bankid_login_localization.status_complete_no_user);
                    clearInterval(bankidRefreshId);
                    break;
                case 'failed':
                    showErrorMessage(wp_bankid_login_localization.status_failed);
                    clearInterval(bankidRefreshId);
                    break;
            }
        },
        error: function(data) {
            if (orderRef === null) {
                clearInterval(bankidRefreshId);
                return;
            }
            // Show error message
            document.getElementById('bankid-status').innerHTML = wp_bankid_login_localization.something_went_wrong;
            document.getElementById('bankid-qr-code').src = '';
            document.getElementById('bankid-qr-code').style.display = 'none';
            document.getElementById('open_bankid').style.display = 'none';
            console.log("Something went wrong. Debug info:\n\n" + data);
            clearInterval(bankidRefreshId);
        }
    });
}

function showErrorMessage(message) {
    document.getElementById('bankid-status').innerHTML = message;
    document.getElementById('bankid-qr-code').src = '';
    document.getElementById('bankid-qr-code').style.display = 'none';
    document.getElementById('open_bankid').style.display = 'none';
    orderRef = null;
    clearInterval(bankidRefreshId);
}