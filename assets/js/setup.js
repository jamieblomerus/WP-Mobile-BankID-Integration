// Remove unnecessary elements
document.getElementById('adminmenumain').remove();
document.getElementById('wpadminbar').remove();
document.getElementById('wpfooter').remove();
document.getElementById('screen-meta').remove();

function nextStep() {
    step = document.getElementById('wizard-content').attributes.step.value;
    if (step < 4) {
        /* Disable functionality on current step */
        for(i in document.getElementById('wizard-content').getElementsByTagName('button')) {
            document.getElementById('wizard-content').getElementsByTagName('button')[i].disabled = true;
        }
        for(i in document.getElementById('wizard-content').getElementsByTagName('input')) {
            document.getElementById('wizard-content').getElementsByTagName('input')[i].disabled = true;
        }
        /* Animate next step */
        document.getElementsByClassName('steps')[0].children[0].children[step - 1].classList.add('done');
        document.getElementsByClassName('steps')[0].children[0].children[step - 1].classList.remove('active');
        document.getElementsByClassName('steps')[0].children[0].children[step -1].classList.add('animate-done');
        document.getElementsByClassName('steps')[0].children[0].children[step].classList.add('active');
        document.getElementsByClassName('steps')[0].children[0].children[step].classList.add('animate-active');

        step++;
        setTimeout(function() {
            window.location.search += '&step='+step;
        }, 500);
    } else {
        console.log("nextStep() can't be used on last step."); /* Only for dev use, shall not have localization */
    }
}

function requireconfirmation(id, confirmationText) {
    document.getElementById('wizard-modal-confirmation-text').innerHTML = confirmationText+'<br><br>' + mobile_bankid_integration_setup_localization.confirmation_abort_text;
    document.getElementById('wizard-modal-abort').setAttribute('onclick', 'abortconfirmation("'+id+'")');
    document.getElementById('wizard-modal-confirm').setAttribute('onclick', 'confirmconfirmation("'+id+'")');
    document.getElementById('wizard-modal-abort').removeAttribute('disabled');
    document.getElementById('wizard-modal-confirm').removeAttribute('disabled');
    document.getElementById('wizard-modal').style.display = 'block';
}

function abortconfirmation(id = null) {
    if (id != null) {
        document.getElementById(id).checked = false;
    }
    document.getElementById('wizard-modal').style.display = 'none';
}

function confirmconfirmation(id) {
    document.getElementById('wizard-modal-abort').setAttribute('disabled', 'disabled');
    document.getElementById('wizard-modal-confirm').setAttribute('disabled', 'disabled');
    if (id == 'mobile-bankid-integration-testenv') {
        autoconfiguretestenv();
    }

    document.getElementById('wizard-modal').style.display = 'none';
}

function configureSubmit() {
    if (!document.getElementById('mobile-bankid-integration-certificate').value) {
        alert(mobile_bankid_integration_setup_localization.certificate_required);
        return false;
    }
    if (!document.getElementById('mobile-bankid-integration-password').value) {
        alert(mobile_bankid_integration_setup_localization.password_required);
        return false;
    }

    // Call REST API
    var xhr = new XMLHttpRequest();
    xhr.open('POST', mobile_bankid_integration_rest_api + '/configuration', true);
    xhr.setRequestHeader('X-WP-Nonce', mobile_bankid_integration_rest_api_nonce);

    xhr.onload = function() {
        if (this.status == 200) {
            nextStep();
        } else {
            response = JSON.parse(this.responseText);
            alert(mobile_bankid_integration_setup_localization.configuration_failed + response['message']);
        }
    }

    formdata = new FormData();
    formdata.append('certificate', document.getElementById('mobile-bankid-integration-certificate').value);
    formdata.append('password', document.getElementById('mobile-bankid-integration-password').value);

    // Send request with data
    xhr.send(formdata);
}

function settingsSubmit() {
    // Call REST API
    var xhr = new XMLHttpRequest();
    xhr.open('POST', mobile_bankid_integration_rest_api + '/setup_settings', true);
    xhr.setRequestHeader('X-WP-Nonce', mobile_bankid_integration_rest_api_nonce);

    xhr.onload = function() {
        if (this.status == 200) {
            nextStep();
        } else {
            response = JSON.parse(this.responseText);
            alert(mobile_bankid_integration_setup_localization.configuration_failed + response['message']);
        }
    }

    formdata = new FormData();
    formdata.append('wplogin', document.getElementById('mobile-bankid-integration-wplogin').value);
    formdata.append('registration', document.getElementById('mobile-bankid-integration-registration').value);

    // Send request with data
    xhr.send(formdata);
}

function autoconfiguretestenv() {
    // Call REST API
    var xhr = new XMLHttpRequest();
    xhr.open('GET', mobile_bankid_integration_rest_api + '/autoconfiguretestenv', true);
    xhr.setRequestHeader('X-WP-Nonce', mobile_bankid_integration_rest_api_nonce);

    xhr.onload = function() {
        if (this.status == 200) {
            nextStep();
        } else {
            alert(mobile_bankid_integration_setup_localization.testenv_autoconfig_failed);
            document.getElementById('mobile-bankid-integration-testenv').checked = false;
        }
    }
    // Send request
    xhr.send();
}

/* Listen for clicks on mobile-bankid-integration-testenv checkbox */
mobile_bankid_integration_testenv = document.getElementById('mobile-bankid-integration-testenv');
if (mobile_bankid_integration_testenv) {
    mobile_bankid_integration_testenv.addEventListener('click', function() {
        if (this.checked) {
            requireconfirmation('mobile-bankid-integration-testenv', mobile_bankid_integration_setup_localization.testenv_confirmation_text);
        }
    });
}