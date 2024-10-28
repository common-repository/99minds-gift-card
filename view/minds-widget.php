<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


function minds_shortcode($atts = [])
{
    global $woocommrce;
    global $wpdb;
    $productId = get_option( 'minds_product_id', 0 );
    
    $productType = "product";
    $postType = "publish";
    $product = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} where `id`=%s and `post_type`=%s and `post_status`=%s", $productId, $productType, $postType));
    $tableName = $wpdb->prefix."wc_webhooks";
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$tableName`"));
    $flag=0;
    foreach($results as $result){
        if(($result->name == '99minds Order Updated') || ($result->name == '99minds Customer Updated') || ($result->name == '99minds Customer Created')){
            $flag=$flag+1;
        }
    }
    $postType = "shop_webhook";
    $webhooks = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE post_type =%s", $postType));
    if($webhooks){
        $flag=0;
        foreach($webhooks as $webhook){
            if(($webhook->post_title == '99minds Order Updated') || ($webhook->post_title == '99minds Customer Updated') || ($webhook->post_title == '99minds Customer Created')){
                $flag=$flag+1;
            }
        }
    }
    $checkplugin = "on";
    $option = get_option('minds_access');
    if((empty($option)) === FALSE && ($product > 0) && ($flag === 3)){
        $checkplugin="off";
    }
    
    $temp_currency = get_woocommerce_currency();
    $woo_currency = get_option('woocommerce_currency');
    if($woo_currency === $temp_currency){
        if($checkplugin === "off"){
            ob_start();
            $cart_page_url = get_permalink( wc_get_page_id( 'cart' ) );
            if(is_array($atts) === FALSE){ $atts = array(); }
            $function_name = "";
            if(empty($atts['onaddtocart']) === FALSE){
                $function_name = $atts['onaddtocart'];
            }
            $redirct_url = "";
            if(empty($atts['redirect_url']) === FALSE){
                $redirct_url = $atts['redirect_url'];
            }
            $currency = "";
            if(empty($atts['currency']) === FALSE){
                $currency = $atts['currency'];
            }
            $show_currency_picker = "";
            if(empty($atts['show_currency_picker']) === FALSE){
                $show_currency_picker = $atts['show_currency_picker'];
            }
            $single_page = "";
            if(empty($atts['single_page']) === FALSE){
                $single_page = $atts['single_page'];
            }
            $default_page = "";
            if(empty($atts['default_page']) === FALSE){
                $default_page = $atts['default_page'];
            }
            $onInit = "";
            if(empty($atts['oninit']) === FALSE){
                $onInit = $atts['oninit'];
            }
            $onCurrencyChange = "";
            if(empty($atts['oncurrencychange']) === FALSE){
                $onCurrencyChange = $atts['oncurrencychange'];
            }
            $getCartDetails = "";
            if(empty($atts['getcartdetails']) === FALSE){
                $getCartDetails = $atts['getcartdetails'];
            }
            $getCurrentCustomer = "";
            if(empty($atts['getcurrentcustomer']) === FALSE){
                $getCurrentCustomer = $atts['getcurrentcustomer'];
            }
            if(empty($atts['client_id']) === TRUE){
                $access_json = get_option('minds_access');
                if(!empty($access_json)){
                    $access_obj = json_decode($access_json);
                    $atts['client_id'] = $access_obj->access_token;
                }
                if(empty($atts['client_id']) === TRUE){
                ?>
                <p>You have not registered with 99minds yet.</p>
                <?php
                }else{
                    if($function_name !== ""){
                        $function_to_add = $function_name . "(";?> payload <?php echo ");";
                    }
                    echo wp_get_inline_script_tag('
                    var xhttp = new XMLHttpRequest();
                    var redirect_url = "'.esc_url($redirct_url).'";
                    var function_name = "'.esc_attr($function_name).'";
                    var minds_widget_callback = function(payload){
                        var all_resolve = new Promise(function(resolve, reject){
                            try {
                                if ( payload !="" ) {
                                    xhttp.open("POST", "' . esc_url(site_url()) . '/wp-json/99minds/v1/gift-product-add/", true);
                                    xhttp.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
                                    xhttp.responseType = \'json\';
                                    xhttp.send(JSON.stringify(payload));
                                    xhttp.onreadystatechange = function() {
                                        if(this.readyState == 4){
                                            if (this.status == 200) {
                                                var res_obj = JSON.parse(this.response);
                                                if(res_obj.status==true){
                                                    if(window.function_name != ""){
                                                        ' . $function_to_add . '
                                                    }else if(redirect_url != ""){
                                                        window.location = redirect_url;
                                                    }else{
                                                        window.location = "' . esc_html($cart_page_url) . '";
                                                    }
                                                    resolve({ status: true, message: \'Proceeding to cart\' });
                                                }else{
                                                    resolve({ status: false, message: "Something went wrong." });
                                                }
                                            }else{
                                                resolve({ status: false, message: \'Endpoint is not working.\' });
                                            }
                                        }
                                    };
                                } else {
                                    resolve({ status: false, message: \'Some error message to widget so that we can show it to customer\' });
                                }
                            } catch(err) {
                                reject(Error("It broke"));
                            }
                        });
                        return all_resolve;
                    }
                    ')
                ?>

                <div id="giftcard-container"></div>
                <?php echo wp_get_script_tag(array("src" => esc_html(MINDS_WIDGET_URL))); ?>
                <?php
                    $currencyjs = ($currency != "") ?  "currency:" . "'" . esc_html($currency) . "'" . "," : "";
                    
                    $show_currency_pickerjs = ($show_currency_picker != "") ? "show_currency_picker:" . esc_html($show_currency_picker) . "," : "";
                    
                    $single_pagejs = ($single_page != "") ? "single_page:" . esc_html($single_page) . "," : "";
                    
                    $default_pagejs = ($default_page != "")? "default_page:" . esc_attr($default_page) . "," : "";
                    
                    $onInitjs = ($onInit != "") ? "onInit:" . esc_attr($onInit) . "," : "";
                    
                    $onCurrencyChangejs = ($onCurrencyChange != "") ? "onCurrencyChange:" . esc_attr($onCurrencyChange) . "," : "";
                    
                    $getCartDetailsjs = ($getCartDetails != "") ? "getCartDetails:" . esc_attr($getCartDetails) . "," : "";
                    
                    $getCurrentCustomerjs = ($getCurrentCustomer != "") ? "getCurrentCustomer:" . esc_attr($getCurrentCustomer) : "";
                    
                    $client_idjs = "client_id:'" . esc_attr($atts["client_id"]) . "',";
                    echo wp_get_inline_script_tag('
                    GiftcardWidget.init({container: "#giftcard-container",
                    ' . $client_idjs . '
                    platform: "WOOCOMMERCE",
                    onAddToCart: minds_widget_callback,
                    checkout_mode: "DROPIN",
                    '.
                    $currencyjs . $show_currency_pickerjs . $single_pagejs . $default_pagejs . $onInitjs . $onCurrencyChangejs . $getCartDetailsjs . $getCurrentCustomerjs
                    .'
                })');
                }
            }else{
            ?>
                <p style="color:red;">Plugin is not activated properly.</p>
            <?php
            }
        }else{
            ?>
                <p style="color:red;">Cannot purchase Giftcard</p>
            <?php
            return ob_get_clean();
        } //end if
    } //end if
} // end minds_shortcode()