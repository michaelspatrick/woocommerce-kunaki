# WooCommerce Kunaki Integration

A WordPress plugin that integrates [WooCommerce](https://woocommerce.com/) with [Kunaki.com](http://kunaki.com) to automatically fulfill physical media orders (like DVDs) when a customer places an order.

## Features

- Automatically sends Kunaki product orders upon successful WooCommerce purchase
- Adds a custom "Kunaki Product ID" field to WooCommerce products
- Maps WooCommerce shipping info to Kunaki order API
- Supports multiple Kunaki products per order
- Admin panel for API credentials and testing
- Logging for debugging order processing

## Requirements

- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.2+
- cURL enabled
- Kunaki.com publisher account

## Installation

1. Upload the plugin folder to `/wp-content/plugins/woocommerce-kunaki/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **WooCommerce > Kunaki Settings**
4. Enter your Kunaki Publisher ID and other settings

## Configuration

1. **Assign Kunaki Product ID**  
   Edit each WooCommerce product and set the Kunaki Product ID in the new custom field provided.

2. **Enable Auto Fulfillment**  
   Under Kunaki settings, check “Enable Automatic Fulfillment” to start sending orders to Kunaki when WooCommerce marks them as completed.

3. **Shipping Mapping**  
   Kunaki shipping is automatically handled based on the WooCommerce customer shipping address. You can customize or override mappings in `shipping.php`.

## Order Processing

When a WooCommerce order contains one or more products linked to Kunaki Product IDs, the plugin will:

- Build a Kunaki XML order
- Submit it using the API endpoint
- Log the response for debugging
- Update the WooCommerce order notes with confirmation or error messages

## Logging

Logs are stored in `wp-content/uploads/kunaki-log.txt`. Enable or disable logging from the admin panel.

## Development

Main plugin entry: `woocommerce-kunaki.php`

Other modules:
- `kunaki-admin.php` – Settings page
- `kunaki.lib.php` – Core Kunaki API functions
- `custom-fields.php` – Adds Kunaki product fields to WooCommerce products
- `process-order.php` – Handles order submission logic
- `shipping.php` – Address translation functions
- `add-product-column.php` – Adds Kunaki ID column to product list

## Roadmap

- Add test mode with mock API
- Track Kunaki shipment status in WooCommerce
- Add order retry on failure

## Disclaimer

This plugin is not officially affiliated with or endorsed by Kunaki.com. Use at your own risk.

## License

MIT License

## Author

Developed by Michael Patrick.

---
