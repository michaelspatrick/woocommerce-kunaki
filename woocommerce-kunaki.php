<?php
/*
Plugin Name: WooCommerce / Kunaki Integration
Plugin URI: https://www.dragonsociety.com
Description: Extends WooCommerce to allow for dropshipping of DVDs with Kunaki.
Version: 1.0.1
Author: DSI
Author URI: http://codex.wordpress.org
Groups: e-commerce
*/

  // Define the version of this plugin
  global $kunaki_db_version;
  $kunaki_db_version = '1.0';

  // include the functions
  include(plugin_dir_path( __FILE__ )."/custom-fields.php");
  include(plugin_dir_path( __FILE__ )."/kunaki-admin.php");
  include(plugin_dir_path( __FILE__ )."/add-product-column.php");
  include(plugin_dir_path( __FILE__ )."/kunaki.lib.php");
  include(plugin_dir_path( __FILE__ )."/shipping.php");
  include(plugin_dir_path( __FILE__ )."/process-order.php");

  // Hook into that action for the cron
  add_action('kunaki_orders', 'kunaki_order_cron');

  function kunaki_install() {
  // create the initial kunaki orders table
    global $wpdb;
    global $kunaki_db_version;
    $table_name = $wpdb->prefix.'kunaki_orders';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
		ID mediumint(9) NOT NULL AUTO_INCREMENT,
		orderID int NOT NULL,
		errorNo int NOT NULL DEFAULT 0,
		errorMsg text,
                status varchar(20) DEFAULT 'pending',
		TS datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY(id)
            ) $charset_collate;";
    add_option('kunaki_db_version', $kunaki_db_version);

    // Schedule an action for the cron if it's not already scheduled
    //if (! wp_next_scheduled('kunaki_orders')) {
      wp_schedule_event(time(), 'every_5_minutes', 'kunaki_orders');
    //}
  }
  register_activation_hook(__FILE__, 'kunaki_install');

  function kunaki_deactivation() {
    if (wp_next_scheduled('kunaki_orders')) {
      // Get the timestamp for the next event.
      $timestamp = wp_next_scheduled('kunaki_orders');
      // If this event was created with any special arguments, you need to get those too.
      $original_args = array();
      wp_unschedule_event($timestamp, 'kunaki_orders', $original_args);
      wp_clear_scheduled_hook( 'kunaki_orders' );
    }
  }
  register_deactivation_hook(__FILE__, 'kunaki_deactivation');
  register_uninstall_hook(__FILE__, 'kunaki_deactivation');

  function kunaki_order_cron() {
  // The cron job to handle pending orders.  If there are pending Kunaki orders to place, they are placed.
  // Does not return any data.
    Global $wpdb;
    $table_name = $wpdb->prefix."kunaki_orders";
    // loop through all pending orders to check
    $orders = $wpdb->get_results("SELECT orderID FROM ".$table_name." WHERE status='pending'");
    foreach($orders as $order) {
      // actually process the order
      //update_kunaki_orders_table($order->orderID, 'queue', 0, "");
      process_kunaki_order($order->orderID);
    }
  }

  function kunaki_update_db_check() {
  // see if we need to update the kunaki orders table schema
    global $kunaki_db_version;
    if (get_site_option('kunaki_db_version') != $kunaki_db_version) kunaki_install();
  }
  add_action( 'plugins_loaded', 'kunaki_update_db_check' );

  function kunaki_add_cron_schedule( $schedules ) {
    if (!isset($schedules['every_5_minutes'])) {
      $schedules['every_5_minutes'] = array(
        'interval' => 300,
        'display'  => __( 'Every 5 minutes' ),
      );
    }
    return $schedules;
  }
  add_filter( 'cron_schedules', 'kunaki_add_cron_schedule' );

?>
