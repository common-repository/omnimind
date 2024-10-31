<?php
/**
 * Assets class
 *
 * @package Omni
 */

namespace Procoders\Omni\Front;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Create the admin menu.
 */
class ClassAssets
{

    /**
     * Main class runner.
     */
    public static function run(): void
    {
        add_action('wp_enqueue_scripts', array(static::class, 'public_assets'));
    }

    /**
     * Enqueues assets for the admin area.
     *
     * @return void
     */
    public static function public_assets(): void
    {
        wp_enqueue_style('omni-search-style', plugins_url('../../assets/css/omni-wp-search.css', __FILE__,), array(), OMNI_PLUGIN_VER);

        wp_register_script('omni-search-script', plugins_url('../../assets/js/omni-wp-search.js', __FILE__), array(), OMNI_PLUGIN_VER, true);
        wp_enqueue_script('omni-search-script');
        wp_localize_script('omni-search-script', 'omni_ajax',
            array(
                'url' => admin_url('admin-ajax.php'),
                'query_nonce' => wp_create_nonce('omni_search_handle_query'),
                'autocomplete_nonce' => wp_create_nonce('omni_search_handle_autocomplete'),
                'search_answer' => esc_attr(get_option('_omni_ai_search_answer')),
                'show_content' => esc_attr(get_option('_omni_ai_search_content')),
                'show_autocomplete' => esc_attr(get_option('_omni_ai_search_autocomplete')),
                'answers_per_page' => esc_attr(get_option('_omni_ai_search_results_limit')),
                '_read_more' => __('Read More', 'omnimind'),
                '_search' => __('Search', 'omnimind'),
                '_results' => __('Results', 'omnimind'),
                '_prev' => __('Prev', 'omnimind'),
                '_next' => __('Next', 'omnimind'),
            )
        );

    }
}
