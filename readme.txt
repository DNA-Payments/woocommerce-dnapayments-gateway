=== WooCommerce DNA Payments Gateway ===
Contributors: dnapayments
Tags: payment gateway, credit card, woocommerce, dna payments, apple pay, google pay
Requires at least: 4.2
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 4.1.0
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

= 4.1.0 - 2025-10-06 =
* Added PayPal button payment integration provided by DNA Payments
* Enhanced payment processing capabilities with PayPal integration
* Added seamless checkout experience for PayPal users

= 4.0.9 - 2025-10-28 =
* Added a new "Place order button text" setting in the payment gateway configuration that allows customization of the order button text for both classic and block-based checkout experiences.
* Implemented global script registration for DNA Payments to ensure better compatibility with WooCommerce block-based checkout systems.
* Refactored Google Pay and Apple Pay components to use the init method instead of the create method for improved initialization and performance.
* Order card schemes in WooCommerce Checkout according to terminal configuration
* Consolidated transaction type retrieval logic into config helper
* Move all gateway constants from individual files to a shared common/constants.js module to improve maintainability and reduce duplication
* Update the update_order_status AJAX action hook to include the gateway ID prefix, so it doesn't conflict with other AJAX actions using the same name.
* Pass gatewayId through payment flow to ensure correct payment method tracking
* Modify order helper to update payment method based on gatewayId before status changes
* Remove the strict order payment method check.

= 4.0.8 - 2025-08-15 =
* Added non-Latin1 character removal functionality for order item names to ensure clean order line item names

= 4.0.7 - 2025-08-13 =
* Added a fallback to terminal configuration when the gateway transaction type is neither “SALE” nor “AUTH” during order status updates triggered via AJAX.
* Add method names to error log messages to provide better context when exceptions occur
* Add stack trace logging for auth data exceptions
* Refactor ConfigHelper to cache terminal config for one day, replacing direct terminal_config access with get_terminal_config() to reduce API calls.
* Fixed custom field validation issue for Apple Pay and Google Pay on the checkout page.
* Added setting to toggle AJAX order status update after frontend payment confirmation. Use only if webhooks are unavailable, as it may cause duplicate completion events.
* Fixed issue where cards were not saved after successful payment using Block-Based Checkout.
* Added validation error handling for card payments in Block-Based Checkout.

= 4.0.6 - 2025-07-18 =
* Implemented Apple Pay button visibility only for supported browsers
* Improved error logging in AJAX and auth helper classes
* Added token caching in AuthDataHelper
* Updated terminal config endpoint path to fetch without token
* Added error message display if token fetch fails on checkout page
* Fixed order line items' price and total calculation to ensure compatibility with PayPal payment processing
* Hide saved cards on DNA Payments page if "Enable payment via saved cards" is unchecked
* Added functionality to hide this payment method at checkout for customers, while keeping it visible to site admins and DNA Payments users for debugging purposes.
* Enable Apple Pay support in third-party browsers by loading Apple Pay JS SDK

= 4.0.5 - 2025-07-07 =
* Fixed a minor payment processing issue on the "Pay for Order" page
* Fixed an issue with saving cards for later use when the payment is processed on the "Pay for Order" page
* Fixed validation error messages for custom required fields on the checkout page
* Handled 'null' string values in the total amount calculation when fetching payment data
* Improved get_posted_value helper to preserve original data types
* Added persistent loading state after successful payment until redirect
* Improved error handling in the payment flow
* Replaced PNG card scheme logos with SVGs for better quality
* Added support for dynamic card scheme logos
* Fixed broken image display when entering an unsupported card scheme in Hosted Fields
* Introduced ConfigHelper for managing terminal configuration and retrieving available card schemes
* Prevented blank page opening when the Terms & Conditions checkbox is unchecked during checkout with Google Pay on mobile devices
* Fixed updating the order's payment method when payment is processed via the "Pay for Order" page
* Add !important to icon height and margin rules to ensure consistent styling by enforcing these properties to override any potential conflicting styles

= 4.0.4 - 2025-06-28 =
* Added abstract gateway class for DNA Payments
* Extracted common gateway logic to abstract class
* Fixed error handling in auth data helper
* Restrict checkout validation to Google/Apple Pay buttons only
* Refactored to use WC()->version instead of WC_VERSION constant
* Cleaned up unused global variables and imports
* Added .vscode to gitignore

= 4.0.3 - 2025-06-25 =
* Add transaction lock mechanism to prevent concurrent order status updates
* Track order update sources in logs and notes for better debugging
* Improve logging for webhook and AJAX order processing
* Add WooCommerce platform info to analytics data

= 4.0.2 - 2025-06-23 =
* Fixed JSON parsing issue in order status updates by removing stripslashes call
* Resolve translation loading timing issue for WordPress 6.7.0 compatibility

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