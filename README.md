# eID-Login extension for TYPO3
This is the eID-Login extension for the [TYPO3](https://typo3.org) platform.
It has been developed by [ecsec](https://ecsec.de) on behalf of the [German Federal Office for Information Security](https://www.bsi.bund.de/).

The eID-Login extension allows to use the German eID-card and similar electronic identity documents for secure and privacy-friendly login as Website-User into TYPO3 sites. For this purpose, a so-called eID-Client, such as the AusweisApp2 or the Open eCard App and eID-Service are required. In the default configuration a suitable eID-Service is provided without any additional costs.

# Installation
The installation of the extension can be done in usual ways for a TYPO3 extension.
It supports TYPO3 instances which are installed the classic or the composer based way.

If your TYPO3 instance is installed the classic way, please add the extension in the extension manager, either through searching the extension at  `Get Extensions` in the extension manager or via manual upload.

If your TYPO3 instance installation is composer based, you can add the extension by running:
```shell
composer require eidlogin/eid-login-typo3
```
PLEASE NOTE: If the extension has been installed via composer, you might need to deactivate and re-activate it in the extension manager, to make the console command available for the scheduler!
# Requirements
The eID-Login extension has some requirements to the TYPO3 instance and the configured sites.
* The extension depends on the presence of the TYPO3 scheduler. In composer based TYPO3 instances you might need to install it [separately](https://packagist.org/packages/typo3/cms-scheduler).
* The mail configuration of the TYPO3 instance must be valid, including a default 'from' address.
* All sites using the eID-Login must use HTTP over TLS as protocol.
* The 'Site Configuration' settings of sites using the eID-Login must have full qualified entry points, i.e not using `/` but `https://domain.tld/`.

# Setup and Usage
## Schedule commands of the eID-Login extension
The extension comes with two maintenance commands:
* `eidlogin:cleandb` - Delete orphaned / old database entries
* `eidlogin:certificate` - Check certificate validity. Does an automated rollover before certificates expire and inform admins about it via email.

The commands must be scheduled as tasks in the scheduler extension:
* Open the scheduler module in the backend and select 'scheduled tasks'
* Use the `+` icon to add a task
* Select `Execute console commands` in the `Class` dropdown
* Add a `Frequency` Value:
    * once every five minutes for `eidlogin:cleandb`
    * once a day for `eidlogin:certificate`
* Select the command (`eidlogin:cleandb`, `eidlogin:certificate`) in the `Schedulable Command` dropdown

## Usage of eID-Login in a site
The eID-Login extension can be configured and used in the sites of a TYPO3 instance separately.
Opening the backend module of the extension will show a matrix of the sites in the TYPO3 instance and its setup and
configuration state regarding the eID-Login.
### Use the 'eID-Login' SAML-Template on a page for technical URLs
To provide URLs needed for the eID-Login, a site most hold a  free accessible page without content, which should not be visible in the menus. This page needs a separate template record with the following configuration:
* Make the template a `Rootlevel` template under `Options`
* Add `eID-Login SAML Template` as static include under `Includes` in the template

### Use the `eID-Login Link` Frontend-Plugin
There must be at least one free accessible page holding the Frontend-Plugin `eID-Login Link` as content element.
This plugin will render a logo, a link to start an eID-Login and a link to the FAQ regarding eID-Login, or a logout link if the Website-User is currently logged in.
Also messages which may occur when doing an eID-Login are rendered by the plugin as TYPO3 Flash-Messages.
Website-Users which should be able to use the plugin must be configured using the `Record Storage Page` option.
The value of this Option must be the same for every eID-Login Frontend-Plugin of a site!
### Use the `eID-Login Settings` Frontend-Plugin
At least one access restricted page holding the Frontend-Plugin `eID-Login Settings` to render the links which enable the creation and deletion of of eID connection for Website-Users.
Messages which occur in the process are rendered there as TYPO3 Flash-Messages.
The pugin also provides a checkbox to disable the password based login for the specific Website-User.
It will be rendered, if an eID connection exists.
Website-Users which may use this plugin must be configured using the `Record Storage Page` option.

**ATTENTION: The extension is listening to the `TYPO3\CMS\FrontendLogin\Event\LoginConfirmedEvent` event to check if the password based login of a Website-User should be prevented.
For this to be fired do not activate the `Disable redirect after successful login, but display logout-form` option of the Login Form!**

### Use the wizard to configure eID-Login for a site
When a site meets all criteria as stated above, it may be configured by using a simple wizard.
Follow the instructions given in the wizard.
Information for the technical background is provided via an Info panel if needed.

### Setting the `Record Storage Page` option right
**ATTENTION: The `Record Storage Page` option for all eID-Login Frontend-Plugins of a specific site must be set to the same value!**
The value is used to connect the Website-Users eID-Connections to to a site.
This means if `Record Storage Page` option values are used for eID-Login Frontend-Plugins of more than one specific site, the resulting Website-Users records and their eID-Connections are used for more than one specific site also.
In case of deletion of eID-Connection when reconfiguring or resetting the eID-Login settings of site `A`, this may lead to Website-Users loosing the eID-Login based access to site `B` too, if the values of `Record Storge Page` intersect.

# Styling of the Frontend Plugins
In the frontend Plugins 'eID-Login Link' and 'eID-Login Settings' all relevant HTML tags carry `id` attributes for easy styling.
Please inspect the souce code of the rendered pages to learn about the used values.

If you want to set the value of the `class` attribute for a specific html tag, this can be done be using the TypoScript paths below, which are evaluated in the templates of the extension:

```typoscript
plugin.tx_eidlogin.settings.classesLoginUnauthenticated=
plugin.tx_eidlogin.settings.classesLoginAuthenticated=
plugin.tx_eidlogin.settings.classesLoginLogo=
plugin.tx_eidlogin.settings.classesLoginLogin=
plugin.tx_eidlogin.settings.classesLoginFaq=
plugin.tx_eidlogin.settings.classesLoginLogout=
plugin.tx_eidlogin.settings.classesSettings=
plugin.tx_eidlogin.settings.classesSettingsTitle=
plugin.tx_eidlogin.settings.classesSettingsHint=
plugin.tx_eidlogin.settings.classesSettingsLink=
plugin.tx_eidlogin.settings.classesSettingsDisablePwLogin=
plugin.tx_eidlogin.settings.classesSettingsDisablePwLoginInput=
plugin.tx_eidlogin.settings.classesSettingsDisablePwLoginLabel=
```
# Manual Only Configuration Options
## Skip XML Validation
If you want the extension to skip XML Validation of SAML Responses for a specific site, set the following in your `Localconfiguration.php` file
```php
['EXTENSIONS']['eidlogin'][ROOT_PAGEID_OF_THE_SITE]['skipxmlvalidation'] => true
```

# Uninstallation
If you want to uninstall the eID-Login extension please follow the steps below:
* Delete the scheduler tasks for running the `eidlogin:dbclean` and `eidlogin:certificate` commands
* Delete the page and template record forming the page used to provide the technical URLs of the extension
* Delete all instances of the Frontend-Plugins `eID-Login Link` and `eID-Login Settings`
* Deactivate the extension in the extension manager
* Delete/Remove the extension (using the extension manager or composer, depending on how the extension has been installed)
* Delete the extension specific configuration entry in the file `Localconfiguration.php`
* Run the `Database Analyzer` to clean the tables `fe_users` and `be_users`
* Remove the database tables with the prefix `tx_eidlogin_` manually
