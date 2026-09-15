# WooCommerce Dummy Checkout Tester

A lightweight, secure WooCommerce payment gateway plugin designed for safely testing checkout flows, order status transitions, and post-purchase routing without processing real transactions. 

This plugin includes custom CSS injection to perfectly match the UI and styling of the Flatsome theme's checkout fields.

## Features

* **Safe Sandbox:** Process test orders without touching live payment network APIs like Stripe or Geidea.
* **Flatsome Integrated:** Input fields automatically inherit Flatsome's typography, borders, and focus states.
* **Scenario Testing:** Trigger specific WooCommerce order statuses (Processing, On-Hold, Failed) based on the test card prefix.

## Test Card Scenarios

Use any future Expiry Date (e.g., `12/28`) and any 3-digit CVC (e.g., `123`). The gateway inspects the first four digits of the card number to trigger specific scenarios:

| Card Number | Scenario | Expected Outcome |
| :--- | :--- | :--- |
| `1000 0000 0000 0000` | **Approved** | Order status becomes **Processing**. Customer is redirected to the standard order receipt page. |
| `2000 0000 0000 0000` | **Declined** | Checkout halts. Customer sees an "Insufficient funds" error banner. |
| `3000 0000 0000 0000` | **Fraud Hold** | Order status becomes **On-Hold** for manual review. Customer is redirected to the receipt page. |
| `4000 0000 0000 0000` | **System Error** | Checkout halts. Customer sees a "Connection timed out" gateway error. |

## Installation

1. Download or clone this repository into your `wp-content/plugins/` directory.
2. Go to **Plugins > Installed Plugins** in your WordPress dashboard and activate "WooCommerce Dummy Checkout Tester".
3. Navigate to **WooCommerce > Settings > Payments**.
4. Toggle on **Dummy Checkout Tester** and save changes.

## Author

**Fareed M. Rifaideen**
