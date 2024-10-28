<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Least version to edit fee html.
$version='4.9.3';

// Endpoints.
add_action('rest_api_init','minds_redirect_post_data');

// Plugin Init.
add_action("init", "minds_init_session");

add_action("woocommerce_checkout_before_customer_details", "minds_add_custom_error_to_checkout");

/***** HOOKS for Purchase Flow *****/
// Redirect function.
add_filter('template_redirect', 'minds_template_redirect', 1, 1);

// Update price of giftcard product.
add_action( 'woocommerce_before_calculate_totals', 'minds_custom_cart_item_price', 30, 1 );

//Adding custom parameters to cart, checkout, order.
add_filter('woocommerce_checkout_cart_item_quantity','minds_add_user_custom_option_from_session_into_cart',1,3);  
add_filter('woocommerce_cart_item_price','minds_add_user_custom_option_from_session_into_cart',1,3);

add_action('woocommerce_new_order_item_meta','minds_add_values_to_order_item_meta',10,2);

add_action('woocommerce_before_cart_item_quantity_zero','minds_remove_user_custom_data_options_from_cart',1,1);

// Custom message for adding giftcard product to cart.
add_action( 'woocommerce_before_cart' , 'minds_add_message_cart' );

// Disable button for currency change and if giftcard is in cart.
add_filter('woocommerce_order_button_html', 'minds_disable_order_button_html' );

// Add error message to cart for currency change and if giftcard is in cart.
add_action( 'woocommerce_before_cart_table', 'minds_add_custom_error_to_cart' );

add_action( 'woocommerce_checkout_create_order_line_item', 'minds_checkout_create_order_line_item', 10, 4 );

add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'minds_unset_specific_order_item_meta_data', 10, 2);

/***** HOOKS for Redeem Flow *****/

// View to Custom Giftcard as a fee.
add_action( 'woocommerce_review_order_before_payment', 'minds_gift_woocommerce_cart_coupon' );

// Add custom fee for added gift card.
add_action( 'woocommerce_cart_calculate_fees', 'minds_giftcoupon_discount_price', 10, 0 );

// Add custom meta data about giftcard.
add_action('woocommerce_checkout_create_order', 'minds_before_checkout_create_order', 20, 2);

// Clearing custom session if cart is empty.
add_filter('woocommerce_cart_is_empty', 'minds_unset_session_for_no_products');

// Add custom css for checkout page.
add_action('woocommerce_before_checkout_form', 'minds_add_custom_css');

// Display Popup for deactivation.
add_action( 'admin_footer', 'minds_deactivate_script' );

// Complete order in case of only giftcard is present in the cart.
add_action('woocommerce_thankyou', 'minds_complete_order_for_only_giftcard', 10, 1);

add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'minds_change_formatted_meta_data', 20, 2 );

add_action( 'woocommerce_after_checkout_validation', 'minds_check_gift_cards_again', 10, 2 );

add_action( 'wp_footer', 'minds_footer_code' );

add_action('wp_enqueue_scripts', 'minds_enqueue_custom_styles');


function minds_enqueue_custom_styles()
{
    wp_enqueue_style('minds-style', plugins_url('assets/css/style.css', dirname(__FILE__)), array(), '1.0', 'all');

}


function minds_init_session()
{
    if(!session_id()){
        session_start();
    }
    
} // end minds_init_session()


function minds_change_formatted_meta_data( $meta_data, $item ) 
{
    $new_meta = array();
    foreach ( $meta_data as $id => $meta_array ) {
        // We are removing the meta with the key 'something' from the whole array.
        if ( '99minds-giftcard' === $meta_array->key ) { continue; }
        $new_meta[ $id ] = $meta_array;
    }
    return $new_meta;
    
} // end minds_change_formatted_meta_data()


function minds_complete_order_for_only_giftcard($order_id)
{
    $giftcard_product_id = get_option('minds_product_id', true);
    $order = wc_get_order( $order_id );
    $i = 0;
    $giftcard_exists = false;
    foreach ( $order->get_items() as $item_id => $item ){
        $product_id = $item->get_product_id();
        if($product_id == $giftcard_product_id){
            $giftcard_exists = true;
        }
        $i++;
    }
    $status = $order->get_status();
    if($status == "processing"){
        if(($giftcard_exists == true) && ($i == 1)){
            $order->update_status( 'completed' );
        }
    }
    
} // end minds_complete_order_for_only_giftcard()


function minds_deactivate_script()
{
    wp_enqueue_style( 'wp-pointer' );
    wp_enqueue_script( 'wp-pointer' );
    wp_enqueue_script( 'utils' ); // for user settings
    echo wp_get_inline_script_tag("
        jQuery('#deactivate-99minds-gift-card').click(function(){
            jQuery('#deactivate-99minds-gift-card').pointer({
                content: '<h3>Do you want to deactivate plugin?</h3><p><a id=\"everything\" class=\"button\" href=\"'+jQuery('#deactivate-99minds-gift-card').attr('href')+'\">Deactivate Plugin</a></p>',
                position: {
                    my: 'left top',
                    at: 'center bottom',
                    offset: '-1 0'
                },
                close: function() {
                    
                }
            }).pointer('open');
            return false;
        });
    ")
?>
    <?php
    
} // end minds_deactivate_script()


function minds_add_custom_error_to_cart()
{
    
        $product_id = get_option('minds_product_id');
        $product_id = (int)$product_id;
        $in_cart = false;
  
        foreach( WC()->cart->get_cart() as $cart_item ) {
            $product_in_cart = $cart_item['product_id'];
            if ( $product_in_cart === $product_id ) $in_cart = true;
        }
        
        $temp_currency = get_woocommerce_currency();
        $woo_currency = get_option('woocommerce_currency');
    
        if($woo_currency == $temp_currency){
            $currency_flag=true;
        }else{
            $currency_flag=false;
        }
        
        $currency_sym = get_woocommerce_currency_symbol( $temp_currency );
    
	    if ( ($in_cart == true) && ($currency_flag == false) ){
		    
		    echo '<div id="error-msg" class="woocommerce-error">Currency mismatch : Cannot purchase giftcard in '.esc_html($currency_sym).'</div>';
	    }

} // end minds_add_custom_error_to_cart()


function minds_add_custom_error_to_checkout()
{
    if(!empty($_SESSION["minds_error_on_checkout"])){
        $errors = $_SESSION["minds_error_on_checkout"];
        $list = "";
        foreach($errors as $value){
            $list .= "<li>".esc_html(sanitize_text_field($value))."</li>";
        }
        echo '<ul class="woocommerce-error">'.esc_html($list).'</ul>';
        unset($_SESSION["minds_error_on_checkout"]);
    }
    
} // end minds_add_custom_error_to_checkout()

function minds_disable_order_button_html($button)
{
        $product_id = get_option('minds_product_id');
        $product_id = (int)$product_id;
        $in_cart = false;
  
        foreach( WC()->cart->get_cart() as $cart_item ) {
            $product_in_cart = $cart_item['product_id'];
            if ( $product_in_cart === $product_id ) $in_cart = true;
        }
        $temp_currency = get_woocommerce_currency();
        $woo_currency = get_option('woocommerce_currency');
    
        if($woo_currency == $temp_currency){
            $currency_flag=true;
        }else{
            $currency_flag=false;
        }
        
        if(($in_cart == true) && ($currency_flag == false)){
            $style = ' style="cursor:not-allowed;text-align: center;"';
            return '<a class="button alt"'.$style.' name="woocommerce_checkout_place_order" id="place_order" >Place order</a>';
        }else{
            return $button;
        }
        
} // end minds_disable_order_button_html()


function minds_add_custom_css()
{
    $temp_currency = get_woocommerce_currency();
    $woo_currency = get_option('woocommerce_currency');
    if($woo_currency != $temp_currency){
        unset($_SESSION["minds_giftcard_redeem"]);
    }
    $product_id = get_option( 'minds_product_id', 0 );
    $in_cart = false;
  
    foreach( WC()->cart->get_cart() as $cart_item ) {
        $product_in_cart = $cart_item['product_id'];
        if ( $product_in_cart == $product_id ){
            $in_cart = true;
        } 
    }
    if($in_cart){
        unset($_SESSION["minds_giftcard_redeem"]);
    }
    
} // end minds_add_custom_css()


function minds_unset_session_for_no_products()
{
    global $woocommerce;
    if($woocommerce->cart->get_cart_contents_count() == 0){
        unset($_SESSION["minds_giftcard_redeem"]);
    }
    
} // end minds_unset_session_for_no_products()


function minds_before_checkout_create_order( $order, $data )
{
    if(isset($_SESSION['minds_giftcard_redeem'])){
        $giftcard=array();
        $redeem_array = $_SESSION['minds_giftcard_redeem'];
        foreach($redeem_array as $key=>$val){
            array_push($giftcard,$val);
        }
        $json=json_encode($giftcard);
        $base64=base64_encode($json);
        $order->update_meta_data( '99minds-giftcard-redeeem',  $base64);
        unset($_SESSION["minds_giftcard_redeem"]);
    }
    
} // end minds_before_checkout_create_order()


/*
This is to create custom end points for 99minds plugin
*/
function minds_redirect_post_data()
{

    // After 99minds registration callback
    register_rest_route('99minds/v1','merchant-reg/', array(
        'methods' => 'POST',
        'callback' => 'minds_create_merchant',
        'permission_callback' => '__return_true',
    ));
    
    // Gift card widget callback
    register_rest_route('99minds/v1','gift-product-add/', array(
        'methods' => 'POST',
        'callback' => 'minds_add_giftproduct',
        'permission_callback' => '__return_true',
    ));
    
    // Redeem Giftcard 
    register_rest_route('99minds/v1','redeem-giftcard/', array(
        'methods' => 'POST',
        'callback' => 'minds_apply_giftcard',
        'permission_callback' => '__return_true',
    ));
    
    // Cancel / remove Giftcard
    register_rest_route('99minds/v1','cancel-giftcard/', array(
        'methods' => 'POST',
        'callback' => 'minds_cancel_giftcard',
        'permission_callback' => '__return_true',
    ));

} // end minds_redirect_post_data()


/*
Custom end point definition

Cancel Giftcard
*/


function minds_cancel_giftcard(WP_REST_Request $request)
{
    $data = $request->get_body();
    $data = json_decode($data);
    if(is_array($_SESSION['minds_giftcard_redeem'])){
        foreach($_SESSION['minds_giftcard_redeem'] as $key=>$val){
            if( $val['custom_id'] == sanitize_text_field($data->cardnumber)){
                unset($_SESSION['minds_giftcard_redeem'][$key]);
            }
        }
    }
    
} // end minds_cancel_giftcard()

/*
Custom end point definition

Apply Giftcard
*/


function minds_apply_giftcard(WP_REST_Request $request)
{
    $data = $request->get_body();
    
    $data = json_decode($data, true);
    $data["cardnumber"] = sanitize_text_field($data["cardnumber"]);
    $data = json_encode($data, true);
    $woo_currency = get_option('woocommerce_currency');
    
    $access = get_option('minds_access');
    $access = json_decode($access);
    $req = wp_remote_request( MINDS_CHECK_BALANCE_URL, array(
        'method' => 'POST',
        'headers' => array(
            "Content-type" => "application/json",
            "Authorization" => "Bearer ".$access->access_token
            ),
        'body' => $data,
        ));
    $response = wp_remote_retrieve_body($req);
    update_option("minds_test_endpoint", $response);
    $response=json_decode($response);

    if(isset($_SESSION['minds_giftcard_redeem'])){
        $count=count($_SESSION['minds_giftcard_redeem']);
    }else{
        $count=0;
    }
    if($count != 0){
        $redeem_array = $_SESSION['minds_giftcard_redeem'];
        foreach($redeem_array as $key=>$val){
            $last=$val['custom_id']+1;
        }
    }else{
        $last=1;
    }

    if(($response->code == 200)){
        $session_redeem = array();
        if(isset($_SESSION['minds_giftcard_redeem'])){
            if(is_array($_SESSION['minds_giftcard_redeem'])){
                $session_redeem = $_SESSION['minds_giftcard_redeem'];
            }
        }
        if(!array_key_exists($response->data->balance->giftcard_number,$session_redeem)){
            $temp_currency = get_woocommerce_currency();
            $array=(array)$response->data->balance;
            if(($array['remaining_value'] == 0) || ($array['remaining_value'] == '0')){
                return json_encode(array("flag"=>3));
            }else{
                if($response->data->balance->active == true){
                    if($temp_currency == $response->data->balance->currency_code){
                        if(isset($_SESSION['minds_giftcard_redeem'])){
                            $array['custom_id']=$last;
                            $_SESSION['minds_giftcard_redeem'][$response->data->balance->giftcard_number]=$array;
                            return json_encode(array("flag"=>1, "response"=>$response));
                        }else{
                            $array['custom_id']=$last;
                            $test=array($response->data->balance->giftcard_number=>$array);
                            $_SESSION['minds_giftcard_redeem'] = $test;
                            return json_encode(array("flag"=>1, "response"=>$response));
                        }
                    }else{
                        return json_encode(array("flag"=>4,"message"=> "Giftcard currency mismatch"));
                    }
                }else{
                    return json_encode(array("flag"=>4,"message"=> "Giftcard is not active"));
                }
            }
        }else{
            return json_encode(array("flag"=>2,"message"=>"Giftcard already exists"));
        }
    }else{
        return json_encode(array("flag"=>4,"message"=> $response->message));
    }
    
} // end minds_apply_giftcard()

function minds_custom_woocommerce_cart_totals_fee_html( $cart_totals_fee_html, $fee )
{
    $checkplugin=get_option("minds_plugin_status","off");
    if($checkplugin == "on"){
        if ((strpos($fee->name, 'Giftcard') !== false) && (strpos($fee->name, '**** **** ****') !== false) && (strpos($fee->name, '| Remaining :') !== false)) {
            $amount=($fee->amount);
            $arr=explode("|",$fee->name);
            $code=$arr[0];
            $code=str_replace('Giftcard ','',$code);
            $code = substr($code, 0, 1);
        ?>
            <p><?php echo (wc_price($amount)); ?>&nbsp;<a class="cancel-giftcard-button" style="display:inline" onclick="cancelGiftCard('<?php echo esc_attr($code); ?>')">Remove</a></p>
        <?php
        }else{
            $amount=($fee->amount);
            ?><p>
            <?php echo (wc_price($amount)); ?></p><?php
        }
    }else{
		return $cart_totals_fee_html;
	}
	
} // end minds_custom_woocommerce_cart_totals_fee_html()


function minds_giftcoupon_discount_price() 
{
    global $woocommerce; 
    $temp_currency = get_woocommerce_currency();
    $main_currency = get_option("woocommerce_currency");
    $subtotal=(float)$woocommerce->cart->get_subtotal() + $woocommerce->cart->shipping_total;
    $coupon = WC()->cart->get_coupon_discount_totals();
    if(count($coupon) > 0){
        foreach($coupon as $key => $val){
            $subtotal = $subtotal - $val;
        }
    }
    $_SESSION['minds_save_subtotal']=$subtotal;
    if($main_currency == $temp_currency){
        if(isset($_SESSION['minds_giftcard_redeem'])){
            if(is_array($_SESSION['minds_giftcard_redeem'])){
                foreach($_SESSION['minds_giftcard_redeem'] as $key=>$val){
                    $subtotal=$_SESSION['minds_save_subtotal'];
                    $discount=(float)$val['remaining_value'];
                    $currency=$val['currency_symbol'];
                    $remaining=0;
                    if($subtotal < $discount){
                        $remaining=round($discount-$subtotal, 2);
                        $discount=$subtotal;
                    }
            		
                    $_SESSION['minds_giftcard_redeem'][$key]['amount_redeemed']=$discount;
                    $id=$val['custom_id'];
                    $newstring = substr($key, -4);
                    $newstring="**** **** **** ".$newstring;
                    $woocommerce->cart->add_fee( "Giftcard $id: $newstring | Remaining : $currency$remaining", -$discount, false, '');
                    
                    $subtotal=$subtotal-$discount;
                    if($subtotal<=0){
                        $subtotal=0;
                    }
                    $_SESSION['minds_save_subtotal']=$subtotal;
                }
            }
        }
    }
    
} // end minds_giftcoupon_discount_price()

/*
Custom end point definition : After 99minds registration callback

Store neccessary data
*/

function minds_create_merchant(WP_REST_Request $request)
{
    $data = $request->get_body();
    $datajson= json_decode($request->get_body());
    update_option('minds_access',$data);
    update_option("minds_product_id", $datajson->product_id);
    
} // end minds_create_merchant()


/*
Custom end point definition : Gift card widget callback

Update custom cart session
*/


function minds_add_giftproduct(WP_REST_Request $request)
{
    $data = $request->get_body();
    
    if(!empty($data)){
        $json_data = json_decode($data);
        
        $custom_amount = 0;
        if(empty($json_data->discounted_amount)){
            $custom_amount = $json_data->amount;
        }else{
            $custom_amount = $json_data->discounted_amount;
        }
        
        $cart_item_data = array(
            "original_obj" => $data, 
            "quantity" => $json_data->quantity,
            "custom_price" => $custom_amount,
            "isGift" => $json_data->isGift
        );
        $temp_currency = get_woocommerce_currency();
        $woo_currency = get_option('woocommerce_currency');
        if($woo_currency == $temp_currency){
            $_SESSION['custom_cart'] = $cart_item_data;
            return json_encode(array('status'=>true, 'message'=>"Custom session set"));
        }else{
            return json_encode(array('status'=>false, 'message'=>"Currency mismatch"));
        }
    }else{
        return json_encode(array('status'=>false,'message'=>"Request is empty."));
    }
    
} // end minds_add_giftproduct()


/*
Add gift product to cart after widget form submit
*/


function minds_template_redirect()
{
    global $woocommerce;
    
    $proceed_to_add = 0;

    if(!empty($_SESSION['custom_cart'])){
        // check gift product is already in cart or not
        if($_SESSION['custom_cart']['isGift']==true){
            //check cart items
            
            $items = $woocommerce->cart->get_cart();
            
            if(empty($items)){
                $proceed_to_add = 1;
            }else{
                $is_gift_incart = 0;
                foreach($items as $item => $values) {
                    if($values['isGift']==true){
                        $is_gift_incart = 1;
                    }
                }
                
                if($is_gift_incart == 0){
                    $proceed_to_add = 1;
                }else{
                    $proceed_to_add = 0;
                }
            }
        }else{
            $proceed_to_add = 1;
        }
        
        if($proceed_to_add==1){
            //get approved product id 
            $product_id = get_option("minds_product_id", false);
            
            if((empty($_SESSION['custom_cart']['quantity'])) && ($_SESSION['custom_cart']['quantity']!=0)){
                $quantity = 1;
            }else{
                $quantity = sanitize_text_field($_SESSION['custom_cart']['quantity']);
            }
            
            if(!empty($product_id)){
                $gift_product = new WC_Product($product_id);
                if($gift_product->is_purchasable()){
                    // add product to cart with custom meta_data
                    $woocommerce->cart->add_to_cart( $product_id, $quantity, 0, array(), $_SESSION['custom_cart'] );
                }else{
                    // false
                    $_SESSION['custom_cart_error'] = 'Product is not purchasable';
                }
            }else{
                // false
                $_SESSION['custom_cart_error'] = 'Verify 99minds plugin activation';
            }
        }else{
            $_SESSION['custom_cart_error'] = 'You can add only one Gift item in a cart';
        }
        $_SESSION['custom_cart'] = NULL;
    }
    
} // end minds_template_redirect()


/*
Update custom price to cart item
*/


function minds_custom_cart_item_price( $cart )
{
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;

    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
        return;

    foreach ( $cart->get_cart() as $cart_item ) {
        if( isset($cart_item['custom_price']) )
            $cart_item['data']->set_price( $cart_item['custom_price'] );
    }
    
} // end minds_custom_cart_item_price()


if(!function_exists('minds_add_user_custom_option_from_session_into_cart'))
{
 function minds_add_user_custom_option_from_session_into_cart($product_name, $values, $cart_item_key )
    {
        /*code to add custom data on Cart & checkout Page*/    
        if(count($values) > 0)
        {
            $return_string = $product_name."<br/><dl class='variation'>";
            
            if(!empty($values['isGift'])){
                if($values['isGift']==true){
                    $return_string .= "<dt>This will be send as Gift.</dt>";
                }else{
                    $return_string .= "<dt>This is Giftcard.</dt>";
                }
            }
            $return_string .= "</dl>"; 
            return $return_string;
        }
        else
        {
            return $product_name;
        }
        
    } // end minds_add_user_custom_option_from_session_into_cart()
}


if(!function_exists('minds_add_values_to_order_item_meta'))
{
  function minds_add_values_to_order_item_meta($item_id, $values)
  {
        global $woocommerce,$wpdb;

        $user_original_obj = $values['original_obj'];
        if(!empty($user_original_obj))
        {
            wc_add_order_item_meta($item_id,'original_obj',$user_original_obj);  
        }
        
        $user_custom_price = $values['custom_price'];
        if(!empty($user_custom_price))
        {
            wc_add_order_item_meta($item_id,'custom_price',$user_custom_price);  
        }
        
        $user_isGift = $values['isGift'];
        if(!empty($user_isGift))
        {
            wc_add_order_item_meta($item_id,'isGift',$user_isGift);  
        }
    
  } // end minds_add_values_to_order_item_meta()
}


/**
 * Add custom meta to order
**/


function minds_checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) 
{
    if( isset( $values['original_obj'] ) ) {
      
        $product_id = get_option('minds_product_id');
        $product_id = (int)$product_id;
        // Loop through cart items
        foreach ( WC()->cart->get_cart() as $cart_item ) { 
            if( in_array( $product_id, array($cart_item['product_id'], $cart_item['variation_id']) ) ){
                $quantity = $cart_item['quantity'];
                break; // stop the loop if product is found
            }
        }
        
        $user_original_obj = $values['original_obj'];
        
        $user_original_obj = json_decode($user_original_obj);
        
        $user_original_obj->quantity = $quantity;
        
        $user_original_obj=(array)$user_original_obj;
        
        $json=json_encode($user_original_obj);
        
        $base64=base64_encode($json);
        
        $item->add_meta_data('99minds-giftcard',
            $base64,
            true
        );
  } // end if
  
} // end minds_checkout_create_order_line_item()



function minds_unset_specific_order_item_meta_data($formatted_meta, $item)
{
    // Only on emails notifications.
    if( !is_admin() && !is_wc_endpoint_url() )
        return $formatted_meta;

    foreach( $formatted_meta as $key => $meta ){
        if( in_array( $meta->key, array('99minds-giftcard','original_obj') ) )
            unset($formatted_meta[$key]);
    }
    return $formatted_meta;
    
} // end minds_unset_specific_order_item_meta_data()


if(!function_exists('minds_remove_user_custom_data_options_from_cart'))
{
    function minds_remove_user_custom_data_options_from_cart($cart_item_key)
    {
        global $woocommerce;
        // Get cart
        $cart = $woocommerce->cart->get_cart();
        // For each item in cart, if item is upsell of deleted product, delete it
        foreach( $cart as $key => $values)
        {
        if ( $values['wdm_user_custom_data_value'] == $cart_item_key )
            unset( $woocommerce->cart->cart_contents[ $key ] );
        }
        
    } // end minds_remove_user_custom_data_options_from_cart()
}

 
function minds_check_gift_cards_again( $fields, $errors )
{
    if(isset($_SESSION['minds_giftcard_redeem'])){
        if(count($_SESSION['minds_giftcard_redeem']) > 0){
            $i = 1;
            $access = get_option('minds_access');
            $access = json_decode($access);
            $redeem_array = array_map('sanitize_key', $_SESSION['minds_giftcard_redeem']);
            foreach($redeem_array as $key => $value){
                $data = array();
                $data["cardnumber"] = sanitize_text_field($key);
                $data = json_encode($data, true);
                $req = wp_remote_request( MINDS_CHECK_BALANCE_URL, array(
                    'method' => 'POST',
                    'headers' => array(
                        "Content-type" => "application/json",
                        "Authorization" => "Bearer ".$access->access_token
                        ),
                    'body' => $data,
                    )
                );
                $response = wp_remote_retrieve_body($req);
                $response=json_decode($response);
                if(($response->code == 200)){
                    if($response->data->balance->active == false){
                        unset($_SESSION['minds_giftcard_redeem'][$key]);
                        $errors->add( 'minds_validation', "Giftcard $i: Giftcard is not active" );
                        $_SESSION["minds_error_on_checkout"][] = "Giftcard $i: Giftcard is not active";
                    }
                }else{
                    $errors->add( 'minds_validation', $response->message );
                }
                $i++;
            }
        }
    } // end if
    
} // end minds_check_gift_cards_again()


function minds_footer_code()
{
    if(is_checkout()){
        echo wp_get_inline_script_tag("
            jQuery( document.body ).on( 'checkout_error', function() {
                var error_text = document.querySelectorAll('.woocommerce-error li');
                location.reload();
            });
        ");
    }
    
} // end minds_footer_code()


function minds_add_message_cart()
{
    if(!empty($_SESSION['custom_cart_error'])){
        wc_print_notice(esc_html(sanitize_text_field($_SESSION['custom_cart_error'])), 'error');
        unset($_SESSION['custom_cart_error']);
    }
    
} // end minds_add_message_cart()


/*
Gift card Apply form
*/

function minds_gift_woocommerce_cart_coupon()
{
    global $woocommerce;
	global $wpdb;
	
    $redeem_url = site_url().'/wp-json/99minds/v1/redeem-giftcard/';
    $cancel_url = site_url().'/wp-json/99minds/v1/cancel-giftcard/';
    $product_id = get_option( 'minds_product_id', 0 );
    $in_cart = false;
  
    foreach( WC()->cart->get_cart() as $cart_item ) {
        $product_in_cart = $cart_item['product_id'];
        if ( $product_in_cart == $product_id ){
            $in_cart = true;
        } 
    }
    $temp_currency = get_woocommerce_currency();
    $woo_currency = get_option('woocommerce_currency');
    
    if($woo_currency == $temp_currency){
        $currency_flag=true;
    }else{
        $currency_flag=false;
    }
    
	   
    $posts_table = $wpdb->posts;
    $product_type = "product";
    $post_type = "publish";
    $product=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} where `id`=%s and `post_type`=%s and `post_status`=%s", $product_id, $product_type, $post_type));
    $table_name = $wpdb->prefix."wc_webhooks";
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM `$table_name`"));
    $flag=0;
    foreach($results as $result){
        if(($result->name == '99minds Order Updated') || ($result->name == '99minds Customer Updated') || ($result->name == '99minds Customer Created')){
            $flag=$flag+1;
        }
    }
    $post_type = "shop_webhook";
    $webhooks = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE post_type =%s", $post_type));
    if($webhooks){
        $flag=0;
        foreach($webhooks as $webhook){
            if(($webhook->post_title == '99minds Order Updated') || ($webhook->post_title == '99minds Customer Updated') || ($webhook->post_title == '99minds Customer Created')){
                $flag=$flag+1;
            }
        }
    }
    $checkplugin = "on";
    $option=get_option('minds_access');
    if((!empty($option)) && ($product>0) && ($flag == 3)){
        $checkplugin = "off";
    }
    
    if($checkplugin == "off"){
        $product_id = get_option('minds_product_id', '0');
        $product_id = (int)$product_id;
    ?>
        <div id="error-msg" class="woocommerce-error" style='display:none;'></div>
        <?php
            if($currency_flag == true){
                $checkout_url = wc_get_checkout_url();
        ?>
            <div class="minds-redeem-div">
                <p class="minds-inline-block1"><?php if($in_cart == false){ ?><input type="text" id="coupon_code_minds" class="minds-input" name="coupon_code_minds"><?php }else{ ?><input type="text" id="coupon_code_minds" class="minds-input" name="coupon_code_minds" disabled><?php } ?></p>
                <p class="minds-inline-block2">
                <?php if($in_cart == false){ ?><a class="button" onclick="applyGiftCard()" id="applyGiftCard" >Apply Giftcard</a><?php }else{ ?><a class="button inactiveLink" id="applyGiftCard" >Apply Giftcard</a><?php } ?>
                </p>
            </div>
            <?php
                echo wp_get_inline_script_tag('
                function applyGiftCard(){
                    var coupon_code_minds = document.getElementById("coupon_code_minds").value;
                    var xhttp = new XMLHttpRequest();
                    xhttp.open("POST", "' . esc_url($redeem_url) . '", true);
                    xhttp.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
                    xhttp.responseType = "json";
                    xhttp.send(JSON.stringify({"cardnumber": coupon_code_minds}));
                    xhttp.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200){
                            var resp = JSON.parse(this.response);
                            if((resp.flag!= null) && (resp.flag == 1)){
                                window.location = "' . esc_url($checkout_url) . '";
                            }
                            else if((resp.flag!= null) && (resp.flag == 2)){
                                document.getElementById("error-msg").innerHTML="Giftcard already applied";
                                document.getElementById("error-msg").style.display = "";
                                setTimeout(function() {
                                    jQuery("#error-msg").fadeOut("fast");
                                }, 3000);
                            }
                            else if((resp.flag!= null) && (resp.flag == 3)){
                                document.getElementById("error-msg").innerHTML="Giftcard don\'t have balance to redeem";
                                document.getElementById("error-msg").style.display = "";
                                setTimeout(function() {
                                    jQuery("#error-msg").fadeOut("fast");
                                }, 3000);
                            }
                            else if((resp.flag!= null) && (resp.flag == 4)){
                                document.getElementById("error-msg").innerHTML= resp.message;
                                document.getElementById("error-msg").style.display = "";
                                setTimeout(function() {
                                    jQuery("#error-msg").fadeOut("fast");
                                }, 3000);
                            }
                            else{
                                document.getElementById("error-msg").innerHTML="Please enter valid giftcard number";
                                document.getElementById("error-msg").style.display = "";
                                setTimeout(function() {
                                    jQuery("#error-msg").fadeOut("fast");
                                }, 3000);
                            }
                        }
                    }
                }
                function cancelGiftCard(key){
                    var xhttp = new XMLHttpRequest();
                    xhttp.open("POST", "' . esc_url($cancel_url) . '", true);
                    xhttp.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
                    xhttp.send(JSON.stringify({"cardnumber": key}));
                    xhttp.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200){
                            window.location = "' . $checkout_url . '";
                        }
                    }
                }
                ');
            ?>
        <?php
            }
            $version='4.9.3';
        if ((version_compare( WC_VERSION, $version ) === -1) || (version_compare( WC_VERSION, $version ) === 0)) {
		    
		    if(isset($_SESSION['minds_giftcard_redeem']) && count($_SESSION['minds_giftcard_redeem']) !== 0){
                ?><div class="minds-redeem-div">
                <p class="minds-inline-block1"><select id="giftcard_drp" class="minds-input select2">
                <?php
                $redeem_array = array_map('sanitize_key', $_SESSION['minds_giftcard_redeem']);
        foreach($redeem_array as $key=>$val){
        $id=$val['custom_id'];
            $newstring = substr($key, -4);
            $newstring="**** **** **** " . $newstring;
            ?>
            <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($newstring); ?></option>
            <?php
            
        }
        ?>
        </select></p><p class="minds-inline-block2">&nbsp;<a onclick="giftcard_remove_drp()" class="button" >Remove</a></p>
        </div>
        <?php
        echo wp_get_inline_script_tag('
            function giftcard_remove_drp(){
                var e = document.getElementById("giftcard_drp");
                var strUser = e.value;
                cancelGiftCard(strUser);
            }
        ');
        ?>
        <?php
                
            }
	    }
    }else{
        ?>
        <p style="color:red;">Plugin is not activated properly.</p>
        <?php
    } // end if
    
} // end minds_gift_woocommerce_cart_coupon()