<section id="content3" class="tab_section">
	<div class="d_table" style="">
	<div class="tab_inner_container">
	<form method="post" id="wc_ast_trackship_form" action="" enctype="multipart/form-data">
    	<?php #nonce?>		
        <?php 
		$wc_ast_api_key = get_option('wc_ast_api_key');
		if($wc_ast_api_key){ ?>		
		<input id="tab6" type="radio" name="pagetabs" class="tab_input_1" checked>
		<label for="tab6"><?php _e( 'General', 'woo-advanced-shipment-tracking' ); ?></label>
		<span style="margin: 0 3px;">|</span>
		<input id="tab7" type="radio" class="tab_input_1" name="pagetabs" <?php if(isset($_GET['tab']) && $_GET['tab'] == 'tracking-page'){ echo 'checked'; } ?>>
		<label for="tab7"><?php _e( 'Tracking Page', 'woo-advanced-shipment-tracking' ); ?></label>
		<span style="margin: 0 3px;">|</span>
		<input id="tab8" type="radio" class="tab_input_1" name="pagetabs" <?php if(isset($_GET['tab']) && $_GET['tab'] == 'shipment-status-notifications'){ echo 'checked'; } ?>>
		<label for="tab8"><?php _e( 'Shipment Status Notifications', 'woo-advanced-shipment-tracking' ); ?></label>
		<br class="clear">		
		<section id="content6" class="tpage_section">
		<h3><?php _e('General Settings', 'woo-advanced-shipment-tracking'); ?></h3>	
		<?php	
		$this->get_html( $this->get_trackship_general_data() );
		if($wc_ast_api_key){
		?>		
        <div class="submit">
			<button name="save" class="button-primary woocommerce-save-button btn_green" type="submit" value="Save changes"><?php _e( 'Save', 'woo-advanced-shipment-tracking' ); ?></button>
            <div class="spinner" style="float:none"></div>
            <div class="success_msg" style="display:none;"><?php _e( 'Settings Saved.', 'woo-advanced-shipment-tracking' ); ?></div>
            <div class="error_msg" style="display:none;"></div>
            <?php wp_nonce_field( 'wc_ast_trackship_form', 'wc_ast_trackship_form' );?>
            <input type="hidden" name="action" value="wc_ast_trackship_form_update">
        </div>
		<?php } ?>
		</section>
		<section id="content7" class="tpage_section">
		<h3><?php _e('Tracking Page', 'woo-advanced-shipment-tracking'); ?></h3>	
		<?php	
		$this->get_html( $this->get_trackship_page_data() );
		if($wc_ast_api_key){
		?>
		<a href="<?php echo admin_url('admin-ajax.php')?>?action=preview_tracking_page" class="tracking-preview-link" target="_blank"><?php _e('Click to preview the tracking page', 'woo-advanced-shipment-tracking'); ?></a>
		<p class="tracking-preview-desc"><?php _e('PLEASE NOTE - make sure to save your settings before preview.', 'woo-advanced-shipment-tracking'); ?></p>		
        <div class="submit">
			<button name="save" class="button-primary woocommerce-save-button btn_green" type="submit" value="Save changes"><?php _e( 'Save', 'woo-advanced-shipment-tracking' ); ?></button>
            <div class="spinner" style="float:none"></div>
            <div class="success_msg" style="display:none;"><?php _e( 'Settings Saved.', 'woo-advanced-shipment-tracking' ); ?></div>
            <div class="error_msg" style="display:none;"></div>
            <?php wp_nonce_field( 'wc_ast_trackship_form', 'wc_ast_trackship_form' );?>
            <input type="hidden" name="action" value="wc_ast_trackship_form_update">
        </div>
		<?php } ?>
		</section>
		<section id="content8" class="tpage_section">
		<h3><?php _e('Shipment Status Notifications ', 'woo-advanced-shipment-tracking'); ?></h3>	
		<?php 
		$wcast_enable_delivered_email = get_option('woocommerce_customer_delivered_order_settings'); 
		$wcast_enable_pretransit_email = get_theme_mod('wcast_enable_pretransit_email');
		$wcast_enable_intransit_email = get_theme_mod('wcast_enable_intransit_email');
		$wcast_enable_outfordelivery_email = get_theme_mod('wcast_enable_outfordelivery_email');
		$wcast_enable_failure_email = get_theme_mod('wcast_enable_failure_email');
		$wcast_enable_delivered_status_email = get_theme_mod('wcast_enable_delivered_status_email');
		$wcast_enable_returntosender_email = get_theme_mod('wcast_enable_returntosender_email');
		$wcast_enable_availableforpickup_email = get_theme_mod('wcast_enable_availableforpickup_email');	
		$wc_ast_api_key = get_option('wc_ast_api_key');			
		//echo '<pre>';print_r($wcast_enable_delivered_email['enabled']);echo '</pre>';		
	?>						
	<section class="ac-container">		
		<?php
		if($wc_ast_api_key){
		?>
		<div class="headig_label <?php if($wcast_enable_intransit_email == 1){ echo 'enable'; } else{ echo 'disable'; }?>">	
			<img class="email-icon" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/css/icons/In-Transit-512.png">
			<span class="email_status_span">
				<span class="mdl-list__item-secondary-action shipment_status_toggle">
					<label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="wcast_enable_intransit_email">
						<input type="checkbox" name="wcast_enable_intransit_email" id="wcast_enable_intransit_email" class="mdl-switch__input" value="yes" <?php if($wcast_enable_intransit_email == 1) { echo 'checked'; } ?> />
					</label>
				</span>			
			</span>
			<a href="<?php echo wcast_intransit_customizer_email::get_customizer_url('customer_intransit_email','shipment-status-notifications') ?>" class="email_heading"><?php _e('In Transit', 'woo-advanced-shipment-tracking'); ?></a>
			<a class="edit_customizer_a" href="<?php echo wcast_intransit_customizer_email::get_customizer_url('customer_intransit_email','shipment-status-notifications') ?>"><?php _e('Edit', 'woo-advanced-shipment-tracking'); ?></a>
			<p class="shipment_about"><?php _e('Carrier has accepted or picked up shipment from shipper. The shipment is on the way.', 'woo-advanced-shipment-tracking'); ?></p>
		</div>			

		<div class="headig_label <?php if($wcast_enable_returntosender_email == 1){ echo 'enable'; } else{ echo 'disable'; }?>">
			<img class="email-icon" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/css/icons/return-to-sender-512.png">		
			<span class="email_status_span">
				<span class="mdl-list__item-secondary-action shipment_status_toggle">
					<label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="wcast_enable_returntosender_email">
						<input type="checkbox" name="wcast_enable_returntosender_email" id="wcast_enable_returntosender_email" class="mdl-switch__input" value="yes" <?php if($wcast_enable_returntosender_email == 1) { echo 'checked'; } ?> />
					</label>
				</span>
			</span>
			<a href="<?php echo wcast_returntosender_customizer_email::get_customizer_url('customer_returntosender_email','shipment-status-notifications') ?>" class="email_heading"><?php _e('Return To Sender', 'woo-advanced-shipment-tracking'); ?></a>
			<a class="edit_customizer_a" href="<?php echo wcast_returntosender_customizer_email::get_customizer_url('customer_returntosender_email','shipment-status-notifications') ?>"><?php _e('Edit', 'woo-advanced-shipment-tracking'); ?></a>
			<p class="shipment_about"><?php _e('Shipment is returned to sender', 'woo-advanced-shipment-tracking'); ?></p>
		</div>

		<div class="headig_label <?php if($wcast_enable_availableforpickup_email == 1){ echo 'enable'; } else{ echo 'disable'; }?>">	
			<img class="email-icon" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/css/icons/available-for-picup-512.png">		
			<span class="email_status_span">
				<span class="mdl-list__item-secondary-action shipment_status_toggle">
					<label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="wcast_enable_availableforpickup_email">
						<input type="checkbox" name="wcast_enable_availableforpickup_email" id="wcast_enable_availableforpickup_email" class="mdl-switch__input" value="yes" <?php if($wcast_enable_availableforpickup_email == 1) { echo 'checked'; } ?> />
					</label>
				</span>
			</span>
			<a href="<?php echo wcast_availableforpickup_customizer_email::get_customizer_url('customer_availableforpickup_email','shipment-status-notifications') ?>" class="email_heading"><?php _e('Available For Pickup', 'woo-advanced-shipment-tracking'); ?></a>
			<a class="edit_customizer_a" href="<?php echo wcast_availableforpickup_customizer_email::get_customizer_url('customer_availableforpickup_email','shipment-status-notifications') ?>"><?php _e('Edit', 'woo-advanced-shipment-tracking'); ?></a>
			<p class="shipment_about"><?php _e('The shipment is ready to pickup.', 'woo-advanced-shipment-tracking'); ?></p>
		</div>
		<div class="headig_label <?php if($wcast_enable_outfordelivery_email == 1){ echo 'enable'; } else{ echo 'disable'; }?>">
			<img class="email-icon" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/css/icons/Out-for-Delivery-512.png">
			<span class="email_status_span">
				<span class="mdl-list__item-secondary-action shipment_status_toggle">
					<label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="wcast_enable_outfordelivery_email">
						<input type="checkbox" name="wcast_enable_outfordelivery_email" id="wcast_enable_outfordelivery_email" class="mdl-switch__input" value="yes" <?php if($wcast_enable_outfordelivery_email == 1) { echo 'checked'; } ?> />
					</label>
				</span>				
			</span>
			<a href="<?php echo wcast_outfordelivery_customizer_email::get_customizer_url('customer_outfordelivery_email','shipment-status-notifications') ?>" class="email_heading"><?php _e('Out For Delivery', 'woo-advanced-shipment-tracking'); ?></a>
			<a class="edit_customizer_a" href="<?php echo wcast_outfordelivery_customizer_email::get_customizer_url('customer_outfordelivery_email','shipment-status-notifications') ?>"><?php _e('Edit', 'woo-advanced-shipment-tracking'); ?></a>
			<p class="shipment_about"><?php _e('Carrier is about to deliver the shipment', 'woo-advanced-shipment-tracking'); ?></p>
		</div>	

		<div class="delivered_shipment_label headig_label <?php if($wcast_enable_delivered_status_email == 1){ echo 'enable'; } else{ echo 'disable'; }?> <?php if($wcast_enable_delivered_email['enabled'] === 'yes'){ echo 'delivered_enabel'; } ?>">
			<img class="email-icon" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/css/icons/Delivered-512.png">
			<span class="email_status_span">
				<span class="mdl-list__item-secondary-action shipment_status_toggle">
					<label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="wcast_enable_delivered_status_email">
						<input type="checkbox" name="wcast_enable_delivered_status_email" id="wcast_enable_delivered_status_email" class="mdl-switch__input" value="yes" <?php if($wcast_enable_delivered_status_email == 1 && $wcast_enable_delivered_email['enabled'] != 'yes') { echo 'checked'; } ?> <?php if($wcast_enable_delivered_email['enabled'] === 'yes'){ echo 'disabled'; }?> />
					</label>
				</span>				
			</span>			
			<a href="<?php echo wcast_delivered_customizer_email::get_customizer_url('customer_delivered_status_email','shipment-status-notifications') ?>" class="email_heading <?php if($wcast_enable_delivered_email['enabled'] === 'yes'){ echo 'disabled_link'; }?>"><?php _e('Delivered', 'woo-advanced-shipment-tracking'); ?></a>
			<a class="edit_customizer_a <?php if($wcast_enable_delivered_email['enabled'] === 'yes'){ echo 'disabled_link'; }?>" href="<?php echo wcast_delivered_customizer_email::get_customizer_url('customer_delivered_status_email','shipment-status-notifications') ?>"><?php _e('Edit', 'woo-advanced-shipment-tracking'); ?></a>
			<p class="shipment_about"><?php _e('The shipment was delivered successfully', 'woo-advanced-shipment-tracking'); ?></p>
			<p class="delivered_message <?php if($wcast_enable_delivered_email['enabled'] === 'yes'){ echo 'disable_delivered'; }?>"><?php _e("You already have delivered email enabled, to enable this email you'll need to disable the order status delivered in settings.", 'woo-advanced-shipment-tracking'); ?></p>
		</div>	
			
		<div class="headig_label <?php if($wcast_enable_failure_email == 1){ echo 'enable'; } else{ echo 'disable'; }?>">
			<img class="email-icon" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/css/icons/failure-512.png">
			<span class="email_status_span">
				<span class="mdl-list__item-secondary-action shipment_status_toggle">
					<label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="wcast_enable_failure_email">
						<input type="checkbox" name="wcast_enable_failure_email" id="wcast_enable_failure_email" class="mdl-switch__input" value="yes" <?php if($wcast_enable_failure_email == 1) { echo 'checked'; } ?> />
					</label>
				</span>				
			</span>
			<a href="<?php echo wcast_failure_customizer_email::get_customizer_url('customer_failure_email','shipment-status-notifications') ?>" class="email_heading"><?php _e('Failed Attempt', 'woo-advanced-shipment-tracking'); ?></a>
			<a class="edit_customizer_a" href="<?php echo wcast_failure_customizer_email::get_customizer_url('customer_failure_email','shipment-status-notifications') ?>"><?php _e('Edit', 'woo-advanced-shipment-tracking'); ?></a>
			<p class="shipment_about"><?php _e('Carrier attempted to deliver but failed, and usually leaves a notice and will try to deliver the package again.', 'woo-advanced-shipment-tracking'); ?></p>
		</div>		
		<?php } ?>	
		</section>	
		</section>
		<?php } else{ ?>
			<div class="section-content trackship_section">
				<div class="" id="">
					<div class="text-center">
						<img src="https://trackship.info/wp-content/uploads/2019/08/trackship-400.png" class="trackship_logo">
					</div>					
					<div class="text-center">
						<h3 class="heading">Automate your Shipment Tracking &amp; Delivery Operations</h3>
						<p class="lead ts_description">TrackShip pro actively sends shipment status updates to your WooCommerce store and streamlines your order management process and provide improved post-purchase experience to your customers.</p>
						<a href="https://trackship.info?utm_source=wp_admin&utm_medium=referral&utm_campaign=coming_soon" target="_self" class="trackship_button"><span>SIGNUP NOW</span></a>
					</div>					
					<div class="col small-12 large-12">
						<div class="col-inner text-center">
							<div class="container section-title-container">
							<h3 class="section-title section-title-center"><b></b><span class="section-title-main">included Features</span><b></b></h3>
							</div>
							<div class="row row-box-shadow-1" id="row-582581561">
							<div class="col-4">
								<div class="col-inner">
									<div class="icon-box featured-box icon-box-center text-center">
										<div class="icon-box-img" style="width: 60px">
										<div class="icon">
											<div class="icon-inner"></div>
										</div>
										</div>
										<div class="icon-box-text last-reset">
										<img class="trackship-icon" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/css/icons/Multi-Carrier-Support.png">
										<h4>Multi-Carrier </br>Support</h4>
										<p>TrackShip’s Tracking API auto-tracks all your shipments with <a href="https://trackship.info/shipping-providers/" target="blak">100+ shipping providers</a> across the globe, so you and your customers can see exactly where their package is 24×7.</p>
										</div>
									</div>
								</div>
							</div>
							<div class="col-4">
								<div class="col-inner">
									<div class="icon-box featured-box icon-box-center text-center">
										<div class="icon-box-img" style="width: 60px">
										<div class="icon">
											<div class="icon-inner"></div>
										</div>
										</div>
										<div class="icon-box-text last-reset">
										<img class="trackship-icon" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/css/icons/woo-inegration.png">
										<h4>WooCommerce </br>Integration</h4>
										<p>Trackship fully integrates with WooCommerce with the  <a href="https://wordpress.org/plugins/woo-advanced-shipment-tracking/">Advanced Shipment Tracking</a> plugin, and most of its features can be easily managed directly from your WooCommerce admin panel.</p>
										</div>
									</div>
								</div>
							</div>
							<div class="col-4">
								<div class="col-inner">
									<div class="icon-box featured-box icon-box-center text-center">
										<div class="icon-box-img" style="width: 60px">
										<div class="icon">
											<div class="icon-inner"></div>
										</div>
										</div>
										<div class="icon-box-text last-reset">
										<img class="trackship-icon" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/css/icons/customer-support.png">
										<h4>Better Customer </br>Support</h4>
										<p>The most frequent question shoppers ask is, “Where's my order?” with TrackShip, you and your customers will know where the package is at all time. tracking information will display on WooCommerce orders panel.</p>
										</div>
									</div>
								</div>
							</div>
							<div class="col-4">
								<div class="col-inner">
									<div class="icon-box featured-box icon-box-center text-center">
										<div class="icon-box-img" style="width: 60px">
										<div class="icon">
											<div class="icon-inner"></div>
										</div>
										</div>
										<div class="icon-box-text last-reset">
										<img class="trackship-icon" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/css/icons/delivery-email-2.png">
										<h4>Shipment Status </br>Notifications</h4>
										<p>Engage your Customer with personalized shipment status email notifications triggered by shipment status changes; In Transit, Out For Delivery, Delivered or have an Exception.</p>
										</div>
									</div>
								</div>
							</div>
							<div class="col-4">
								<div class="col-inner">
									<div class="icon-box featured-box icon-box-center text-center">
										<div class="icon-box-img" style="width: 60px">
										<div class="icon">
											<div class="icon-inner"></div>
										</div>
										</div>
										<div class="icon-box-text last-reset">
										<img class="trackship-icon" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/css/icons/Branded-Tracking-Page.png">
										<h4>Branded Tracking </br>Page</h4>
										<p>Direct customers to a tracking page on your website and further engage customers after sales. Instead of sending your customers to track their order at a carrier page, you can direct customers to a detailed tracking page on your store.</p>
										</div>
									</div>
								</div>
							</div>
							<div class="col-4">
								<div class="col-inner">
									<div class="icon-box featured-box icon-box-center text-center">
										<div class="icon-box-img" style="width: 60px">
										<div class="icon">
											<div class="icon-inner"></div>
										</div>
										</div>
										<div class="icon-box-text last-reset">
										<img class="trackship-icon" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/css/icons/shiping-and-delivery-analytics.png">
										<h4>Shipping & Delivery </br>Analytics</h4>
										<p>Analyse delivery performance using tracking data. Find out exception for your past shipments and get an overview of your historic shipments data, find shipments by provider, delivery status,, ship date and more.</p>
										</div>
									</div>
								</div>
							</div>
							</div>
						</div>
					</div>
				</div>
				</div>
		<?php }
	?>
	</form>
	</div>
	<?php 
	if($wc_ast_api_key){
		include 'zorem_admin_ts_sidebar.php';
	}
	?>
	</div>
</section>