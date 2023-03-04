// Remove unnecessary elements
document.getElementById('adminmenumain').remove();
document.getElementById('wpadminbar').remove();
document.getElementById('wpfooter').remove();
document.getElementById('screen-meta').remove();

function nextStep() {
    step = document.getElementById('wizard-content').attributes.step.value;
    if (step < 3) {
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
    document.getElementById('wizard-modal-confirmation-text').innerHTML = confirmationText+'<br><br>' + wp_bankid_setup_localization.confirmation_abort_text;
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
    if (id == 'wp-bankid-testenv') {
        autoconfiguretestenv();
    }

    document.getElementById('wizard-modal').style.display = 'none';
}

function configureSubmit() {
    // Check if all required fields are filled
    if (!document.getElementById('wp-bankid-endpoint').value) {
        alert(wp_bankid_setup_localization.endpoint_required);
        return false;
    }
    if (!document.getElementById('wp-bankid-certificate').value) {
        alert(wp_bankid_setup_localization.certificate_required);
        return false;
    }
    if (!document.getElementById('wp-bankid-password').value) {
        alert(wp_bankid_setup_localization.password_required);
        return false;
    }

    // Call REST API
    var xhr = new XMLHttpRequest();
    xhr.open('POST', wp_bankid_rest_api + '/configuration', true);
    xhr.setRequestHeader('X-WP-Nonce', wp_bankid_rest_api_nonce);

    xhr.onload = function() {
        if (this.status == 200) {
            nextStep();
        } else {
            response = JSON.parse(this.responseText);
            alert(wp_bankid_setup_localization.configuration_failed + response['message']);
        }
    }

    formdata = new FormData();
    formdata.append('endpoint', document.getElementById('wp-bankid-endpoint').value);
    formdata.append('certificate', document.getElementById('wp-bankid-certificate').value);
    formdata.append('password', document.getElementById('wp-bankid-password').value);

    // Send request with data
    xhr.send(formdata);
}

function autoconfiguretestenv() {
    // Call REST API
    var xhr = new XMLHttpRequest();
    xhr.open('GET', wp_bankid_rest_api + '/autoconfiguretestenv', true);
    xhr.setRequestHeader('X-WP-Nonce', wp_bankid_rest_api_nonce);

    xhr.onload = function() {
        if (this.status == 200) {
            nextStep();
        } else {
            alert(wp_bankid_setup_localization.testenv_autoconfig_failed);
            document.getElementById('wp-bankid-testenv').checked = false;
        }
    }
    // Send request
    xhr.send();
}

/* Listen for clicks on wp-bankid-testenv checkbox */
wp_bankid_testenv = document.getElementById('wp-bankid-testenv');
if (wp_bankid_testenv) {
    wp_bankid_testenv.addEventListener('click', function() {
        if (this.checked) {
            requireconfirmation('wp-bankid-testenv', wp_bankid_setup_localization.testenv_confirmation_text);
        }
    });
}