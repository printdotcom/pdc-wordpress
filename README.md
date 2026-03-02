# Print.com Print on Demand - WordPress Plugin

[![WordPress](https://img.shields.io/badge/WordPress-6.0+-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.5+-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.0+-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0+-red.svg)](LICENSE.txt)

A WordPress plugin that integrates with the Print.com API to enable custom print-on-demand products in WooCommerce stores. Configure, edit, and sell custom printed products seamlessly through your WordPress/WooCommerce site.

## ✨ Features

- 🖨️ **Print.com API Integration** - Direct connection to Print.com's print-on-demand services
- 🎨 **Product Configuration** - Connect your product to a Print.com and we will take care of the fulfilment
- 🎨 **Order Synchronization** - Automatically receive track-and-trace data from Print.com

## 🚀 Quick Start

### Prerequisites

- [Docker](https://www.docker.com/get-started) and Docker Compose
- [Git](https://git-scm.com/downloads)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/printdotcom/pdc-pod.git
   cd pdc-pod
   ```

2. **Run composer**
   ```bash
   composer dump-autoload
   ```

3. **Start the development environment:**
   ```bash
   # Start WordPress with PHP 8.2 and WordPress 6.8
   bin/run-wordpress 68 82
   ```

3. **Optionally Seed WooCommerce with sample data:**
   ```bash
   # Configure WooCommerce and create sample products
   bin/seed-woocommerce 68 82
   ```

4. **Optionally use the mock api:**
```bash
# Configure WooCommerce and create sample products
bin/run-mock-api start
```

5. **Access your site:**
   - **Frontend:** http://localhost:8068
   - **Admin:** http://localhost:8068/wp-admin (admin/password)

## 🐳 Local Environment

### Available Commands

| Command | Description | Example |
|---------|-------------|----------|
| `bin/run-wordpress [WP_VER] [PHP_VER]` | Start WordPress environment | `bin/run-wordpress 68 82` |
| `bin/stop-wordpress [WP_VER] [PHP_VER]` | Stop WordPress environment | `bin/stop-wordpress 68 82` |
| `bin/seed-woocommerce [WP_VER] [PHP_VER]` | Seed WooCommerce with sample data | `bin/seed-woocommerce 68 82` |
| `bin/run-mock-api {start\|stop\|status}` | Manage Print.com Mock API | `bin/run-mock-api start` |

### Supported Versions

Check `config/wp-version.conf` for available WordPress/PHP combinations:

```bash
# WordPress 6.7 with PHP 7.0
bin/run-wordpress 67 70

# WordPress 6.8 with PHP 8.2 (if available)
bin/run-wordpress 68 82
```

### Port Mapping

The port format is `80[WORDPRESS_VERSION]`:
- WordPress 6.7: `http://localhost:8067`
- WordPress 6.8: `http://localhost:8068`

## 🔧 Configuration

### Print.com API Credentials

1. Navigate to **WordPress Admin → Print.com → Settings**
2. Enter your Print.com API credentials:
   - API Key
   - API Secret
   - Environment (Sandbox/Production)

## 🛠️ Development

### Code Standards

This project follows:
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Documentation Standards](https://developer.wordpress.org/coding-standards/inline-documentation-standards/)
- [Conventional Commits](https://www.conventionalcommits.org/) for commit messages


### Translations

#### When a new translation is added

When you add new strings that need translation in your PHP code:

1. **Regenerate the POT template file:**
   ```bash
   wp i18n make-pot . languages/pdc-pod.pot
   ```

2. **Update existing PO files with new strings:**
   ```bash
   wp i18n update-po languages/pdc-pod.pot
   ```

3. **Translate the new strings** in each `.po` file using [Poedit](https://poedit.net/) or a text editor

4. **Compile translations to MO files:**
   ```bash
   wp i18n make-mo languages
   ```

#### Adding a New Language

When you want to add a new language:

1. **Create a new PO file from the template:**
   ```bash
   cd languages
   cp pdc-pod.pot pdc-pod-[LOCALE].po
   ```
   Replace `[LOCALE]` with the WordPress locale code (e.g., `es_ES` for Spanish, `it_IT` for Italian)

2. **Update the PO file header** with language information:
   - Set `Language-Team` and `Language` fields
   - Add appropriate plural forms for the language

3. **Translate all strings** using Poedit or manually edit the `msgstr` values

4. **Compile to MO file:**
   ```bash
   wp i18n make-mo languages
   ```

### 🏗️ Project Structure

```
pdc-pod/
├── admin/                  # Admin-specific functionality
│   ├── AdminCore.php      # Main admin class
│   ├── PrintDotCom/       # Print.com API integration
│   │   ├── APIClient.php  # API client
│   │   ├── Product.php    # Product model
│   │   └── Preset.php     # Preset model
│   ├── css/               # Admin stylesheets
│   ├── js/                # Admin JavaScript
│   └── partials/          # Admin template partials
├── bin/                   # Development scripts
│   ├── run-wordpress      # Start WordPress environment
│   ├── stop-wordpress     # Stop WordPress environment
│   └── seed-woocommerce   # Seed WooCommerce data
├── config/                # Configuration files
│   ├── docker-compose.yml # Docker Compose configuration
│   └── wp-version.conf    # WordPress/PHP version mapping
├── includes/              # Core plugin files
│   ├── Core.php          # Main plugin class
│   ├── Activator.php     # Plugin activation
│   ├── Deactivator.php   # Plugin deactivation
│   └── Loader.php        # Hook loader
├── front/                 # Public-facing functionality
│   └── FrontCore.php      # Main public class
└── vendor/                # Composer dependencies
```

### Autoloading

The project uses PSR-4 autoloading via Composer:

```json
{
  "autoload": {
    "psr-4": {
      "PdcPod\\Admin\\": "admin/",
      "PdcPod\\Front\\": "front/",
      "PdcPod\\Includes\\": "includes/"
    }
  }
}
```

### Unit tests

Running automated unit tests via phpunit is possible through:
```bash
bin/test-unit
```

### Mock API

When working locally or running end-2-end tests, the mock-api via
wiremock will mock the Print.com API.

```bash
# Start the mock API
bin/run-mock-api start
```

See [`test/wiremock/README.md`](test/wiremock/README.md) for detailed documentation.

### Releasing

```bash
bin/create-dist
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feat/amazing-feature`)
3. Open a Pull Request

## 📞 Support

- **Documentation:** [Print.com Developer Docs](https://developer.print.com)
- **Issues:** [GitHub Issues](https://github.com/printdotcom/pdc-pod/issues)
- **Email:** [devops@print.com](mailto:devops@print.com)

## 📊 Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed history of changes.

---

**Made with ❤️ by the Print.com team**
