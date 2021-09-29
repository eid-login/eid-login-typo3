document.addEventListener('DOMContentLoaded', function(e) {

    const inputDisablePwLogin = document.getElementById('eidlogin-settings-disable_pw_login-checkbox');
    const urlDisablePwLogin = document.getElementById('eidlogin-settings-data').dataset.url_disable_pw_login;
    const urlEnablePwLogin = document.getElementById('eidlogin-settings-data').dataset.url_enable_pw_login;

    inputDisablePwLogin.addEventListener('change', function(){
        if(this.checked) {
            window.location = urlDisablePwLogin;
        } else {
            window.location = urlEnablePwLogin;
        };
    });

}); // DOMContentLoaded