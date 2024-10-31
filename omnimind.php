<?php
/**
 * Plugin Name: Omnimind
 * Plugin URI: #
 * Description: Seamlessly connect your website with OmniMind to automate your content management and search processes.
 * Version: 1.0.9
 * Author: ProCoders
 * Author URI: https://procoders.tech/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP: 7.3
 * Text Domain: omnimind
 * Domain Path: /languages
 */

namespace Procoders\Omni;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

define('OMNI_ENV_URL', 'https://app-api.omnimind.ai');

// Temporary id set
define('OMNI_WIDGET_TYPE_ID', 12);

define('OMNI_FILE', __FILE__);
define('OMNI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OMNI_PLUGIN_VER', '1.0.9');

use Procoders\Omni\Admin\{ClassAdmin as AdminInit, ClassAssets as AdminAssets, ClassNav as AdminNav};
use Procoders\Omni\Front\{ClassPublic as PublicInit};

class Omni
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Class initializer.
     */
    public function plugins_loaded(): void
    {
        load_plugin_textdomain(
            'omnimind',
            false,
            basename(__FILE__) . '/languages'
        );

        // Register the admin menu.
        AdminNav::run();
        // Register Script.
        AdminAssets::run();

        $public_init = new PublicInit();
        $admin_init = new AdminInit();

        // Register ajax cals for search.
        add_action('wp_ajax_nopriv_omni_search_handle_query', array($public_init, 'omni_search_handle_query'));
        add_action('wp_ajax_omni_search_handle_query', array($public_init, 'omni_search_handle_query'));

        // Register ajax cals for autocomplete.
        add_action('wp_ajax_nopriv_omni_handle_autocomplete', array($public_init, 'omni_search_handle_autocomplete'));
        add_action('wp_ajax_omni_handle_autocomplete', array($public_init, 'omni_search_handle_autocomplete'));

        // Register ajax cals for create project.
        add_action('wp_ajax_create_project_action', array($admin_init, 'create_project_action'));

        add_action('admin_init', array($admin_init, 'add_omni_columns_to_post_types'));
        add_action('admin_init', array($admin_init, 'add_quick_and_bulk_edit_to_post_types'));
        add_action('add_meta_boxes', array($admin_init, 'add_meta_box'));
        add_action('wp_ajax_sync_data_action', array($admin_init, 'sync_data_ajax_handler'));
        add_action('save_post', array($admin_init, 'bulk_quick_save_post'));
        // Add shortcode and search query handler to WordPress hooks
        add_shortcode('omni_search', array($public_init, 'omni_search_shortcode'));
    }
}

add_action(
    'plugins_loaded',
    function () {
        $omni = Omni::get_instance();
        $omni->plugins_loaded();
    }
);

register_activation_hook(
    __FILE__,
    function () {
        $answer_prompt = '[question]. Return links if you have, be like store consultant. suggest additional suitable products with links. Use markdown';
        $search_prompt = 'You are a search engine like google. You must return suitable items that regarding the user question. Generate in [lang] language and user search question [query], sort by relevance in descending order.';
        update_option( '_omni_ai_custom_answer_prompt', $answer_prompt );
        update_option( '_omni_ai_custom_search_prompt', $search_prompt );
    }
);