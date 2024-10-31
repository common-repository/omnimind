<?php

namespace Procoders\Omni\Front;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Parsedown as Markdown;
use Procoders\Omni\ClassLoader as Loader;
use Procoders\Omni\Front\ClassAssets as PublicAssets;
use Procoders\Omni\Includes\api as Api;

class ClassPublic
{
    private $template;
    private $api;

    /**
     * Creates a shortcode for displaying the Omni Search form.
     *
     * @return string The output of the shortcode.
     */
    public function __construct()
    {
        $this->template = new Loader();
        $this->api = new Api();
    }

    /**
     * Generate and display the Omni search shortcode.
     *
     * @return string The rendered search form HTML.
     */
    public function omni_search_shortcode()
    {

        PublicAssets::run(); // Conditional Assets Loading
        ob_start();
        $this->template->set_template_data(
            array(
                'template' => $this->template,
                'form' => [
                    'search_answer' => get_option('_omni_ai_search_answer'),
                ]
            )
        )->get_template_part('public/search-form');
        return ob_get_clean();
    }

    /**
     * Handle search query and return cached results if they exist
     *
     * @return void Echoes json data and exits.
     */
    public function omni_search_handle_query(): void
    {
        // Check and sanitize input values
        if (!isset($_POST['nonce'], $_POST['query']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'omni_search_handle_query')) {
            wp_send_json_error(['message' => __('Permission denied...', 'omnimind')]);
            return;
        }

        $query = sanitize_text_field($_POST['query']);
        $this->perform_search($query);
    }

    /**
     * Handle autocomplete search request.
     *
     * @return void
     */
    public function omni_search_handle_autocomplete(): void
    {
        // Check and sanitize input values.
        if (!isset($_POST['nonce'], $_POST['query']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'omni_search_handle_autocomplete')) {
            wp_send_json_error(['message' => __('Permission denied...', 'omnimind')]);
            return;
        }

        // Get selected post types from settings.
        $selected_post_types = get_option('_omni_selected_post_types');
        if (is_array($selected_post_types)) {
            unset($selected_post_types['ID']);
            unset($selected_post_types['filter']);
        } else {
            $selected_post_types = ['post', 'page'];
        }

        $args = array(
            'post_type' => $selected_post_types,
            'posts_per_page' => 10,
            's' => sanitize_text_field($_POST['query']),
        );

        $query = new \WP_Query($args);
        $posts = $query->posts;

        $data = [];

        foreach ($posts as $post) {
            $data[] = array(
                'id' => $post->ID,
                'text' => $post->post_title,
            );
        }
        wp_send_json_success($data);
    }

    /**
     * Perform a search with a given query.
     *
     * @param string $query The search query.
     * @return void
     */
    private function perform_search($query): void
    {

        $markdown = new Markdown();
        $markdown->setMarkupEscaped(true);
        // Create a unique cache key for this query
        $cache_key = 'omni_search_results_' . md5($query);
        $cache_lifetime = (int)get_option('_omni_ai_cache') ?? 1;

        if (get_option('_omni_ai_search_cache') === '1') {
            // Try to retrieve the result from the cache
            $cache = get_transient($cache_key);

            if (!empty($cache)) {
                wp_send_json_success($cache);
                return;
            }
        }
        $answer = '';
        // If no cached response exits, make the search request
        $response = $this->api->make_search_req($query);


        if (get_option('_omni_ai_search_answer') === '1') {
            $response_ = $this->api->make_answer_req($query);
            if ($response_ === false) {
                wp_send_json_error(['message' => __('Unable to process request.', 'omnimind')]);
                return;
            }
            $answer = $markdown->text($response_['results'][0]['text']);
        }
        $res['results'] = json_decode( $response['results'][0]['text'] );
        $res['timestamp'] = date_create()->format('Uv');
        $res['query'] = $query;
        $res['answer'] = $answer;
        // If the search request fails, return an error
        if ($response === false) {
            wp_send_json_error(['message' => __('Unable to process request.', 'omnimind')]);
            return;
        }

        // If the search request succeeds, store response in cache and return response
        $cached = set_transient($cache_key, $res, $cache_lifetime * 60 * MINUTE_IN_SECONDS); // Cache for 5 minutes
        if ($cached) {
            wp_send_json_success($res);
        } else {
            wp_send_json_error(['message' => __('Unable to cache request.', 'omnimind')]);
        }
    }


}

