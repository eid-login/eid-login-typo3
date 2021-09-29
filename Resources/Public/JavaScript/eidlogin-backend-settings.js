var EidloginBackendSettingsMod = '/typo3conf/ext/eidlogin/Resources/Public/JavaScript/EidloginBackendSettingsModule.js';
require([
    EidloginBackendSettingsMod
    ], function () {
    var be = require(EidloginBackendSettingsMod);
    // init our js module
    be.init();
});