=== hsm-stripe ===
Contributors: hsmobility
Tags: stripe, woocommerce, payments, configurator, tax
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
WC requires at least: 7.1.0
WC tested up to: 8.5.0
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

hsm-stripe connects the HSMobility product configurator to WooCommerce and Stripe without complex setup. The plugin follows KISS and DRY principles so that the frontend payment store can reuse familiar structures while the CMS handles tax, payment intent creation, and WooCommerce order syncing from one file.

= Features =
* REST API endpoints for tax calculation, Stripe payment intents, and WooCommerce order creation
* Server-side tax logic that reuses WooCommerce tax tables with caching and a 13% fallback
* Stripe PaymentIntent creation with customer metadata mapped from the configurator
* Order builder that persists cart selections, pricing, and metadata inside WooCommerce
* Minimal admin settings page for securely storing the Stripe secret key and reviewing endpoints

== Installation ==
1. Upload the `cms-plugin-simplified` folder to the `/wp-content/plugins/` directory or install the ZIP through the WordPress Plugins screen.
2. Activate "HSM" through the "Plugins" menu in WordPress.
3. Navigate to `Settings → HSM` and add your Stripe secret key (starts with `sk_`).
4. Ensure WooCommerce is active and configured with the tax rates you want to expose to the configurator.
5. Update the frontend payment store to call the new REST endpoints under `/wp-json/hsm/v1`.

== Frequently Asked Questions ==

= Does this plugin replace the existing WooCommerce checkout? =
No. It creates WooCommerce orders from the configurator flow and leaves the native checkout untouched.

= Do I need to run custom migrations? =
Activation creates lightweight options and an optional log table (`wp_hsm_stripe_logs`). No additional migrations are required.

= Can I use it without WooCommerce taxes configured? =
Yes. If no matching tax rate is found, the plugin falls back to a 13% tax calculation so the configurator can keep working.

== Screenshots ==
1. HSM settings page showing the Stripe secret key field and REST endpoint summary.

== Changelog ==

= 1.0.0 =
* Initial release with tax calculation, Stripe PaymentIntent, and WooCommerce order endpoints.

== Upgrade Notice ==

= 1.0.0 =
First public release. Install to connect the configurator payment flow to WordPress.
