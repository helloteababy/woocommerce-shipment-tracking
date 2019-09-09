<?php
/**
 * Customizer Setup and Custom Controls
 *
 */

/**
 * Adds the individual sections, settings, and controls to the theme customizer
 */
class wcast_tracking_page_customizer {
	// Get our default values	
	public function __construct() {
		// Get our Customizer defaults
		$this->defaults = $this->wcast_generate_defaults();
		$wc_ast_api_key = get_option('wc_ast_api_key');
		if(!$wc_ast_api_key){
			return;
		}
		
		// Register our sample default controls
		add_action( 'customize_register', array( $this, 'wcast_register_sample_default_controls' ) );
		
		// Only proceed if this is own request.
		if ( ! wcast_tracking_page_customizer::is_own_customizer_request() && ! wcast_tracking_page_customizer::is_own_preview_request() ) {
			return;
		}
		
		// Set up preview.		
		
		// Register our Panels
		add_action( 'customize_register', array( wcast_customizer(), 'wcast_add_customizer_panels' ) );

		// Register our sections
		add_action( 'customize_register', array( wcast_customizer(), 'wcast_add_customizer_sections' ) );	
		
		// Remove unrelated components.
		add_filter( 'customize_loaded_components', array( wcast_customizer(), 'remove_unrelated_components' ), 99, 2 );

		// Remove unrelated sections.
		add_filter( 'customize_section_active', array( wcast_customizer(), 'remove_unrelated_sections' ), 10, 2 );	
		
		// Unhook divi front end.
		add_action( 'woomail_footer', array( wcast_customizer(), 'unhook_divi' ), 10 );

		// Unhook Flatsome js
		add_action( 'customize_preview_init', array( wcast_customizer(), 'unhook_flatsome' ), 50  );				
		
		add_filter( 'customize_controls_enqueue_scripts', array( wcast_customizer(), 'enqueue_customizer_scripts' ) );
		
		add_action( 'customize_preview_init', array( $this, 'enqueue_preview_scripts' ) );			
		
		add_action( 'parse_request', array( $this, 'set_up_preview' ) );
	}
	
	 public function enqueue_preview_scripts() {
		 
		 wp_enqueue_script('wcast-preview-scripts', wc_advanced_shipment_tracking()->plugin_dir_url() . '/assets/js/preview-scripts.js', array('jquery', 'customize-preview'), wc_advanced_shipment_tracking()->version, true);		 
		 wp_enqueue_style('wcast-preview-styles', wc_advanced_shipment_tracking()->plugin_dir_url() . 'assets/css/preview-styles.css', array(), wc_advanced_shipment_tracking()->version  );
		 wp_localize_script('wcast-preview-scripts', 'wcast_preview', array(
			'site_title'   => $this->get_blogname(),			
		));
	 }
	 /**
	 * Get blog name formatted for emails.
	 *
	 * @return string
	 */
	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}	
	/**
	 * Checks to see if we are opening our custom customizer preview
	 *
	 * @access public
	 * @return bool
	 */
	public static function is_own_preview_request() {
		return isset( $_REQUEST['wcast-tracking-page-preview'] ) && '1' === $_REQUEST['wcast-tracking-page-preview'];
	}
	/**
	 * Checks to see if we are opening our custom customizer controls
	 *
	 * @access public
	 * @return bool
	 */
	public static function is_own_customizer_request() {
		return isset( $_REQUEST['email'] ) && $_REQUEST['email'] === 'tracking_page_section';
	}	
	/**
	 * Get Customizer URL
	 *
	 */
	public static function get_customizer_url($email) {		
			$customizer_url = add_query_arg( array(
				'wcast-customizer' => '1',
				'email' => $email,
				'url'                  => urlencode( add_query_arg( array( 'wcast-tracking-page-preview' => '1' ), home_url( '/' ) ) ),
				'return'               => urlencode( wcast_tracking_page_customizer::get_email_settings_page_url() ),
			), admin_url( 'customize.php' ) );		

		return $customizer_url;
	}	
	/**
	 * Get WooCommerce email settings page URL
	 *
	 * @access public
	 * @return string
	 */
	public static function get_email_settings_page_url() {
		return admin_url( 'admin.php?page=woocommerce-advanced-shipment-tracking' );
	}
	public function wcast_generate_defaults() {
		$customizer_defaults = array(
			'wcast_tracking_page_list' => 'select',
			'remove_trackship_branding' => '',
			'use_tracking_page' => '',
			'tpage_primary_color' => '',
			'tpage_success_color' => '',
			'tpage_warning_color' => '',
			'tpage_border_color' => '',
			'tracking_info_width' => '800',
			'tdetails_border_color' => '#E4ECEF',
			'tinfo_shade_color' => '#F5F8F8',
			'tevents_font_color' => '#777',
			'table_margin_top_bottom' => '20',
		);

		return apply_filters( 'skyrocket_customizer_defaults', $customizer_defaults );
	}	
	/**
	 * Register our sample default controls
	 */
	public function wcast_register_sample_default_controls( $wp_customize ) {		
		/**
		* Load all our Customizer Custom Controls
		*/
		require_once trailingslashit( dirname(__FILE__) ) . 'custom-controls.php';
		$page_list = wp_list_pluck( get_pages(), 'post_title', 'ID' );							
		
		// Display Shipment Provider image/thumbnail
		$wp_customize->add_setting( 'remove_trackship_branding',
			array(
				'default' => $this->defaults['remove_trackship_branding'],
				'transport' => 'postMessage',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'remove_trackship_branding',
			array(
				'label' => __( 'Remove Trackship branding from tracking page', 'woo-advanced-shipment-tracking' ),
				'description' => esc_html__( '', 'woo-advanced-shipment-tracking' ),
				'section' => 'tracking_page_section',
				'type' => 'checkbox'
			)
		);		
		
		// Primary color for tracking page
		$wp_customize->add_setting( 'tpage_primary_color',
			array(
				'default' => $this->defaults['tpage_primary_color'],
				'transport' => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color'
			)
		);
		$wp_customize->add_control( 'tpage_primary_color',
			array(
				'label' => __( 'Header Color', 'woo-advanced-shipment-tracking' ),
				'section' => 'tracking_page_section',
				'type' => 'color',				
			)
		);
		
		// Success color for tracking page
		$wp_customize->add_setting( 'tpage_success_color',
			array(
				'default' => $this->defaults['tpage_success_color'],
				'transport' => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color'
			)
		);
		$wp_customize->add_control( 'tpage_success_color',
			array(
				'label' => __( 'Success Status Color', 'woo-advanced-shipment-tracking' ),
				'section' => 'tracking_page_section',
				'type' => 'color',				
			)
		);
		
		// Warning color for tracking page
		$wp_customize->add_setting( 'tpage_warning_color',
			array(
				'default' => $this->defaults['tpage_warning_color'],
				'transport' => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color'
			)
		);
		$wp_customize->add_control( 'tpage_warning_color',
			array(
				'label' => __( 'Warning Status Color', 'woo-advanced-shipment-tracking' ),
				'section' => 'tracking_page_section',
				'type' => 'color',				
			)
		);
		
		// Content border color for tracking page
		$wp_customize->add_setting( 'tpage_border_color',
			array(
				'default' => $this->defaults['tpage_border_color'],
				'transport' => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color'
			)
		);
		$wp_customize->add_control( 'tpage_border_color',
			array(
				'label' => __( 'Tracking info Border Color', 'woo-advanced-shipment-tracking' ),
				'section' => 'tracking_page_section',
				'type' => 'color',				
			)
		);
		// Tracking info width
		$wp_customize->add_setting( 'tracking_info_width',
			array(
				'default' => $this->defaults['tracking_info_width'],
				'transport' => 'postMessage',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( new Skyrocket_Slider_Custom_Control( $wp_customize, 'tracking_info_width',
			array(
				'label' => __( 'Tracking info width (px)', 'woo-advanced-shipment-tracking' ),
				'section' => 'tracking_page_section',
				'input_attrs' => array(
						'default' => $this->defaults['tracking_info_width'],
						'step'  => 10,
						'min'   => 200,
						'max'   => 1500,
					),
			)
		));
		// Tracking details Border Color
		$wp_customize->add_setting( 'tdetails_border_color',
			array(
				'default' => $this->defaults['tdetails_border_color'],
				'transport' => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color'
			)
		);
		$wp_customize->add_control( 'tdetails_border_color',
			array(
				'label' => __( 'Tracking details Border Color', 'woo-advanced-shipment-tracking' ),
				'section' => 'tracking_page_section',
				'type' => 'color',				
			)
		);
		// Tracking info table shade color
		$wp_customize->add_setting( 'tinfo_shade_color',
			array(
				'default' => $this->defaults['tinfo_shade_color'],
				'transport' => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color'
			)
		);
		$wp_customize->add_control( 'tinfo_shade_color',
			array(
				'label' => __( 'Tracking info table shade color', 'woo-advanced-shipment-tracking' ),
				'section' => 'tracking_page_section',
				'type' => 'color',				
			)
		);
		// Tracking events font color
		$wp_customize->add_setting( 'tevents_font_color',
			array(
				'default' => $this->defaults['tevents_font_color'],
				'transport' => 'postMessage',
				'sanitize_callback' => 'sanitize_hex_color'
			)
		);
		$wp_customize->add_control( 'tevents_font_color',
			array(
				'label' => __( 'Tracking events font color', 'woo-advanced-shipment-tracking' ),
				'section' => 'tracking_page_section',
				'type' => 'color',				
			)
		);
		// Tracking info width
		$wp_customize->add_setting( 'table_margin_top_bottom',
			array(
				'default' => $this->defaults['table_margin_top_bottom'],
				'transport' => 'postMessage',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( new Skyrocket_Slider_Custom_Control( $wp_customize, 'table_margin_top_bottom',
			array(
				'label' => __( 'Tracking info table margin top/bottom(px)', 'woo-advanced-shipment-tracking' ),
				'section' => 'tracking_page_section',
				'input_attrs' => array(
						'default' => $this->defaults['table_margin_top_bottom'],
						'step'  => 1,
						'min'   => 5,
						'max'   => 50,
					),
			)
		));		
	}	
	
	/**
	 * Set up preview
	 *
	 * @access public
	 * @return void
	 */
	public function set_up_preview() {		
		// Make sure this is own preview request.
		if ( ! wcast_tracking_page_customizer::is_own_preview_request() ) {
			return;
		}		
		include wc_advanced_shipment_tracking()->get_plugin_path() . '/includes/customizer/preview/tracking_page_preview.php';
		exit;
	}
	
	public function preview_tracking_page(){
	
	$wc_ast_api_key = get_option('wc_ast_api_key');	
	$remove_trackship_branding = get_theme_mod('remove_trackship_branding');
	$use_tracking_page = get_theme_mod('use_tracking_page');
	$primary_color = get_theme_mod('tpage_primary_color');	
	$success_color = get_theme_mod('tpage_success_color');
	$warning_color = get_theme_mod('tpage_warning_color');
	$border_color = get_theme_mod('tpage_border_color');
	$remove_trackship_branding =  get_theme_mod('remove_trackship_branding');
	$tracking_info_width =  get_theme_mod('tracking_info_width');	
	$tdetails_border_color =  get_theme_mod('tdetails_border_color');	
	$tinfo_shade_color =  get_theme_mod('tinfo_shade_color');
	$tevents_font_color =  get_theme_mod('tevents_font_color');
	$table_margin_top_bottom = get_theme_mod('table_margin_top_bottom');
	?>
	<style>
	<?php if($success_color){ ?>
	.progress-bar.bg-success{
		background-color:<?php echo $success_color; ?>;
	}
	.tracker-progress-bar-with-dots .success .dot{
		border-color: <?php echo $success_color; ?>;
	}
	.text-success{
		color: <?php echo $success_color; ?>;
	}
	<?php } ?>
	<?php if($warning_color){ ?>
	.progress-bar.bg-warning{
		background-color:<?php echo $warning_color; ?>;
	}
	.tracker-progress-bar-with-dots .warning .dot{
		border-color: <?php echo $warning_color; ?>;
	}
	.text-warning{
		color: <?php echo $warning_color; ?>;
	}
	<?php } ?>
	<?php if($primary_color){ ?>
	.bg-secondary{
		background-color:<?php echo $primary_color; ?>;
	}
	.tracker-progress-bar-with-dots .secondary .dot {
		border-color: <?php echo $primary_color; ?>;
	}
	.text-secondary{
		color: <?php echo $primary_color; ?>;
	}
	<?php } ?>	
	<?php if($border_color){ ?>
	.col.tracking-detail{
		border: 1px solid <?php echo $border_color; ?>;
	}
	<?php }
	if($remove_trackship_branding == 1){ ?>
	.trackship_branding{
		display:none;
	}
	<?php }
	
	if($tracking_info_width){ ?>
	.col.tracking-detail{
		width: <?php echo $tracking_info_width; ?>px;
	}		
	<?php
	}
	if($tdetails_border_color){ ?>
	.tracking-details{
		border-color: <?php echo $tdetails_border_color; ?>;
	}		
	<?php }
	if($tinfo_shade_color){ ?>
	.bg-gray-100{
		background-color: <?php echo $tinfo_shade_color; ?>;
	}
	<?php } 
	if($tevents_font_color){ ?>
	.text-gray-300{
		color: <?php echo $tevents_font_color; ?>;
	}
	<?php } if($table_margin_top_bottom){
	?>
	.col.tracking-detail{
		margin: <?php echo $table_margin_top_bottom; ?>px auto;
	}
	
	<?php
	}?>
	</style>
	<?php
	if(!$wc_ast_api_key){
		return;
	}
	
/*	$order_id = get_theme_mod('wcast_tpage_preview_order_id');
	if($order_id == '' || $order_id == 'mockup') {
		$content = '<div style="padding: 35px 40px; background-color: white;">' . __( 'Please select preview order.', 'woo-advanced-shipment-tracking' ) . '</div>';							
		echo $content;
		return;
	}	
		$order    = new WC_Order( $order_id );
		
		//$order_key = $_GET['key'];
		if(!get_post_status( $order_id )){
			return;
		}		
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
		$tracking_items = get_post_meta( $order_id, '_wc_shipment_tracking_items', true );
			//$get_order_key = get_post_meta( $order_id, 'order_key', true );			
		} else {
			$order          = new WC_Order( $order_id );
			$tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );
			//$get_order_key = $order->order_key;
		}
		$num = 1;
		$total_trackings = sizeof($tracking_items);		
		foreach($tracking_items as $item){
		$tracking_number = $item['tracking_number'];
		$trackship_url = 'https://trackship.info';
		$url = $trackship_url.'/wp-json/wc/v1/get_tracking_info_by_number';		
		$args['body'] = array(
			'tracking_number' => $tracking_number
		);	
		$response = wp_remote_post( $url, $args );
		$data = $response['body'];				
		$decoded_data = json_decode($data);
		
		$tracker = $decoded_data[0];
		
		$tracking_detail_org = '';
		
		
		
		if($tracker->tracking_detail != 'null'){			
			$tracking_detail = array_reverse(json_decode($tracker->tracking_detail));
			$tracking_detail_org = json_decode($tracker->tracking_detail);							
			$trackind_detail_by_status = array();			
			foreach ($tracking_detail_org as $key => $item) {			
				$trackind_detail_by_status[$item->status] = $item;
			}			
			$trackind_detail_by_status_rev = array_reverse($trackind_detail_by_status);	
		}		
		
		$unixTimestamp = strtotime($tracker->est_delivery_date);		
		//Get the day of the week using PHP's date function.
		$day = date("l", $unixTimestamp);		
		if($tracking_detail_org){
	?>
		
		<div class="tracking-detail col">			
				<?php if($total_trackings > 1 ){ ?>
				<p class="shipment_heading"><?php 				
				echo sprintf(__("Shipment - %s (out of %s)", 'woo-advanced-shipment-tracking'), $num , $total_trackings); ?></p>
				<?php } ?>
				<h1 class="shipment_status_heading text-secondary"><?php echo apply_filters("trackship_status_filter",$tracker->ep_status);?></h1>
				<div class="status-section">
					<div class="tracker-progress-bar tracker-progress-bar-with-dots">
						<div class="progress">
							<div class="progress-bar <?php if($tracker->ep_status == "delivered") { echo 'bg-success'; } elseif($tracker->ep_status == "return_to_sender" || $tracker->ep_status == "failure"){ echo 'bg-warning'; } else{ echo 'bg-secondary';} ?>" style="<?php if($tracker->ep_status == "in_transit") { echo 'width:33%;'; } elseif($tracker->ep_status == "out_for_delivery" || $tracker->ep_status == "available_for_pickup" || $tracker->ep_status == "return_to_sender" || $tracker->ep_status == "failure"){ echo 'width:66%';} elseif($tracker->ep_status == "delivered") { echo 'width:100%'; } ?>"></div>
						</div>
						<div class="<?php if($tracker->ep_status == "delivered") { echo 'success'; } elseif($tracker->ep_status == "return_to_sender" || $tracker->ep_status == "failure" || $tracker->ep_status == "unknown") { echo 'warning'; } else{ echo 'secondary';} ?>">
						<span class="dot state-0"></span>
						<span class="state-label state-0 <?php if($tracker->ep_status =="pre_transit"){ echo 'current-state'; } else{ echo 'past-state';} ?>">
						<?php 
							if($tracker->ep_status == "unknown"){
								echo apply_filters("trackship_status_filter",'unknown');								
							} else{
								echo apply_filters("trackship_status_filter",'pre_transit');	
							}	
						?>						
						</span>
						<?php 
							if($tracker->ep_status == "in_transit" || $tracker->ep_status == "out_for_delivery" || $tracker->ep_status == "available_for_pickup" || $tracker->ep_status == "return_to_sender" || $tracker->ep_status == "failure" || $tracker->ep_status == "delivered")	{
								echo '<span class="dot state-1"></span>';
							}	
						?>               
						<span class="state-label state-1 <?php if($tracker->ep_status == "in_transit"){ echo 'current-state'; } elseif($tracker->ep_status == "pre_transit"){ echo 'future-state'; } else{ echo 'past-state'; } ?>">
						<?php echo apply_filters("trackship_status_filter",'in_transit'); ?>						
						</span>
						<?php 
						if($tracker->ep_status == "delivered" || $tracker->ep_status == "return_to_sender" || $tracker->ep_status == "failure" || $tracker->ep_status == "out_for_delivery" || $tracker->ep_status == "available_for_pickup")	{
								echo '<span class="dot state-2"></span>';
							}
						?>
						<span class="state-label state-2 <?php if($tracker->ep_status == "out_for_delivery" || $tracker->ep_status == "available_for_pickup" || $tracker->ep_status == "failure" || $tracker->ep_status == "return_to_sender"){ echo 'current-state'; } elseif($tracker->ep_status == "pre_transit" || $tracker->ep_status == "in_transit"){ echo 'future-state'; } ?>">
						<?php
							if($tracker->ep_status == "return_to_sender"){
								echo apply_filters("trackship_status_filter",'return_to_sender');								
							} elseif($tracker->ep_status == "failure"){
								echo apply_filters("trackship_status_filter",'failure');								
							} else{
								echo apply_filters("trackship_status_filter",'out_for_delivery');
							}
						?>						
						</span>
							<?php 
						if($tracker->ep_status == "delivered")	{
								echo '<span class="dot state-3"></span>';
							}
						?>
						<span class="state-label state-3 <?php if($tracker->ep_status == "delivered"){ echo 'current-state'; } elseif($tracker->ep_status == "pre_transit" || $tracker->ep_status == "in_transit" || $tracker->ep_status == "out_for_delivery" || $tracker->ep_status == "available_for_pickup" || $tracker->ep_status == "return_to_sender" || $tracker->ep_status == "failure"){ echo 'future-state'; }?>">
						<?php echo apply_filters("trackship_status_filter",'delivered'); ?>
						</span>
						</div>
					</div>
				</div>			
			<div class="tracker-top-level">
				<div class="col-md col-md-6 pb-6 pb-md-0 est-delivery-date-container">
					<?php 
					if($tracker->est_delivery_date){
					?>
					<div class="text-muted">
						<?php _e( 'Estimated Delivery Date', 'woo-advanced-shipment-tracking' ); ?>
					</div>
					<h1 class="est-delivery-date text-secondary"><?php echo $day; ?>, <?php echo  date('M d', strtotime($tracker->est_delivery_date)); ?></h1>
					<?php } else{ ?>
					<div class="text-muted">
						<?php _e( 'No Estimated Delivery Date', 'woo-advanced-shipment-tracking' ); ?>
					</div>	
					<?php } ?>
				</div>
				<div class="col-md col-md-6">
					<div class="mb-2 mt-6 mt-md-0"><?php echo $tracker->carrier; ?></div>
					<div class="tracking-code text-secondary text-truncate font-weight-bold font-size-h5">
						<?php echo $tracker->tracking_code; ?>
					</div>
				</div>
			</div>										
			<div class="shipment_progress_div">
				<div class="shipment_progress_heading_div">
	                      <?php if( sizeof($trackind_detail_by_status_rev) > 0 ){?>
                              <div class="col-md col-md-6">
                                  <h2 class="font-weight-bold text-secondary py-2 mb-3"><?php _e( 'Shipment Progress', 'woo-advanced-shipment-tracking' ); ?></h2>
                              </div>
                              <div class="col-md col-md-6">
                                  <p class="small text-right"><span class="text-muted"><?php _e( 'Last Updated:', 'woo-advanced-shipment-tracking' ); ?></span> <strong> <?php echo  date('F d g:iA', strtotime($tracker->ep_updated_at)); ?></strong></p>
                              </div>
					<?php } ?>
				</div>
				<div class="">					
						<?php if( sizeof($trackind_detail_by_status_rev) > 0 ){?>
						<ul class="tracking-details list-unstyled mb-4 border border-light">
                              <?php } ?>
							<?php 
							$i=0;
							foreach($trackind_detail_by_status_rev as $key=>$status_detail){ 
							
							?>
								<!--li class="px-3 py-2 mb-3 font-weight-bold bg-gray-100 text-secondary">
									<?php echo date("F d, Y",strtotime($status_detail->datetime)); ?>
								</li-->
									<?php //foreach($date_detail as $time=>$time_detail){ 
										$bg_class = '';
										if ($i % 2 == 0){
											$bg_class = 'bg-gray-100';
										}
									?>
										<li class="d-flex align-items-center mb-0 <?php echo $bg_class; ?>">	
											<div class="font-size-h3 px-3 px-lg-4 <?php if($status_detail->status_detail == "arrived_at_destination" || $status_detail->status_detail == "arrived_at_pickup_location"){ echo 'ep-icon-theme1-delivered text-success'; } elseif($status_detail->status_detail == "out_for_delivery" || $status_detail->status_detail == "available_for_pickup"){ echo 'ep-icon-theme1-clock text-secondary'; }  elseif($status_detail->status_detail == "label_created"){ echo 'ep-icon-theme1-barcode text-secondary'; } elseif($status_detail->status_detail == "failure"){ echo 'ep-icon-theme1-exclamation text-warning'; } elseif($status_detail->status_detail == "return"){ echo 'ep-icon-theme1-back-arrow text-warning'; } else{ echo 'ep-icon-theme1-default';} ?>  "></div>										
											<div class="tracking-details-date text-gray-300 font-size-h5 text-center">
												<?php echo date("F d, Y",strtotime($status_detail->datetime)); ?>
											</div>
											<div class="tracking-details-time text-gray-300 font-size-h5 text-center">
												<?php echo date("g:i a", strtotime($status_detail->datetime)); ?>
											</div>
											
											<div>
												<div class="font-size-h5">
													<strong><?php echo $status_detail->description; ?></strong>
												</div>
												<div class="text-gray-300">
													<?php echo $status_detail->tracking_location->city; if($status_detail->tracking_location->city) echo ',';?>
													<?php echo $status_detail->tracking_location->state; ?>
													<?php echo $status_detail->tracking_location->zip; ?>
													<?php echo $status_detail->tracking_location->country; ?>
												</div>
											</div>
										</li>
									<?php //} ?>
							<?php $i++; } ?>                          
						<?php if( sizeof($trackind_detail_by_status_rev) > 0 ){?>
						</ul>
                              <?php } ?>					
				</div>
			</div>					
		</div>		
		<?php }
		$num++;}*/ ?>
		<?php 
			
			?> 
				<div class="tracking-detail col">
				<h1 class="shipment_status_heading text-secondary">Delivered</h1>
				<div class="status-section">
					<div class="tracker-progress-bar tracker-progress-bar-with-dots">
						<div class="progress">
							<div class="progress-bar bg-success" style="width:100%"></div>
						</div>
						<div class="success">
							<span class="dot state-0"></span>
							<span class="state-label state-0 past-state">
							Pre Transit						
							</span>
							<span class="dot state-1"></span>               
							<span class="state-label state-1 past-state">
							In Transit						
							</span>
							<span class="dot state-2"></span>						<span class="state-label state-2 ">
							Out for delivery						
							</span>
							<span class="dot state-3"></span>						<span class="state-label state-3 current-state">
							Delivered						</span>
						</div>
					</div>
				</div>
				<div class="tracker-top-level">
					<div class="col-md col-md-6 pb-6 pb-md-0 est-delivery-date-container">
						<div class="text-muted">
							Estimated Delivery Date					
						</div>
						<h1 class="est-delivery-date text-secondary">Monday, Jun 03</h1>
					</div>
					<div class="col-md col-md-6">
						<div class="mb-2 mt-6 mt-md-0">FedEx</div>
						<div class="tracking-code text-secondary text-truncate font-weight-bold font-size-h5">
							775313062254					
						</div>
					</div>
				</div>
				<div class="shipment_progress_div">
					<div class="shipment_progress_heading_div">
						<div class="col-md col-md-6">
							<h2 class="font-weight-bold text-secondary py-2 mb-3">Shipment Progress</h2>
						</div>
						<div class="col-md col-md-6">
							<p class="small text-right"><span class="text-muted">Last Updated:</span> <strong> May 30 2:30AM</strong></p>
						</div>
					</div>
					<div class="">
						<ul class="tracking-details list-unstyled mb-4 border border-light">
							<!--li class="px-3 py-2 mb-3 font-weight-bold bg-gray-100 text-secondary">
							May 30, 2019								</li-->
							<li class="d-flex align-items-center mb-0 bg-gray-100">
							<div class="font-size-h3 px-3 px-lg-4 ep-icon-theme1-delivered text-success  "></div>
							<div class="tracking-details-date text-gray-300 font-size-h5 text-center">
								May 30, 2019											
							</div>
							<div class="tracking-details-time text-gray-300 font-size-h5 text-center">
								1:18 am											
							</div>
							<div>
								<div class="font-size-h5">
									<strong>Delivered</strong>
								</div>
								<div class="text-gray-300">
									TAITO-KU JP,																																																			
								</div>
							</div>
							</li>
							<!--li class="px-3 py-2 mb-3 font-weight-bold bg-gray-100 text-secondary">
							May 29, 2019								</li-->
							<li class="d-flex align-items-center mb-0 ">
							<div class="font-size-h3 px-3 px-lg-4 ep-icon-theme1-default  "></div>
							<div class="tracking-details-date text-gray-300 font-size-h5 text-center">
								May 29, 2019											
							</div>
							<div class="tracking-details-time text-gray-300 font-size-h5 text-center">
								7:34 am											
							</div>
							<div>
								<div class="font-size-h5">
									<strong>In transit</strong>
								</div>
								<div class="text-gray-300">
									TOKYO-KOTO-KU JP,																																																			
								</div>
							</div>
							</li>
							<!--li class="px-3 py-2 mb-3 font-weight-bold bg-gray-100 text-secondary">
							May 26, 2019								</li-->
							<li class="d-flex align-items-center mb-0 bg-gray-100">
							<div class="font-size-h3 px-3 px-lg-4 ep-icon-theme1-barcode text-secondary  "></div>
							<div class="tracking-details-date text-gray-300 font-size-h5 text-center">
								May 26, 2019											
							</div>
							<div class="tracking-details-time text-gray-300 font-size-h5 text-center">
								8:36 am											
							</div>
							<div>
								<div class="font-size-h5">
									<strong>Shipment information sent to FedEx</strong>
								</div>
								<div class="text-gray-300">
								</div>
							</div>
							</li>
						</ul>
					</div>
				</div>
				</div>
				<div class="trackship_branding" >
					<p>Shipment Tracking info by <a href="https://trackship.info" title="TrackShip" target="blank">TrackShip</a></p>
				</div>			
		<?php 
	
	}		
}
/**
 * Initialise our Customizer settings
 */

$wcast_customizer_settings = new wcast_tracking_page_customizer();