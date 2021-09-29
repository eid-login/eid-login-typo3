document.addEventListener('DOMContentLoaded', function(e) {

    // check for default_mail_from_address
    if (document.getElementById('eidlogin-settings-datasrc').dataset.default_mail_from_address_present !== '1') {
        document.getElementById('eidlogin-settings-missingdefaultmailfromaddress').classList.remove('hidden');
    }
    // check for tasks
    if (document.getElementById('eidlogin-settings-datasrc').dataset.tasks_present !== '1') {
        document.getElementById('eidlogin-settings-missingtask').classList.remove('hidden');
    }
    // check for TLS and tasks
    if(window.location.protocol!=='https:') {
        document.getElementById('eidlogin-settings-notls').classList.remove('hidden');
    } else {
        document.getElementById('eidlogin-settings-index-panel').classList.remove('hidden');
    }
    document.getElementById('eidlogin-settings-spinner').classList.add('hidden');

    // toggle the wizard help div
    function toggleHelp() {
        const panelHelp = document.getElementById('eidlogin-settings-wizard-panel-help'); 
        const buttonHelp = document.getElementById('eidlogin-settings-button-help');
        if (panelHelp.classList.contains('hidden')) {
            panelHelp.classList.remove('hidden');
            buttonHelp.classList.add('active');
        } else {
            panelHelp.classList.add('hidden');
            buttonHelp.classList.remove('active');
        }
    }
    Array.from(document.querySelectorAll('[data-help="help"]')).forEach(el=>el.addEventListener('click', toggleHelp));

    // open settings for a specific site
    function openSettings() {
        window.location = this.dataset.open_settings_link;
    }
    Array.from(document.querySelectorAll('[data-open_settings_link]')).forEach(el=>el.addEventListener('click', openSettings));

}); // DOMContentLoaded