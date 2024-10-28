<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<style>
    #wpcontent{
        padding-left: 0px;
        height: inherit;
    }
    #wpbody{
        height: inherit;
    }
    
    .minds-container{
        padding: 20px 40px;
    }
    
    #redirect-data-form, .width-50{
        text-align: left;
        width: 50%;
    }
    
    #redirect-data-form label span{
        cursor: default;
    }
    
    .minds-logo{
        height: 30px;
    }
</style>

<?php
$nonce = "";
if(empty($_REQUEST["_wpnonce"]) === FALSE){
    $nonce = sanitize_text_field($_REQUEST["_wpnonce"]);
}

if ( wp_verify_nonce( $nonce, "minds-nonce" ) === FALSE && isset($_POST["minds_redirect_true"]) === TRUE && (sanitize_text_field($_POST["minds_redirect_true"]) !== "") ) {
    die( "Security check" ); 
} else {
    if(isset($_POST["minds_redirect_true"]) && (sanitize_text_field($_POST["minds_redirect_true"]) != "")){
        $domain = get_site_url();
        $storeUrl = get_option('siteurl');
        $currentUser = wp_get_current_user();
        $val2 = get_user_meta($currentUser->ID);
        $code = WC()->countries->get_base_country();
        $cname = WC()->countries->countries[$code];
        $installerUserName = $currentUser->user_login;
        $installerUserEmail = $currentUser->user_email;
        $currencyCode = get_woocommerce_currency();
        $storeName = get_option('blogname');
        $v='3.5';
        if ( version_compare( WC_VERSION, $v, ">=" ) ) {
            $version = "v3";
        }else{
            $version = "v2";
        }
        $var = "domain=$domain&store_url=$storeUrl&installer_user_name=$installerUserName&installer_user_email=$installerUserEmail&currency_code=$currencyCode&country_name=$code&store_name=$storeName&api_version=$version";
        
        $var = base64_encode($var);
        
        update_option( "minds_redirect_site", "false" );
        
        wp_redirect(MINDS_INSTALL_URL . "?data=$var");
    } // end if
}

$redirect = get_option('minds_redirect_site');
$nonce = wp_create_nonce("minds-nonce");
if($redirect == "true"){
    if(get_option('minds_plugin_status') == 'on'){
    ?>
    <div class="minds-container">
        <div class="width-50">
            <img src="<?php echo plugin_dir_url(dirname( __FILE__ )) . 'assets/images/99minds-logo.png'; ?>" class="minds-logo">
            <p>99minds platform enables you to create coupons, gift cards, discounts, send out referrals, build loyalty programs, and location-based promotions for your customers.</p>
        </div>
        <form id="redirect-data-form" method="POST" action="admin.php?page=99minds-board&_wpnonce=<?php echo $nonce; ?>">
            <p>
                <label>
                    <input type="checkbox" id="minds_agree_terms" name="minds_agree_terms" value="yes" required>
                    <span>I agree to the <a href="https://app.termly.io/document/terms-of-use-for-saas/30ada467-9d58-4b21-b3ed-e7d79c015bc4" target="_blank">Terms and Conditions</a></span>
                </label>
            </p>
            <p>
                <label>
                    <input type="checkbox" id="minds_agree_privacy" name="minds_agree_privacy" value="yes" required>
                    <span>I agree to the <a href="https://app.termly.io/document/privacy-policy/e1054a68-e1ab-43c0-92ad-e4b68339718d" target="_blank">Privacy Policy</a></span>
                </label>
            </p>
            <input type="hidden" name="minds_redirect_true" value="true">
            
            <button type="submit" class="button">Redirect to 99minds</button>
            <p><small>This will connect 99minds with the plugin.</small></p>
        </form>
    </div>
    
    
    <?php
    }
}else{
    remove_all_actions("all_admin_notices");
    $storeUrl = get_option('siteurl');
    $currentUser = wp_get_current_user();
    $installerUserEmail = $currentUser->user_email;
    $string = "domain=" . $storeUrl . "&email=" . $installerUserEmail;
    $access = get_option('minds_access');
    if($access){
        $access = json_decode($access);
        $secret = $access->key_secret;
        $sig = hash_hmac('sha256', $string , $secret );
        $string = MINDS_SETTINGS_URL . "?domain=" . $storeUrl . "&email=" . $installerUserEmail . "&hmac=" . $sig;
        ?>
        <div class="minds-container">
            <img src="<?php echo plugin_dir_url(dirname( __FILE__ )) . 'assets/images/99minds-logo.png'; ?>" class="minds-logo">
            <p>Access your 99minds admin panel by clicking <a href="<?php echo esc_url($string); ?>" target="_blank" >here.</a></p>
        </div>
        <?php
    }else{
        ?>
        <p style="color:red;">Plugin is not activated properly</p>
        <?php
    }
}



?>