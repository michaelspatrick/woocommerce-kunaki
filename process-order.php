<?php
  // Hook on an order after payment is complete and send DVDs to Kunaki when possible for drop shipment
  function prepare_kunaki_order($order_id) {
  // Places data into a table which will be checked by a cron job (kunaki_order_cron).
  // This function is called when the woocommerce order is completed.
  // Does not return any data.
    Global $wpdb;
    $table_name = $wpdb->prefix."kunaki_orders";
    $wpdb->query("INSERT INTO ".$table_name." (orderID,errorNo,errorMsg,status,TS) VALUES (".$order_id.",0,'','pending',NOW())");
  }
  add_action('woocommerce_payment_complete', 'prepare_kunaki_order');

  function update_kunaki_orders_table($orderID, $status, $errorNo, $errorMsg) {
  // Update the Kunaki orders table
    Global $wpdb;
    $table_name = $wpdb->prefix."kunaki_orders";
    $wpdb->query("UPDATE ".$table_name." SET status='".$status."', errorNo=".$errorNo.", errorMsg=\"".addslashes($errorMsg)."\", TS=NOW() WHERE orderID=".$orderID);
  }

  function get_kunaki_products_from_order($items) {
  // Look through all items in an order for Kunaki products
  // Returns an array if Kunaki products are found
    // initialize some variables
    $num_all_products = 0;
    $num_kunaki_products = 0;

    // loop through each item in the order
    foreach( $items as $order_item_id => $item ) {
      $product = new WC_Product( $item['product_id'] );

      // get product information
      $productID = $item['product_id'];
      $productName = get_the_title($productID);

      // Look for Kunaki products
      unset($kunaki_product_id);

      // Check whether this product is a physical DVD or not?
      $is_physical = true;
      if (isset($item[variation_id])) {
        // This must be a variable product so make sure the format is not Streaming
        if ($item[pa_video_format] == "video_format_streaming") $is_physical = false;
      }

      // Check whether product is physical.  Don't try to ship a Kunaki DVD for a variable product that has both DVD and Streaming options
      if ($is_physical) {
        $kunaki_product_id = trim(get_post_meta($productID, '_kunaki_product_id', true));

        if ($kunaki_product_id != "") {
          // check whether Kunaki ID is a package with comma-delimited Kunaki IDs
          if (stristr($kunaki_product_id, ",")) {
            // Break the field apart into separate Kunaki IDs
            $pieces = explode(",", $kunaki_product_id);
            for ($i=0; $i < count($pieces); $i++) {
              $kunaki_products[$num_kunaki_products]['ID'] = trim($pieces[$i]);
              $kunaki_products[$num_kunaki_products]['name'] = $productName;
              if($item['qty']) $kunaki_products[$num_kunaki_products]['qty'] = trim($item['qty']); else $kunaki_products[$num_kunaki_products]['qty'] = 1;
              $num_kunaki_products++;
            }
          } else {
            $kunaki_products[$num_kunaki_products]['ID'] = trim($kunaki_product_id);
            $kunaki_products[$num_kunaki_products]['name'] = $productName;
            if($item['qty']) $kunaki_products[$num_kunaki_products]['qty'] = trim($item['qty']); else $kunaki_products[$num_kunaki_products]['qty'] = 1;
            $num_kunaki_products++;
          }
        }
      }
      $num_all_products++;
    }
    $data['num_all_products'] = $num_all_products;
    $data['num_kunaki_products'] = $num_kunaki_products;
    $data['products'] = $kunaki_products;
    return $data;
  }

  function process_kunaki_order($order_id) {
  // Places the order with Kunaki if Kunaki products are found
  // Does not return any data
    Global $wpdb;

    // Pull info about the site
    $admin_email = get_option('admin_email');
    $site = get_bloginfo('name','raw');

    // Retrieve the order given the Order ID Number
    $order = new WC_Order( $order_id );

    // Make sure the order contained items
    if (count($order->get_items()) > 0) {
      // get order items
      $items = $order->get_items();

      // get shipping method from Order
      $shipping_items = $order->get_items('shipping');
      foreach($shipping_items as $el){
        $order_shipping_method_label = $el['method_label'] ;
      }

      // retrieve any Kunaki products from the order
      $data = get_kunaki_products_from_order($items);
      $kunaki_products = $data['products'];
      $num_all_products = $data['num_all_products'];
      $num_kunaki_products = $data['num_kunaki_products'];

      // We need to process the order with Kunaki if we found products with a Kunaki Product ID
      if (count($kunaki_products) > 0) {
        $option1 = get_option("_kunaki_email");
        $option2 = get_option("_kunaki_password");
        $Kunaki = new Kunaki($option1['_kunaki_email'], $option2['_kunaki_password'], "LIVE");

        // Send XML to Kunaki to get shipping options
        $Korder = new Kunaki_Order();

        // Build new order with products and quantities
        for ($i=0; $i < count($kunaki_products); $i++) {
          $Korder->addProductId(trim($kunaki_products[$i]['ID']), $kunaki_products[$i]['qty']);
        }

        // Get shipping method
        $shipping_items = $order->get_items('shipping');
        foreach($shipping_items as $el) {
          $shipping_method = $el['name'];
        }

        // mostly for Click Bank, if there are no cart items, check to see if there is an order
        if (($shipping_method == "Shipping") || ($shipping_method == "") || ($shipping_method == "Flat Rate")) {
          $out = get_kunaki_shipping_by_order_id($order_id);
//          $shipping_method = $out['method'];
        }

        // Add customer data to the Kunaki order
        $Korder->Name = $order->shipping_first_name." ".$order->shipping_last_name;
        //$Korder->ShippingDescription = $shipping_method;
        $Korder->ShippingDescription = "Flat Rate";  // Changed by Mike to send the Flat Rate to Kunaki
        $Korder->Address1 = $order->shipping_address_1;
        if($order->shipping_address_2) $Korder->Address2 = $order->shipping_address_2;
        $Korder->City = $order->shipping_city;
        $Korder->State_Province = $order->shipping_state;
        $Korder->PostalCode = $order->shipping_postcode;
        $Korder->Country = get_full_country_name($order->shipping_country);
        if (!$Korder->Country) $Korder->Country = "United States";

        // Submit the order to Kunaki
        $orderResult = $Kunaki->processOrder($Korder);
        $orderID = (integer)$orderResult->OrderId;
        $errorCode = (integer)$orderResult->ErrorCode;
        $errorMsg = $orderResult->ErrorText;

        // Check the return codes on the Kunaki order
        if ($errorCode == 0) {

          // Update the Kunaki order table
          update_kunaki_orders_table($order_id, 'success', 0, "Order accepted at Kunaki.");

          $order->add_order_note("Order #".$orderID." was accepted at the DVD production facility.");

          // write Order ID as meta info to the database
if ($orderID) {
          $order->update_meta_data( '_kunaki_order_id', $orderID );
} else {
          $order->update_meta_data( '_kunaki_order_id', $order_id );
}

          // Determine whether order will be split
          if ($num_kunaki_products < $num_all_products) {
            $order->add_order_note("Your order may be split into separate shipments and will arrive separately.", true);
          } else {
            $order->add_order_note("All of your items will be shipped from our DVD production facility.", true);
            $order->update_status('completed');
          }

          // Send XML to Kunaki and check Kunaki order status to make sure we had enough money in account
          $orderStatus = $Kunaki->getOrderStatus($orderID);
          if ((integer)$orderStatus->ErrorCode == 0)  {
            if ($orderStatus->OrderStatus == "pending") {
              $order->add_order_note("The order is pending action at the DVD production facility.");
              mail($admin_email, "[".$site."] WooCommerce: Insufficient Credits for Kunaki Order ".$orderID, "Please visit www.kunkai.com and pay for order.");
            } else {
              $last_one = "";
              for ($y=0; $y < count($kunaki_products); $y++) {
                if ($last_one != $kunaki_products[$y]['name']) {
                  $product_name = str_replace("Private: ", "", $kunaki_products[$y]['name']);
                  $order->add_order_note($product_name." will be shipped from our DVD production facility.");
                }
                $last_one = $kunaki_products[$y]['name'];
              }
            }
          } else {
            // Update the Kunaki order table
            update_kunaki_orders_table($order_id, 'rejected', $orderResult->ErrorCode, $orderResult->ErrorText);
            $order->add_order_note("The order has been rejected by the DVD production facility but will be shipped from ".$site.".");
            $order->add_order_note("Error #".(string)$orderResult->ErrorCode.": ".(string)$orderResult->ErrorText);
            mail($admin_email, "[".$site."] WooCommerce: Problem with Kunaki Order ".$orderID, (string)$orderResult->ErrorText);
          }
        } else {
          // Update the Kunaki order table
          update_kunaki_orders_table($order_id, 'rejected', $orderResult->ErrorCode, $orderResult->ErrorText);
          mail($admin_email, "[".$site."] WooCommerce: Problem (#".(string)$orderResult->ErrorCode.") with Kunaki Order ".$orderID, (string)$orderResult->ErrorText);
          $order->add_order_note("There was a problem with the order which was not accepted at the DVD production facility, so the order will be processed at ".$site.".");
          $order->add_order_note("Your order will be shipped from ".$site.".", true);
          $order->add_order_note("Error #".(string)$orderResult->ErrorCode.": ".(string)$orderResult->ErrorText);
          $order->add_order_note("We tried to ship ".$out['num_items']." via ".$shipping_method);
        }
      } else {
        // No Kunaki products in order
        update_kunaki_orders_table($order_id, 'N/A', 0, "No Kunaki products in order.");
      }
    } else {
      update_kunaki_orders_table($order_id, 'Error', -1, "No products in order.");
    }
  }
?>
