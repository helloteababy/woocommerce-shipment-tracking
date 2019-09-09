<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Advanced_Shipment_Tracking_Admin {
	
	/**
	 * Initialize the main plugin function
	*/
    public function __construct() {

		global $wpdb;
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
			
	}
	
	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
	private static $instance;
	
	/**
	 * Get the class instance
	 *
	 * @return WC_Advanced_Shipment_Tracking_Admin
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
	
	/*
	* init from parent mail class
	*/
	public function init(){
		
		//database check
		add_action( 'init', array( $this, 'database_table_check') );
		
		//rename order status +  rename bulk action + rename filter
		add_filter( 'wc_order_statuses', array( $this, 'wc_renaming_order_status') );
		add_filter( 'woocommerce_register_shop_order_post_statuses', array( $this, 'filter_woocommerce_register_shop_order_post_statuses'), 10, 1 );
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'modify_bulk_actions'), 50, 1 );
		
		//new order status
		$newstatus = get_option( "wc_ast_status_delivered", 0);
		if( $newstatus == true ){
			//register order status 
			add_action( 'init', array( $this, 'register_order_status') );
			//add status after completed
			add_filter( 'wc_order_statuses', array( $this, 'add_delivered_to_order_statuses') );
			//Custom Statuses in admin reports
			add_filter( 'woocommerce_reports_order_statuses', array( $this, 'include_custom_order_status_to_reports'), 20, 1 );
			// for automate woo to check order is paid
			add_filter( 'woocommerce_order_is_paid_statuses', array( $this, 'delivered_woocommerce_order_is_paid_statuses' ) );
			//add bulk action
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_actions'), 50, 1 );
		}
		
		//cron_schedules
		add_filter( 'cron_schedules', array( $this, 'add_cron_interval') );
		//cron hook
		//add_action( WC_Advanced_Shipment_Tracking_Cron::CRON_HOOK, array( $this, 'wc_ast_cron_callback' ) );

		//filter in shipped orders
		add_filter( 'is_order_shipped', array( $this, "check_tracking_exist" ),10,2);
		add_filter( 'is_order_shipped', array( $this, "check_order_status" ),5,2);		
		
		$wc_ast_status_delivered = get_option('wc_ast_status_delivered');		
		if($wc_ast_status_delivered == 1){
			add_action( 'woocommerce_order_actions', array( $this, 'add_order_meta_box_actions' ) );
			add_action( 'woocommerce_order_action_resend_delivered_order_notification', array( $this, 'process_order_meta_box_actions' ) );
		}		
		
		//batch process cron hook
		//add_action( 'wc_ast_batch_process', array( $this, 'wc_ast_batch_process_callback' ) );
		
		$api_enabled = get_option( "wc_ast_api_enabled", 0);
		if( $api_enabled == true ){
			//add column after tracking
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'wc_add_order_shipment_status_column_header'), 20 );
			//shipment status content in order page
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'wc_add_order_shipment_status_column_content') );
			
			//add bulk action - get shipment status
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_actions_get_shipment_status'), 10, 1 );
			
			// Make the action from selected orders to get shipment status
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'get_shipment_status_handle_bulk_action_edit_shop_order'), 10, 3 );
			
			// Bulk shipment status sync ajax call from settings
			add_action( 'wp_ajax_bulk_shipment_status_from_settings', array( $this, 'bulk_shipment_status_from_settings_fun' ) );
			
			// The results notice from bulk action on orders
			add_action( 'admin_notices', array( $this, 'shipment_status_bulk_action_admin_notice' ) );
			
			// add 'get_shipment_status' order meta box order action
			add_action( 'woocommerce_order_actions', array( $this, 'add_order_meta_box_get_shipment_status_actions' ) );
			add_action( 'woocommerce_order_action_get_shipment_status_edit_order', array( $this, 'process_order_meta_box_actions_get_shipment_status' ) );
			
			// add bulk order filter for exported / non-exported orders
			add_action( 'restrict_manage_posts', array( $this, 'filter_orders_by_shipment_status') , 20 );
			add_filter( 'request', array( $this, 'filter_orders_by_shipment_status_query' ) );	

			//add_action( 'wp_dashboard_setup', array( $this, 'ast_add_dashboard_widgets') );					
		}
		
		// trigger when order status changed to shipped or completed
		add_action( 'woocommerce_order_status_completed', array( $this, 'trigger_woocommerce_order_status_completed'), 10, 1 );
		
		add_action( 'woocommerce_order_status_updated-tracking', array( $this, 'trigger_woocommerce_order_status_completed'), 10, 1 );
		
		add_filter( 'woocommerce_email_title', array( $this, 'change_completed_woocommerce_email_title'), 10, 2 );
		
		
		add_action( 'wp_ajax_wc_ast_upload_csv_form_update', array( $this, 'upload_tracking_csv_fun') );

		add_action( 'wp_ajax_update_delivered_order_email_status', array( $this, 'update_delivered_order_email_status_fun') );
		
		add_action( 'wp_ajax_update_shipment_status_email_status', array( $this, 'update_shipment_status_email_status_fun') );		

		add_action( 'admin_footer', array( $this, 'footer_function') );			
		
		// filter for shipment status
		add_filter("trackship_status_filter", array($this, "trackship_status_filter_func"), 10 , 1);
		
		// filter for shipment status icon
		add_filter("trackship_status_icon_filter", array($this, "trackship_status_icon_filter_func"), 10 , 2);				
		
		add_action( 'wcast_retry_trackship_apicall', array( $this, 'wcast_retry_trackship_apicall_func' ) );			
		
		add_action( 'wp_ajax_update_email_preview_order', array( $this, 'update_email_preview_order_fun') );
		
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_delivered_order_status_actions_button'), 100, 2 );		
		add_filter( 'woocommerce_admin_order_preview_actions', array( $this, 'additional_admin_order_preview_buttons_actions'), 5, 2 );
		
		//Shipping Provider Action
		add_action( 'wp_ajax_filter_shipiing_provider_by_status', array( $this, 'filter_shipiing_provider_by_status_fun') );

		add_action( 'wp_ajax_add_custom_shipment_provider', array( $this, 'add_custom_shipment_provider_fun') );
		
		add_action( 'wp_ajax_get_provider_details', array( $this, 'get_provider_details_fun') );
		
		add_action( 'wp_ajax_update_custom_shipment_provider', array( $this, 'update_custom_shipment_provider_fun') );
		
		add_action( 'wp_ajax_woocommerce_shipping_provider_delete', array( $this, 'woocommerce_shipping_provider_delete' ) );				
		
		add_action( 'wp_ajax_update_provider_status_active', array( $this, 'update_provider_status_active_fun') );
		
		add_action( 'wp_ajax_update_provider_status_inactive', array( $this, 'update_provider_status_inactive_fun') );
		
		add_action( 'wp_ajax_update_default_provider', array( $this, 'update_default_provider_fun') );
		
		add_action( 'wp_ajax_update_shipment_status', array( $this, 'update_shipment_status_fun') );
		
		add_action( 'wp_ajax_sync_providers', array( $this, 'sync_providers_fun') );		
	}	
	
	/*
	* database table check
	*/
	function database_table_check(){
		global $wpdb;
		$table_name = $wpdb->prefix.'shipment_batch_process';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE `{$table_name}` (
		  id int NOT NULL AUTO_INCREMENT,
		  order_id text NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;";
		
		if ( !function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . '/wp-admin/includes/upgrade.php';
		}
		maybe_create_table( $table_name, $sql );
	}
	
	/*
	* Rename WooCommerce Order Status
	*/
	function wc_renaming_order_status( $order_statuses ) {
		
		$enable = get_option( "wc_ast_status_shipped", 0);
		if( $enable == false )return $order_statuses;
		
		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
			if ( 'wc-completed' === $key ) {
				$order_statuses['wc-completed'] = esc_html__( 'Shipped','woo-advanced-shipment-tracking' );
			}
		}		
		return $order_statuses;
	}
	
	/*
	* define the woocommerce_register_shop_order_post_statuses callback 
	* rename filter 
	* rename from completed to shipped
	*/
	function filter_woocommerce_register_shop_order_post_statuses( $array ) {
		
		$enable = get_option( "wc_ast_status_shipped", 0);
		if( $enable == false )return $array;
		
		if( isset( $array[ 'wc-completed' ] ) ){
			$array[ 'wc-completed' ]['label_count'] = _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'woo-advanced-shipment-tracking' );
		}
		return $array; 
	}
	
	/*
	* rename bulk action
	*/
	function modify_bulk_actions($bulk_actions) {
		
		$enable = get_option( "wc_ast_status_shipped", 0);
		if( $enable == false )return $bulk_actions;
		
		if( isset( $bulk_actions['mark_completed'] ) ){
			$bulk_actions['mark_completed'] = __( 'Change status to shipped', 'woo-advanced-shipment-tracking' );
		}
		return $bulk_actions;
	}
	
	/** 
	 * Register new status : Delivered
	**/
	function register_order_status() {
		register_post_status( 'wc-delivered', array(
			'label'                     => __( 'Delivered', 'woo-advanced-shipment-tracking' ),
			'public'                    => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true,
			'exclude_from_search'       => false,
			'label_count'               => _n_noop( 'Delivered <span class="count">(%s)</span>', 'Delivered <span class="count">(%s)</span>', 'woo-advanced-shipment-tracking' )
		) );
		$wc_ast_api_key = get_option('wc_ast_api_key');
		$api_enabled = get_option( "wc_ast_api_enabled", 0);		
		if($wc_ast_api_key && $api_enabled){
			register_post_status( 'wc-updated-tracking', array(
				'label'                     => __( 'Updated Tracking', 'woo-advanced-shipment-tracking' ),
				'public'                    => true,
				'show_in_admin_status_list' => true,
				'show_in_admin_all_list'    => true,
				'exclude_from_search'       => false,
				'label_count'               => _n_noop( 'Updated Tracking <span class="count">(%s)</span>', 'Updated Tracking <span class="count">(%s)</span>', 'woo-advanced-shipment-tracking' )
			) );
		}
	}
	
	/*
	* add status after completed
	*/
	function add_delivered_to_order_statuses( $order_statuses ) {
		$new_order_statuses = array();
		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
			if ( 'wc-completed' === $key ) {
				$new_order_statuses['wc-delivered'] = __( 'Delivered', 'woo-advanced-shipment-tracking' );
				//$new_order_statuses['wc-updated-tracking'] = __( 'Updated Tracking', 'woo-advanced-shipment-tracking' );
			}
		}
		$wc_ast_api_key = get_option('wc_ast_api_key');
		$api_enabled = get_option( "wc_ast_api_enabled", 0);		
		if($wc_ast_api_key && $api_enabled){
			foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
				if ( 'wc-completed' === $key ) {
					//$new_order_statuses['wc-delivered'] = __( 'Delivered', 'woo-advanced-shipment-tracking' );
					$new_order_statuses['wc-updated-tracking'] = __( 'Updated Tracking', 'woo-advanced-shipment-tracking' );
				}
			}
		}
		return $new_order_statuses;
	}
	
	/*
	* Adding the custom order status to the default woocommerce order statuses
	*/
	function include_custom_order_status_to_reports( $statuses ){
		if($statuses)$statuses[] = 'delivered';
		if($statuses)$statuses[] = 'updated-tracking';
		return $statuses;
	}
	
	/*
	* mark status as a paid.
	*/
	function delivered_woocommerce_order_is_paid_statuses( $statuses ) { 
		$statuses[] = 'delivered';
		$statuses[] = 'updated-tracking';	
		return $statuses; 
	}
	
	/*
	* add bulk action
	* Change order status to delivered
	*/
	function add_bulk_actions( $bulk_actions ){
		$bulk_actions['mark_delivered'] = __( 'Change status to delivered', 'woo-advanced-shipment-tracking' );
		return $bulk_actions;		
	}
	
	/*
	* add_cron_interval
	*/
	function add_cron_interval( $schedules ){
		$schedules['wc_ast_1hr'] = array(
			'interval' => 60*60,//1 hour
			'display'  => esc_html__( 'Every one hour' ),
		);
		$schedules['wc_ast_6hr'] = array(
			'interval' => 60*60*6,//6 hour
			'display'  => esc_html__( 'Every six hour' ),
		);
		$schedules['wc_ast_12hr'] = array(
			'interval' => 60*60*12,//6 hour
			'display'  => esc_html__( 'Every twelve hour' ),
		);
		$schedules['wc_ast_1day'] = array(
			'interval' => 60*60*24*1,//1 days
			'display'  => esc_html__( 'Every one day' ),
		);
		$schedules['wc_ast_2day'] = array(
			'interval' => 60*60*24*2,//2 days
			'display'  => esc_html__( 'Every two day' ),
		);
		$schedules['wc_ast_7day'] = array(
			'interval' => 60*60*24*7,//7 days
			'display'  => esc_html__( 'Every Seven day' ),
		);
		
		//every 5 sec for batch proccessing
		$schedules['wc_ast_2min'] = array(
			'interval' => 2*60,//1 hour
			'display'  => esc_html__( 'Every two min' ),
		);
		return $schedules;
	}
	
	/*
	* get shipped orders
	*/
	function get_shipped_orders() {
		$range = get_option('wc_ast_api_date_range', 30 );
		$args = array(
			'status'	=> 'wc-completed',
			'limit'		=> -1,
		);
		if( $range != 0 ){
			$start = strtotime( date( 'Y-m-d 00:00:00', strtotime( '-'.$range.' days' ) ));
			$end = strtotime( date( 'Y-m-d 23:59:59', strtotime( '-1 days' ) ));
			$args['date_completed'] = $start.'...'.$end;
		}
		//$orders = wc_get_orders( $args );print_r($orders);exit;
		return $orders = wc_get_orders( $args );
	}
	
	/*
	* cron callback
	*/
	function wc_ast_cron_callback(){

		//get shipped orders
		$orders = $this->get_shipped_orders();

		foreach( $orders as $order ){
			$order_shipped = apply_filters( 'is_order_shipped', true, $order );
			
			if( $order_shipped ){
				$this->add_in_batch_process( $order->get_id() );
			}
		}
				
		if ( ! wp_next_scheduled( 'wc_ast_batch_process' ) ) {
			wp_schedule_event( time(), 'wc_ast_2min', 'wc_ast_batch_process' );
		}
	}
	
	/*
	* tracking number filter
	* if number not found. return false
	* if number found. return true
	*/
	function check_tracking_exist( $value, $order ){
		
		if($value == true){
				
			$tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );
			if( $tracking_items ){
				return true;
			} else {
				return false;
			}
		}
		return $value;
	}		
	
	function check_order_status($value, $order){
		$order_status  = $order->get_status(); 
		
		if($order_status == 'updated-tracking' || $order_status == 'completed'){
			return true;
		} else {
			return false;
		}
		return $value;				
	}
	/*
	* add in batch process
	*/
	function add_in_batch_process( $order_id ){
		global $wpdb;
		$table_name = $wpdb->prefix.'shipment_batch_process';
		$data = array(
			'order_id' => $order_id
		);
		$wpdb->insert( $table_name, $data );
		return $wpdb->insert_id;
	}
	
	/*
	* remove from batch process
	*/
	function remove_from_batch_process( $batch_process_id ){
		global $wpdb;
		$table_name = $wpdb->prefix.'shipment_batch_process';
		$wpdb->delete( $table_name, array( 'id' => $batch_process_id ) );
	}
	
	/*
	* batch process cron func
	* this will run when data in table "shipment_batch_process"
	* if no data cron clear
	*/
	function wc_ast_batch_process_callback(){
		error_reporting(E_ALL); ini_set('display_errors', 1);
		global $wpdb;
		$table_name = $wpdb->prefix.'shipment_batch_process';
		$result = $wpdb->get_results( "SELECT * FROM `{$table_name}` LIMIT 30", ARRAY_A );
		$result = $wpdb->get_results( "SELECT * FROM `{$table_name}` LIMIT 2", ARRAY_A );
		
		foreach( (array)$result as $row ){
			$order_id = $row['order_id'];
			$array = $this->shipment_api_call( $order_id );
			$this->remove_from_batch_process( $row['id'] );
		}
		
		if( count($result) == 0 ){
			wp_clear_scheduled_hook( 'wc_ast_batch_process' );
		}
	}
	
	/*
	* shipment api call
	*/
	function shipment_api_call( $order_id ){
		$array = array();
		
		$api = new WC_Advanced_Shipment_Tracking_Api_Call;
		$service_provider = $this->get_service_provider();
		
		if( $service_provider == 'snapcx' ){
			$array = $api->get_snapcx_apicall( $order_id );
		} elseif( $service_provider == 'aftership' ){
			$array = $api->get_aftership_apicall( $order_id );
		}
		return $array;
	}
	
	/*
	* get selected service provider
	* return ex snapcx
	*/
	public function get_service_provider() {
		if ( empty( $this->service_provider ) ) {
			$this->service_provider = get_option("wc_ast_api_provider");
		}
		return $this->service_provider;
	}
	
	/**
	 * Adds 'shipment_status' column header to 'Orders' page immediately after 'woocommerce-advanced-shipment-tracking' column.
	 *
	 * @param string[] $columns
	 * @return string[] $new_columns
	 */
	function wc_add_order_shipment_status_column_header( $columns ) {
	
		$new_columns = array();
	
		foreach ( $columns as $column_name => $column_info ) {
	
			$new_columns[ $column_name ] = $column_info;
	
			/*if ( 'order_status' === $column_name ) {
				$new_columns['woocommerce-advanced-shipment-tracking'] = __( 'Shipment Tracking', 'woo-advanced-shipment-tracking' );
				$new_columns['shipment_status'] = __( 'Shipment status', 'woo-advanced-shipment-tracking' );
			}*/
			
			if ( 'woocommerce-advanced-shipment-tracking' === $column_name ) {
				//$new_columns['shipment_status_old'] = __( 'Shipment status', 'woo-advanced-shipment-tracking' );
				$new_columns['shipment_status'] = __( 'Shipment status', 'woo-advanced-shipment-tracking' );
			}
		}
		return $new_columns;
	}
	
	/**
	 * Adds 'shipment_status' column content to 'Orders' page.
	 *
	 * @param string[] $column name of column being displayed
	 */
	function wc_add_order_shipment_status_column_content( $column ) {
		global $post;
		if ( 'shipment_status_old' === $column ) {
			
			$shipment_status = get_post_meta( $post->ID, "shipment_status", true);
			
			if( is_array($shipment_status) ){
				foreach( $shipment_status as $data ){
					$status = $data["status"];
					$est_delivery_date = $data["est_delivery_date"];
					echo "<div class='ast-shipment-status shipment-".sanitize_title($status)."' >".apply_filters("trackship_status_filter",$status) . apply_filters( "trackship_status_icon_filter", "", $status )."</div>";
					
					$date = $data["status_date"];
					if( $date ){
						$date = date( "Y-m-d", strtotime($date) );
						echo "<span class=description>on {$date}</span>";
					}
					if( $est_delivery_date ){
						echo "<div>EST Delivery: {$est_delivery_date}</div>";
					}
				}
			}
		}
		
		if ( 'shipment_status' === $column ) {
			
			$ast = new WC_Advanced_Shipment_Tracking_Actions;
			$tracking_items = $ast->get_tracking_items( $post->ID );
			$shipment_status = get_post_meta( $post->ID, "shipment_status", true);
			//echo '<pre>';print_r($shipment_status);echo '</pre>';
			
			if ( count( $tracking_items ) > 0 ) {
				?>
                	<ul class="wcast-shipment-status-list">
                    	<?php foreach ( $tracking_items as $key => $tracking_item ) { 
								if( !isset($shipment_status[$key]) ){
									echo '<li class="tracking-item-'.$tracking_item['tracking_id'].'"></li>';continue;
								}
								$has_est_delivery = false;
								$status = $shipment_status[$key]['status'];
								$status_date = $shipment_status[$key]['status_date'];
								if(isset($shipment_status[$key]['est_delivery_date'])){
									$est_delivery_date = $shipment_status[$key]['est_delivery_date'];
								}
								if( $status != 'delivered' && $status != 'return_to_sender' && !empty($est_delivery_date) ){
									$has_est_delivery = true;
								}								
                            ?>
                            <li id="tracking-item-<?php echo $tracking_item['tracking_id'];?>" class="tracking-item-<?php echo $tracking_item['tracking_id'];?>">
                            	<div class="wcast-shipment-status-icon">
                                	<?php echo apply_filters( "trackship_status_icon_filter", "", $status );?>
                                </div>
                                <div class="ast-shipment-status shipment-<?php echo sanitize_title($status)?> has_est_delivery_<?php echo ( $has_est_delivery ? 1 : 0 )?>">
									<span class="ast-shipment-tracking-status"><?php echo apply_filters("trackship_status_filter",$status);?></span>
									<span class="showif_has_est_delivery_1 ft11">(<?php echo date( "d/m", strtotime($status_date))?>)</span>
									<span class="showif_has_est_delivery_0 ft11">on <?php echo date( "d/m", strtotime($status_date))?></span>
                                    <?php if( $has_est_delivery){?>
                                    	<span class="wcast-shipment-est-delivery ft11">Est. Delivery(<?php echo date( "d/m", strtotime($est_delivery_date))?>)</span>
									<?php } ?>
                                </div>
                            </li>
						<?php } ?>
                    </ul>
				<?php
			} else {
				echo '–';
			}
		}
	}
	
	/**
	* Load admin styles.
	*/
	public function admin_styles($hook) {
		
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		
		wp_enqueue_style( 'font-awesome',  wc_advanced_shipment_tracking()->plugin_dir_url() . 'assets/css/font-awesome.min.css', array(), '4.7' );

		wp_register_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2.full' . $suffix . '.js', array( 'jquery' ), '4.0.3' );
		wp_enqueue_script( 'select2');
		
		wp_enqueue_style( 'shipment_tracking_styles',  wc_advanced_shipment_tracking()->plugin_dir_url() . 'assets/css/admin.css', array(), wc_advanced_shipment_tracking()->version );
		
		wp_enqueue_script( 'woocommerce-advanced-shipment-tracking-js', wc_advanced_shipment_tracking()->plugin_dir_url() . 'assets/js/admin.js', array( 'jquery' ), wc_advanced_shipment_tracking()->version);
		
		wp_localize_script( 'woocommerce-advanced-shipment-tracking-js', 'ast_admin_js', array(
			'i18n' => array(
				'get_shipment_status_message' => __( 'Get Shipment Status is limited to 100 orders at a time, please select up to 100 orders.', 'woo-advanced-shipment-tracking' ),
			),			
		) );
		
		if(!isset($_GET['page'])) {
			return;
		}
		if( $_GET['page'] != 'woocommerce-advanced-shipment-tracking') {
			return;
		}
			
		

		
		wp_register_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.4' );
		wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), WC_VERSION );
		wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		
		wp_enqueue_script( 'selectWoo');
		wp_enqueue_script( 'wc-enhanced-select');
		
		wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		wp_enqueue_style( 'woocommerce_admin_styles' );
		
		wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), WC_VERSION, true );
		wp_enqueue_script( 'jquery-tiptip' );
		wp_enqueue_script( 'jquery-blockui' );
		wp_enqueue_script( 'wp-color-picker' );		
		wp_enqueue_script( 'jquery-ui-sortable' );
		//wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');		
		wp_enqueue_style('thickbox');
		wp_enqueue_style( 'checkbox-slider',  wc_advanced_shipment_tracking()->plugin_dir_url() . 'assets/css/checkbox-slider.css' );
		
		wp_enqueue_style( 'material-css',  wc_advanced_shipment_tracking()->plugin_dir_url() . 'assets/css/material.css', array(), wc_advanced_shipment_tracking()->version );		
		wp_enqueue_script( 'material-js', wc_advanced_shipment_tracking()->plugin_dir_url().'assets/js/material.min.js', array( 'jquery' ), wc_advanced_shipment_tracking()->version );						
		
		wp_enqueue_script( 'ajax-queue', wc_advanced_shipment_tracking()->plugin_dir_url().'assets/js/jquery.ajax.queue.js', array( 'jquery' ), wc_advanced_shipment_tracking()->version);
				
		wp_enqueue_script( 'advanced_shipment_tracking_settings', wc_advanced_shipment_tracking()->plugin_dir_url().'assets/js/settings.js', array( 'jquery' ), wc_advanced_shipment_tracking()->version );		
		
		wp_register_script( 'shipment_tracking_table_rows', wc_advanced_shipment_tracking()->plugin_dir_url().'assets/js/shipping_row.js' , array( 'jquery', 'wp-util' ), wc_advanced_shipment_tracking()->version );
		wp_localize_script( 'shipment_tracking_table_rows', 'shipment_tracking_table_rows', array(
			'i18n' => array(
				'order'			=> __( 'Order', 'woo-advanced-shipment-tracking' ),
				'item'			=> __( 'Item', 'woo-advanced-shipment-tracking' ),
				'line_item'		=> __( 'Line Item', 'woo-advanced-shipment-tracking' ),
				'class'			=> __( 'Class', 'woo-advanced-shipment-tracking' ),
				'delete_rates'	=> __( 'Delete the selected rates?', 'woo-advanced-shipment-tracking' ),
				'dupe_rates'	=> __( 'Duplicate the selected rates?', 'woo-advanced-shipment-tracking' ),
				'provider_status_alert'   => __( 'Really want to bulk change all provider status?', 'woo-advanced-shipment-tracking' ),
				'data_saved'	=> __( 'Data saved successfully.', 'woo-advanced-shipment-tracking' ),
				'delete_provider' => __( 'Really delete this entry? This will not be undo.', 'woo-advanced-shipment-tracking' ),
				'upload_only_csv_file' => __( 'You can upload only csv file.', 'woo-advanced-shipment-tracking' ),
				'browser_not_html' => __( 'This browser does not support HTML5.', 'woo-advanced-shipment-tracking' ),
				'upload_valid_csv_file' => __( 'Please upload a valid CSV file.', 'woo-advanced-shipment-tracking' ),
			),
			'delete_rates_nonce' => wp_create_nonce( "delete-rate" ),
		) );
		if (isset($_GET['page']) && $_GET['page'] == 'woocommerce-advanced-shipment-tracking') {
			wp_enqueue_media();			
		}
	}
	
	/*
	* Admin Menu add function
	* WC sub menu
	*/
	public function register_woocommerce_menu() {
		add_submenu_page( 'woocommerce', 'Shipment Tracking', 'Shipment Tracking', 'manage_options', 'woocommerce-advanced-shipment-tracking', array( $this, 'woocommerce_advanced_shipment_tracking_page_callback' ) ); 
	}
	public function sortByCountryAsc($a, $b) {
		return strcmp($a->country, $b->country);
	}
	public function sortByCountryDesc($a, $b) {
		return strcmp($b->country, $a->country);
	}
	/*
	* callback for Shipment Tracking page
	*/
	public function woocommerce_advanced_shipment_tracking_page_callback(){		  
		global $order;
		$WC_Countries = new WC_Countries();
		$countries = $WC_Countries->get_countries();
		
		global $wpdb;
		$woo_shippment_table_name = $this->table;			
		
		$default_shippment_providers = $wpdb->get_results( "SELECT * FROM $woo_shippment_table_name WHERE display_in_order = 1" );

		foreach($default_shippment_providers as $key => $value){			
			$search  = array('(US)', '(UK)');
			$replace = array('', '');
			if($value->shipping_country && $value->shipping_country != 'Global'){
				$country = str_replace($search, $replace, $WC_Countries->countries[$value->shipping_country]);
				$default_shippment_providers[$key]->country = $country;			
			} elseif($value->shipping_country && $value->shipping_country == 'Global'){
				$default_shippment_providers[$key]->country = 'Global';
			}
		}	
		$checked = '';	
		if(isset($_GET['tab'])){
			if($_GET['tab'] == 'settings'){
					
			}
		}
		wp_enqueue_script( 'shipment_tracking_table_rows' );		
		 ?>		
			<h1 class="plugin-title"><?php _e('Advanced Shipment Tracking', 'woo-advanced-shipment-tracking'); ?></h1>
            <div class="wrap woocommerce zorem_admin_layout">
                <div class="ast_admin_content" >
                    
                    <input id="tab1" type="radio" name="tabs" class="tab_input"  <?php if(!isset($_GET['tab'])){ echo 'checked'; } ?> >
                    <label for="tab1" class="tab_label"><?php _e('Shipping Providers', 'woo-advanced-shipment-tracking'); ?></label>
                    
                    <input id="tab2" type="radio" name="tabs" class="tab_input" <?php if(isset($_GET['tab']) && ($_GET['tab'] == 'settings' || $_GET['tab'] == 'delivered-order-status')){ echo 'checked'; } ?>>
                    <label for="tab2" class="tab_label"><?php _e('Settings', 'woo-advanced-shipment-tracking'); ?></label>
					
					<input id="tab4" type="radio" name="tabs" class="tab_input" <?php if(isset($_GET['tab']) && $_GET['tab'] == 'bulk-upload'){ echo 'checked'; } ?>>					
					<label for="tab4" class="tab_label"><?php _e('Bulk Upload', 'woo-advanced-shipment-tracking'); ?></label>
					
					<input id="tab3" type="radio" name="tabs" class="tab_input" <?php if(isset($_GET['tab']) && ($_GET['tab'] == 'trackship' || $_GET['tab'] == 'tracking-page' || $_GET['tab'] == 'shipment-status-notifications')){ echo 'checked'; } ?>>
                    <label for="tab3" class="tab_label"><?php _e('TrackShip', 'woo-advanced-shipment-tracking'); ?></label>
                    
                    <?php require_once( 'views/admin_options_shipping_provider.php' );?>
					<?php require_once( 'views/admin_options_settings.php' );?>
					<?php require_once( 'views/admin_options_trackship_integration.php' );?>
					<?php require_once( 'views/admin_options_bulk_upload.php' );?>					
                </div>
				<?php //require_once( 'views/zorem_admin_sidebar.php' );?>
            </div>            
			<div id="demo-toast-example" class="mdl-js-snackbar mdl-snackbar">
				<div class="mdl-snackbar__text"></div>
				<button class="mdl-snackbar__action" type="button"></button>
			</div>		
	<?php
		if(isset( $_GET['tab'] ) && $_GET['tab'] == 'trackship'){ ?>
			<script>
			jQuery("#tab3").trigger('click');
			</script>
		<?php }	
	}
	
	/*
	* get html of fields
	*/
	private function get_html( $arrays ){
		
		$checked = '';
		?>
		<table class="form-table">
			<tbody>
            	<?php foreach( (array)$arrays as $id => $array ){
				
					if($array['show']){
					?>
                	<?php if($array['type'] == 'title'){ ?>
                		<tr valign="top titlerow">
                        	<th colspan="2"><h3><?php echo $array['title']?></h3></th>
                        </tr>    	
                    <?php continue;} ?>
				<tr valign="top" class="<?php echo $array['class']; ?>">
					<?php if($array['type'] != 'desc'){ ?>										
					<th scope="row" class="titledesc"  >
						<label for=""><?php echo $array['title']?><?php if(isset($array['title_link'])){ echo $array['title_link']; } ?>
							<?php if( isset($array['tooltip']) ){?>
                            	<span class="woocommerce-help-tip tipTip" title="<?php echo $array['tooltip']?>"></span>
                            <?php } ?>
                        </label>
					</th>
					<?php } ?>
					<td class="forminp"  <?php if($array['type'] == 'desc'){ ?> colspan=2 <?php } ?>>
                    	<?php if( $array['type'] == 'checkbox' ){								
							if($id === 'wcast_enable_delivered_email'){
								$wcast_enable_delivered_email = get_option('woocommerce_customer_delivered_order_settings');
								if($wcast_enable_delivered_email['enabled'] === 'yes'){
									$checked = 'checked';
								} else{
									$checked = '';
								}								
							} else{								
								if(get_option($id)){
									$checked = 'checked';
								} else{
									$checked = '';
								} 
							} 
							
							if(isset($array['disabled']) && $array['disabled'] == true){
								$disabled = 'disabled';
								$checked = '';
							} else{
								$disabled = '';
							}							
							?>
						<span class="mdl-list__item-secondary-action">
							<label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="<?php echo $id?>">
								<input type="hidden" name="<?php echo $id?>" value="0"/>
								<input type="checkbox" id="<?php echo $id?>" name="<?php echo $id?>" class="mdl-switch__input" <?php echo $checked ?> value="1" <?php echo $disabled; ?>/>
							</label>
						</span>
                        <?php } elseif( $array['type'] == 'multiple_checkbox' ){ ?>
								<?php foreach((array)$array['options'] as $key => $val ){
										$multi_checkbox_data = get_option($id);
										if(!$multi_checkbox_data){
											$multi_checkbox_data = array();
											$multi_checkbox_data['show_in_completed'] = 1;
											$data_array = array('show_in_completed' => 1);
											update_option( 'wc_ast_unclude_tracking_info', $data_array ); 
										}											
										if(isset($multi_checkbox_data[$key])){
											$checked="checked";
										} else{
											$checked="";
										}?>
								<span class="mdl-list__item-secondary-action multiple_checkbox">
									<label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="<?php echo $key?>">	
										<input type="checkbox" id="<?php echo $key?>" name="<?php echo $id?>[<?php echo $key?>]" class="mdl-switch__input"  <?php echo $checked; ?> value="1"/>
										<span class="multiple_label"><?php echo $val; ?></span>	
										<br>
									</label>																		
								</span>	
															
                                  <?php } ?>
						
                        <?php }  elseif( isset( $array['type'] ) && $array['type'] == 'dropdown' ){?>
                        	<?php
								if( isset($array['multiple']) ){
									$multiple = 'multiple';
									$field_id = $array['multiple'];
								} else {
									$multiple = '';
									$field_id = $id;
								}
							?>
                        	<fieldset>
								<select class="select select2" id="<?php echo $field_id?>" name="<?php echo $id?>" <?php echo $multiple;?>>    <?php foreach((array)$array['options'] as $key => $val ){?>
                                    	<?php
											$selected = '';
											if( isset($array['multiple']) ){
												if (in_array($key, (array)$this->data->$field_id ))$selected = 'selected';
											} else {
												if( get_option($id) == (string)$key )$selected = 'selected';
											}
                                        
										?>
										<option value="<?php echo $key?>" <?php echo $selected?> ><?php echo $val?></option>
                                    <?php } ?>
								</select>
							</fieldset>
                        <?php } elseif( $array['type'] == 'title' ){?>
						<?php }
						elseif( $array['type'] == 'key_field' ){ ?>
							<fieldset>
                                <!--input class="input-text regular-input " type="text" name="<?php echo $id?>" id="<?php echo $id?>" style="" value="<?php echo get_option($id)?>" placeholder="" readonly-->
								<?php if($array['connected'] == true){ ?>
									<a href="https://my.trackship.info/" target="_blank">
										<span class="api_connected"><label><?php _e( 'Connected', 'woo-advanced-shipment-tracking' ); ?></label><span class="dashicons dashicons-yes"></span></span></a>
								<?php } ?>								
                            </fieldset>
						<?php }
						elseif( $array['type'] == 'desc' ){ ?>
							<fieldset>
								<p><?php _e( 'Auto-track all your shipments, get real-time shipment tracking updates without leaving your stores admin.', 'woo-advanced-shipment-tracking' ); ?></p>
								<p><?php 
								$url = '<a href="https://trackship.info/my-account" target="blank">TrackShip</a>';
								echo sprintf(__("You must have account and connect your store to %s in order to activate these advanced features.", 'woo-advanced-shipment-tracking'), $url); ?></p>
								<p><?php 
								$url = '<a href="https://trackship.info/my-account" target="blank">Trackship</a>';
								echo sprintf(__("50 free Trackers for every new account! Get your %s account now>>", 'woo-advanced-shipment-tracking'), $url); ?></p>	
                            </fieldset>
						<?php } 
						elseif( $array['type'] == 'label' ){ ?>
							<fieldset>
                               <label><?php echo $array['value']; ?></label>
                            </fieldset>
						<?php }
						elseif( $array['type'] == 'tooltip_button' ){ ?>
							<fieldset>
								<a href="<?php echo $array['link']; ?>" class="button-primary" target="<?php echo $array['target'];?>"><?php echo $array['link_label'];?></a>
                            </fieldset>
						<?php }
						elseif( $array['type'] == 'button' ){ ?>
							<fieldset>
								<button class="button-primary btn_green <?php echo $array['button_class'];?>"><?php echo $array['label'];?></button>
							</fieldset>
						<?php }
						else { ?>
                                                    
                        	<fieldset>
                                <input class="input-text regular-input " type="text" name="<?php echo $id?>" id="<?php echo $id?>" style="" value="<?php echo get_option($id)?>" placeholder="<?php if(!empty($array['placeholder'])){echo $array['placeholder'];} ?>">
                            </fieldset>
                        <?php } ?>
                        
					</td>
				</tr>
				<?php if(isset($array['desc']) && $array['desc'] != ''){ ?>
					<tr class="<?php echo $array['class']; ?>"><td colspan="2" style=""><p class="description"><?php echo (isset($array['desc']))? $array['desc']: ''?></p></td></tr>
				<?php } ?>				
	<?php } } ?>
			</tbody>
		</table>
	<?php }

	/*
	* get settings tab array data
	* return array
	*/
	function get_trackship_general_data(){		
		$wc_ast_api_key = get_option('wc_ast_api_key');
		$trackers_balance = get_option( 'trackers_balance' );
		$wc_ast_status_delivered = get_option( 'wc_ast_status_delivered' );
		if($wc_ast_api_key){
			$connected = true;
			$show_trackship_field = true;
			$show_trackship_description = false;
		} else{
			$connected = false;
			$show_trackship_field = false;
			$show_trackship_description = true;
		}	
		if($wc_ast_status_delivered){
			$disabled_change_to_delivered = false;
		} else{
			$disabled_change_to_delivered = true;
		}
		$page_list = wp_list_pluck( get_pages(), 'post_title', 'ID' );
		
		// Get orders completed.
		$args = array(
			'status' => 'wc-completed',
			'limit'	 => -1,	
			'date_created' => '>' . ( time() - 2592000 ),
		);
		$orders = wc_get_orders( $args );
		$completed_order_with_tracking = 0;
		foreach($orders as $order){
			$order_id = $order->get_id();
			
			$ast = new WC_Advanced_Shipment_Tracking_Actions;
			$tracking_items = $ast->get_tracking_items( $order_id, true );
			if($tracking_items){
				$shipment_status = get_post_meta( $order_id, "shipment_status", true);
				foreach ( $tracking_items as $key => $tracking_item ) { 
					if( !isset($shipment_status[$key]) ){						
						$completed_order_with_tracking++;		
					}
				}									
			}			
		}
		
		if($completed_order_with_tracking > 0){
			$show_bulk_sync = true;
		} else{
			$show_bulk_sync = false;
		}

		$wc_ast_status_shipped = get_option('wc_ast_status_shipped');
		if($wc_ast_status_shipped == 1){
			$completed_order_label = '<span class="shipped_label">shipped</span>';			
		} else{
			$completed_order_label = '<span class="shipped_label">completed</span>';			
		}		
		//echo '<pre>';print_r($completed_order_with_tracking);echo '</pre>';
		$form_data = array(
			'wc_ast_trackship_connection_status' => array(
				'type'		=> 'key_field',
				'title'		=> __( 'TrackShip Connection Status', 'woo-advanced-shipment-tracking' ),
				'connected' => $connected,	
				'show' => $show_trackship_field,
				'class'     => '',
			),
			'wc_ast_api_enabled' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Enable/Disable', 'woo-advanced-shipment-tracking' ),							
				'show' => $show_trackship_field,
				'class'     => '',
			),						
			'wc_ast_status_change_to_delivered' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Set order status Delivered when order is delivered', 'woo-advanced-shipment-tracking' ),				
				'show' => $show_trackship_field,
				'class'     => '',
				'disabled'  => $disabled_change_to_delivered,
			),			
			'wc_ast_bulk_shipment_status' => array(
				'type'		=> 'button',
				'title'		=> sprintf(__('You have %s %s orders from the last 30 days, would you like to bulk send all these orders to TrackShip?', 'woo-advanced-shipment-tracking'), $completed_order_with_tracking , $completed_order_label),
				'label' => __( 'Get Shipment Status', 'woo-advanced-shipment-tracking' ),
				'show' => $show_bulk_sync,
				'button_class'     => 'bulk_shipment_status_button',
				'class'     => '',
			),
			'wc_ast_trackship_description' => array(
				'type'		=> 'desc',
				'title'		=> '',				
				'show' => $show_trackship_description,
				'class'     => '',
			),
		);
		return $form_data;

	}
	/*
	* get settings tab array data
	* return array
	*/
	function get_trackship_page_data(){		
		$wc_ast_api_key = get_option('wc_ast_api_key');
		$trackers_balance = get_option( 'trackers_balance' );
		if($wc_ast_api_key){
			$connected = true;
			$show_trackship_field = true;
			$show_trackship_description = false;
		} else{
			$connected = false;
			$show_trackship_field = false;
			$show_trackship_description = true;
		}
			
		$page_list = wp_list_pluck( get_pages(), 'post_title', 'ID' );
		$wc_ast_trackship_page_id = get_option('wc_ast_trackship_page_id');
		$post = get_post($wc_ast_trackship_page_id); 
		$slug = $post->post_name;
		
		if($slug != 'ts-shipment-tracking'){
			$page_desc = __( 'You must add the shortcode [wcast-track-order] to the "page name" in order for the tracking page to work.', 'woo-advanced-shipment-tracking' );
		} else{
			$page_desc = '';
		}		
		
		$form_data = array(			
			'wc_ast_trackship_page_id' => array(
				'type'		=> 'dropdown',
				'title'		=> __( 'Select Tracking Page', 'woo-advanced-shipment-tracking' ),
				'options'   => $page_list,				
				'show' => $show_trackship_field,
				'desc' => $page_desc,
				'class'     => '',
			),	
			'wc_ast_remove_trackship_branding' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Remove Trackship branding from tracking page', 'woo-advanced-shipment-tracking' ),				
				'show' => $show_trackship_field,
				'class'     => '',
			),
			'wc_ast_use_tracking_page' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Use the tracking page in the customer email/my account tracking link', 'woo-advanced-shipment-tracking' ),				
				'show' => $show_trackship_field,
				'class'     => '',
			),
			'wc_ast_select_primary_color' => array(
				'type'		=> 'color',
				'title'		=> __( 'Select primary color for tracking page', 'woo-advanced-shipment-tracking' ),				
				'class'		=> 'color_field',
				'show' => $show_trackship_field,				
			),			
			'wc_ast_select_border_color' => array(
				'type'		=> 'color',
				'title'		=> __( 'Select content border color for tracking page', 'woo-advanced-shipment-tracking' ),				
				'class'		=> 'color_field',
				'show' => $show_trackship_field,				
			),				
		);
		return $form_data;

	}

	/*
	* settings form save
	*/
	function wc_ast_trackship_form_update_callback(){
		
		if ( ! empty( $_POST ) && check_admin_referer( 'wc_ast_trackship_form', 'wc_ast_trackship_form' ) ) {
			
			$data1 = $this->get_trackship_page_data();
			$data2 = $this->get_trackship_general_data();			
			
			foreach( $data1 as $key1 => $val1 ){
				update_option( $key1, sanitize_text_field( $_POST[ $key1 ] ) );
			}
			foreach( $data2 as $key2 => $val2 ){
				update_option( $key2, sanitize_text_field( $_POST[ $key2 ] ) );
			}
			echo json_encode( array('success' => 'true') );die();

		}
	}
	
	/*
	* trigger when order status changed to shipped or completed or update tracking
	* param $order_id
	*/	
	function trigger_woocommerce_order_status_completed( $order_id ){
		
		//error_log( "Order complete for order $order_id", 0 );
		$order = wc_get_order( $order_id );
		$order_shipped = apply_filters( 'is_order_shipped', true, $order );
		
		//error_log( "Order shipped :  $order_shipped", 0 );
		if( $order_shipped ){
			$api_enabled = get_option( "wc_ast_api_enabled", 0);
			if( $api_enabled ){
				$api = new WC_Advanced_Shipment_Tracking_Api_Call;
				$array = $api->get_trackship_apicall( $order_id );				
			}
		}
	}
	
	/*
	* get settings tab array data
	* return array
	*/
	function get_settings_data(){
		if ( is_plugin_active( 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php' ) ) {
			$show_invoice_field = true;
		} else{
			$show_invoice_field = false;
		}

		$wc_ast_status_shipped = get_option('wc_ast_status_shipped');
		if($wc_ast_status_shipped == 1){
			$completed_order_label = __( 'Shipped', 'woo-advanced-shipment-tracking' );	
			$mark_as_shipped_label = __( 'Default "mark as <span class="shipped_label">shipped</span>" checkbox state', 'woo-advanced-shipment-tracking' );	
			$mark_as_shipped_tooltip = __( "This means that the 'mark as <span class='shipped_label'>shipped</span>' will be selected by default when adding tracking info to orders.", 'woo-advanced-shipment-tracking' );				
		} else{
			$completed_order_label = __( 'Completed', 'woo-advanced-shipment-tracking' );
			$mark_as_shipped_label = __( 'Default "mark as <span class="shipped_label">completed</span>" checkbox state', 'woo-advanced-shipment-tracking' );
			$mark_as_shipped_tooltip = __( "This means that the 'mark as <span class='shipped_label'>completed</span>' will be selected by default when adding tracking info to orders.", 'woo-advanced-shipment-tracking' );	
		}
		
		$form_data = array(
			'wc_ast_status_shipped' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Rename the “Completed” Order status to “Shipped”', 'woo-advanced-shipment-tracking' ),				
				'show'		=> true,
				'class'     => '',
			),
			'wc_ast_default_mark_shipped' => array(
				'type'		=> 'checkbox',
				'title'		=> $mark_as_shipped_label,
				'tooltip'      => $mark_as_shipped_tooltip,
				'show'		=> true,
				'class'     => '',
			),
			'wc_ast_unclude_tracking_info' => array(
				'type'		=> 'multiple_checkbox',
				'title'		=> __( 'On which customer order status email to include tracking info?', 'woo-advanced-shipment-tracking' ),'options'   => array( 
									"show_in_cancelled" =>__( 'Cancelled', 'woo-advanced-shipment-tracking' ),
									"show_in_customer_invoice" =>__( 'Customer Invoice', 'woo-advanced-shipment-tracking' ),
									"show_in_refunded" =>__( 'Refunded', 'woo-advanced-shipment-tracking' ),
									"show_in_processing" =>__( 'Processing', 'woo-advanced-shipment-tracking' ),
									"show_in_failed" =>__( 'Failed', 'woo-advanced-shipment-tracking' ),
									"show_in_completed" =>__( $completed_order_label, 'woo-advanced-shipment-tracking' ),
								),						
				'show'		=> true,
				'class'     => '',
			),		
			'wc_ast_show_tracking_invoice' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Show tracking info in Invoice', 'woo-advanced-shipment-tracking' ),				
				'show'		=> $show_invoice_field,
				'class'     => '',
			),
			'wc_ast_show_tracking_packing_slip' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Show tracking info in Packing Slip', 'woo-advanced-shipment-tracking' ),				
				'show'		=> $show_invoice_field,
				'class'     => '',
			),			
		);
		return $form_data;

	}

	/*
	* get settings tab array data
	* return array
	*/
	function get_delivered_data(){		
		$form_data = array(			
			'wc_ast_status_delivered' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Enable a New Custom order status -  “Delivered”', 'woo-advanced-shipment-tracking' ),
				'tooltip'		=> __( 'if you enable the delivered item, you will have the option to send delivered email notifications.', 'woo-advanced-shipment-tracking' ),
				'show'		=> true,
				'class'     => '',
			),			
			'wc_ast_status_label_color' => array(
				'type'		=> 'color',
				'title'		=> __( 'Delivered Status Label color', 'woo-advanced-shipment-tracking' ),				
				'class'		=> 'status_label_color_th',
				'show'		=> true,
			),
			'wcast_enable_delivered_email' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Delivered order status email', 'woo-advanced-shipment-tracking' ),
				'title_link'=> "<a class='settings_edit' href='".wcast_initialise_customizer_email::get_customizer_url('customer_delivered_email')."'>".__( 'Edit', 'woo-advanced-shipment-tracking' )."</a>",
				'class'		=> 'status_label_color_th',
				'show'		=> true,
			),			
		);
		return $form_data;

	}

	/*
	* get settings tab array data
	* return array
	*/
	function get_uninstall_data(){		
		$form_data = array(				
			'wc_ast_deactivate_delivered' => array(
				'type'		=> 'dropdown',
				'title'		=> __( 'Change the "Delivered" orders to "Completed" when you deactivate the plugin', 'woo-advanced-shipment-tracking' ),				
				'options'   => array( 
									"no" =>__( 'No, I will use the snippet', 'woo-advanced-shipment-tracking' ),
									"yes" =>__( 'Yes, change all Delivered orders to Completed', 'woo-advanced-shipment-tracking' ),
									),
				'desc'		=> sprintf(__('PLEASE NOTE - If you use the custom order status "Delivered", when you deactivate the plugin, you must register this order status in function.php in order to see these orders in the orders admin. You can find the snippet to use in functions.php %s or we can set to change all your "delivered" order to "completed".', 'woo-advanced-shipment-tracking'), '<a href="https://www.zorem.com/docs/woocommerce-advanced-shipment-tracking/code-snippets/#delivered_code" target="blank">here</a>'),				
				'show'		=> true,
				'class'     => 'status_label_color_th',
			),			
		);
		return $form_data;

	}	
	
	/*
	* settings form save
	*/
	function wc_ast_settings_form_update_callback(){			
		if ( ! empty( $_POST ) && check_admin_referer( 'wc_ast_settings_form', 'wc_ast_settings_form' ) ) {

			$data = $this->get_settings_data();						
			
			foreach( $data as $key => $val ){								
				update_option( $key, $_POST[ $key ] );				
			}
			
			$data = $this->get_delivered_data();						
			
			foreach( $data as $key => $val ){
				if($key == 'wcast_enable_delivered_email'){
					$wcast_enable_delivered_email = get_option('woocommerce_customer_delivered_order_settings'); 
					if($_POST['wcast_enable_delivered_email'] == 1){
						$enabled = 'yes';
					} else{
						$enabled = 'no';
					}
					$opt = array(
						'enabled' => $enabled,
						'subject' => $wcast_enable_delivered_email['subject'],
						'heading' => $wcast_enable_delivered_email['heading'],
					);
					update_option( 'woocommerce_customer_delivered_order_settings', $opt );				
				}
					update_option( $key, $_POST[ $key ] );				
			}
						
			echo json_encode( array('success' => 'true') );die();

		}
	}		
	
	

	
	function footer_function(){
		$color = get_option('wc_ast_status_label_color');
		?>
		<style>
		.order-status.status-delivered{
			    background: <?php echo $color; ?>;
		}
		</style>
		<?php
	}	
	
	function upload_tracking_csv_fun(){
		
		$wast = WC_Advanced_Shipment_Tracking_Actions::get_instance();
		
		$replace_tracking_info = $_POST['replace_tracking_info'];
		$order_id = $_POST['order_id'];			
		
		if ( is_plugin_active( 'custom-order-numbers-for-woocommerce/custom-order-numbers-for-woocommerce.php' ) ) {
			$alg_wc_custom_order_numbers_enabled = get_option('alg_wc_custom_order_numbers_enabled');
			if($alg_wc_custom_order_numbers_enabled == 'yes'){
				$args = array(
					'post_type'		=>	'shop_order',			
					'posts_per_page'    => '1',
					'meta_query'        => array(
						'relation' => 'AND', 
						array(
						'key'       => '_alg_wc_custom_order_number',
						'value'     => $order_id
						),
					),
					'post_status' => array('wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-delivered', 'wc-cancelled', 'wc-refunded', 'wc-failed','wc-bit-payment') , 	
				);
				$posts = get_posts( $args );
				$my_query = new WP_Query( $args );				
				
				if( $my_query->have_posts() ) {
					while( $my_query->have_posts()) {
						$my_query->the_post();
						if(get_the_ID()){
							$order_id = get_the_ID();
						}									
					} // end while
				} // end if
				wp_reset_postdata();	
			}			
		}
		
		if ( is_plugin_active( 'woocommerce-sequential-order-numbers/woocommerce-sequential-order-numbers.php' ) ) {
						
			$s_order_id = wc_sequential_order_numbers()->find_order_by_order_number( $order_id );			
			if($s_order_id){
				$order_id = $s_order_id;
			}
		}
		
		if ( is_plugin_active( 'wp-lister-amazon/wp-lister-amazon.php' ) ) {
			$wpla_use_amazon_order_number = get_option( 'wpla_use_amazon_order_number' );
			if($wpla_use_amazon_order_number == 1){
				$args = array(
					'post_type'		=>	'shop_order',			
					'posts_per_page'    => '1',
					'meta_query'        => array(
						'relation' => 'AND', 
						array(
						'key'       => '_wpla_amazon_order_id',
						'value'     => $order_id
						),
					),
					'post_status' => array('wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-delivered', 'wc-cancelled', 'wc-refunded', 'wc-failed','wc-bit-payment') , 	
				);
				$posts = get_posts( $args );
				$my_query = new WP_Query( $args );				
				
				if( $my_query->have_posts() ) {
					while( $my_query->have_posts()) {
						$my_query->the_post();
						if(get_the_ID()){
							$order_id = get_the_ID();
						}									
					} // end while
				} // end if
				wp_reset_postdata();	
			}			
		}	
		
		if ( is_plugin_active( 'wp-lister/wp-lister.php' ) || is_plugin_active( 'wp-lister-for-ebay/wp-lister.php' )) {
			$args = array(
				'post_type'		=>	'shop_order',			
				'posts_per_page'    => '1',
				'meta_query'        => array(
					'relation' => 'AND', 
					array(
					'key'       => '_ebay_extended_order_id',
					'value'     => $order_id
					),
				),
				'post_status' => array('wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-delivered', 'wc-cancelled', 'wc-refunded', 'wc-failed','wc-bit-payment') , 	
			);
			$posts = get_posts( $args );
			$my_query = new WP_Query( $args );				
			
			if( $my_query->have_posts() ) {
				while( $my_query->have_posts()) {
					$my_query->the_post();
					if(get_the_ID()){
						$order_id = get_the_ID();
					}									
				} // end while
			} // end if
			wp_reset_postdata();
		}
		
		$tracking_provider = $_POST['tracking_provider'];
		$tracking_number = $_POST['tracking_number'];
		$date_shipped = str_replace("/","-",$_POST['date_shipped']);
		if(empty($date_shipped)){
			$date_shipped = date("d-m-Y");
		}
		$replace_tracking_info = $_POST['replace_tracking_info'];
		
		global $wpdb;	
		$woo_shippment_table_name = $this->table;		
		$shippment_provider = $wpdb->get_var( "SELECT COUNT(*) FROM $woo_shippment_table_name WHERE provider_name = '".$tracking_provider."'" );
		
		if($shippment_provider == 0){
			echo '<li class="error">Failed - Invalid Tracking Provider for Order Id - '.$_POST['order_id'].'</li>';exit;
		}
		if(empty($tracking_number)){
			echo '<li class="error">Failed - Empty Tracking Number for Order Id - '.$_POST['order_id'].'</li>';exit;
		}
		if(empty($date_shipped)){
			echo '<li class="error">Failed - Empty Date Shipped for Order Id - '.$_POST['order_id'].'</li>';exit;
		}	
		if(!$this->isDate($date_shipped)){
			echo '<li class="error">Failed - Invalid Date Shipped for Order Id - '.$_POST['order_id'].'</li>';exit;
		}	
		
		if($replace_tracking_info == 1){
			$order = wc_get_order($order_id);
			
			if($order){	
				$tracking_items = $wast->get_tracking_items( $order_id );			
				
				if ( count( $tracking_items ) > 0 ) {
					foreach ( $tracking_items as $key => $item ) {
						$tracking_number = $item['tracking_number'];						
						if(in_array($tracking_number, array_column($_POST['trackings'], 'tracking_number'))) {
							
						} else{
							unset( $tracking_items[ $key ] );
						}											
					}
					$wast->save_tracking_items( $order_id, $tracking_items );
				}
			}
		}
		if($tracking_provider && $tracking_number && $date_shipped){

			$args = array(
				'tracking_provider'     => wc_clean( sanitize_title($_POST['tracking_provider']) ),					
				'tracking_number'       => wc_clean( $_POST['tracking_number'] ),
				'date_shipped'          => wc_clean( $_POST['date_shipped'] ),
				'status_shipped'		=> wc_clean( $_POST['status_shipped'] ),
			);
			$order = wc_get_order($order_id);
					
			if ( $order === false ) {
				echo '<li class="error">Failed - Invalid Order Id - '.$_POST['order_id'].'</li>';exit;
			} else{
				$wast->add_tracking_item( $order_id, $args );
				echo '<li class="success">Success - Successfully added tracking info for Order Id- '.$_POST['order_id'].'</li>';
				exit;
			}
			
		} else{
			echo '<li class="error">Failed - Invalid Tracking Data</li>';exit;
		}	
	}
	/**
	* Check if the value is a valid date
	*
	* @param mixed $value
	*
	* @return boolean
	*/
	function isDate($value) 
	{
		if (!$value) {
			return false;
		}
	
		try {
			new \DateTime($value);
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}
	
	/*
	* add bulk action
	* Change order status to delivered
	*/
	function add_bulk_actions_get_shipment_status($bulk_actions){
		$bulk_actions['get_shipment_status'] = 'Get Shipment status';
		return $bulk_actions;
	}
	
	function get_shipment_status_handle_bulk_action_edit_shop_order( $redirect_to, $action, $post_ids ){
		
		if ( $action !== 'get_shipment_status' )
			return $redirect_to;
	
		$processed_ids = array();
		
		$order_count = count($post_ids);
		
		if($order_count > 100){
			return $redirect_to;
		}
		
		foreach ( $post_ids as $post_id ) {
						
			wp_schedule_single_event( time() + 1, 'wcast_retry_trackship_apicall', array( $post_id ) );			
			$processed_ids[] = $post_id;
			
		}
	
		return $redirect_to = add_query_arg( array(
			'get_shipment_status' => '1',
			'processed_count' => count( $processed_ids ),
			'processed_ids' => implode( ',', $processed_ids ),
		), $redirect_to );
	}
	
	public static function bulk_shipment_status_from_settings_fun(){
		$args = array(
			'status' => 'wc-completed',
			'limit'	 => -1,	
			'date_created' => '>' . ( time() - 2592000 ),
		);		
		$orders = wc_get_orders( $args );		
		foreach($orders as $order){
			$order_id = $order->get_id();
			
			$ast = new WC_Advanced_Shipment_Tracking_Actions;
			$tracking_items = $ast->get_tracking_items( $order_id, true );
			if($tracking_items){
				$shipment_status = get_post_meta( $order_id, "shipment_status", true);
				foreach ( $tracking_items as $key => $tracking_item ) { 
					if( !isset($shipment_status[$key]) ){						
						wp_schedule_single_event( time() + 1, 'wcast_retry_trackship_apicall', array( $order_id ) );					
					}
				}									
			}			
		}
		$url = admin_url('/edit.php?post_type=shop_order');		
		echo $url;die();		
	}
	
	/**
	 * Add 'get_shipment_status' link to order actions select box on edit order page
	 *
	 * @since 1.0
	 * @param array $actions order actions array to display
	 * @return array
	 */
	public function add_order_meta_box_get_shipment_status_actions( $actions ) {

		// add download to CSV action
		$actions['get_shipment_status_edit_order'] = __( 'Get shipment status', 'woo-advanced-shipment-tracking' );
		return $actions;
	}
	
	public function process_order_meta_box_actions_get_shipment_status( $order ){
		$this->trigger_woocommerce_order_status_completed( $order->get_id() );
	}
	
	// The results notice from bulk action on orders
	function shipment_status_bulk_action_admin_notice() {
		if ( empty( $_REQUEST['get_shipment_status'] ) ) return; // Exit
	
		$count = intval( $_REQUEST['processed_count'] );
	
		printf( '<div id="message" class="updated fade"><p>' .
			_n( 'Tracking info sent to Trackship for %s Order.',
			'Tracking info sent to Trackship for %s Orders.',
			$count,
			'get_shipment_status'
		) . '</p></div>', $count );
	}
	
	/*
	* filter for shipment status
	*/
	function trackship_status_filter_func( $status ){
		switch ($status) {
			case "in_transit":
				$status = __( 'In Transit', 'woo-advanced-shipment-tracking' );
				break;
			case "pre_transit":
				$status = __( 'Pre Transit', 'woo-advanced-shipment-tracking' );
				break;
			case "delivered":
				$status = __( 'Delivered', 'woo-advanced-shipment-tracking' );
				break;
			case "out_for_delivery":
				$status = __( 'Out for delivery', 'woo-advanced-shipment-tracking' );
				break;
			case "available_for_pickup":
				$status = __( 'Available For Pickup', 'woo-advanced-shipment-tracking' );
				break;
			case "return_to_sender":
				$status = __( 'Return To Sender', 'woo-advanced-shipment-tracking' );
				break;
			case "failure":
				$status = __( 'Delivery Failure', 'woo-advanced-shipment-tracking' );
				break;
			case "unknown":
				$status = __( 'Unknown', 'woo-advanced-shipment-tracking' );
				break;
			case "pending_trackship":
				$status = __( 'Pending TrackShip', 'woo-advanced-shipment-tracking' );
				break;
			case "INVALID_TRACKING_NUM":
				$status = __( 'Invalid Tracking', 'woo-advanced-shipment-tracking' );
				break;
			case "carrier_unsupported":
				$status = __( 'Carrier unsupported', 'woo-advanced-shipment-tracking' );
				break;
			case "invalid_user_key":
				$status = __( 'Invalid User Key', 'woo-advanced-shipment-tracking' );
				break;
				
		}
		return $status;
	}
	
	/*
	* filter for shipment status icon
	*/
	function trackship_status_icon_filter_func( $html, $status ){
		switch ($status) {
			case "in_transit":
				$html = '<span class="icon-'.$status.'">';
				break;
			case "pre_transit":
				$html = '<span class="icon-'.$status.'">';
				break;
			case "delivered":
				$html = '<span class="icon-'.$status.'"></span>';
				break;
			case "out_for_delivery":
				$html = '<span class="icon-'.$status.'">';
				break;
			case "available_for_pickup":
				$html = '<span class="icon-'.$status.'">';
				break;
			case "return_to_sender":
				$html = '<span class="icon-'.$status.'">';
				break;
			case "failure":
				$html = '<span class="icon-'.$status.'">';
				break;
			case "unknown":
				$html = '<span class="icon-'.$status.'">';
				break;
			case "pending_trackship":
				$html = '<span class="icon-'.$status.'">';
				break;
			case "INVALID_TRACKING_NUM":
				$html = '<i class="fa fa-question-circle icon-'.$status.'" aria-hidden="true"></i>';
				break;
			case "invalid_user_key":
				$html = '<span class="icon-'.$status.'">';
				break;	
			default:
				$html = '<i class="fa fa-question-circle icon-'.$status.'" aria-hidden="true"></i>';
				break;

		}
		return $html;
	}		
	
	/*
	* retry trackship api call
	*/
	function wcast_retry_trackship_apicall_func( $order_id ){
		$logger = wc_get_logger();
		$context = array( 'source' => 'retry_trackship_apicall' );
		$logger->info( "Retry trackship api call for Order id : ".$order_id, $context );
		$this->trigger_woocommerce_order_status_completed( $order_id );
	}		
	/*
	* define the item in the meta box by adding an item to the $actions array
	*/	
	function add_order_meta_box_actions( $actions ) {					
		$actions['resend_delivered_order_notification'] = __( 'Resend delivered order notification', 'woo-advanced-shipment-tracking' );
		return $actions;		
	}		
	/*
	* function call when resend delivered order email notification trigger
	*/	
	function process_order_meta_box_actions($order){
		require_once( 'email-manager.php' );
		$old_status = 'in_transit';
		$new_status = 'delivered';
		$order_id  = $order->get_id(); 	
		//wc_advanced_shipment_tracking_email_class()->delivered_shippment_status_email_trigger($order_id, $order, $old_status, $new_status);		
		WC()->mailer()->emails['WC_Email_Customer_Delivered_Order']->trigger( $order_id, $order );
	}
	/*
	* update preview order id in customizer
	*/
	public function update_email_preview_order_fun(){
		set_theme_mod('wcast_availableforpickup_email_preview_order_id', $_POST['wcast_preview_order_id']);
		set_theme_mod('wcast_returntosender_email_preview_order_id', $_POST['wcast_preview_order_id']);
		set_theme_mod('wcast_delivered_status_email_preview_order_id', $_POST['wcast_preview_order_id']);
		set_theme_mod('wcast_outfordelivery_email_preview_order_id', $_POST['wcast_preview_order_id']);
		set_theme_mod('wcast_intransit_email_preview_order_id', $_POST['wcast_preview_order_id']);
		set_theme_mod('wcast_pretransit_email_preview_order_id', $_POST['wcast_preview_order_id']);
		set_theme_mod('wcast_email_preview_order_id', $_POST['wcast_preview_order_id']);
		set_theme_mod('wcast_preview_order_id', $_POST['wcast_preview_order_id']);		
		exit;
	}
	/*
	* update delivered order email status
	*/
	public function update_delivered_order_email_status_fun(){		
		$wcast_enable_delivered_email = get_option('woocommerce_customer_delivered_order_settings'); 
		$opt = array(
			'enabled' => $_POST['wcast_enable_delivered_email'],
			'subject' => $wcast_enable_delivered_email['subject'],
			'heading' => $wcast_enable_delivered_email['heading'],
		);
		update_option( 'woocommerce_customer_delivered_order_settings', $opt );
		exit;
	}
	/*
	* update all shipment status email status
	*/
	public function update_shipment_status_email_status_fun(){		
		set_theme_mod($_POST['id'], $_POST['wcast_enable_status_email']);
		exit;
	}
	/*
	* Change completed order email title to Shipped Order
	*/
	public function change_completed_woocommerce_email_title($email_title, $email){
		$wc_ast_status_shipped = get_option('wc_ast_status_shipped');		
		// Only on backend Woocommerce Settings "Emails" tab
		if($wc_ast_status_shipped == 1){
			if( isset($_GET['page']) && $_GET['page'] == 'wc-settings' && isset($_GET['tab'])  && $_GET['tab'] == 'email' ) {
				switch ($email->id) {
					case 'customer_completed_order':
						$email_title = __("Shipped Order", 'woo-advanced-shipment-tracking');
						break;
				}
			}
		}
		return $email_title;
	}
	/*
	* Add action button in order list to change order status from completed to delivered
	*/
	public function add_delivered_order_status_actions_button($actions, $order){
		$wc_ast_status_delivered = get_option('wc_ast_status_delivered');
		if($wc_ast_status_delivered){
			if ( $order->has_status( array( 'completed' ) ) ) {
				// Get Order ID (compatibility all WC versions)
				$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
				// Set the action button
				$actions['delivered'] = array(
					'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=delivered&order_id=' . $order_id ), 'woocommerce-mark-order-status' ),
					'name'      => __( 'Mark order as delivered', 'woo-advanced-shipment-tracking' ),
					'action'    => "delivered_icon", // keep "view" class for a clean button CSS
				);
			}	
		}
		$actions['add_tracking'] = array(
			'url'       => "#".$order->get_id(),
			'name'      => __( 'Add Tracking', 'woo-advanced-shipment-tracking' ),
			'action'    => "add_inline_tracking", // keep "view" class for a clean button CSS
		);		
		return $actions;
	}
	/*
	* Add delivered action button in preview order list to change order status from completed to delivered
	*/
	public function additional_admin_order_preview_buttons_actions($actions, $order){
		$wc_ast_status_delivered = get_option('wc_ast_status_delivered');
		if($wc_ast_status_delivered){
			// Below set your custom order statuses (key / label / allowed statuses) that needs a button
			$custom_statuses = array(
				'delivered' => array( // The key (slug without "wc-")
					'label'     => __("Delivered", "woo-advanced-shipment-tracking"), // Label name
					'allowed'   => array( 'completed'), // Button displayed for this statuses (slugs without "wc-")
				),
			);
		
			// Loop through your custom orders Statuses
			foreach ( $custom_statuses as $status_slug => $values ){
				if ( $order->has_status( $values['allowed'] ) ) {
					$actions['status']['group'] = __( 'Change status: ', 'woocommerce' );
					$actions['status']['actions'][$status_slug] = array(
						'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status='.$status_slug.'&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
						'name'   => $values['label'],
						'title'  => __( 'Change order status to', 'woo-advanced-shipment-tracking' ) . ' ' . strtolower($values['label']),
						'action' => $status_slug,
					);
				}
			}
		}		
		return $actions;
	}
	
	public function filter_shipiing_provider_by_status_fun(){		
		$status = $_POST['status'];
		global $wpdb;		
		if($status == 'active'){			
			$default_shippment_providers = $wpdb->get_results( "SELECT * FROM $this->table WHERE display_in_order = 1" );	
		}
		if($status == 'inactive'){			
			$default_shippment_providers = $wpdb->get_results( "SELECT * FROM $this->table WHERE display_in_order = 0" );	
		}
		if($status == 'custom'){			
			$default_shippment_providers = $wpdb->get_results( "SELECT * FROM $this->table WHERE shipping_default = 0" );	
		}
		if($status == 'all'){
			$status = '';
			$default_shippment_providers = $wpdb->get_results( "SELECT * FROM $this->table" );	
		}
		$html = $this->get_provider_html($default_shippment_providers,$status);
		echo $html;exit;		
	}
	
	public function get_provider_html($default_shippment_providers,$status){
		$WC_Countries = new WC_Countries();
		?>
		<div class="provider_list">
		<?php 
			if($default_shippment_providers){
			foreach($default_shippment_providers as $d_s_p){ ?>
			<div class="provider <?php if($d_s_p->display_in_order == 1) { echo 'active_provider'; } ?>">
				<div class="row-1">
					<div class="left-div">
						<a href="<?php echo str_replace("%number%","",$d_s_p->provider_url ); ?>" title="<?php echo str_replace("%number%","",$d_s_p->provider_url ); ?>" target="_blank">
						<?php  if( $d_s_p->shipping_default == 1 ){ ?>
						<img class="provider-thumb" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/shipment-provider-img/<?php echo sanitize_title($d_s_p->provider_name);?>.png?v=<?php echo wc_advanced_shipment_tracking()->version;?>">
						<?php } else{ 
						$custom_thumb_id = $d_s_p->custom_thumb_id;
						$image_attributes = wp_get_attachment_image_src( $custom_thumb_id , array('60','60') );
						//echo '<pre>';print_r($custom_thumb_id);echo '</pre>';exit;
						if($custom_thumb_id != 0){ ?>
							<img class="provider-thumb" src="<?php echo $image_attributes[0]; ?>">
						<?php } else{
						?>
							<img class="provider-thumb" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/shipment-provider-img/icon-default.png">
						<?php } ?>
						<?php } ?>					
						</a>
					</div>
					<div class="right-div">
						<a href="<?php echo str_replace("%number%","",$d_s_p->provider_url ); ?>" title="<?php echo str_replace("%number%","",$d_s_p->provider_url ); ?>" target="_blank">
							<span class="provider_name"><?php echo $d_s_p->provider_name; ?></span>
						</a>
						<br>
						<span class="provider_country"><?php
									$search  = array('(US)', '(UK)');
									$replace = array('', '');
									if($d_s_p->shipping_country && $d_s_p->shipping_country != 'Global'){
										echo str_replace($search, $replace, $WC_Countries->countries[$d_s_p->shipping_country]);
									} elseif($d_s_p->shipping_country && $d_s_p->shipping_country == 'Global'){
										echo 'Global';
									}													
									?></span>
					</div>
				</div>
				<div class="row-2">
					<div class="default-provider">
						<?php $default_provider = get_option("wc_ast_default_provider" );?>
						<label for="make_default_<?php echo $d_s_p->id; ?>" id="default_label_<?php echo $d_s_p->id; ?>" class="<?php if($d_s_p->display_in_order != 1) { echo 'disable_label'; } ?>">
							<input type="checkbox" id="make_default_<?php echo $d_s_p->id; ?>" name="make_provider_default" data-id="<?php echo $d_s_p->id; ?>" class="make_provider_default" value="<?php echo sanitize_title( $d_s_p->provider_name )?>" <?php if( $default_provider == sanitize_title( $d_s_p->provider_name ) )echo 'checked';?> <?php if($d_s_p->display_in_order != 1) { echo 'disabled'; } ?>>
							<span>default</span>
						</label>
					</div>
					<div class="provider-status">
						<?php if( $d_s_p->shipping_default == 0 ){ ?>
							<span class="dashicons dashicons-edit edit_provider" data-pid="<?php echo $d_s_p->id; ?>"></span>
							<span class="dashicons dashicons-trash remove" data-pid="<?php echo $d_s_p->id; ?>"></span>													
                        <?php } ?>
						<span class="mdl-list__item-secondary-action">
							<label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="list-switch-<?php echo $d_s_p->id; ?>">
								<input type="checkbox" name="select_custom_provider[]" id="list-switch-<?php echo $d_s_p->id; ?>" class="mdl-switch__input status_slide" value="<?php echo $d_s_p->id; ?>" <?php if($d_s_p->display_in_order == 1) { echo 'checked'; } ?> />
							</label>
						</span>
					</div>
				</div>	
			</div>	
			<?php } } else{ 
				$status = 'active';
			?>
				<h3><?php echo sprintf(__("You don't have any %s shipping providers.", 'woo-advanced-shipment-tracking'), $status); ?></h3>
			<?php } ?>		
		</div>	
		<?php 
	}
	/*
	* Update shipment provider status
	*/
	function update_shipment_status_fun(){			
		global $wpdb;		
		$woo_shippment_table_name = $this->table;
		$success = $wpdb->update($woo_shippment_table_name, 
			array(
				"display_in_order" => $_POST['checked'],
			),	
			array('id' => $_POST['id'])
		);
		exit;	
	}
	
	function update_default_provider_fun(){
		if($_POST['checked'] == 1){
			update_option("wc_ast_default_provider", $_POST['default_provider'] );
		} else{
			update_option("wc_ast_default_provider", '' );
		}
		exit;
	}
	
	function add_custom_shipment_provider_fun(){
		
		global $wpdb;
		
		$woo_shippment_table_name = $this->table;
			
		$data_array = array(
			'shipping_country' => sanitize_text_field($_POST['shipping_country']),
			'provider_name' => sanitize_text_field($_POST['shipping_provider']),
			'ts_slug' => sanitize_title($_POST['shipping_provider']),
			'provider_url' => sanitize_text_field($_POST['tracking_url']),
			'custom_thumb_id' => sanitize_text_field($_POST['thumb_id']),			
			'display_in_order' => 1,
			'shipping_default' => 0,
		);
		$result = $wpdb->insert( $woo_shippment_table_name, $data_array );
		
		$status = 'custom';
		$default_shippment_providers = $wpdb->get_results( "SELECT * FROM $this->table WHERE shipping_default = 0" );	
		$html = $this->get_provider_html($default_shippment_providers,$status);
		echo $html;exit;		
	}
	
	/*
	* delet provide by ajax
	*/
	public function woocommerce_shipping_provider_delete(){				

		$provider_id = $_POST['provider_id'];
		if ( ! empty( $provider_id ) ) {
			global $wpdb;
			$where = array(
				'id' => $provider_id,
				'shipping_default' => 0
			);
			$wpdb->delete( $this->table, $where );
		}
		$status = 'custom';
		$default_shippment_providers = $wpdb->get_results( "SELECT * FROM $this->table WHERE shipping_default = 0" );	
		$html = $this->get_provider_html($default_shippment_providers,$status);
		echo $html;exit;
	}
	
	public function get_provider_details_fun(){
		$id = $_POST['provider_id'];
		global $wpdb;
		$shippment_provider = $wpdb->get_results( "SELECT * FROM $this->table WHERE id=$id" );
		$image = wp_get_attachment_url($shippment_provider[0]->custom_thumb_id);
		echo json_encode( array('id' => $shippment_provider[0]->id,'provider_name' => $shippment_provider[0]->provider_name,'provider_url' => $shippment_provider[0]->provider_url,'shipping_country' => $shippment_provider[0]->shipping_country,'custom_thumb_id' => $shippment_provider[0]->custom_thumb_id,'image' => $image) );exit;			
	}
	
	public function update_custom_shipment_provider_fun(){
		
		global $wpdb;		
		$data_array = array(
			'shipping_country' => sanitize_text_field($_POST['shipping_country']),
			'provider_name' => sanitize_text_field($_POST['shipping_provider']),
			'ts_slug' => sanitize_title($_POST['shipping_provider']),
			'custom_thumb_id' => sanitize_text_field($_POST['thumb_id']),
			'provider_url' => esc_url($_POST['tracking_url'])		
		);
		$where_array = array(
			'id' => $_POST['provider_id'],			
		);
		$wpdb->update( $this->table, $data_array, $where_array );
		$status = 'custom';
		$default_shippment_providers = $wpdb->get_results( "SELECT * FROM $this->table WHERE shipping_default = 0" );	
		$html = $this->get_provider_html($default_shippment_providers,$status);
		echo $html;exit;
	}
	
	public function update_provider_status_active_fun(){
		global $wpdb;
		$data_array = array(
			'display_in_order' => 1,			
		);
		$where_array = array(
			'display_in_order' => 0,			
		);
		$wpdb->update( $this->table, $data_array, $where_array);
		$status = 'active';
		$default_shippment_providers = $wpdb->get_results( "SELECT * FROM $this->table WHERE display_in_order = 1" );	
		$html = $this->get_provider_html($default_shippment_providers,$status);
		exit;
	}
	
	public function update_provider_status_inactive_fun(){
		global $wpdb;
		$data_array = array(
			'display_in_order' => 0,			
		);
		$where_array = array(
			'display_in_order' => 1,			
		);
		$status = 'inactive';
		$wpdb->update( $this->table, $data_array, $where_array);
		update_option("wc_ast_default_provider", '' );
		$default_shippment_providers = $wpdb->get_results( "SELECT * FROM $this->table WHERE display_in_order = 0" );	
		$html = $this->get_provider_html($default_shippment_providers,$status);
		exit;
	}
	
	public function sync_providers_fun(){
		global $wpdb;		
		
		$url = 'https://trackship.info/wp-json/WCAST/v1/Provider';		
		$resp = wp_remote_get( $url );
		$providers = json_decode($resp['body'],true);
		
		$default_shippment_providers = $wpdb->get_results( "SELECT * FROM $this->table WHERE shipping_default = 1" );
		
		foreach ( $default_shippment_providers as $key => $val ){
			$shippment_providers[ $val->provider_name ] = $val;						
		}

		foreach ( $providers as $key => $val ){
			$providers_name[ $val['provider_name'] ] = $val;						
		}		
			
		$added = 0;
		$updated = 0;
		$deleted = 0;
		$added_html = '';
		$updated_html = '';
		$deleted_html = '';
		
		foreach($providers as $provider){
			
			$provider_name = $provider['shipping_provider'];
			$provider_url = $provider['provider_url'];
			$shipping_country = $provider['shipping_country'];
			$ts_slug = $provider['shipping_provider_slug'];
			
			if($shippment_providers[$provider_name]){				
				$db_provider_url = $shippment_providers[$provider_name]->provider_url;
				$db_shipping_country = $shippment_providers[$provider_name]->shipping_country;
				$db_ts_slug = $shippment_providers[$provider_name]->ts_slug;
				if(($db_provider_url != $provider_url) || ($db_shipping_country != $shipping_country) || ($db_ts_slug != $ts_slug)){
					$data_array = array(
						'ts_slug' => $ts_slug,
						'provider_url' => $provider_url,
						'shipping_country' => $shipping_country,						
					);
					$where_array = array(
						'provider_name' => $provider_name,			
					);					
					$wpdb->update( $this->table, $data_array, $where_array);
					$updated_data[$updated] = array('provider_name' => $provider_name);
					$updated++;
				}
			} else{
				$img_url = $provider['img_url'];
				
				$img_slug = sanitize_title($provider_name);
				$img = wc_advanced_shipment_tracking()->get_plugin_path().'/assets/shipment-provider-img/'.$img_slug.'.png';
				
				$ch = curl_init(); 
  
				curl_setopt($ch, CURLOPT_HEADER, 0); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
				curl_setopt($ch, CURLOPT_URL, $img_url); 
			
				$data = curl_exec($ch); 
				curl_close($ch); 
				
				file_put_contents($img, $data); 			
							
								
				$data_array = array(
					'shipping_country' => sanitize_text_field($shipping_country),
					'provider_name' => sanitize_text_field($provider_name),
					'ts_slug' => $ts_slug,
					'provider_url' => sanitize_text_field($provider_url),			
					'display_in_order' => 0,
					'shipping_default' => 1,
				);
				$result = $wpdb->insert( $this->table, $data_array );
				$added_data[$added] = array('provider_name' => $provider_name);
				$added++;
			}		
		}		
		foreach($default_shippment_providers as $db_provider){
			if(!isset($providers_name[$db_provider->provider_name])){			
				$where = array(
					'provider_name' => $db_provider->provider_name,
					'shipping_default' => 1
				);
				$wpdb->delete( $this->table, $where );
				$deleted_data[$deleted] = array('provider_name' => $db_provider->provider_name);
				$deleted++;		
			}
		}
		if($added > 0){
			ob_start();
			$added_html = $this->added_html($added_data);
			$added_html = ob_get_clean();	
		}
		if($updated > 0){
			ob_start();
			$updated_html = $this->updated_html($updated_data);
			$updated_html = ob_get_clean();	
		}
		if($deleted > 0){
			ob_start();
			$deleted_html = $this->deleted_html($deleted_data);
			$deleted_html = ob_get_clean();	
		}
		
		$status = 'active';
		$default_shippment_providers = $wpdb->get_results( "SELECT * FROM $this->table WHERE display_in_order = 1" );	
		ob_start();
		$html = $this->get_provider_html($default_shippment_providers,$status);
		$html = ob_get_clean();	
		echo json_encode( array('added' => $added,'added_html' =>$added_html,'updated' => $updated,'updated_html' =>$updated_html,'deleted' => $deleted,'deleted_html' =>$deleted_html,'html' => $html) );exit;			
	}

	public function added_html($added_data){ ?>
		<ul class="updated_details" id="added_providers">
			<?php 
			foreach ( $added_data as $added ){ ?>
				<li><?php echo $added['provider_name']; ?></li>	
			<?php }
			?>
		</ul>
		<a class="view_synch_details" id="view_added_details" href="javaScript:void(0);" style="display: block;"><?php _e( 'view details', 'woo-advanced-shipment-tracking'); ?></a>
		<a class="view_synch_details" id="hide_added_details" href="javaScript:void(0);" style="display: none;"><?php _e( 'hide details', 'woo-advanced-shipment-tracking'); ?></a>
	<?php }

	public function updated_html($updated_data){ ?>
		<ul class="updated_details" id="updated_providers">
			<?php 
			foreach ( $updated_data as $updated ){ ?>
				<li><?php echo $updated['provider_name']; ?></li>	
			<?php }
			?>
		</ul>
		<a class="view_synch_details" id="view_updated_details" href="javaScript:void(0);" style="display: block;"><?php _e( 'view details', 'woo-advanced-shipment-tracking'); ?></a>
		<a class="view_synch_details" id="hide_updated_details" href="javaScript:void(0);" style="display: none;"><?php _e( 'hide details', 'woo-advanced-shipment-tracking'); ?></a>
	<?php }
	
	public function deleted_html($deleted_data){ ?>
		<ul class="updated_details" id="deleted_providers">
			<?php 
			foreach ( $deleted_data as $deleted ){ ?>
				<li><?php echo $deleted['provider_name']; ?></li>	
			<?php }
			?>
		</ul>
		<a class="view_synch_details" id="view_deleted_details" href="javaScript:void(0);" style="display: block;"><?php _e( 'view details', 'woo-advanced-shipment-tracking'); ?></a>
		<a class="view_synch_details" id="hide_deleted_details" href="javaScript:void(0);" style="display: none;"><?php _e( 'hide details', 'woo-advanced-shipment-tracking'); ?></a>
	<?php }	
	/**
	 * Add bulk filter for Shipment status in orders list
	 *
	 * @since 2.4
	 */
	public function filter_orders_by_shipment_status(){
		global $typenow;

		if ( 'shop_order' === $typenow ) {

			$count = $this->get_order_count();

			$terms = array(
				'unknown' => (object) array( 'count' => $count['unknown'], 'term' => __( 'Unknown', 'woo-advanced-shipment-tracking' ) ),
				'pre_transit' => (object) array( 'count' => $count['pre_transit'],'term' => __( 'Pre Transit', 'woo-advanced-shipment-tracking' ) ),
				'in_transit' => (object) array( 'count' => $count['in_transit'],'term' => __( 'In Transit', 'woo-advanced-shipment-tracking' ) ),
				'available_for_pickup' => (object) array( 'count' => $count['available_for_pickup'],'term' => __( 'Available for Pickup', 'woo-advanced-shipment-tracking' ) ),
				'out_for_delivery' => (object) array( 'count' => $count['out_for_delivery'],'term' => __( 'Out for Delivery', 'woo-advanced-shipment-tracking' ) ),
				'delivered' => (object) array( 'count' => $count['delivered'],'term' => __( 'Delivered', 'woo-advanced-shipment-tracking' ) ),
				'failed_attempt' => (object) array( 'count' => $count['failed_attempt'],'term' => __( 'Failed Attempt', 'woo-advanced-shipment-tracking' ) ),
				'cancelled' => (object) array( 'count' => $count['cancelled'],'term' => __( 'Cancelled', 'woo-advanced-shipment-tracking' ) ),
				'carrier_unsupported' => (object) array( 'count' => $count['carrier_unsupported'],'term' => __( 'Carrier Unsupported', 'woo-advanced-shipment-tracking' ) ),
				'return_to_sender' => (object) array( 'count' => $count['return_to_sender'],'term' => __( 'Return To Sender', 'woo-advanced-shipment-tracking' ) ),				
				'INVALID_TRACKING_NUM' => (object) array( 'count' => $count['invalid_tracking_number'],'term' => __( 'Invalid Tracking Number', 'woo-advanced-shipment-tracking' ) ),
			);

			?>
			<select name="_shop_order_shipment_status" id="dropdown_shop_order_shipment_status">
				<option value=""><?php _e( 'Filter by shipment status', 'woo-advanced-shipment-tracking' ); ?></option>
				<?php foreach ( $terms as $value => $term ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php echo esc_attr( isset( $_GET['_shop_order_shipment_status'] ) ? selected( $value, $_GET['_shop_order_shipment_status'], false ) : '' ); ?>>
					<?php printf( '%1$s (%2$s)', esc_html( $term->term ), esc_html( $term->count ) ); ?>
				</option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}
	
	/**
	 * Get the order count for orders by shipment status	 	 	 
	 * @since 2.4	 
	 */
	private function get_order_count() {

		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_order',
			'post_status' => isset( $_GET['post_status'] ) ? $_GET['post_status'] : 'any',
			'meta_query'  => array(
				array(
					'key'   => 'shipment_status',
					'value' => '',
					'compare' => 'LIKE',
				)
			),
			'nopaging'    => true,
		);

		$order_query = new WP_Query( $query_args );

		$query_args['meta_query'][0]['value'] = 'delivered';
		$delivered_query = new WP_Query( $query_args );
		
		$query_args['meta_query'][0]['value'] = 'unknown';
		$unknown_query = new WP_Query( $query_args );
		
		$query_args['meta_query'][0]['value'] = 'pre_transit';
		$pre_transit_query = new WP_Query( $query_args );
		
		$query_args['meta_query'][0]['value'] = 'in_transit';
		$in_transit_query = new WP_Query( $query_args );
		
		$query_args['meta_query'][0]['value'] = 'available_for_pickup';
		$available_for_pickup_query = new WP_Query( $query_args );
		
		$query_args['meta_query'][0]['value'] = 'out_for_delivery';
		$out_for_delivery_query = new WP_Query( $query_args );
		
		$query_args['meta_query'][0]['value'] = 'failed_attempt';
		$failed_attempt_query = new WP_Query( $query_args );
		
		$query_args['meta_query'][0]['value'] = 'cancelled';
		$cancelled_query = new WP_Query( $query_args );

		$query_args['meta_query'][0]['value'] = 'carrier_unsupported';
		$carrier_unsupported_query = new WP_Query( $query_args );

		$query_args['meta_query'][0]['value'] = 'return_to_sender';
		$return_to_sender_query = new WP_Query( $query_args );

		$query_args['meta_query'][0]['value'] = 'INVALID_TRACKING_NUM';
		$invalid_tracking_number_query = new WP_Query( $query_args );		

		return array( 'unknown' => $unknown_query->found_posts, 'pre_transit' => $pre_transit_query->found_posts, 'in_transit' => $in_transit_query->found_posts, 'available_for_pickup' => $available_for_pickup_query->found_posts, 'out_for_delivery' => $out_for_delivery_query->found_posts, 'failed_attempt' => $failed_attempt_query->found_posts, 'cancelled' => $cancelled_query->found_posts, 'carrier_unsupported' => $carrier_unsupported_query->found_posts, 'return_to_sender' => $return_to_sender_query->found_posts, 'delivered' => $delivered_query->found_posts, 'invalid_tracking_number' => $invalid_tracking_number_query->found_posts);
	}
	/**
	 * Process bulk filter action for shipment status orders
	 *
	 * @since 3.0.0
	 * @param array $vars query vars without filtering
	 * @return array $vars query vars with (maybe) filtering
	 */
	public function filter_orders_by_shipment_status_query( $vars ){
		global $typenow;

		if ( 'shop_order' === $typenow && isset( $_GET['_shop_order_shipment_status'] ) && $_GET['_shop_order_shipment_status'] != '') {
			$vars['meta_key']   = 'shipment_status';
			$vars['meta_value'] = $_GET['_shop_order_shipment_status'];
			$vars['meta_compare'] = 'LIKE';						
		}

		return $vars;
	}
	/**
	* Add a new dashboard widget.
	*/
	public function ast_add_dashboard_widgets() {
		wp_add_dashboard_widget( 'dashboard_widget', 'TrackShip Reports', array( $this, 'dashboard_widget_function') );
	}
	/**
	* Output the contents of the dashboard widget
	*/
	public function dashboard_widget_function( $post, $callback_args ) { ?>
		<div class="ast-dashborad-widget row align-equal row-dashed" id="">
			<div class="col medium-6 small-12 large-6">
				<div class="col-inner">
					<p data-line-height="s"><strong>Shipment Trackers</strong></p>
					<p><span style="font-size: 100%;"><span style="font-size: 200%;" data-text-color="primary">7</span>
						<span class="ft12 red">42% <i class="fa fa-arrow-down"></i></span></span>
					</p>
				</div>
			</div>
			<div class="col medium-6 small-12 large-6">
				<div class="col-inner">
					<p data-line-height="s"><strong>Shipment Providers</strong></p>
					<table class="table">
						<tbody>
						<tr class="card-category">
							<td><strong>ups</strong></td>
							<td>3</td>
							<td><span class="ft12 green">0% <i class="fa fa-arrow-up"></i></span></td>
						</tr>
						<tr class="card-category">
							<td><strong>usps</strong></td>
							<td>2</td>
							<td><span class="ft12 red">33% <i class="fa fa-arrow-down"></i></span></td>
						</tr>
						<tr class="card-category">
							<td><strong>dhlparcel-nl</strong></td>
							<td>1</td>
							<td><span class="ft12 green">100% <i class="fa fa-arrow-up"></i></span></td>
						</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="col medium-6 small-12 large-6">
				<div class="col-inner">
					<p data-line-height="s"><strong>Shipment Status</strong></p>
					<p data-line-height="s">
					</p>
					<table class="table">
						<tbody>
						<tr class="card-category">
							<td><strong>Delivered</strong></td>
							<td>6</td>
							<td><span class="ft12 red">45% <i class="fa fa-arrow-down"></i></span></td>
						</tr>
						<tr class="card-category">
							<td><strong>Return to sender</strong></td>
							<td>1</td>
							<td><span class="ft12 green">100% <i class="fa fa-arrow-up"></i></span></td>
						</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="col medium-6 small-12 large-6">
				<div class="col-inner">
					<p data-line-height="s"><strong>Tracking Issues</strong></p>
					<table class="table">
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
			<style scope="scope">
			</style>
		</div>
	<?php }
}