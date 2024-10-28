=== 99minds Giftcard ===
Contributors: 99mindskeys
Tags: 99minds, gift, giftcards, gift certificates, gift vouchers
Version: 1.0.0
Stable tag: 1.0.0
Tested up: 6.4.3
WordPress Version: 4.5 and above
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Author URI: https://99minds.io/
Languages: English (US)


== Description ==
99minds Giftcard is the simplest way to sell giftcards on your wordpress store. It’s very simple for you to install, and it’s a seamless experience for your customers.
99minds provides a very simple and automated installation process, which say goodbye to api keys.

This plugin enables you to access your 99minds account in your wordpress admin, if you have WooCommerce plugin active in your setup this plugin will allow to purchase and redeem your gift cards.
This provides you the easy way to communicate with your existing account at 99minds (https://99minds.io/).

Compatible with WooCommerce 3.0 and higher.

Currently we are supporting English (US) language only, let us know if you have requirement. You can contact us here: https://support.99minds.io/portal/en/home

Suitable for single currency store.

= External Service Integration - 99minds =

**Overview**
This WooCommerce plugin is developed by the team at 99minds, enabling merchants to seamlessly integrate gift card purchase and redemption functionalities into their stores. The plugin leverages the capabilities of 99minds, our own gift card processing platform, to provide a robust and user-friendly gift card solution.

**About 99minds**
99minds is a comprehensive gift card processing service designed to facilitate the issuance, management, and redemption of gift cards within WooCommerce stores. As the developers of both the plugin and 99minds, we ensure a seamless integration that enhances the eCommerce experience.

**Service Integration**
The plugin requires an active account with 99minds, which merchants can set up easily. This integration allows for:
1. Adding gift cards to the WooCommerce cart.
2. Real-time balance checks for gift cards.
3. Secure processing of gift card transactions.
The documentation of the APIs is available at [here](https://docs.99minds.io/).

**Data Processing and User Privacy**
As the developers of both the plugin and the 99minds platform, we prioritize user privacy and data security:
- No personal user data is collected or processed without explicit consent.
- The plugin's communication with 99minds is strictly confined to gift card transaction processing.
- We adhere to the highest standards of data protection and privacy laws.

**Transparency and Compliance**
We are committed to transparency and compliance with all relevant regulations:
- All API calls and data processing activities are detailed and serve only the specified functionalities of the plugin.
- The plugin does not include any user tracking or data collection features that operate without user consent.

**Getting Started**
To use this plugin, merchants need to register for a 99minds account. This account facilitates all gift card transactions and ensures secure and efficient processing.

When a merchant selects the 'Activate plugin' option, they are automatically redirected to the 99minds portal. Here, they have the choice to either sign up for a new account or log in to an existing one.

For detailed information on setting up and managing your 99minds account, and to understand our privacy practices, please visit [99minds](https://www.99minds.io/).

== Installation ==
1. Install 99minds plugin from WordPress plugin store. Otherwise, you can take zip file of plugin and upload it to WordPress setup with add new plugin section, this will need FTP details for secured server.
2. Activate plugin which will redirect you to 99minds registration page.
3. After clicking on create account or sign in your store will go through automatic integration with 99minds which will allow your store to sell and redeem giftcards.
4. Copy our shortcode [minds] and add it to any page you wish to show get giftcard section.
[minds] shortcode takes below attributes

- `redirect_url` - Redirect URL after add to cart. Default - Cart page URL.
- `onaddtocart` - Function to call after add to cart. Default - None. Eg. giftcard_added_to_cart
- `currency` - Currency of shortcode. Default - Store Currency.
- `show_currency_picker` - Show currency picked. Default - false
- `single_page` - Show only single page. Default - false
- `default_page` - One of PURCHASE, CHECK_BALANCE. Default - PURCHASE.
- `oninit` - Callback when the widget is initialized and finished loading. Default - None.
- `oncurrencychange` - Callback when currency is changed in widget. Default - None
- `getcartdetails` - Should return a simple object containing current cart_id, i.e { cart_id: 'xxxxx' } Default - None
- `getcurrentcustomer` - Should return a simple object containing current logged in customer data, i.e { id: 'xxxxx', email: 'jhon.doe@example.com', name: 'Jhon Doe' }. id is required.
- `client_id` - Client id of store to attach. Default - Current store client id.

Enjoy gift card sales!

== Screenshots ==

1. dashboard
2. Purchase gift card
3. Check balance of gift card
4. Redeem gitcard

== Changelog ==

= 1.0.0 =
Sync with 99minds account

== Upgrade Notice ==
Please update to latest version to get bug free expirience
