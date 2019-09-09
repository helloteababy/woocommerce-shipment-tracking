                <div class="zorem_admin_sidebar">
                	<div class="ts_launch">                    	
                        <h3>Advanced Shipment Tracking</h3>
						<p>Hey, your opinion matters to us. If you like the advanced shipment tracking plugin, please take 1 minute to leave us review on WordPress.org</br>
						<span>Thanks :)</span>
						</p>
						
                        <a href="https://wordpress.org/support/plugin/woo-advanced-shipment-tracking/reviews/#new-post" target="_self" class="button button-primary btn_green" target="_blank"><span><?php _e('Leave your review', 'woo-advanced-shipment-tracking'); ?></span><i class="icon-angle-right"></i></a>
                    </div>
                    
                	<!--<div class="zorem-sidebar-title"></div>-->
                    <div class="zorem-sidebar__section">
                    	<h3>More plugins by zorem</h3>
						<?php
						$plugin_array = array(
							array(
								'name'		=> 'Shop Manager Admin Bar for WooCommerce',
								'url'		=> 'https://wordpress.org/plugins/woo-shop-manager-admin-bar/',
								'img'		=> 'woocommerce-shop-manager-admin-bar-thumbnail.jpg',
							),
							array(
								'name'		=> 'Ajax Login/Register for WooCommerce',
								'url'		=> 'https://wordpress.org/plugins/woo-ajax-loginregister/',
								'img'		=> 'WooCommerce-Ajax-Login-Register-thumbnail.jpg',
							),
							/*0 => array(
								'name'		=> 'Country Based Restrictions for WooCommerce',
								'url'		=> 'https://wordpress.org/plugins/woo-product-country-base-restrictions/',
								'img'		=> 'WooCommerce-Country-Based-Restrictions-thumbnail.jpg',
							),
							2 => array(
								'name'		=> 'Sales Report Email for WooCommerce',
								'url'		=> 'https://wordpress.org/plugins/woo-advanced-sales-report-email/',
								'img'		=> 'woocommerce-advanced-sales-report-email-thumbnail.jpg',
							),
							4 => array(
								'name'		=> 'Sales Report By Country for WooCommerce',
								'url'		=> 'https://wordpress.org/plugins/woo-sales-by-country-reports/',
								'img'		=> 'country-based-report-banner-thumbnail.jpg',
							),
							5 => array(
								'name'		=> 'Sales Report for WooCommerce & WP-Lister',
								'url'		=> 'https://wordpress.org/plugins/woo-sales-report-for-wp-lister/',
								'img'		=> 'WooCommerce-Sales-Report-for-WP-Lister-thumbnail.jpg',
							),
							6 => array(
								'name'		=> 'Bit Payment Gateway for WooCommerce',
								'url'		=> 'https://wordpress.org/plugins/woo-bit-payment-gateway/',
								'img'		=> 'WooCommerce-Bit-payment-thumbnail.jpg',
							),*/
						);
						?>	
                        <ul>
							<?php foreach($plugin_array as $plugin){ ?>
								<li><img class="plugin_thumbnail" src="<?php echo wc_advanced_shipment_tracking()->plugin_dir_url()?>assets/images/<?php echo $plugin['img']?>"><a class="plugin_url" href="<?php echo $plugin['url']?>" target="_blank"><?php echo $plugin['name']?></a></li>
							<?php }?>
                        </ul>
                        <!--a href="https://www.zorem.com/plugins/" target="_blank">view all zorem plugins</a-->
                    </div>
                </div>