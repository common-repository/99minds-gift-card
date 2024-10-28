<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Plugin Name: 99minds Gift Card
 * Plugin URI: https://support.99minds.io/portal/en/home
 * Description: Gift card for your WooCommerce shopping cart.
 * Version: 1.0.2
 * Stable tag: 1.0.2
 * Author: 99minds
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Developer: 99minds Gift Card
 * Author URI: https://99minds.io/
 * WP requires at least: 4.5
 * Tested up to: 6.4.3
 * Languages: English (US)
 */

define('MINDS_VERSION', '1.0.0');
define('MINDS_INSTALL_URL', 'https://api.giftcard.99minds.io/app/callbacks/woocommerce/install/');
define('MINDS_UNINSTALL_URL', 'https://api.giftcard.99minds.io/app/callbacks/woocommerce/uninstall');
define('MINDS_DEACTIVATE_URL', 'https://api.giftcard.99minds.io/app/callbacks/woocommerce/deactivate');
define('MINDS_WIDGET_URL', 'https://assets.99minds.io/live/giftcard/bundle.js');
define('MINDS_SETTINGS_URL', 'https://api.giftcard.99minds.io/app/callbacks/woocommerce/load');
define('MINDS_CHECK_BALANCE_URL', 'https://api.giftcard.99minds.io/api/v1/widget/giftcard/woocommerce/checkBalanceUnsecure');
define( 'MINDS_ADD_GIFT_PRODUCT_URL', 'https://api.giftcard.99minds.io/api/v1/widget/giftcard/woocommerce/addToCart' );

require_once plugin_dir_path( __FILE__ ) . 'view/minds-widget.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-minds-options.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-minds-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/minds-woocommerce-hooks.php';

new minds_settings( plugin_basename( __FILE__ ), plugin_dir_path( __FILE__ ) );

add_action( 'init', 'minds_init' );

register_uninstall_hook( __FILE__, 'minds_uninstall' );
register_activation_hook( __FILE__, 'minds_activated' );
register_deactivation_hook( __FILE__, 'minds_deactivated' );

add_action('admin_init', 'minds_plugin_redirect');


function minds_init() 
{ 
	add_shortcode( 'minds', 'minds_shortcode' );
	$version='4.9.3';
	$vflag = version_compare( WC_VERSION, $version );
    if($vflag === 1){
	    add_filter('woocommerce_cart_totals_fee_html', 'minds_custom_woocommerce_cart_totals_fee_html', 100, 2);
    }
    
}  // end minds_init()


function minds_activated()
{
    if(isset($_SERVER['HTTPS']) === TRUE && $_SERVER['HTTPS'] === 'on'){
        // Check woocommerce plugin status.
        if ( is_plugin_active('woocommerce/woocommerce.php') === TRUE ) {
            update_option("minds_plugin_status", "on");
            update_option("minds_multsite_check", "off");
            update_option("minds_redirect_site", "true");
        }else{
            update_option("minds_plugin_status", "off");
        }
    }
    
} // end minds_activated()


function minds_plugin_redirect() 
{
    if (get_option('minds_plugin_do_activation_redirect', false)) {
        delete_option('minds_plugin_do_activation_redirect');
        minds_options::get_parameters();
    }
    
} // end minds_plugin_redirect()


function minds_deactivated() 
{
	minds_options::deactivation();
	
} // end minds_deactivated()


function minds_uninstall()
{
	remove_shortcode( 'minds' );
	minds_options::disconnect();
	
} // end minds_uninstall()