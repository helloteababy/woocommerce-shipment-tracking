 <section id="content2" class="tab_section">
	<div class="tab_inner_container">
		<form method="post" id="wc_ast_settings_form" action="" enctype="multipart/form-data">
			<?php #nonce?>
			
			<input id="tab9" type="radio" name="pagetabs" class="tab_input_1" checked>
			<label for="tab9"><?php _e( 'General', 'woo-advanced-shipment-tracking' ); ?></label>
			<span style="margin: 0 3px;">|</span>						
			<input id="tab10" type="radio" class="tab_input_1" name="pagetabs" <?php if(isset($_GET['tab']) && $_GET['tab'] == 'delivered-order-status'){ echo 'checked'; } ?>>
			<label for="tab10"><?php _e( 'Delivered Order Status', 'woo-advanced-shipment-tracking' ); ?></label>
			<span style="margin: 0 3px;">|</span>
			<label style="vertical-align: top;"><a style="text-decoration: none;" href="<?php echo wcast_initialise_customizer_settings::get_customizer_url('default_controls_section','settings') ?>" class=""><?php _e( 'Tracking Info Display Designer', 'woo-advanced-shipment-tracking' ); ?> <span class="dashicons dashicons-welcome-view-site"></span> </a></label>			
			<br class="clear">
			<section id="content9" class="tpage_section">
				<h3><?php _e( 'General Settings', 'woo-advanced-shipment-tracking' ); ?></h3>
				<?php $this->get_html( $this->get_settings_data() );?>					
			</section>
			<section id="content10" class="tpage_section">
				<h3><?php _e( 'Delivered Order Status', 'woo-advanced-shipment-tracking' ); ?></h3>
				<?php $this->get_html( $this->get_delivered_data() );?>
				<p><?php echo sprintf(__('<strong>PLEASE NOTE</strong> - If you use the custom order status "Delivered", when you deactivate the plugin, you must register this order status in function.php in order to see these orders in the orders admin. You can find the <a href="%s" target="blank">snippet</a> to use in functions.php here or you can manually change all your "delivered" order to "completed" before deactivating the plugin.', 'woo-advanced-shipment-tracking'), 'https://gist.github.com/zorem/6f09162fe91eab180a76a621ce523441'); ?></p>
			</section>			
			<div class="submit">								
				<button name="save" class="button-primary woocommerce-save-button btn_green" type="submit" value="Save changes"><?php _e( 'Save', 'woo-advanced-shipment-tracking' ); ?></button>
				<div class="spinner" style="float:none"></div>
				<div class="success_msg" style="display:none;"><?php _e( 'Data saved successfully.', 'woo-advanced-shipment-tracking' ); ?></div>
				<div class="error_msg" style="display:none;"></div>
				<?php wp_nonce_field( 'wc_ast_settings_form', 'wc_ast_settings_form' );?>
				<input type="hidden" name="action" value="wc_ast_settings_form_update">
			</div>	
		</form>
	</div>	
	<?php include 'zorem_admin_sidebar.php';?>
 </section>