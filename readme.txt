=== WooCommerce DNA Payments Gateway ===
Contributors: dnapayments
Tags: payment gateway, credit card, woocommerce, dna payments, apple pay, google pay
Requires at least: 4.2
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 4.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 4.8
WC tested up to: 9.4

Take credit card payments on your store with DNA Payments gateway. Supports Apple Pay and Google Pay.

== Description ==

The WooCommerce DNA Payments Gateway plugin integrates the DNA Payments gateway into your WooCommerce store, supporting both block-based and shortcode-based checkout pages.

= Features =

* Accept credit card payments directly on your store
* Support for Apple Pay
* Support for Google Pay
* Compatible with both block-based and classic checkout pages
* Secure payment processing
* Easy configuration

= Requirements =

* WordPress 4.2 or higher
* WooCommerce 4.8 or higher
* PHP 7.4 or higher
* A DNA Payments merchant account

== Installation ==

1. Download the ZIP file from GitHub or the WordPress plugin repository.
2. In your WordPress admin panel, go to **Plugins > Add New > Upload Plugin**.
3. Upload the downloaded ZIP file and click **Install Now**.
4. Activate the plugin once installation is complete.

== Configuration ==

After installing and activating the plugin:

1. Go to **WooCommerce > Settings > Payments**.
2. Enable the **DNA Payments Gateway**.
3. Click **Manage** to configure the gateway settings.
4. Fill in the required fields:
   * **Client ID**
   * **Client Secret**
   * **Terminal ID**

These credentials will be provided to you during the onboarding process with DNA Payments.

== Frequently Asked Questions ==

= Does this plugin support Apple Pay and Google Pay? =

Yes, the plugin supports both Apple Pay and Google Pay payment methods.

= Is this plugin compatible with the WooCommerce Blocks checkout? =

Yes, this plugin supports both the new block-based checkout and the classic shortcode-based checkout.

= Where can I get support? =

For support, please contact DNA Payments directly through their website at https://www.dnapayments.com.

== Screenshots ==

1. Payment gateway settings page
2. Checkout page with DNA Payments option
3. Apple Pay and Google Pay buttons

== Changelog ==

= 4.0.1 - 2025-06-18 =
* Enhanced webhook security by implementing proper permission validation
* Improved error handling and logging for webhook requests
* Fixed double order completion when the order contains only virtual products
* Added readme.txt and changelog.txt files
* Updated all NPM package dependencies
* Escaped all HTML outputs using esc_html(), esc_attr(), and wp_kses_post() where applicable
* Sanitized $_POST, $_GET, and $_SERVER inputs to address PHPCS and Semgrep audit warnings
* Added missing escaping for image src attributes and dynamic content outputs
* Security: Updated @babel/runtime to version 7.27.6 to address security vulnerabilities
* Security: Added webpack-dev-server version 5.2.2 to address CVE-2025-9jgg-88mc-972h and CVE-2025-4v9v-hfq4-rm2v
* Completed all preparations required for publishing the plugin to the WordPress marketplace

= 4.0.0 - 2025-05-27 =
* Fully refactored the codebase for better structure and maintainability
* Improved rendering of Apple Pay and Google Pay buttons
* Enabled order status updates without relying on webhooks
* Improved refund processing for Apple Pay and Google Pay payments
* Hide saved card payment options when the integration type is not "Hosted Fields"
* Added README.md with installation and local development instructions
* Fixed bug where "Pay with DNA Payments" button remains disabled after switching from Google Pay to another payment method
* Fixed issue where the order address is empty due to delayed frontend address data not being attached to the order in time
* Added Analytics Helper for telemetry
* Do not send order lines when both PayPal and Klarna are disabled
* Hide warning messages related to failed block support script registration on the login page
* Replaced the integration type value "hosted-fields" with "seamless"
* Added the "empty" class to card input fields when they are cleared
* Fixed the method name used when calling the logger class
* Handled exception when fetching terminal configuration
* Fixed validation for "Create an account" option on the checkout page

== Upgrade Notice ==

= 4.0.0 =
Major update with improved structure, better Apple Pay and Google Pay support, and numerous bug fixes. This version includes significant improvements to payment processing and checkout experience.

== Privacy Policy ==

This plugin integrates with DNA Payments for payment processing. Please see the [DNA Payments Privacy Policy](https://www.dnapayments.com/page/privacy-policy) for details on how your payment data is handled.