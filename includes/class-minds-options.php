<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class minds_options
{
    
    
    public static function disconnect() { // code to do when plugin is disconnected
        $domain = get_site_url();
        $currentUser = wp_get_current_user();
        if(empty($currentUser) === FALSE){
            $installerUserName = $currentUser->user_login;
            $installerUserEmail = $currentUser->user_email;
            $firstjson = new stdClass;
            $firstjson->name = $installerUserName;
            $firstjson->email = $installerUserEmail;
            $firstjson->domain = $domain;
            $secondjson = new stdClass;
            $secondjson->woocommerce = $firstjson;
            $secondjson = json_encode($secondjson);
            $string = "domain=" . $domain . "&email=" . $installerUserEmail;
            $access = get_option('minds_access');
            $access = json_decode($access);
            $secret = $access->key_secret;
            $sig = hash_hmac('sha256', $string , $secret );
            
            $req = wp_remote_request( 
                MINDS_UNINSTALL_URL, 
                [
                    'method' => 'POST',
                    'headers' => [
                        "Content-type" => "application/json",
                        "X-99minds-Signature" => $sig,
                        ],
                    'body' => $secondjson,
                ]
            );
            
            $response = wp_remote_retrieve_body($req);
            
            update_option('minds_uninstall_data',$response);
            
            delete_option( "minds_multsite_check" );
            delete_option( "minds_plugin_status" );
            delete_option( "minds_access" );
            delete_option( "minds_product_id" );
            delete_option( "minds_redirect_site" );
        }
        
    } //end disconnect()
    

    private static function get_option( $option, $default=false ) { // start get_option()
        return get_option( "minds_$option", $default );
        
    } // end get_option()
    
    
    private static function update_option( $option, $value ) { // start update_option()
        return update_option( "minds_$option", $value );
    } // end update_option()
    
    
    public static function get_parameters(){ // start get_parameters()
        update_option( "minds_redirect_site", "true" );
        wp_redirect(admin_url("admin.php?page=99minds-board"));
        exit;
        
    } // end get_parameters()
    
    
    public static function get_rest_token(){ // start get_rest_token()
        // Check token is present.
        $giftcardRestToken = get_option("minds_rest_token","");
        $isValid = 0;
        
        if($isValid === 0){
            // Generate new token.
            $app_name = "99minds access";
            $app_user_id = get_current_user_id();
            $scope = "read_write";
            
            $permissions_new = array(
                'read'       => __( 'Read', 'woocommerce' ),
                'write'      => __( 'Write', 'woocommerce' ),
                'read_write' => __( 'Read/Write', 'woocommerce' ),
            );
            $user = wp_get_current_user();
            // Created API keys.
            $permissions     = in_array( $scope, array( 'read', 'write', 'read_write' ), true ) ? sanitize_text_field( $scope ) : 'read';
            $consumer_key    = 'ck_' . wc_rand_hash();
            $consumer_secret = 'cs_' . wc_rand_hash();
            $encript_key = wc_api_hash( $consumer_key );
            global $wpdb;
    		$wpdb->insert(
    			$wpdb->prefix . 'woocommerce_api_keys',
    			array(
    				'user_id'         => $user->ID,
    				'description'     => $description,
    				'permissions'     => $permissions,
    				'consumer_key'    => $encript_key,
    				'consumer_secret' => $consumer_secret,
    				'truncated_key'   => substr( $encript_key, -7 ),
    			)
    		);
    		
    		update_option( "minds_rest_token", $wpdb->insert_id );
            
            $res_array = array("status"=>"new", "keys"=>array("ck"=>$consumer_key, "cs"=>$consumer_secret));
        }
        return $res_array;
    } // end get_rest_token()
    
    
    public static function deactivation(){ // Start deactivation().
        $domain=get_site_url();
        $currentUser = wp_get_current_user();
        if(empty($currentUser) === FALSE){
            $installerUserName = $currentUser->user_login;
            $installerUserEmail = $currentUser->user_email;
            $firstjson = new stdClass;
            $firstjson->name = $installerUserName;
            $firstjson->email = $installerUserEmail;
            $firstjson->domain = $domain;
            $secondjson = new stdClass;
            $secondjson->woocommerce = $firstjson;
            $secondjson = json_encode($secondjson);
            
            $string= "domain=" . $domain . "&email=" . $installerUserEmail;
            $access = get_option('minds_access');
            $access = json_decode($access);
            $secret = $access->key_secret;
            $sig = hash_hmac('sha256', $string , $secret);
            
            $req = wp_remote_request( 
                MINDS_UNINSTALL_URL, 
                [
                    'method' => 'POST',
                    'headers' => [
                        "Content-type" => "application/json",
                        "X-99minds-Signature" => $sig
                    ],
                        "body" => $secondjson,
                ]
            );
            
            $response = wp_remote_retrieve_body($req);
            update_option("minds_deactivate_data", $response);
        }
        
    } // End deactivation().
    
    
} // End Class