=== Advanced Shipment Tracking for WooCommerce  ===
Contributors: zorem
Tags: woocommerce, delivery, shipping, shipment tracking, tracking, fedex, ups, usps
Requires at least: 4.0
Requires PHP: 5.2.4
Tested up to: 5.2.3
Stable tag: 5.2.3
License: GPLv2 
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add shipment tracking information to your WooCommerce orders and provide your customers with an easy way to track their orders, reduce your customer service inquiries and to Improve your customers Post-Purchase Experience.

== Description ==

Add shipment tracking information to your WooCommerce orders and provide your customers with an easy way to track their orders.

This plugin provide Shop managers easy ways to add shipment tracking information to order, once the order is Completed (Shipped) the customer will receive the tracking details and a link to tracker their order in the order emails and on my account section.

AST provide a list of 100+ shipping providers with pre-set tracking link and image, you can add your own custom provider, customize the tracking display, created delivered order status, customize the emails and more.

== Features ==

* Add shipment tracking info to orders – shipping provider, tracking number and shipping date
* Add multiple tracking numbers to orders
* Add tracking info to orders from the orders admin (inline)
* List of 100+ default shipping providers (carriers)
* Select shipping providers to use when adding tracking info to orders
* Set the default provider when adding tracking info to orders
* Add custom shipping providers
* Sync the Providers list with TrackShip
* Display Shipment tracking info and tracking link on user accounts
* Display Shipment tracking info and tracking link on customer emails
* Customize and preview the Tracking info display on customer emails using email designer.
* Choose on which Customer emails to include the tracking info.
* Bulk import tracking info to orders with CSV file.
* WooCommerce REST API support to update shipment tracking information
* Rename the Completed Order status to Shipped
* Enable Delivered custom order status
* Customer order status Delivered email to customers
* Customize and preview the Delivered status email using email designer.

== TrackShip Integration== 

[TracksShip](https://trackship.info/) is a premium shipment tracking API flatform that fully integrates with WooCommerce with the Advanced Shipment Tracking. TrackShip automates the order management workflows, reduces customer inquiries, reduces time spent on customer service, and improves the post-purchase experience and satisfaction of your customers.

You must have account [TracksShip](https://trackship.info/) and connect your store in order to activate these advanced features:

* Automatically track your shipments with 100+ shipping providers.
* Display Shipment Status and latest shipment status, update date and est. delivery date on WooCommerce orders admin.
* Option to manually get shipment tracking updates for orders.
* Automatically change order status to Delivered once the shipment is delivered to your customers.
* Option to filter orders with invalid tracking numbers or by shipment status event in orders admin
* Send personalized emails to notify the customer when their shipments are In Transit, Out For Delivery, Delivered or have an exception.
* Direct customers to a Tracking page on your store.

== Localization == 

The plugin is translation ready, we added translation to: 
Hebrew, Hindi, Italian, Norwegian (Bokmål), Russian, Swedish, Turkish, Bulgarian, Danish, German, Spanish (Spain), French (France), Greek


== Compatibility with WooCommerce email customization plugins ==

* [Kadence WooCommerce Email Designer](https://wordpress.org/plugins/kadence-woocommerce-email-designer/)
* [WP HTML Mail – Email Designer](https://wordpress.org/plugins/wp-html-mail/)
* [Decorator – WooCommerce Email Customizer](https://wordpress.org/plugins/decorator-woocommerce-email-customizer/)
* [WooCommerce Email Customizer with Drag and Drop Email Builder](https://codecanyon.net/item/woocommerce-email-customizer-with-drag-and-drop-email-builder/19849378)
* [Email Customizer for WooCommerce](https://codecanyon.net/item/email-customizer-for-woocommerce/8654473)

== Compatibility with custom order numbers plugins for WooCommerce ==
* [Custom Order Numbers for WooCommerce](https://wordpress.org/plugins/custom-order-numbers-for-woocommerce/)
* [WooCommerce Sequential Order Numbers](https://wordpress.org/plugins/woocommerce-sequential-order-numbers/)
* [WP-Lister Pro for Amazon](https://www.wplab.com/plugins/wp-lister-for-amazon/)

== Compatibility with PDF Invoices & Packing Slips  plugins ==
* [WooCommerce PDF Invoices & Packing Slips plugin](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips).


https://www.youtube.com/watch?v=Mw7laecPtyw

== Frequently Asked Questions == 

= Where will my customer see the tracking info?
Tracking info and link to track the package on the shipment provider website will be added to the “Completed” (Shipped) orders emails.  We will also display the tracking info in user accounts in the order history tab (see screenshots)
= Can I add multiple tracking numbers to orders?
Yes, you can add as many tracking numbers to orders and they will all be displayed to your customers. 
= Can I add a shipping provider that is not on your list?
Yes, you can add custom providers, choose your default shipment provider, Change the providers order in the list and enable only providers that are relevant to you.
= Can I design the display of Tracking info on WooCommerce emails?
Yes, you have full control over the design and display of the tracking info and you can customize it.
= can I track my order and send shipment status and delivery notifications to my customers?
Yes, you can signup to Trackship and we provide full integration, once connected, TrackShip proactively sends shipment status updates to your WooCommerce store and streamlines your order management process and provide improved post-purchase experience to your customers.
We are currently in beta stage, [signup]https://trackship.info to get your invitation
= How do I set the custom provider URL so it will direct exactly to the tracking number results?
You can add tracking number parameter in this format:
http://shippingprovider.com?tracking_number=%number% , %number% - this variable will hold the tracking number for the order.
= is it possible to import multiple tracking numbers to orders in bulk?
Yes, you can use our Bulk import option to import multiple tracking inumbers to orders, you need to add each tracking number is one row.
=How do I use the Rest API to add/retrieve/delete tracking info to my orders?
you can use the plugin to add, retrieve, delete tracking information for orders using WooCommerce REST API. 
For example, in order to add tracking number to order:
use the order id that you wish to update in the URL instead of <order-id>, add the shipping provider and tracking code. 

curl -X POST 
http://a32694-tmp.s415.upress.link/wp-json/wc/v1/orders/<order-id>/shipment-trackings \
    -u consumer_key:consumer_secret \
    -H "Content-Type: application/json" \
    -d '{
  "tracking_provider": "USPS",
  "tracking_number": "123456789",
}'

== Installation ==

1. Upload the folder `woo-advanced-shipment-tracking` to the `/wp-content/plugins/` folder
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Select default shipping provider from setting page and add tracking number in order page.

== Changelog ==

= 2.4.6 =
* Updated option to Send only last 30 days shipped orders to trackship from settings panel

= 2.4.5 =
* Fixed issue with search
* Fixed translabled string issue in delivered email
* Added option to Send all shipped orders to trackship from settings panel
* Updated Ebay meta key - '_ebay_extended_order_id'
* Removed - limit bulk action Get Shipment Status to maximum 100 orders at one time

= 2.4.4 =
* Fixed PHP Notice:  Undefined index: DHL uk in plugins\woo-advanced-shipment-tracking\includes\class-wc-advanced-shipment-tracking-install.php on line 1560
* Fixed - If Delivered Status is not enabled - do not change order status "Delivered" or to any other status (Pending right now)
* Updated html class of add tracking provider lightbox in orders listing page
* Updated - remove event grouping by status to display all events in trackship tracking page
* Added - limit bulk action Get Shipment Status to maximum 100 orders at one time
* Added description on select tracking page field

= 2.4.3 =
* Fixed issue with TranslatePress plugin
* Fixed issue in tracking page
* Fixed issue if ts_slug field not created in database
* Added compatibility with WP-Lister for eBay
* Added hook for order number in tracking page

= 2.4.2 =
* Fixed issue in tracking page for unknown status
* Added PostNL International 3S,GLS Europe and Yun Express Tracking  provider
* In tracking url added two more parameter - %country_code% and %postal_code%
* Updated code for add tracking item programatically
* Updated tracking page endpoint
* Removed Tracking URL validation for add/edit custom shiping provider and if not added tracking url than not display track button

= 2.4.1 =
* Fixed Hermes World shipping provider issue
* Fixed warnings Undefined index: Hermes Germany
* Updated completed order status to shipped on "On which customer order status email to include tracking info?" is "Rename the “Completed” Order status to “Shipped”" enabled

= 2.4 =
* Fixed warnings in tracking page
* Fixed error from customizer if WooCommerce uninstalled
* Fixed date format issue in Bulk Upload 
* Addes Sync Providers List functionality for all users
* Added Hermes World Shipping provider
* Added option for "mark as shipped" will be selected by default when adding tracking info to orders
* Added Shipment Status filter in order list panel
* Updated Delivered orderemail customizer layout and Google analytics tracking
* Updated tracking page design

= 2.3.9 =
* Added "DHL Freight" provider
* Added functionality when check connection store status if api key is not available in plugin than add it

= 2.3.8 =
* Fixed - Trackship connection issue when trackship setting saved

= 2.3.7 =
* Removed option auto change of delivered orders to completed when deactivating
* Updated Royal Mail shipping provider logo
* Fixed - Notice Trying to get property ‘ID’ of non-object

= 2.3.6 =
* Fixed jQuery(…).select2 is not a function admin.js error 

= 2.3.5 =
* Added option Google Analytics link tracking in email customizer
* Added option for adding and deleting tracking info in order from order list panel
* Updated - Set Change the "Delivered" orders to "Completed" when you deactivate the plugin option default No.
* Updated all language translation files
* Updated design of settings panel
* Removed Option "Retry api call for failed order" from TrackShip

= 2.3 =
* Fixed - PHP Warning
* Fixed - Unable to override customer-delivered-order.php template
* Fixed - Sync Providers issues with images when adding new providers
* Added details info of the changes after Sync Providers
* Added "Change order to Shipped?" checkbox when adding tracking info to orders
* Added "DHL Freight","Sendle" and "Deppon" shipping provider
* Added Slug field to shipping providers table
* Added ajax overlay in customizer refresh
* Updated settings page design
* Updated - When new provider added default status is inactive
* Updated - added parameter in return url of tracking info customizer and shipping status email customizer so when close customizer directly go into particular tab
* Updated - remove trim from tracking number when add tracking info to orders

= 2.2.5 =
* Fixed - PHP Warning
* Added 20+ new Shipping Provider
* Updated design of emails section in customizer
* Updated Shipment Status Notifications  in TrackShip
* Updated Shipping Providers List Design
* Updated Correios, Mondial Relay
* Updated Shipping Provider La Poste, Colissimo and Chronopost
* Updated Shipping Provider DHL.at, DHL.cz, DHL.se, DHL
* Updated TrackShip Tracking Page

= 2.2 =
* Fixed issue with Kadence email customizer plugin
* Fixed – Fixed PHP Notice: Undefined index: custom_tracking_provider
* Add Shipping Provider Mondial Relay as a default provider
* Fixed delivered icon issue in order list action
* Added option for which customer order status email user want to include tracking info.
* Added option in tracking info customizer to display tracking info before order details or after order details.
* Added Change order status to Delivered button in order preview popup if order status is complete
* Added option in settings to enable/disable for show tracking info in Invoice/Packing Slips for compatibility with WooCommerce PDF Invoices & Packing Slips plugin.
* Updated Greek language translation
* Updated design of shipment status email customizer

= 2.1 =
* Created new order status “Updated Tracking” for TrackShip users.
* Added action button in order list to mark completed order as delivered
* Added settings so user can select that change all delivered order status to completed or remain same when plugin deactivate
* Updated all language file for new changes

= 2.0.9 =
* Update Track URL of CouriersPlease and IL Post
* Create Shipment tracking when TrackShip connected

= 2.0.8 =
* Bug Fixed - Fixed issue for subscription report.

= 2.0.7 =
* Bug Fixed - Fixed front end design conflict issue with theme 

= 2.0.6 =
* Update Swedish language translation files
* Change Delhivery tracking URL
* Added functionality to change lable of “Complete Order” to “Shipped Order” in email setting page

= 2.0.5 =
* Bug Fixed - Undefined index: class in class-wc-advanced-shipment-tracking-admin.php on line 741
* Updated language files

= 2.0.4 =
* Update design of email notification tab, Set toggle button in that to enable/disable email notifications
* Update german language translation for “Track”
* In Tracking info display customizer add settings for change table header text
* Bug fixed - Fix typo issue in Bulk Upload tab
* Make all text translatable in Bulk Upload tab

= 2.0.3 =
* Teste with the latest version of WooCommerce
* Bug fixed - YITH WooCommerce Request A Quote emails disappeared when installing plugin
* Bug fixed - Call to undefined method WC_Advanced_Shipment_Tracking_Install::get_tracking_items() in class-wc-advanced-shipment-tracking-install.php:660
* Updated Swedish language translation files
* Updated design of email notifications tab

= 2.0.2 =
* Added Email recipient field in delivered order status email
* Bug fixed - fixed several typos mistakes
* Bug fixed - Shipment providers settings changed when doing version update
* Bug fixed - if shipped date is null than it will take current date in bulk upload
* Bug fixed - Undefined index: show_track_label in class-wc-tracking-info-customizer.php on line 202

= 2.0.1 =
* Added An Post Shipping provider as a default shipping provider
* Update minor design of shipping providers list
* Bug Fixed - Frontend design issue
* In Tracking Info Display designer added option for add label on Track
* In Tracking Rest API Endpoint added option for Status shipped

= 2.0 =
* Added TrackShip Integration
* Added compatibility with WooCommerce PDF Invoices & Packing Slips plugin
* Added compatibility with Custom Order Numbers plugin to Tracking Rest API Endpoint
* Added compatibility with Sequential order numbers plugin to Tracking Rest API Endpoint
* Twick: Changed Tracking Display "Date" text to "Shipped Date"
* Fix - Customizer did not load on Store Front & Bazar Shop Themes
 
= 1.9.9.3 =
* Bug fixed - Notice: Undefined index: wcast_show_billing_address
* Bug fixed - Notice: Undefined index: wcast_show_shipping_address
* Bug fixed - Notice: Undefined variable: username
* Bug fixed - Delivered order status email not sending when change order stats to delivered

= 1.9.9.2 =
* Bug fixed - Undefined index: in /plugins/woo-advanced-shipment-tracking/includes/class-wc-advanced-shipment-tracking-admin.php on line 671
* Bug fixed - Tracking Info is not showing when using WooCommerce Email Customizer with Drag and Drop Email Builder

= 1.9.9.1 =
* Bug fixed - Cannot read property ‘length’ of undefined

= 1.9.9 =
* Updated database for default shipping providers, remove duplicate entry of default shipping providers and add default shipping provider if not available.

= 1.9.8 =
* Improved Customizer UI
* Improved plugin settings UI
* Added more design options to Customizer
* Added Customizer option to change tracking table padding
* Added Support for translation for all new plugin settings/options
* Fix - variables not working in delivered email header in Customizer
* Fix - delivered email customizer link directed to tracking display designer

= 1.9.7.1 =
* Added functionality for remove white space from tracking number in tracking link

= 1.9.7 =

* Updated add and edit custom shipping provider design
* Change shipping provider name from DHL Logistics to DHL Parcel
* Bug fixed - Sorting companies by country instead of country code
* Added option to search by country in providers dropdown in order page
* Remove tracking info to display from processing order,cancel order, refund order and on hold order.
* Added functionality for customize Delivered order status email into customizer
* Removed WooCommerce default email fro Delivered order status email
* Changed URL of StarTrack shipping provider
* In tracking info display customizer change preview order defaut test from 'Mockup order' to 'Select order to preview'
* Added functionality for remove white space from tracking number when added in order

= 1.9.6.1 =

* Bug fixed - Added Save button in settings tab

= 1.9.6 =

* Updated Email Notifications settings design
* Updated add custom shipping provider design
* Fixed issue of Hebrew shipping provider not added in orders
* Added DHL Logistics Shipping provider as a default shipping provider
* Fixed bulk upload issue - if shipped date is null than it will take current date
* Added functionality for add multiple tracked into bulk upload for single order
* Setup tracking info display customizer with live preview in email
* Setup functionality for show Tracking Info only in completed order

= 1.9.5.2 =
* Added DTDC Shipping provider as a default shipping provider

= 1.9.5.1 =
* Bug Fixed - Fixed email template issue for WooCommerce Delivered order.

= 1.9.5 =
* Update design of email notification section for small screen
* Update design of bulk upload section
* Make bulk upload compatible with Custom Order Numbers for WooCommerce plugin
* Make bulk upload compatible with WooCommerce Sequential Order Numbers plugin
* Make bulk upload compatible with WP-Lister Pro for Amazon plugin
* Change Chronopost provider url in database

= 1.9.4.4 =
* Bug Fixed - Fixed all php warnings
* Bug Fixed - Fixed ArrowXL shipping provider country issue
* Updated design of Bulk upload section

= 1.9.4.3 =
* Bug Fixed - Fixed bulk upload csv issue

= 1.9.4.2 =
* Bug Fixed - Fixed design issue in small screen

= 1.9.4.1 =
* Added language support for Greek.
* Bug Fixed - added missing translated strings text domains in js file.

= 1.9.4 =
* language file updated.

= 1.9.3.2 =
* language file updated.

= 1.9.3.1 =
* Bug Fixed - Warnig when installing the plugin - Duplicate column name 'enable'. 

= 1.9.3 =
* Added Turkish Translation
* Minor admin css updates
* Select Delivered Email Type - Changed to Ajax save when changing selection.

= 1.9.2.5 =
* Bug Fixed - Email templated file override issue in theme
* Bug Fixed - New email notification order status not show

= 1.9.2.4 =
* Added WP Editor in email notification content
* Added option for choose delivered email type - Default WooCommerce email or Advanced Shipment Tracking plugin email.
* Added option for remove date from tracking info.
* Added language support for Danish.

= 1.9.2.3 =
* Added variable for Tracking Info Table in Delivered Email notification

= 1.9.2.2 =
* Bug Fixed - Tracking info display table design in My Account page
* Added Resend delivered order email notification to order actions (if set)
* Added admin notice for information that "you can change (and preview) the style of the shipment tracking display on order email and customer account"

= 1.9.2.1 =
* Bug Fixed - tiptip() is not a function.

= 1.9.2 =
* Additional shipping providers + removed non relevant shipping providers.
* Added option for change the style of tracking info display in email and my account.
* Added functionality for Bulk upload shipment tracking details.
* Added email notifications option in settings for delivered order.
* Added Bulgerian language support.
* Fixed “Delivered” status not included reports on non-english websites.

= 1.9.1.1 =
* fix translation of date

= 1.9.1.0 =
* Bug fix - Fix multisite issue

= 1.9 =
* Setup Customer delivered order email if Custom order status - “Delivered” is enabled.
* Updated swedish language

= 1.8.9 =
* Added plugin compatibility with WordPress multisite.
* Added language support for Swedish.

= 1.8.8 =
* Bug fix - Fix translation issue for front page information such as tracking information, provider, tracking number, date, track button. 

= 1.8.7 =
* Added filter in get_providers function for shipping url 

= 1.8.6 =
* Improve functionality for add custom icon for custom shipment provider

= 1.8.5 =
* Bug fix - Hebrew RTL with Ajax

= 1.8.4 = 
* Improved French Language Translation

= 1.8.3 = 
* Bug fixed.

= 1.8.2 = 
* Bug fixed.

= 1.8.1 = 
* Improved UI of Shipping Provider Table
* Added option for change Delivered status label color
* Added Norweign,Spanish,Hindi,Italian and Russian Language Support.

= 1.8 = 
* Add version in js file to fix cache issue

= 1.7 = 
* fixed. Language Issue 

= 1.6 = 
* fixed. Fatal error: Cannot redeclare maybe_create_table() 

= 1.5 = 
* improved UI.
* Added funtionality of ajax for adding tracking number
* shipping provider table css updated.
* added settings to new custom order status ( Delivered ) and rename existing order status Completed to Shipped.


= 1.4 = 
* improved UI.
* Added language support for French and German.

= 1.3 = 
* improved UI.

= 1.2 = 
* Added support for woocommerce shipment tracking official plugin.
* Added rest api support.
* improved UI.

= 1.1 =
* Added Hebrew Language Support.

= 1.0 =
* Initial version.