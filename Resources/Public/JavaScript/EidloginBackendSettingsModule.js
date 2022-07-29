// js module for the eid login settings backend module
var AjaxRequestMod = 'TYPO3/CMS/Core/Ajax/AjaxRequest';
var NotificationMod = 'TYPO3/CMS/Backend/Notification';
var ModalMod='TYPO3/CMS/Backend/Modal';
define([
    AjaxRequestMod,
    NotificationMod,
    ModalMod,
    ], function() {

    // setup required stuff
    //
    var AjaxRequest = require(AjaxRequestMod);
    var Notification = require(NotificationMod);
    var Modal = require(ModalMod);

    // define variables
    //
    var mod = {
        dom: '',
        skidMetadataUrl: 'https://service.skidentity.de/fs/saml/metadata',
        skidManagementUrl: 'https://sp.skidentity.de/',
        activated: false,
    };

    // define functions
    //
    // init state
    mod.init = function() {
        // dom elements
        this.dom = {
            dataSrc: document.getElementById('eidlogin-settings-datasrc'),
            wizard: document.getElementById('eidlogin-settings-wizard'),
            buttonHelp: document.getElementById('eidlogin-settings-button-help'),
            buttonSelectSkid: document.getElementById('eidlogin-settings-button-select-skid'),
            inputMetaIdp: document.getElementById('eidlogin-settings-form-wizard-idp_metadata_url'),
            stepWizardSave: document.getElementById('eidlogin-settings-wizard-step-3'),
            buttonToggleIdp: document.getElementById('eidlogin-settings-button-toggleidp'),
            buttonWizardSave: document.getElementById('eidlogin-settings-button-next-3'),
            skidRow: document.getElementById('eidlogin-settings-skid-row'),
            buttonToggleSp: document.getElementById('eidlogin-settings-button-togglesp'),
            stepWizardActivate: document.getElementById('eidlogin-settings-wizard-step-4'),
            buttonWizardActivate: document.getElementById('eidlogin-settings-button-next-4'),
            buttonWizardFinish: document.getElementById('eidlogin-settings-button-finish'),
            manual: document.getElementById('eidlogin-settings-manual'),
            formManual: document.getElementById('eidlogin-settings-manual-form'),
            certActDiv: document.getElementById('eidlogin-settings-manual-div-cert-act'),
            certActEncDiv: document.getElementById('eidlogin-settings-manual-div-cert-act-enc'),
            certNewDiv: document.getElementById('eidlogin-settings-manual-div-cert-new'),
            certNewEncDiv: document.getElementById('eidlogin-settings-manual-div-cert-new-enc'),
            buttonRolloverPrep: document.getElementById('eidlogin-settings-button-rollover-prepare'),
            buttonRolloverExec: document.getElementById('eidlogin-settings-button-rollover-execute'),
            spanRolloverExec: document.getElementById('eidlogin-settings-span-rollover-execute')
        }
        // load data
        const settingsPresent = mod.dom.dataSrc.dataset.settings_present;
        const certActPresent = mod.dom.dataSrc.dataset.act_cert_present;
        const certNewPresent = mod.dom.dataSrc.dataset.new_cert_present;
        // event listener setup
        Array.from(document.querySelectorAll('button[data-panel]')).forEach(el=>el.addEventListener('click', mod.switchPanelEventListener));
        Array.from(document.querySelectorAll('[data-help="help"]')).forEach(el=>el.addEventListener('click', mod.toggleHelp));
        mod.dom.buttonSelectSkid.addEventListener('click', mod.selectSkid);
        mod.dom.inputMetaIdp.addEventListener('input', mod.debounce(mod.updateIdpSettings, 200));
        mod.dom.buttonToggleIdp.addEventListener('click', mod.toggleIdp);
        mod.dom.stepWizardSave.addEventListener('click', mod.saveSettings);
        mod.dom.buttonWizardSave.addEventListener('click', mod.saveSettings);
        mod.dom.buttonToggleSp.addEventListener('click', mod.toggleSp);
        document.getElementById('eidlogin-settings-button-skid').addEventListener('click', mod.openSkid);
        mod.dom.stepWizardActivate.addEventListener('click', mod.activate);
        mod.dom.buttonWizardActivate.addEventListener('click', mod.activate);
        mod.dom.buttonWizardFinish.addEventListener('click', (e)=>{
            e.preventDefault();
            window.scrollTo(0,0);
            window.location.reload();
        });
        document.getElementById('eidlogin-settings-input-activated').addEventListener('click', mod.toggleActivated);
        document.getElementById('eidlogin-settings-button-manual-save').addEventListener('click', mod.confirmSave);
        document.getElementById('eidlogin-settings-button-reset').addEventListener('click', mod.reset);
        mod.dom.buttonRolloverPrep.addEventListener('click', mod.prepRollover);
        mod.dom.buttonRolloverExec.addEventListener('click', mod.execRollover);
        // decide what to show
        if(window.location.protocol!=='https:') {
            document.getElementById('eidlogin-settings-notls').classList.remove('hidden');
        } else if(settingsPresent=="") {
            mod.switchWizardPanel(1);
            mod.dom.manual.classList.add('hidden');
            mod.dom.wizard.classList.remove('hidden');
            // prefill SP EntityID in wizard, use base url of site if present
            if (mod.dom.dataSrc.dataset.url_base!='') {
                document.getElementById('eidlogin-settings-form-wizard-sp_entity_id').value = mod.dom.dataSrc.dataset.url_base;
            } else {
                document.getElementById('eidlogin-settings-form-wizard-sp_entity_id').value = window.location.protocol+'//'+window.location.host;
            }
        } else {
            mod.dom.wizard.classList.add('hidden');
            mod.dom.manual.classList.remove('hidden');
            // decide about rollover div
            if(certActPresent==='1') {
                document.getElementById('eidlogin-settings-manual-div-rollover').classList.remove('hidden');
            }
            // decide about showing new cert and key rollover execute button state
            if(certNewPresent==='1') {
                mod.dom.buttonRolloverExec.disabled = false;
                mod.dom.spanRolloverExec.classList.add('hidden');
            }
            // decide about acs url
            var acsUrl = mod.dom.dataSrc.dataset.url_acs_post;
            if (document.getElementById('eidlogin-settings-form-manual-idp_ext_tr03130').value!='') {
                acsUrl = mod.dom.dataSrc.dataset.url_acs_redirect;
            }
            document.getElementById('eidlogin-settings-form-manual-sp_acs_url').value=acsUrl;
        }
        document.getElementById('eidlogin-settings-spinner').classList.add('hidden');
    };

    // select skid and save instantly
    mod.selectSkid = function(e) {
        document.getElementById('eidlogin-settings-form-wizard-idp_metadata_url').value = mod.skidMetadataUrl;
        mod.updateIdpSettings(e);
    }

    // switch to a wizard panel
    mod.switchWizardPanel = function(panel) {
        mod.dom.buttonToggleIdp.innerText=TYPO3.lang.be_js_txt_show_idp;
        mod.dom.buttonToggleSp.innerText=TYPO3.lang.be_js_txt_show_sp;
        Array.from(mod.dom.wizard.getElementsByClassName('step')).forEach(el => {
            el.classList.remove('active')
            el.classList.add('disabled')
            el.removeEventListener('click', mod.switchPanelEventListener)
            el.removeEventListener('click', mod.saveSettings)
        });
        Array.from(mod.dom.wizard.getElementsByClassName('panel')).forEach(el => {
            el.classList.remove('active');
            el.classList.add('hidden')
        });
        document.getElementById('eidlogin-settings-wizard-panel-'+panel).classList.remove('hidden');
        for (var i=1;i<=parseInt(panel)+1;i++) {
        // enable panel switching via step links
        if (i<=4) {
            // enable form save via step link 3 coming from the start
            if (panel<=2 && i==3) {
                document.getElementById('eidlogin-settings-wizard-step-'+i).addEventListener('click', mod.saveSettings);
            } else {
                document.getElementById('eidlogin-settings-wizard-step-'+i).addEventListener('click', mod.switchPanelEventListener);
            }
            document.getElementById('eidlogin-settings-wizard-step-'+i).classList.remove('disabled');
        }
        }
        document.getElementById('eidlogin-settings-wizard-step-'+panel).classList.add('active');
    };

    // toggle the wizard help div
    mod.toggleHelp = function() {
        const panelHelp = document.getElementById('eidlogin-settings-wizard-panel-help');
        if (panelHelp.classList.contains('hidden')) {
            panelHelp.classList.remove('hidden');
            mod.dom.buttonHelp.classList.add('active');
        } else {
            panelHelp.classList.add('hidden');
            mod.dom.buttonHelp.classList.remove('active');
        }
    }

    // switch the active wizard panel by buttons
    mod.switchPanelEventListener = function(e) {
        e.preventDefault();
        // don`t switch if we use skid, save or activate, is handled in saveSettings and activate
        if (e.target === mod.dom.buttonSelectSkid || e.target === mod.dom.buttonWizardSave || e.target === mod.dom.buttonWizardActivate || e.target === mod.dom.stepWizardActivate) {
            return;
        }
        mod.switchWizardPanel(e.target.dataset.panel);
    }

    // an improved debounce function from http://modernjavascript.blogspot.com/2013/08/building-better-debounce.html
    mod.debounce = function(func, wait) {
        var timeout, args, context, timestamp;
        return function() {
            context = this;
            args = [].slice.call(arguments, 0);
            timestamp = Date.now();
            var later = function() {
                var last = (Date.now()) - timestamp;
                if (last < wait) {
                    timeout = setTimeout(later, wait - last);
                } else {
                    timeout = null;
                    func.apply(context, args);
                }
            };
            if (!timeout) {
                timeout = setTimeout(later, wait);
            }
        }
    };

    // fetch and replace idp metadata values when url is changed
    mod.updateIdpSettings = function(e) {
        const sp_enforce_enc = document.getElementById('eidlogin-settings-form-wizard-sp_enforce_enc');
        const idp_cert_enc = document.getElementById('eidlogin-settings-form-wizard-idp_cert_enc');
        const idp_cert_sign = document.getElementById('eidlogin-settings-form-wizard-idp_cert_sign');
        const idp_entity_id = document.getElementById('eidlogin-settings-form-wizard-idp_entity_id');
        const idp_sso_url = document.getElementById('eidlogin-settings-form-wizard-idp_sso_url');
        const idp_ext_tr03130 = document.getElementById('eidlogin-settings-form-wizard-idp_ext_tr03130');
        if (mod.dom.inputMetaIdp.value==='') {
            sp_enforce_enc.value = '';
            idp_cert_enc.value = '';
            idp_cert_sign.value = '';
            idp_entity_id.value = '';
            idp_sso_url.value = '';
            idp_ext_tr03130.value = '';

            return;
        }
        mod.dom.buttonWizardSave.disabled = true;
        mod.dom.stepWizardSave.classList.add("disabled");
        var idpMetaURL = mod.dom.inputMetaIdp.value;
        idpMetaURL = encodeURIComponent(idpMetaURL);
        idpMetaURL = btoa(idpMetaURL);
        var url = mod.dom.dataSrc.dataset.url_fetchidpmeta
        url = url.replace('IDP_META_URL', idpMetaURL);
        var xhr = new XMLHttpRequest();
        xhr.addEventListener('load', (e2)=>{
            sp_enforce_enc.value = '';
            idp_cert_enc.value = '';
            idp_cert_sign.value = '';
            idp_entity_id.value = '';
            idp_sso_url.value = '';
            idp_ext_tr03130.value = '';
            if(e2.target.status == 200) {
                var idpMetadata = JSON.parse(e2.target.responseText);
                idp_cert_enc.value = idpMetadata['idp_cert_enc'];
                idp_cert_sign.value = idpMetadata['idp_cert_sign'];
                idp_entity_id.value = idpMetadata['idp_entity_id'];
                idp_sso_url.value = idpMetadata['idp_sso_url'];
                if (e.target==mod.dom.buttonSelectSkid) {
                    mod.saveSettings(e);
                }
            } else if(e2.target.status == 500) {
                Notification.error(TYPO3.lang.be_js_msg_err_idp_meta_fetch);
            }
            mod.dom.buttonWizardSave.disabled = false;
            mod.dom.stepWizardSave.classList.remove("disabled");
        });
        xhr.addEventListener('error', (e2)=>{
            Notification.error(TYPO3.lang.be_js_msg_err_idp_meta_fetch);
        });
        xhr.open('GET', url, true);
        xhr.send();
    }

    // toggle idp settings under configure panel
    mod.toggleIdp = function(e) {
        e.preventDefault();
        const panelIdpSettings = document.getElementById('eidlogin-settings-wizard-panel-idp_settings');
        if (panelIdpSettings.classList.contains('hidden')) {
            panelIdpSettings.classList.remove('hidden');
            mod.dom.buttonToggleIdp.innerText=TYPO3.lang.be_js_txt_hide_idp;
        } else {
            panelIdpSettings.classList.add('hidden');
            mod.dom.buttonToggleIdp.innerText=TYPO3.lang.be_js_txt_show_idp;
        }
    }

    // save the settings with a post of the form to SettingsController
    mod.saveSettings = function(e) {
        // maybe we need to switch panel
        const switchPanel = e.target.dataset.panel=="3";
        const url = mod.dom.dataSrc.dataset.url_save;
        var form;
        if (mod.dom.wizard.classList.contains('hidden')) {
            form = document.getElementById('eidlogin-settings-form-manual');
        } else {
            form = document.getElementById('eidlogin-settings-form-wizard');
        }
        const data = new FormData(form);
        const body = Object.fromEntries(data.entries());
        const init = {
            mode: 'cors'
        };
        const request = new AjaxRequest(url);
        request.post(body, init).then(
            async function (response) {
                const data = await response.resolve();
                if (data.status=="success") {
                    Notification.success(data.message);
                    if (switchPanel) {
                        // hide the skid button and it`s text, if we don't have skid as configured idp
                        if (mod.dom.inputMetaIdp.value===mod.skidMetadataUrl) {
                            mod.dom.skidRow.classList.remove('hidden');
                        } else {
                            mod.dom.skidRow.classList.add('hidden');
                        }
                        mod.switchWizardPanel(3);
                        // display the sp_entity_id
                        document.getElementById('eidlogin-settings-wizard-display-sp_entity_id').innerText=document.getElementById('eidlogin-settings-form-wizard-sp_entity_id').value;
                    }
                    // decide about the sp_acs_url
                    var acsUrl = mod.dom.dataSrc.dataset.url_acs_post;
                    if (document.getElementById('eidlogin-settings-form-wizard-idp_ext_tr03130').value!='' ||
                    document.getElementById('eidlogin-settings-form-manual-idp_ext_tr03130').value!=''
                     ) {
                        acsUrl = mod.dom.dataSrc.dataset.url_acs_redirect;
                    }
                    document.getElementById('eidlogin-settings-wizard-display-sp_acs_url').innerText=acsUrl;
                    document.getElementById('eidlogin-settings-form-manual-sp_acs_url').value=acsUrl;
                } else {
                    data.errors.forEach(error => {
                        Notification.error(error);
                    });
                }
            }, function (error) {
                Notification.error(TYPO3.lang.be_js_msg_err_save);
            }
        );
    }

    // open skid in a new tab/win
    mod.openSkid = function(e) {
        e.preventDefault();
        window.open(mod.skidManagementUrl,'_blank');
    }

    // toggle sp metadata under idp panel
    mod.toggleSp = function(e) {
        e.preventDefault();
        const spPanel = document.getElementById('eidlogin-settings-wizard-panel-register-sp');
        if (spPanel.classList.contains('hidden')) {
            const errMsg = TYPO3.lang.be_js_msg_err_sp_meta_fetch;
            const url = mod.dom.dataSrc.dataset.url_meta;
            var xhr = new XMLHttpRequest();
            xhr.addEventListener('load', (e2)=>{
                if(e2.target.status == 200) {
                    var spMetadata = e2.target.responseText;
                    var spMetadataPre = document.getElementById('eidlogin-settings-wizard-panel-register-sp-metadata');
                    spMetadataPre.innerText = "";
                    spMetadataPre.appendChild(document.createTextNode(spMetadata));
                } else {
                    Notification.error(errMsg);
                }
            });
            xhr.addEventListener('error', (e2)=>{
                showError(errMsg);
            });
            xhr.open('GET', url, true);
            xhr.send();
            mod.dom.buttonToggleSp.innerText=TYPO3.lang.be_js_txt_hide_sp;
            spPanel.classList.remove('hidden');
        } else {
            mod.dom.buttonToggleSp.innerText=TYPO3.lang.be_js_txt_show_sp;
            spPanel.classList.add('hidden');
        }
    }

    // activate the eID-Login after security question
    mod.activate = function(e) {
        e.preventDefault();
        if (mod.activated) {
            mod.switchWizardPanel(4);
            return
        }
        var activateModalConf = {
            type: Modal.types.default,
            severity: 2,
            title: TYPO3.lang.be_js_txt_activate_title,
            content: TYPO3.lang.be_js_txt_activate_content,
            buttons: [
                {
                    text: TYPO3.lang.be_js_txt_cancel,
                    trigger: function() {
                        Modal.dismiss();
                    }
                },
                {
                    text: TYPO3.lang.be_js_txt_next,
                    active: true,
                    btnClass: 'btn-danger',
                    trigger: function() {
                        Modal.dismiss();
                        const errMsg = TYPO3.lang.be_js_msg_err_activate;
                        const url = mod.dom.dataSrc.dataset.url_toggle_activated;
                        var request = new AjaxRequest(url);
                        request = request.withQueryArguments({'site_root_page_id': mod.dom.dataSrc.dataset.pageid_siteroot});
                        request.get().then(
                            async function (response) {
                                const data = await response.resolve();
                                if (data.status=="success") {
                                    mod.activated = true;
                                    Notification.success(data.message);
                                    mod.switchWizardPanel(4);
                                } else {
                                    Notification.error(errMsg);
                                }
                            }, function (error) {
                                Notification.error(errMsg);
                            }
                        );
                    }
                },
             ]
        };
        Modal.advanced(activateModalConf);
    }

    // toggle activated state of the app
    mod.toggleActivated = function() {
        const errMsg = TYPO3.lang.be_js_msg_err_activate;
        const url = mod.dom.dataSrc.dataset.url_toggle_activated;
        var request = new AjaxRequest(url);
        request = request.withQueryArguments({'site_root_page_id': mod.dom.dataSrc.dataset.pageid_siteroot});
        request.get().then(
            async function (response) {
                const data = await response.resolve();
                if (data.status=="success") {
                    mod.activated = true;
                    Notification.success(data.message);
                } else {
                    Notification.error(errMsg);
                }
            }, function (error) {
                Notification.error(errMsg);
            }
        );
    }

    // save the settings after checking about the deletion of existing eids
    mod.confirmSave = function(e) {
        e.preventDefault();
        var modalConf = {
            type: Modal.types.default,
            severity: 2,
            title: TYPO3.lang.be_js_txt_save_title,
            content: TYPO3.lang.be_js_txt_save_content,
            buttons: [
                {
                    text: TYPO3.lang.be_js_txt_cancel,
                    trigger: function() {
                        Modal.dismiss();
                    }
                },
                {
                    text: TYPO3.lang.be_js_txt_save_delete,
                    active: true,
                    btnClass: 'btn-danger',
                    trigger: function() {
                        document.getElementById('eidlogin-settings-form-manual-eid_delete').value=true;
                        mod.saveSettings(e);
                        Modal.dismiss();
                    }
                },
                {
                    text: TYPO3.lang.be_js_txt_save_only,
                    btnClass: 'btn-danger',
                    trigger: function() {
                        document.getElementById('eidlogin-settings-form-manual-eid_delete').value=false;
                        mod.saveSettings(e);
                        Modal.dismiss();
                    }
                }
            ]
        };
        Modal.advanced(modalConf);
    }

    // reset the settings
    mod.reset = function(e) {
        e.preventDefault();
        var modalConf = {
            type: Modal.types.default,
            severity: 2,
            title: TYPO3.lang.be_js_txt_reset_title,
            content: TYPO3.lang.be_js_txt_reset_content,
            buttons: [
                {
                    text: TYPO3.lang.be_js_txt_cancel,
                    trigger: function() {
                        Modal.dismiss();
                    }
                },
                {
                    text: TYPO3.lang.be_js_txt_yes,
                    active: true,
                    btnClass: 'btn-danger',
                    trigger: function() {
                        const errMsg = TYPO3.lang.be_js_msg_err_reset;
                        const url = mod.dom.dataSrc.dataset.url_reset;
                        var request = new AjaxRequest(url);
                        request = request.withQueryArguments({'site_root_page_id': mod.dom.dataSrc.dataset.pageid_siteroot});
                        request.get().then(
                            async function (response) {
                                const data = await response.resolve();
                                if (data.status=="success") {
                                    window.location.reload();
                                    Notification.success(data.message);
                                } else {
                                    Notification.error(errMsg);
                                }
                            }, function (error) {
                                Notification.error(errMsg);
                            }
                        );
                        Modal.dismiss();
                    }
                }
            ]
        };
        Modal.advanced(modalConf);
    }

    // prepare a SAML Certificate Rollover
    mod.prepRollover = function(e) {
        e.preventDefault();
        var content = TYPO3.lang.be_js_txt_preprollover_content_nonew
        if (mod.dom.dataSrc.dataset.new_cert_present==='1') {
            content = TYPO3.lang.be_js_txt_preprollover_content_new
        }
        var modalConf = {
            type: Modal.types.default,
            severity: 2,
            title: TYPO3.lang.be_js_txt_preprollover_title,
            content: content,
            buttons: [
                {
                    text: TYPO3.lang.be_js_txt_cancel,
                    trigger: function() {
                        Modal.dismiss();
                    }
                },
                {
                    text: TYPO3.lang.be_js_txt_yes,
                    active: true,
                    btnClass: 'btn-danger',
                    trigger: function() {
                        const errMsg = TYPO3.lang.be_js_msg_err_preprollover;
                        const url = mod.dom.dataSrc.dataset.url_preprollover;
                        var request = new AjaxRequest(url);
                        request = request.withQueryArguments({'site_root_page_id': mod.dom.dataSrc.dataset.pageid_siteroot});
                        request.get().then(
                            async function (response) {
                                const data = await response.resolve();
                                if (data.status=="success") {
                                    mod.dom.certNewDiv.innerText = '... '+data.cert_new;
                                    mod.dom.certNewEncDiv.innerText = '... '+data.cert_new_enc;
                                    mod.dom.buttonRolloverExec.disabled = false;
                                    mod.dom.spanRolloverExec.classList.add('hidden');
                                    Notification.success(data.message);
                                } else {
                                    Notification.error(errMsg);
                                }
                            }, function (error) {
                                Notification.error(errMsg);
                            }
                        );
                        Modal.dismiss();
                    }
                }
            ]
        };
        Modal.advanced(modalConf);
    }

    // execute a SAML Certificate Rollover
    mod.execRollover = function(e) {
        e.preventDefault();
        var modalConf = {
            type: Modal.types.default,
            severity: 2,
            title: TYPO3.lang.be_js_txt_execrollover_title,
            content: TYPO3.lang.be_js_txt_execrollover_content,
            buttons: [
                {
                    text: TYPO3.lang.be_js_txt_cancel,
                    trigger: function() {
                        Modal.dismiss();
                    }
                },
                {
                    text: TYPO3.lang.be_js_txt_yes,
                    active: true,
                    btnClass: 'btn-danger',
                    trigger: function() {
                        const errMsg = TYPO3.lang.be_js_msg_err_execrollover;
                        const url = mod.dom.dataSrc.dataset.url_execrollover;
                        var request = new AjaxRequest(url);
                        request = request.withQueryArguments({'site_root_page_id': mod.dom.dataSrc.dataset.pageid_siteroot});
                        request.get().then(
                            async function (response) {
                                const data = await response.resolve();
                                if (data.status=="success") {
                                    mod.dom.certActDiv.innerText = '... '+data.cert_act;
                                    mod.dom.certActEncDiv.innerText = '... '+data.cert_act_enc;
                                    mod.dom.certNewDiv.innerText = TYPO3.lang.be_js_txt_nocert;
                                    mod.dom.certNewEncDiv.innerText = TYPO3.lang.be_js_txt_nocert;
                                    mod.dom.buttonRolloverExec.disabled = true;
                                    mod.dom.spanRolloverExec.classList.remove('hidden');
                                    Notification.success(data.message);
                                } else {
                                    Notification.error(errMsg);
                                }
                            }, function (error) {
                                Notification.error(errMsg);
                            }
                        );
                        Modal.dismiss();
                    }
                }
            ]
        };
        Modal.advanced(modalConf);
    }

    // To let the module be a dependency of another module, we return our object
return mod;
});

