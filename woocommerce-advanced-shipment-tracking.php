<?php
/**
 * @wordpress-plugin
 * Plugin Name: Advanced Shipment Tracking for WooCommerce 
 * Plugin URI:  
 * Description: Add shipment tracking information to your WooCommerce orders and provide customers with an easy way to track their orders. Shipment tracking Info will appear in customers accounts (in the order panel) and in WooCommerce order complete email. 
 * Version: 2.4.6
 * Author:      zorem
 * Author URI:  
 * License:     GPL-2.0+
 * License URI: 
 * Text Domain: woo-advanced-shipment-tracking
 * Domain Path: /lang/
 * WC tested up to: 3.7
*/


class zorem_woocommerce_advanced_shipment_tracking {
	
	/**
	 * WooCommerce Advanced Shipment Tracking version.
	 *
	 * @var string
	 */
	public $version = '2.4.6';
	
	/**
	 * Initialize the main plugin function
	*/
    public function __construct() {
		
		$this->plugin_file = __FILE__;
		// Add your templates to this array.
		
		if(!defined('SHIPMENT_TRACKING_PATH')) define( 'SHIPMENT_TRACKING_PATH', $this->get_plugin_path());		
		
		global $wpdb;
		$this->table = $wpdb->prefix."woo_shippment_provider";
		if( is_multisite() ){
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			if ( is_plugin_active_for_network( 'woo-advanced-shipment-tracking/woocommerce-advanced-shipment-tracking.php' ) ) {
				$main_blog_prefix = $wpdb->get_blog_prefix(BLOG_ID_CURRENT_SITE);			
				$this->table = $main_blog_prefix."woo_shippment_provider";	
			} else{
				$this->table = $wpdb->prefix."woo_shippment_provider";
			}	
			
		} else{
			$this->table = $wpdb->prefix."woo_shippment_provider";	
		}
		
			
		
		if ( $this->is_wc_active() ) {			
			// Include required files.
			$this->includes();
					
			// Init REST API.
			$this->init_rest_api();
			
			//start adding hooks
			$this->init();
			
			//admin class init
			$this->admin->init();
			
			//plugin install class init
			$this->install->init();
			
			add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );	
		}	
		add_action( 'admin_footer', array( $this, 'uninstall_notice') );	
		//register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );	
    }	
	/**
	 * Check if WooCommerce is active
	 *
	 * @access private
	 * @since  1.0.0
	 * @return bool
	*/
	private function is_wc_active() {
		
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$is_active = true;
		} else {
			$is_active = false;
		}
		

		// Do the WC active check
		if ( false === $is_active ) {
			add_action( 'admin_notices', array( $this, 'notice_activate_wc' ) );
		}		
		return $is_active;
	}
	
	/**
	 * Display WC active notice
	 *
	 * @access public
	 * @since  1.0.0
	*/
	public function notice_activate_wc() {
		?>
		<div class="error">
			<p><?php printf( __( 'Please install and activate %sWooCommerce%s for WooCommerce Advanced Shipment Tracking!', 'woo-advanced-shipment-tracking' ), '<a href="' . admin_url( 'plugin-install.php?tab=search&s=WooCommerce&plugin-search-input=Search+Plugins' ) . '">', '</a>' ); ?></p>
		</div>
		<?php
	}
	
	/*
	* init when class loaded
	*/
	public function init(){		
		
		add_action( 'plugins_loaded', array( $this, 'wst_load_textdomain'));
		register_activation_hook( __FILE__, array( $this->install, 'woo_shippment_tracking_install' ));
		//add_action( 'woocommerce_view_order', array( $this, 'show_tracking_box' ) );
		add_action( 'add_meta_boxes', array( $this->actions, 'add_meta_box' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this->actions, 'save_meta_box' ), 0, 2 );
		add_action( 'woocommerce_view_order', array( $this->actions, 'show_tracking_info_order' ) );
		//add_filter( 'woocommerce_my_account_my_orders_actions', array( $this->actions, 'show_track_actions_in_orders' ), 10, 2 );	
		
		add_action( 'wp_ajax_wc_shipment_tracking_delete_item', array( $this->actions, 'meta_box_delete_tracking' ) );
		add_action( 'wp_ajax_wc_shipment_tracking_save_form', array( $this->actions, 'save_meta_box_ajax' ) );							
		
		if(isset( $_REQUEST['wcast-tracking-preview'] ) && '1' === $_REQUEST['wcast-tracking-preview']){
			$preview = true;
		} else{
			$preview = false;
		}
		if(!$preview){
			$display_tracking_info_at = get_theme_mod('display_tracking_info_at','before_order');
			if($display_tracking_info_at == 'after_order'){
				add_action( 'woocommerce_email_order_meta', array( $this->actions, 'email_display' ), 0, 4 );
			} else{
				add_action( 'woocommerce_email_before_order_table', array( $this->actions, 'email_display' ), 0, 4 );
			}	
		}	
		
		add_action( 'wpo_wcpdf_before_order_details', array( $this->actions, 'tracking_display_in_invoice' ), 0, 4 );
		
		add_shortcode( 'woo_mb_tracking_info' , array( $this, 'tracking_info_shortcode'));
		
		// Custom tracking column in admin orders list.
		add_filter( 'manage_shop_order_posts_columns', array( $this->actions, 'shop_order_columns' ), 99 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this->actions, 'render_shop_order_columns' ) );				
		
		//fix shipment tracking for deleted tracking
		add_action("fix_shipment_tracking_for_deleted_tracking", array( $this->actions, 'func_fix_shipment_tracking_for_deleted_tracking' ), 10, 3 );
		
		add_action('admin_footer', array( $this->actions, 'custom_validation_js'));
		
		add_action('admin_footer', array( $this->actions, 'add_inline_tracking_lightbox'));
		add_action( 'wp_ajax_add_inline_tracking_number', array( $this->actions, 'save_inline_tracking_number' ) );							
		
		//new 
		//load css js 
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'admin_styles' ), 4);
		
		//Custom Woocomerce menu
		add_action('admin_menu', array( $this->admin, 'register_woocommerce_menu' ), 99 );
				
		//ajax save admin api settings
		add_action( 'wp_ajax_wc_ast_settings_form_update', array( $this->admin, 'wc_ast_settings_form_update_callback' ) );
		
		//ajax save admin trackship settings
		add_action( 'wp_ajax_wc_ast_trackship_form_update', array( $this->admin, 'wc_ast_trackship_form_update_callback' ) );
		
		$wc_ast_status_delivered = get_option('wc_ast_status_delivered');		
		if($wc_ast_status_delivered == 1) 
		add_action( 'woocommerce_order_status_delivered', array( $this, 'email_trigger_delivered' ), 10, 2 );
	
		add_action( 'wp_ajax_nopriv_preview_tracking_page', array( $this->front, 'preview_tracking_page') );
		add_action( 'wp_ajax_preview_tracking_page', array( $this->front, 'preview_tracking_page') );
	}
	
	/**
	 * Send email when order status change to "Delivered"
	 *
	*/
	public function email_trigger_delivered($order_id, $order = false){		
		require_once( 'includes/email-manager.php' );		
		//wc_advanced_shipment_tracking_email_class()->delivered_order_status_email_trigger($order_id, $order);			
		WC()->mailer()->emails['WC_Email_Customer_Delivered_Order']->trigger( $order_id, $order );
	}
	public function tracking_info_shortcode(){
		ob_start();		
		$order_id = $woo_mb_base->order_id;
		if($order_id == 0){
			$order_id = get_theme_mod('wcast_email_preview_order_id');	
		}		
		$tracking_items = $this->get_tracking_items( $order_id, true );
		wc_get_template( 'emails/tracking-info.php', array( 'tracking_items' => $tracking_items ), 'woocommerce-advanced-shipment-tracking/', $this->get_plugin_path() . '/templates/' );
		$html = ob_get_contents();
		ob_end_clean();
        return $html;		
	}
	/**
	 * Init advanced shipment tracking REST API.
	 *
	*/
	private function init_rest_api() {
		add_action( 'rest_api_init', array( $this, 'rest_api_register_routes' ) );
	}
	
	
	/*** Method load Language file ***/
	function wst_load_textdomain() {
		load_plugin_textdomain( 'woo-advanced-shipment-tracking', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
	}
	
	
	/**
	 * Gets the absolute plugin path without a trailing slash, e.g.
	 * /path/to/wp-content/plugins/plugin-directory.
	 *
	 * @return string plugin path
	 */
	public function get_plugin_path() {
		if ( isset( $this->plugin_path ) ) {
			return $this->plugin_path;
		}

		$this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );

		return $this->plugin_path;
	}	
	
	/**
	 * Get shipping providers with normalized values (respect decimal separator
	 * settings), for display.
	 *
	 * @return array
	 */
	public function get_normalized_shipping_providers() {
		$shipping_providers = $this->get_shipping_providers( ARRAY_A );		
		$decimal_separator = wc_get_price_decimal_separator();
		$normalize_keys = array(
			'id',
			'provider_name',
			'shipping_country',
			'provider_url',
		);

		foreach ( $shipping_providers as $index => $shipping_provider ) {
			foreach ( $normalize_keys as $key ) {
				if ( ! isset( $shipping_provider[ $key ] ) ) {
					continue;
				}

				$shipping_providers[ $index ][ $key ] = str_replace( '.', $decimal_separator, $shipping_providers[ $index ][ $key ] );
			}
		}

		return $shipping_providers;
	}
	
	/**
	 * Get raw shipping providers from the DB.
	 *
	 * @param string $output Output format.
	 * @return mixed
	 */
	public function get_shipping_providers( $output = OBJECT ) {
		global $wpdb;
		$woo_shippment_table_name = $wpdb->prefix . 'woo_shippment_provider';
		return $wpdb->get_results( "
			SELECT * FROM {$woo_shippment_table_name}			
			WHERE shipping_default = 0
			ORDER BY id ASC
		", $output );
	}
	/*
	* include files
	*/
	private function includes(){				
		
		require_once $this->get_plugin_path() . '/includes/class-wc-advanced-shipment-tracking.php';
		$this->actions = WC_Advanced_Shipment_Tracking_Actions::get_instance();
		
		require_once $this->get_plugin_path() . '/includes/class-wc-advanced-shipment-tracking-install.php';
		$this->install = WC_Advanced_Shipment_Tracking_Install::get_instance();
		
		require_once $this->get_plugin_path() . '/includes/class-wc-advanced-shipment-tracking-admin.php';
		$this->admin = WC_Advanced_Shipment_Tracking_Admin::get_instance();				
		
		require_once $this->get_plugin_path() . '/includes/class-wc-advanced-shipment-tracking-front.php';
		$this->front = WC_Advanced_Shipment_Tracking_Front::get_instance();
		
		//cron function
		require_once $this->get_plugin_path() . '/includes/class-wc-advanced-shipment-tracking-cron.php';
		//api call function 
		require_once $this->get_plugin_path() . '/includes/class-wc-advanced-shipment-tracking-api-call.php';
		
		$wc_ast_status_delivered = get_option('wc_ast_status_delivered');		
		if($wc_ast_status_delivered == 1) require_once $this->get_plugin_path() . '/includes/email-manager.php';							
	}
	
	/**
	 * Register shipment tracking routes.
	 *
	 * @since 1.5.0
	 */
	public function rest_api_register_routes() {
		if ( ! is_a( WC()->api, 'WC_API' ) ) {
			return;
		}
		require_once $this->get_plugin_path() . '/includes/api/v1/class-wc-advanced-shipment-tracking-rest-api-controller.php';
		require_once $this->get_plugin_path() . '/includes/api/class-wc-advanced-shipment-tracking-rest-api-controller.php';

		// v1
		WC()->api->WC_Advanced_Shipment_Tracking_V1_REST_API_Controller = new WC_Advanced_Shipment_Tracking_V1_REST_API_Controller();
		WC()->api->WC_Advanced_Shipment_Tracking_V1_REST_API_Controller->register_routes();

		// v2
		WC()->api->WC_Advanced_Shipment_Tracking_REST_API_Controller = new WC_Advanced_Shipment_Tracking_REST_API_Controller();
		WC()->api->WC_Advanced_Shipment_Tracking_REST_API_Controller->register_routes();
		
	}
	public function on_plugins_loaded() {		
		require_once $this->get_plugin_path() . '/includes/email-manager.php';
		
		require_once $this->get_plugin_path() . '/includes/customizer/class-wcast-customizer.php';
		
		require_once $this->get_plugin_path() . '/includes/customizer/class-wc-tracking-info-customizer.php';	
		
		require_once $this->get_plugin_path() . '/includes/customizer/class-wc-email-customizer.php';
		
		require_once $this->get_plugin_path() . '/includes/customizer/class-wc-failure-email-customizer.php';

		require_once $this->get_plugin_path() . '/includes/customizer/class-wc-intransit-email-customizer.php';

		require_once $this->get_plugin_path() . '/includes/customizer/class-wc-outfordelivery-email-customizer.php';

		require_once $this->get_plugin_path() . '/includes/customizer/class-wc-delivered-email-customizer.php';	
		
		require_once $this->get_plugin_path() . '/includes/customizer/class-wc-returntosender-email-customizer.php';

		require_once $this->get_plugin_path() . '/includes/customizer/class-wc-availableforpickup-email-customizer.php';			
	}
	public function plugin_dir_url(){
		return plugin_dir_url( __FILE__ );
	}	
	/*
	* Deactivate plugin code - change order status from delivered to completed
	*/
	/*public function uninstall(){	
		$wc_ast_deactivate_delivered = get_option('wc_ast_deactivate_delivered','no');		
		if($wc_ast_deactivate_delivered == 'no')return;
				
		remove_action( 'woocommerce_order_status_completed_notification', array( WC()->mailer()->emails['WC_Email_Customer_Completed_Order'], 'trigger' ) );		
		
		$statuses = ['delivered'];
		$orders = wc_get_orders( ['limit' => -1, 'status' => $statuses] );
		foreach($orders as $order){
			$order_id = $order->get_id();
			$order = new WC_Order($order_id);
			$order->update_status('completed');			
		}				
	}*/
	
	public function uninstall_notice(){ ?>
		<script>
		jQuery(document).on("click","[data-slug='woo-advanced-shipment-tracking'] .deactivate a",function(e){			
			e.preventDefault();
			jQuery('.uninstall_popup').show();
			var theHREF = jQuery(this).attr("href");
			jQuery(document).on("click",".uninstall_plugin",function(e){
				window.location.href = theHREF;
			});			
		});
		jQuery(document).on("click",".popupclose",function(e){
			jQuery('.uninstall_popup').hide();
		});
		jQuery(document).on("click",".uninstall_close",function(e){
			jQuery('.uninstall_popup').hide();
		});
		
		</script>
		<div id="" class="popupwrapper uninstall_popup" style="display:none;">
			<div class="popuprow" style="text-align: left;max-width: 380px;">
				<h3 class="popup_title"><?php _e( 'Advanced Shipment Tracking for WooCommerce', 'woo-advanced-shipment-tracking'); ?></h2>
				<p><?php echo sprintf(__('<strong>PLEASE NOTE</strong> - If you use the custom order status "Delivered", when you deactivate the plugin, you must register this order status in function.php in order to see these orders in the orders admin. You can find the <a href="%s" target="blank">snippet</a> to use in functions.php here or you can manually change all your "delivered" order to "completed" before deactivating the plugin.', 'woo-advanced-shipment-tracking'), 'https://gist.github.com/zorem/6f09162fe91eab180a76a621ce523441'); ?></p>
				<p class="" style="text-align:left;">	
					<input type="button" value="Uninstall" class="uninstall_plugin button-primary btn_green">
					<input type="button" value="Close" class="uninstall_close button-primary btn_red">				
				</p>
			</div>
			<div class="popupclose"></div>
		</div>		
	<?php }
}

/**
 * Returns an instance of zorem_woocommerce_advanced_shipment_tracking.
 *
 * @since 1.6.5
 * @version 1.6.5
 *
 * @return zorem_woocommerce_advanced_shipment_tracking
*/
function wc_advanced_shipment_tracking() {
	static $instance;

	if ( ! isset( $instance ) ) {		
		$instance = new zorem_woocommerce_advanced_shipment_tracking();
	}

	return $instance;
}

/**
 * Register this class globally.
 *
 * Backward compatibility.
*/
$GLOBALS['WC_advanced_Shipment_Tracking'] = wc_advanced_shipment_tracking();