# WooCommerce DNA Payments Gateway

This plugin integrates the DNA Payments gateway into WooCommerce, supporting both block-based and shortcode-based checkout pages.

---

## 🛠️ Installation

To install the plugin on your WooCommerce store:

1. Download the ZIP file from GitHub.
2. In your WordPress admin panel, go to **Plugins > Add New > Upload Plugin**.
3. Upload the downloaded ZIP file and click **Install Now**.
4. Activate the plugin once installation is complete.

---

## ⚙️ Enabling the Payment Gateway and Configuration

After installing and activating the plugin:

1. Go to **WooCommerce > Settings > Payments**.
2. Enable the **DNA Payments Gateway**.
3. Click **Manage** to configure the gateway settings.
4. Fill in the required fields:
    - **Client ID**
    - **Client Secret**
    - **Terminal ID**

These credentials will be provided to you during the onboarding process with DNA Payments.

---

## 🔄 Subscription Support

The DNA Payments Gateway supports recurring payments through the WooCommerce Subscriptions plugin.

### WooCommerce Subscriptions
- **Plugin**: [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/) (Official WooCommerce extension)
- **Features**: Full support for subscription management, renewals, cancellations, and amount changes
- **Automatic Detection**: The gateway automatically detects when WooCommerce Subscriptions is active

### How It Works
1. **Initial Payment**: When a customer purchases a subscription product, the initial payment is processed normally
2. **Recurring Payments**: The gateway uses the parent order's transaction ID for subsequent renewal payments
3. **Automatic Processing**: Renewal payments are handled automatically by the subscription plugin

### Supported Features
- ✅ Subscription creation and initial payment
- ✅ Automatic recurring payments
- ✅ Subscription cancellation
- ✅ Subscription suspension and reactivation
- ✅ Subscription amount changes
- ✅ Multiple subscriptions per order
- ✅ Failed payment handling and retry logic

---

## 🧪 Local Development

To set up the plugin for local development:

### 1. Set Node Version

Make sure you have [Node Version Manager (nvm)](https://github.com/nvm-sh/nvm) installed.

```
nvm use
```

This command sets the Node.js version defined in the `.nvmrc` file. Ensure this version is installed on your machine.

### 2. Install Dependencies

Install all required npm packages:

```
npm install
```

### 3. Start Development Servers

**Block-based (Checkout Blocks)**

In one terminal window:

```
npm run start
```

This runs Webpack in watch mode for block-based frontend development.

**Classic (Shortcode-based Checkout)**

In a second terminal window:

```
npm run start:classic
```

This runs Webpack for the classic shortcode-based frontend.

> ⚠️ Both terminals should use the Node.js version specified in `.nvmrc`.

---

## 📦 Building for Production

To generate production-ready frontend assets:

```
npm run build
npm run build:classic
```

After running the build commands:

1. Zip the **root** directory of the plugin.
2. **Exclude** the `node_modules` folder from the ZIP file, as it is only required for development.

This ZIP can now be used for installation via the WordPress admin panel.

---

## 📁 Folder Structure

```
woocommerce-dnapayments-gateway/
├── assets/
│ ├── css/
│ ├── img/
│ └── js/
│   ├── blocks/     # Compiled JS for block-based frontend
│   └── classic/    # Compiled JS for shortcode-based frontend
│
├── client/
│ ├── blocks/       # Source JS components for block-based checkout
│ ├── classic/      # Source JS for shortcode-based frontend
│ └── common/       # Shared JS utility functions for both frontends
│
├── includes/
│ ├── admin/        # PHP code for the admin panel
│ ├── blocks/       # PHP support for block-based checkout
│ ├── clients/      # PHP code for client-facing logic
│ ├── gateways/     # Payment gateway classes
│ ├── utils/        # PHP helper and utility classes
│ │ ├── class-ajax-init.php             # Handles AJAX requests
│ │ ├── class-auth-data-helper.php      # Generates auth tokens
│ │ ├── class-checkout-validation.php   # Validates checkout fields (classic/shortcode)
│ │ ├── class-helper.php                # Shared utility helpers
│ │ ├── class-logger.php                # WooCommerce log writer ("dnapayments" tag)
│ │ ├── class-order-helper.php          # Handles order update logic with conditions
│ │ ├── class-payment-data-helper.php   # Generates payment data for DNA Payments API
│ │ └── class-webhooks-init.php         # Handles DNA Payments success/failure webhooks
│ │
│ └── WC_DNA_Payments_Gateway.php       # Main payment gateway class
│
├── woocommerce-gateway-dnapayments.php # Plugin entry point
```
