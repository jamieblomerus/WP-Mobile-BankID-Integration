=== Unofficial Mobile BankID Integration ===
Contributors: jamieblomerus
Tags: mobile bankid, bankid, authentication
Requires at least: 5.2
Tested up to: 6.7
Stable tag: 1.4
Requires PHP: 7.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Let your users use Mobile BankID to authenticate themself.

== Description ==

This is a plugin that allows you to integrate Mobile BankID with your WordPress site and use it for the following:

- Authenticate users (as an alternative to username and password)
- Perform age checks of customers (Woocommerce)
- Tailor it to your needs with extensions (Very developer friendly)
And more.

**Docs and support**

I am currently working on creating documentation for the plugin. Right now I'll just ask you to be patient.
You can use the WordPress support forum for help with installing and configuring the plugin.

**Legal notice**

This plugin is not affiliated with Finansiell ID-Teknik BID AB or any bank. Your use of the BankID Service is governed by your agreement with your bank or any other party that provides you with the BankID Service.

== Frequently Asked Questions ==

= Can I help translate it to my language? =

Contribute to the translation of this plugin at [Translating WordPress](https://translate.wordpress.org/projects/wp-plugins/mobile-bankid-integration/).

= I do miss one feature =

If you would like to suggest any feature to be added, please write an email to our project manager [jamie.blomerus@protonmail.com](mailto:jamie.blomerus@protonmail.com).

= How do I buy the BankID service? =
To buy the BankID service and receive a RP certificate, you need to contact your bank. For more information, please visit this [guide](https://www.bankid.com/en/foretag/anslut-foeretag).

= I want to test the plugin, but I don't have a Mobile BankID or RP certificate =

If you are only testing the service out, you can during the setup choose to run the plugin against the testing environment and use a [test BankID](https://www.bankid.com/en/utvecklare/test/skaffa-testbankid/test-bankid-get).

== Changelog ==

= 1.4 =
* Recreated the login screen to be more user-friendly, modern and accessible.
* Fixed a contrast issue within the setup wizard.
* Resolved a SSL verification issue which caused the plugin to not work with the BankID test environment.

= 1.3 =
* Added credits tab to admin page
* Added more actions and filters to improve extensibility
* Added privacy features
* Fixed bug which made it impossible to update personal identity number in user settings
* Added official support for the Windows platform
* Resolved authentication button bug
* Updated "Personal number" to "Personal identity number" in the user settings

= 1.2 =
* Added support for API version 6.0 while removing support for API version 5.1.
* Fixed some errors being thrown on activation and deactivation of the plugin.

= 1.1.1 =
* Changed the license of the plugin to GPLv3 or later.
* Fixed a minor problem with translations.

= 1.1 =
* Replaced the use of $_SESSION with the use of a custom system to avoid bugs.
* Minor improvements to code quality.

= 1.0.2 =
* Improved code quality.
* Fixed a minor UI bug on the settings page.

= 1.0.1 =
* Security fix: Fixed a security issue where the plugin would deserialize data from the database without checking if it was safe to do so.
* Added tab "Contribute" to the plugin settings page. This tab contains information about how you can contribute or support the plugin.
* Changed some code to be more in line with WordPress coding standards.
* Improved developer documentation.

= 1.0.0 =
* I ensured the plugin is stable for production use.

== Upgrade Notice ==

= 1.4 =
A major update that changes the login screen and fixes some bugs.

= 1.3 =
Fixes some bugs and adds some minor new features.

= 1.2 =
Major update. Requires total reinstallation of the plugin.

= 1.1.1 =
This update changes the license of the plugin to GPLv3 or later. And fixes a minor problem with translations.

= 1.1 =
This update improves the plugin's compatibility with some hosting providers and plugins.

= 1.0.2 =
A minor update that improves code quality and fixes a minor UI bug.

= 1.0.1 =
This is a security update. Please update as soon as possible.

= 1.0.0 =
This is the first stable version.
