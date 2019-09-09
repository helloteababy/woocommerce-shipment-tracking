<?php 
	$table_header_font_size = '';
	$table_header_font_color = '';
	$table_content_font_size = '';
	$table_content_font_color = '';
	$email_border_color = '';
	$email_border_size = '';
	$shipment_tracking_header_text = '';
	$email_table_backgroud_color = '';
	$tracking_link_font_color = '';
	$tracking_link_bg_color = '';
	
	$display_thumbnail = get_option('display_shipment_provider_thumbnail');
	if(get_option('email_border_color')){ $email_border_color = get_option('email_border_color'); } else{ $email_border_color = "#e4e4e4"; }
	if(get_option('email_border_size')){ $email_border_size = get_option('email_border_size'); } else{ $email_border_size = "1"; }
	if(get_option('email_shipment_tracking_header')){ $shipment_tracking_header = get_option('email_shipment_tracking_header'); } else{ $shipment_tracking_header = "Tracking Information"; }
	if(get_option('email_shipment_tracking_header_text')){ $shipment_tracking_header_text = get_option('email_shipment_tracking_header_text'); } 
	$email_table_backgroud_color = get_option('email_table_backgroud_color');
	
	if(get_option('email_table_header_font_size')){ $table_header_font_size = get_option('email_table_header_font_size'); }
	if(get_option('email_table_header_font_color')){ $table_header_font_color = get_option('email_table_header_font_color'); } else{ $table_header_font_color =  "#737373"; }
	
	if(get_option('email_table_content_font_size')){ $table_content_font_size = get_option('email_table_content_font_size'); }
	
	if(get_option('email_table_content_font_color')){ $table_content_font_color = get_option('email_table_content_font_color'); } else{ $table_content_font_color =  "#737373"; }
	
	if(get_option('email_table_tracking_link_font_color')){ $tracking_link_font_color = get_option('email_table_tracking_link_font_color'); }
	
	if(get_option('email_table_tracking_link_bg_color')){ $tracking_link_bg_color = get_option('email_table_tracking_link_bg_color'); }
	
	$th_column_style = "text-align: center; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;font-size:".$table_header_font_size."px; color: ".$table_header_font_color." ; border: ".$email_border_size."px solid ".$email_border_color."; padding: 12px;";
	
	$td_column_style = "text-align: center; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size:".$table_content_font_size."px; color: ".$table_content_font_color." ; border: ".$email_border_size."px solid ".$email_border_color."; padding: 12px;";
	
	$tracking_link_style = "color: ".$tracking_link_font_color." ;background:".$tracking_link_bg_color.";padding: 10px;";
	
	$remove_date_from_tracking_info = get_option('remove_date_from_tracking_info');
	?>
	<h2><?php echo apply_filters( 'woocommerce_shipment_tracking_my_orders_title', __( $shipment_tracking_header, 'woo-advanced-shipment-tracking' ) ); ?></h2>
	<p><?php echo $shipment_tracking_header_text; ?></p>
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%;border-collapse: collapse;margin-bottom: 20px;background:<?php echo $email_table_backgroud_color; ?>" border="1">

		<thead>
			<tr>
				<th class="tracking-provider" scope="col" class="td" style="<?php echo $th_column_style; ?>"><?php _e( 'Provider', 'woo-advanced-shipment-tracking' ); ?></th>
				<th class="tracking-number" scope="col" class="td" style="<?php echo $th_column_style; ?>"><?php _e( 'Tracking Number', 'woo-advanced-shipment-tracking' ); ?></th>
				<?php if($remove_date_from_tracking_info != 1){ ?>
				<th class="date-shipped" scope="col" class="td" style="<?php echo $th_column_style; ?>"><?php _e( 'Date', 'woocommerce' ); ?></th>
				<?php } ?>
				<th class="order-actions" scope="col" class="td" style="<?php echo $th_column_style; ?>">&nbsp;</th>
			</tr>
		</thead>

		<tbody>
			<tr class="tracking">
				<td class="tracking-provider" style="<?php echo $td_column_style; ?>">
					<?php 
					if($display_thumbnail == 1){
						$src = wc_advanced_shipment_tracking()->plugin_dir_url()."assets/shipment-provider-img/usps.png";	
						?><img style="width: 50px;" src="<?php echo $src; ?>"><?php } _e( 'USPS', 'woo-advanced-shipment-tracking' ); ?>
				</td>
				<td class="tracking-number"  style="<?php echo $td_column_style; ?>">
					123456789
				</td>
				<?php if($remove_date_from_tracking_info != 1){ ?>
				<td class="date-shipped" style="<?php echo $td_column_style; ?>">March 9, 2019</td>	
				<?php } ?>
				<td class="order-actions" style="<?php echo $td_column_style; ?>">
					<a href="#" style="<?php echo $tracking_link_style; ?>" target="_blank"><?php _e( 'Track', 'woo-advanced-shipment-tracking' ); ?></a>
				</td>	
			</tr>
		</tbody>
	</table>