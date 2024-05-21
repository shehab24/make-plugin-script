<?php

// Function to get user input
function get_input($prompt) {
    echo $prompt . ': ';
    return trim(fgets(STDIN));
}

// Function to convert a string to CamelCase
function to_camel_case($string) {
    return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $string)));
}

// Get plugin details from user
$plugin_name = get_input('Enter Plugin Name');
$plugin_slug = strtolower(str_replace(' ', '-', $plugin_name));
$plugin_namespace = to_camel_case($plugin_name);
$plugin_description = get_input('Enter Plugin Description');
$plugin_version = '1.0.0';
$plugin_author = get_input('Enter Author Name');
$plugin_author_uri = get_input('Enter Author URL');
$plugin_author_email = get_input('Enter Author Email');
$plugin_uri = get_input('Enter Plugin URL');

// Define plugin directory structure
$pluginDir = __DIR__ . "/$plugin_slug";
$srcDir = $pluginDir . '/src';
$incDir = $srcDir . '/Inc';
$frontendDir = $incDir . '/Frontend';
$adminDir = $incDir . '/Admin';
$assetsDir = $pluginDir . '/assets';
$cssDir = $assetsDir . '/css';
$jsDir = $assetsDir . '/js';
$imagesDir = $assetsDir . '/images';

// Create directories
@mkdir($pluginDir, 0755, true);
@mkdir($srcDir, 0755, true);
@mkdir($incDir, 0755, true);
@mkdir($frontendDir, 0755, true);
@mkdir($adminDir, 0755, true);
@mkdir($assetsDir, 0755, true);
@mkdir($cssDir, 0755, true);
@mkdir($jsDir, 0755, true);
@mkdir($imagesDir, 0755, true);

// Create main plugin file
$pluginFileContent = <<<PHP
<?php
/*
Plugin Name: $plugin_name
Description: $plugin_description
Version: $plugin_version
Author: $plugin_author
Author URI: $plugin_author_uri
Plugin URI: $plugin_uri
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('PLUGIN_NAME', '$plugin_slug');
define('PLUGIN_VERSION', '$plugin_version');
define('PLUGIN_TEXT_DOMAIN', '$plugin_slug');
define('PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload dependencies
require_once __DIR__ . '/vendor/autoload.php';

use $plugin_namespace\\Main;

function run_plugin() {
    Main::init();
}
run_plugin();
PHP;

file_put_contents($pluginDir . "/$plugin_slug.php", $pluginFileContent);

// Create Main.php
$mainClassContent = <<<PHP
<?php

namespace $plugin_namespace;

class Main {
    public static function init() {
        \$plugin_name = PLUGIN_NAME;
        \$version = PLUGIN_VERSION;
        \$plugin_text_domain = PLUGIN_TEXT_DOMAIN;

        // Initialize the admin class
        if (is_admin()) {
            new Inc\\Admin\\Admin(\$plugin_name, \$version, \$plugin_text_domain);
        }

        // Initialize the frontend class
        if (!is_admin()) {
            new Inc\\Frontend\\Frontend(\$plugin_name, \$version, \$plugin_text_domain);
        }
    }
}
PHP;

file_put_contents($srcDir . '/Main.php', $mainClassContent);

// Create Admin.php
$adminClassContent = <<<PHP
<?php

namespace $plugin_namespace\\Inc\\Admin;

class Admin {

    private \$plugin_name;
    private \$version;
    private \$plugin_text_domain;

    public function __construct(\$plugin_name, \$version, \$plugin_text_domain) {
        \$this->plugin_name = \$plugin_name;
        \$this->version = \$version;
        \$this->plugin_text_domain = \$plugin_text_domain;

        add_action('admin_enqueue_scripts', [\$this, 'enqueue_styles']);
        add_action('admin_enqueue_scripts', [\$this, 'enqueue_scripts']);
    }

    public function enqueue_styles() {
        wp_enqueue_style(\$this->plugin_name, plugin_dir_url(__FILE__) . 'css/{$plugin_slug}-admin.css', [], \$this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script(\$this->plugin_name, plugin_dir_url(__FILE__) . 'js/{$plugin_slug}-admin.js', ['jquery'], \$this->version, false);
    }
}
PHP;

file_put_contents($adminDir . '/Admin.php', $adminClassContent);

// Create Frontend.php
$frontendClassContent = <<<PHP
<?php

namespace $plugin_namespace\\Inc\\Frontend;

class Frontend {

    private \$plugin_name;
    private \$version;
    private \$plugin_text_domain;

    public function __construct(\$plugin_name, \$version, \$plugin_text_domain) {
        \$this->plugin_name = \$plugin_name;
        \$this->version = \$version;
        \$this->plugin_text_domain = \$plugin_text_domain;

        add_action('wp_enqueue_scripts', [\$this, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [\$this, 'enqueue_scripts']);
        add_action('wp_head', [\$this, 'wp_callback_for_head']);
    }

    public function enqueue_styles() {
        wp_enqueue_style(\$this->plugin_name, plugin_dir_url(__FILE__) . 'css/{$plugin_slug}-frontend.css', [], \$this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script(\$this->plugin_name, plugin_dir_url(__FILE__) . 'js/{$plugin_slug}-frontend.js', ['jquery'], \$this->version, false);
    }

    public function wp_callback_for_head() {
        echo "This is a callback for the head section.";
    }
}
PHP;

file_put_contents($frontendDir . '/Frontend.php', $frontendClassContent);

// Create composer.json
$composerJsonContent = <<<JSON
{
    "name": "yourname/$plugin_slug",
    "description": "$plugin_description",
    "type": "wordpress-plugin",
    "autoload": {
        "psr-4": {
            "$plugin_namespace\\\\": "src/"
        }
    },
    "require": {}
}
JSON;

file_put_contents($pluginDir . '/composer.json', $composerJsonContent);

// Create README.md
$readmeContent = <<<MD
# $plugin_name

$plugin_description

## Installation

1. Upload the plugin files to the `/wp-content/plugins/$plugin_slug` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

## Usage

Describe how to use your plugin here.

## Changelog

### 1.0.0
* Initial release
MD;

file_put_contents($pluginDir . '/README.md', $readmeContent);

// Create sample CSS and JS files
file_put_contents($cssDir . '/style.css', "/* Frontend styles for $plugin_name */");
file_put_contents($cssDir . '/admin.css', "/* Admin styles for $plugin_name */");
file_put_contents($jsDir . '/script.js', "// Frontend scripts for $plugin_name");
file_put_contents($jsDir . '/admin.js', "// Admin scripts for $plugin_name");

// Run composer install
chdir($pluginDir);
exec('composer install');

echo "Plugin setup completed.\n";
echo "Plugin Directory: $pluginDir\n";
echo "Remember to activate your plugin in the WordPress admin panel.\n";
