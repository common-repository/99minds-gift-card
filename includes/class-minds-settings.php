<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class minds_settings
{
    
    public static $plugin; // plugin variable.
    public static $plugin_directory; // directory.
    
    /*
     * Settings class constructor.
     *
     * @param  string   $plugin_directory   name of the plugin directory.
     *
     * @return void.
     */
     
    
    public function __construct( $plugin, $plugin_directory )
    {
        self::$plugin = $plugin;
        self::$plugin_directory = $plugin_directory;
      
        add_action( 'init', array( __CLASS__, 'set_up_menu' ) );
        
    } // end __construct()
    

    /**
     * Method that is called to set up the settings menu
     *
     * @return void
     */
     
    
    public static function set_up_menu()
    {
        // Add 99minds! settings page in the menu
        add_action( 'admin_menu', array( __CLASS__, 'add_settings_menu' ) );
        
        // Add 99minds! settings page in the plugin list
        add_filter( 'plugin_action_links_' . self::$plugin, array( __CLASS__, 'add_settings_link' ) );

        // Add 99minds! notification globally
        add_action( 'admin_notices', array( __CLASS__, 'show_nag_messages' ) );
        
    } // end set_up_menu()
    
    
    /**
     * Add 99minds! settings page in the menu
     *
     * @return void
     */
     
     
    public static function add_settings_menu() {
        add_menu_page( "99minds GiftCard", "99minds GiftCard", 'manage_options', '99minds-board', array( __CLASS__, 'show_settings_page' ) );
        
    } // end add_settings_menu()
    

    /**
     * Add Gift Card! settings page in the plugin list
     *
     * @param  mixed   $links   links
     *
     * @return mixed            links
     */
     
     
    public static function add_settings_link( $links )
    {
        $settings_link = '<a href="admin.php?page=99minds-board">Settings</a>';
        array_unshift( $links, $settings_link );
        return $links;
        
    } // end add_settings_link();
    
    
    /**
     * Method that is called to warn if 99minds is not connected
     *
     * @return void
     */
     
     
    public static function show_nag_messages() {
        $plugin_status = get_option("minds_plugin_status");
        if(!is_plugin_active('woocommerce/woocommerce.php')){
            echo '<div class="notice notice-warning" id="minds-nag-2"><p>99minds gift card has dependancy on Woocommerce plugin, activate Woocommerce plugin.</p></div>';
        }elseif(empty($plugin_status)){
            echo '<div class="notice notice-warning" id="minds-nag-2"><p>99minds gift card: plugin is not activated properly, please try again.</p></div>';
        }
        
    } // end show_nag_messages();
    

    /**
     * Display 99minds! settings page content
     *
     * @return void
     */
     
     
    public static function show_settings_page()
    {
        require_once self::$plugin_directory . 'view/minds-settings.php';
    } // end show_settings_page()
    
    
} // end class