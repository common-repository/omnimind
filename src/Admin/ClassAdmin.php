<?php

namespace Procoders\Omni\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Procoders\Omni\ClassLoader as Loader;
use Procoders\Omni\Includes\api as Api;
use Procoders\Omni\Includes\debugger as debugger;

class ClassAdmin
{
    private $template;
    private $api;
    private $message;

    public function __construct()
    {
        $this->template = new Loader();
        $this->api = new Api();
        $this->debug = new debugger();
    }

    private function check_user_permission(): bool
    {
        return current_user_can('manage_options');
    }

    public function omni_settings_page()
    {
        if (!$this->check_user_permission()) {
            $this->render_message(__('Permission error', 'omnimind'), 'admin/error');
            return;
        }

        $this->handle_post_requests();
        $form = $this->get_form();

        $this->template->set_template_data(
            array(
                'template' => $this->template,
                'form' => $form)
        )->get_template_part('admin/settings-page');
    }

    public function handle_post_requests()
    {

        if (isset($_POST['check_api_key'])) {
            if (!isset($_POST['project_apikey_nonce'])
                && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['project_apikey_nonce'])), 'project_apikey_nonce_action')) {
                die();
            }
            $this->handle_api_key();
        }

        if (isset($_POST['save_post_types'])) {
            $this->handle_save_post_types();
        }

        if (isset($_POST['send_post_types'])) {
            $this->handle_send_post_types();
        }

        if (isset($_POST['send_project_name'])) {
            $this->handle_send_project_name();
        }

        if (isset($_POST['existing_project'])) {
            $this->handle_select_project();
        }

        if (isset($_POST['delete_project'])) {
            $this->handle_delete_project();
        }

        if (isset($_POST['reindex_project'])) {
            $this->handle_reindex_project();
        }

        if (isset($_POST['purge_cache'])) {
            $this->handle_purge_cache();
        }

        if (isset($_POST['save_general'])) {
            $this->handle_save_general();
        }

        if (isset($_POST['custom_answer_prompt'])) {
            $this->handle_custom_answer_prompt();
        }

        if (isset($_POST['custom_search_prompt'])) {
            $this->handle_custom_search_prompt();
        }
    }

    public function get_form()
    {
        $setting = str_replace('-api', '', OMNI_ENV_URL) . '/projects/' . get_option('_omni_project_id');
        return array(
            'selected_post_types' => get_option('_omni_selected_post_types', array()),
            'projects_list' => $this->get_projects_list(),
            'api_key_status' => get_option('_omni_api_key_status'),
            'omni_api_key' => get_option('_omni_api_key'),
            'project_name' => get_option('_omni_project_name'),
            'project_id' => get_option('_omni_project_id'),
            'ai_search_answer' => get_option('_omni_ai_search_answer'),
            'ai_search_content' => get_option('_omni_ai_search_content'),
            'ai_search_cache' => get_option('_omni_ai_search_cache'),
            'ai_search_autocomplete' => get_option('_omni_ai_search_autocomplete'),
            'ai_search_results_limit' => get_option('_omni_ai_search_results_limit'),
            'ai_search_trust_level' => get_option('_omni_ai_search_trust_level'),
            'ai_cache' => get_option('_omni_ai_cache'),
            'ai_omni_setting' => $setting,
            'search_log' => $this->get_transient_log(),
            'custom_answer_prompt' => get_option('_omni_ai_custom_answer_prompt'),
            'custom_search_prompt' => get_option('_omni_ai_custom_search_prompt'),
            'popup' => $this->message,
        );
    }

    /**
     * @return void
     */
    public function add_omni_columns_to_post_types(): void
    {
        $selected_post_types = get_option('_omni_selected_post_types');
        if (is_array($selected_post_types) && !empty($selected_post_types)) {
            foreach ($selected_post_types as $post_type) {
                add_filter("manage_{$post_type}_posts_columns", array($this, 'add_omni_column'));
                add_action("manage_{$post_type}_posts_custom_column", array($this, 'omni_column_content'), 10, 2);
            }
        }
    }

    public function get_transient_log(): array
    {
        $transients = $this->read_transient();
        $log = [];

        foreach ($transients as $transient) {
            $transient = get_transient(str_replace('_transient_', '', $transient));

            $log[] = [
                'date' => $transient['timestamp'] ?? 0,
                'question' => $transient['query'] ?? '',
                'answer' => $transient['answer'] ?? '',
                'data' => $transient['results'] ?? [],
            ];
        }
        return $log;
    }

    /**
     * @return void
     */
    public function add_quick_and_bulk_edit_to_post_types(): void
    {
        $selected_post_types = get_option('_omni_selected_post_types');

        if (is_array($selected_post_types) && !empty($selected_post_types)) {
            foreach ($selected_post_types as $post_type) {
                add_action("bulk_edit_custom_box", array($this, 'omni_edit_exclude_function'), 10, 2);
                add_action("quick_edit_custom_box", array($this, 'omni_edit_exclude_function'), 10, 2);
            }
        }
    }

    /**
     * @param $column_name
     * @param $post_type
     *
     * @return void
     */
    public function omni_edit_exclude_function($column_name, $post_type): void
    {
        // ToDo: $post_type unused

        if ($column_name == 'omni_column') {
            wp_nonce_field('save_exclude_from_omni', 'exclude_from_omni_nonce');
            ?>
            <fieldset class="inline-edit-col-right">
                <div class="inline-edit-col">
                    <label class="alignleft">
                        <input type="checkbox" name="exclude_from_omni_bulk" value="1"/>
                        <span class="checkbox-title"><?php esc_html_e('Exclude from Omnimind', 'omnimind'); ?></span>
                    </label>
                </div>
            </fieldset>
            <?php
        }
    }

    public function add_omni_column(array $columns): array
    {
        $columns['omni_column'] = __('In Omni', 'omnimind');
        return $columns;
    }


    /**
     * @param $column_name
     * @param $post_id
     *
     * @return void
     */
    public function omni_column_content($column_name, $post_id): void
    {
        if ($column_name == 'omni_column') {
            $post_exclude = get_post_meta($post_id, '_exclude_from_omni', true);
            if ($post_exclude) {
                echo '<span style="color:#d03030;" class="dashicons dashicons-no"></span>';
            } else {
                echo '<span style="color:#2baf3a;" class="dashicons dashicons-yes"></span>';
            }
        }
    }

    private function render_message(string $msg, string $part): void
    {
        $this->template->set_template_data(
            array('message' => $msg)
        )->get_template_part($part);
        die();
    }

    private function handle_api_key(): void
    {

        if (!isset($_POST['project_apikey_nonce'])
            && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['project_apikey_nonce'])), 'project_apikey_nonce_action')) {
            die();
        }

        $api_key_status = $this->api->verify_api_key(sanitize_text_field($_POST['verify_api_key']));
        update_option('_omni_api_key_status', $api_key_status);
        update_option('_omni_api_key', sanitize_text_field($_POST['verify_api_key']));

        $this->message = $api_key_status === true
            ? ['status' => 'success', 'message' => __('API Key stored successfully!', 'omnimind')]
            : ['status' => 'success', 'message' => __('Something went wrong! Please check your API key or try again later.', 'omnimind')];
    }

    private function handle_save_post_types(): void
    {
        if (!isset($_POST['project_content_nonce'])
            && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['project_content_nonce'])), 'project_content_nonce_action')) {
            die();
        }

        $selected_post_types = sanitize_post($_POST['post_types']) ?? array();
        update_option('_omni_selected_post_types', $selected_post_types);
        if (isset($_POST['post_type_fields'])) {
            $selected_fields = sanitize_post($_POST['post_type_fields']);
            $filtered_selected_fields = array();
            foreach ($selected_fields as $post_type => $fields) {
                if (in_array($post_type, $selected_post_types)) {
                    $title_columns = $fields['advanced-title-columns'] ?? array();
                    $metadata_columns = $fields['advanced-metadata-columns'] ?? array();
                    $filtered_fields = array();
                    if (is_array($fields)) {
                        foreach ($fields as $field) {
                            if (!empty($field['status'])) {
                                $filtered_fields[$field['name']] = $field;
                            }
                        }
                    }
                    $filtered_selected_fields[$post_type] = $filtered_fields;
                    if (!empty($title_columns)) {
                        $filtered_selected_fields[$post_type]['advanced-title-columns'] = $title_columns;
                    }
                    if (!empty($metadata_columns)) {
                        $filtered_selected_fields[$post_type]['advanced-metadata-columns'] = $metadata_columns;
                    }
                }
            }
            update_option('_omni_selected_fields_option', $filtered_selected_fields);
        }
    }

    private function handle_send_post_types(): void
    {
        $selected_post_types = get_option('_omni_selected_post_types');

        // ToDo: sync_data() does not expect parameters
        $data_sent = $this->sync_data($selected_post_types);
        if ($data_sent === true) {
            $this->debug->omni_error_log('Data successfully sent to remote server in CSV format.');
        } else {
            $this->debug->omni_error_log('data not sended.');
        }
    }

    private function get_projects_list()
    {
        return $this->api->get_projects();
    }

    private function handle_send_project_name(): void
    {

        if (!isset($_POST['project_name_nonce'])
            && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['project_name_nonce'])), 'project_name_nonce_action')) {
            die();
        }

        $project_name = sanitize_text_field($_POST['project_name']);
        $project_created = $this->api->create_project($project_name);
        $this->message = [
            'status' => 'success',
            'message' => $project_created['message']
        ];
    }

    private function handle_select_project()
    {
        if (!isset($_POST['project_name_nonce'])
            && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['project_name_nonce'])), 'project_name_nonce_action')) {
            die();
        }
        $project_id = sanitize_text_field($_POST['existing_project']);
        update_option('_omni_project_id', $project_id);

    }

    private function handle_delete_project(): void
    {
        $project_deleted = $this->api->delete_project();
        $this->message = [
            'status' => 'success',
            'message' => $project_deleted === true
                ? __('Project Deleted successfully! API Key has been cleared', 'omnimind')
                : __('Failed to delete project! Please try again later.', 'omnimind'),
        ];
    }

    private function handle_reindex_project(): void
    {
        $project_reindexed = $this->reindex_project();
        $this->message = [
            'status' => 'success',
            'message' => $project_reindexed === true
                ? __('Project Re-indexed successfully!', 'omnimind')
                : __('Failed to update project! Please try again later.', 'omnimind'),
        ];
    }

    /**
     * Handle the purging of the cache.
     *
     * @return void
     */
    private function handle_purge_cache(): void
    {
        if ($this->purge_cache()) {
            $this->message = [
                'status' => 'success',
                'message' => __('Cache purged successfully!', 'omnimind'),
            ];
        } else {
            $this->message = [
                'status' => 'warning',
                'message' => __('Failed to purge cache! Please try again later.', 'omnimind'),
            ];

        }


    }

    private function handle_save_general(): void
    {

        if (!isset($_POST['project_config_nonce'])
            && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['project_config_nonce'])), 'project_config_nonce_action')) {
            die();
        }

        $ai_search_answer = isset($_POST['ai_search_answer']) ? 1 : 0;
        $ai_search_content = isset($_POST['ai_search_content']) ? 1 : 0;
        $ai_search_autocomplete = isset($_POST['ai_search_autocomplete']) ? 1 : 0;
        $ai_search_cache = isset($_POST['ai_search_cache']) ? 1 : 0;
        $ai_search_results_limit = isset($_POST['ai_search_results_limit']) ? intval($_POST['ai_search_results_limit']) : 5;
        $ai_search_trust_level = isset($_POST['ai_search_trust_level']) ? floatval($_POST['ai_search_trust_level']) : 0.6;
        $ai_cache = isset($_POST['ai_cache']) ? intval($_POST['ai_cache']) : 24;

        // save data
        update_option('_omni_ai_search_answer', $ai_search_answer);
        update_option('_omni_ai_search_content', $ai_search_content);
        update_option('_omni_ai_search_cache', $ai_search_cache);
        update_option('_omni_ai_search_autocomplete', $ai_search_autocomplete);
        update_option('_omni_ai_search_results_limit', $ai_search_results_limit);
        update_option('_omni_ai_search_trust_level', $ai_search_trust_level);
        update_option('_omni_ai_cache', $ai_cache);
    }

    private function handle_custom_answer_prompt()
    {
        if (!isset($_POST['custom_answer_prompt'])
            && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['custom_prompts_nonce'])), 'custom_prompts_nonce_action')) {
            die();
        }

        update_option('_omni_ai_custom_answer_prompt', sanitize_text_field(trim($_POST['custom_answer_prompt'])));
    }

    private function handle_custom_search_prompt()
    {
        if (!isset($_POST['custom_answer_prompt'])
            && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['custom_prompts_nonce'])), 'custom_prompts_nonce_action')) {
            die();
        }

        update_option('_omni_ai_custom_search_prompt', sanitize_text_field(trim($_POST['custom_search_prompt'])));
    }

    /**
     * @return bool|null
     */
    private function sync_data($selected_post_types = []): ?bool
    {
        $omni_api_key = get_option('_omni_api_key');
        $project_id = get_option('_omni_project_id');
        $fields_array = get_option('_omni_selected_fields_option');

        $uploaded_fields_array = get_option('_omni_uploaded_fields_option');

        $chains = array();
        if (!is_array($uploaded_fields_array)) {
            $uploaded_fields_array = array();
        }
        foreach ($fields_array as $type => $fields) {
            if (isset($uploaded_fields_array[$type])) {
                if ($this->compare_second_level($fields, $uploaded_fields_array[$type]) || $this->has_post_count_changed($type)) {
                    $this->delete_posts_of_type($type, $project_id, $chains);
                    $this->add_posts_of_type($type, $project_id, $chains, $fields);
                }
            } else {
                $this->add_posts_of_type($type, $project_id, $chains, $fields);
            }
        }
        foreach ($uploaded_fields_array as $type => $fields) {
            if (!isset($fields_array[$type])) {
                $this->delete_posts_of_type($type, $project_id, $chains);
            }
        }

        if (!count($chains)) {
            update_option('_omni_last_sync_date', current_time('mysql'));
            return true;
        }
        $status = $this->api->send_requests($chains, $omni_api_key, $project_id, $fields_array);
        if ($status) {
            update_option('_omni_last_sync_date', current_time('mysql'));
        }
        return $status;
    }

    /**
     * Synchronize data with pointer one by one.
     *
     * @param int $pointer The pointer position. Default is 0.
     *
     * @return int The updated pointer position.
     */
    private function sync_data_pointer(int $pointer = 0): array
    {
        $chains = array();
        $omni_api_key = get_option('_omni_api_key');
        $project_id = get_option('_omni_project_id');
        $fields_array = get_option('_omni_selected_fields_option');
        $uploaded_fields_array = get_option('_omni_uploaded_fields_option');

        if ($pointer === -1) {

            add_option('_omni_chains_cache', '');

            if (!is_array($uploaded_fields_array)) {
                $uploaded_fields_array = array();
            }


            foreach ($fields_array as $type => $fields) {
                if (isset($uploaded_fields_array[$type])) {
                    if ($this->compare_second_level($fields, $uploaded_fields_array[$type]) || $this->has_post_count_changed($type)) {
                        $this->delete_posts_of_type($type, $project_id, $chains);
                        $this->add_posts_of_type($type, $project_id, $chains, $fields);
                    }
                } else {
                    $this->add_posts_of_type($type, $project_id, $chains, $fields);
                }
            }
            foreach ($uploaded_fields_array as $type => $fields) {
                if (!isset($fields_array[$type])) {
                    $this->delete_posts_of_type($type, $project_id, $chains);
                }
            }

            update_option('_omni_chains_cache', $chains);
            $count = count($chains);
            return ['pointer' => $pointer + 1, 'count' => $count];
        } else {
            $chains = get_option('_omni_chains_cache');
            $count = count($chains);
            if (array_key_exists($pointer, $chains)) {

                $status = $this->api->send_requests($chains[$pointer], $omni_api_key, $project_id, $fields_array);

                if ($status) {
                    return ['pointer' => $pointer + 1, 'count' => $count];
                }
            } else {
                update_option('_omni_last_sync_date', current_time('mysql'));
                return ['pointer' => -1, 'count' => $count];
            }
        }
        return ['pointer' => -1, 'count' => 100];
    }

    /**
     * Check if the post count for a specific post type has changed.
     *
     * @param string $post_type The post type to check the count for.
     *
     * @return bool Returns true if the post count has changed, false otherwise.
     */
    private function has_post_count_changed(string $post_type): bool
    {
        // Get the stored count
        $stored_count = get_transient('_omni_post_count_' . $post_type);

        // Get the current count
        $count_query = new \WP_Query(array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1
        ));
        $current_count = $count_query->found_posts;
        // If stored count is not available or mismatched with current post count
        if ($stored_count === false || (int)$stored_count !== $current_count) {
            set_transient('_omni_post_count_' . $post_type, $current_count, 12 * HOUR_IN_SECONDS);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function reindex_project(): bool
    {
        $project_id = get_option('_omni_project_id');
        $data = $this->api->get_resources($project_id);
        if (!$data && !is_array($data)) {
            $this->debug->omni_error_log('Reindex error code: ' . $data);
            return false;
        } else {
            if ($data && isset($data[0]->url)) {
                $data_url = $data[0]->url;
                $this->api->del_resources($data_url, $project_id);

                $data_sent = $this->sync_data();
                if ($data_sent === true) {
                    $this->debug->omni_error_log('Data successfully updated in reindex_project.');
                    $this->debug->omni_error_log('=========='); // Separator
                } else {
                    $this->debug->omni_error_log('Error sending data in reindex_project.');
                    $this->debug->omni_error_log('=========='); // Separator
                }
                return true;
            } else {
                return false;
            }

        }
    }

    /**
     * Purges the cache by deleting all transients related to Omni search results.
     *
     * @return bool Returns true if all transients are successfully deleted, otherwise false.
     */
    public function purge_cache(): bool
    {

        $transients = $this->read_transient();
        $res = [];
        foreach ($transients as $transient) {
            // Strip away the WordPress prefix in order to arrive at the transient key.
            $key = str_replace('_transient_', '', $transient);

            // Now that we have the key, so delete the transient.
            $res[] = delete_transient($key);
        }

        if (count(array_unique($res)) === 1)
            return current($res);
        else return false;
    }

    /**
     * Retrieves a list of transients that match a specific prefix.
     *
     * @return array List of transients.
     */
    private function read_transient(): array
    {
        global $wpdb;

        $prefix = 'omni_search_results_';
        $cache_key = 'omni_search_results_transient_cache';

        $cached_value = wp_cache_get($cache_key);

        if (false === $cached_value) {
            $cached_value = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
                    $wpdb->esc_like('_transient_' . $prefix) . '%'
                )
            );
            wp_cache_set($cache_key, $cached_value);
        }
        return $cached_value;
    }

    /**
     * @return void
     */
    public function sync_data_ajax_handler(): void
    {
        check_ajax_referer('project_sync_nonce_action', 'nonce');
        $pointer = sanitize_text_field($_POST['pointer']);
        $result = $this->sync_data_pointer($pointer);
        wp_send_json_success(array('pointer' => $result['pointer'], 'count' => $result['count']));
    }

    public function create_project_action(): void
    {
        check_ajax_referer('project_create_nonce_action', 'nonce');
        $project_name = sanitize_text_field($_POST['projectName']);
        $project_created = $this->api->create_project($project_name);
        if ($project_created['status'] === 'success') {
            wp_send_json_success($project_created['message']);
        } else {
            wp_send_json_error($project_created['message']);
        }
    }

    /**
     * @param $post_type
     * @param $project_id
     * @param $chains
     *
     * @return void
     */
    private function delete_posts_of_type($post_type, $project_id, &$chains): void
    {
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );
        $posts = get_posts($args);

        foreach ($posts as $post) {
            $chain_item = array(
                "chain" => "basic-delete",
                "payload" => array(
                    "indexName" => $project_id,
                    "where" => wp_json_encode(array(
                        "operator" => "Equal",
                        "path" => array("eid"),
                        "valueNumber" => $post->ID
                    ))
                )
            );
            $chains[] = $chain_item;
        }
    }


    /**
     * @param $post_type
     * @param $project_id
     * @param $chains
     * @param $fields
     *
     * @return void
     */
    private function add_posts_of_type($post_type, $project_id, &$chains, $fields): void
    {
        if (!isset($fields)) {
            return;
        }

        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_exclude_from_omni',
                    'value' => '1',
                    'compare' => '!=',
                ),
                array(
                    'key' => '_exclude_from_omni',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );
        $posts = get_posts($args);

        foreach ($posts as $post) {
            $post_id = $post->ID;
            $post_title = $post->post_title;
            $post_content = wp_strip_all_tags($post->post_content, false);
            $post_url = get_permalink($post->ID);
            $post_author = get_the_author_meta('display_name', $post->post_author);
            $post_title = $post_title ?: $post_url;
            $post_data = '';
            foreach ($fields as $field) {
                if (isset($field['status']) && $field['status'] == 1) {
                    $label = $field['label'] ?: $field['name'];
                    $content = get_post_meta($post_id, $field['name'], true);

                    switch ($field['name']) {
                        case 'Title':
                            $content = $post_title;
                            break;
                        case 'Content':
                            $content = $post_content;
                            break;
                        case 'Author':
                            $content = $post_author;
                            break;
                    }

                    if (is_array($content)) {
                        $content = $this->api->createAttributeStrings($content);
                    }

                    $post_data .= "{$label}: {$content}\n";
                }
            }

            $post_data .= "url: {$post_url}\n";

            $chain_item = array(
                "chain" => "basic-informer",
                "payload" => array(
                    "indexName" => $project_id,
                    "no-wait" => true,
                    "widgetTypeId" => OMNI_WIDGET_TYPE_ID,
                    "json" => array(
                        "informer" => array(
                            "type" => "text",
                            "family" => "informer",
                            "settings" => array(
                                "content" => $post_data,
                                "metadata" => array(
                                    "title" => $post_title,
                                    "url" => $post_url,
                                    "eid" => $post_id
                                )
                            )
                        )
                    )
                )
            );

            $chains[] = $chain_item;
        }
    }

    /**
     * @param $array1
     * @param $array2
     *
     * @return bool
     */
    private function compare_second_level($array1, $array2): bool
    {
        foreach ($array1 as $key => $value) {
            if (!isset($array2[$key]) || array_diff_assoc($value, $array2[$key])) {
                return true;
            }
        }
        foreach ($array2 as $key => $value) {
            if (!isset($array1[$key])) {
                return true;
            }
        }

        return false;
    }

    public function add_meta_box(): void
    {
        $post_types = get_post_types(array('public' => true));
        foreach ($post_types as $post_type) {
            add_meta_box(
                'exclude_omni', // ID
                __('Exclude from Omnimind', 'omnimind'), // Title
                array($this, 'exclude_omni_meta_box_callback'), // Callback function
                $post_type, // Screen (post type)
                'side', // Context
                'default' // Priority
            );
        }
    }

    public function exclude_omni_meta_box_callback($post): void
    {
        $value = get_post_meta($post->ID, '_exclude_from_omni', true);
        echo '<label><input type="checkbox" name="exclude_from_omni" value="1"' . checked(esc_html($value), 1, false) . '/> ' . esc_html__('Exclude from Omnimind', 'omnimind') . '</label>';
    }

    /**
     * @param $post_id
     *
     * @return void
     */
    public function bulk_quick_save_post($post_id): void
    {
        // Check if this function has already been executed in the current request
        if (defined('OMNI_CUSTOM_FUNCTION_EXECUTED') && OMNI_CUSTOM_FUNCTION_EXECUTED) {
            return;
        }
        // Do not execute on DOING_AUTOSAVE
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        // Do not execute on post revision
        if (wp_is_post_revision($post_id)) {
            return;
        }
        // check inlint edit nonce if _inline_edit nonce is set and verify it
        if (isset($_POST['_inline_edit']) && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_inline_edit'])), 'inlineeditnonce')) {
            return;
        }
        // not inline - fix multiple sending request
        if (!isset($_POST['_inline_edit'])) {
            if (wp_doing_ajax() || !is_admin()) {
                return;
            }
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['exclude_from_omni_bulk'])) {
            update_post_meta($post_id, '_exclude_from_omni', sanitize_text_field($_POST['exclude_from_omni_bulk']));
        } elseif (isset($_POST['exclude_from_omni'])) {
            update_post_meta($post_id, '_exclude_from_omni', sanitize_text_field($_POST['exclude_from_omni']));
        } else {
            delete_post_meta($post_id, '_exclude_from_omni');
        }
        $post_exclude = get_post_meta($post_id, '_exclude_from_omni', true);

        // Send updates
        $fields_array = get_option('_omni_selected_fields_option');
        $post_type = get_post_type($post_id);
        $status = get_post_status($post_id);

        // Send post to Omnimind
        $this->handle_post($fields_array, $post_type, $post_id, $status);
        $this->debug->omni_error_log('=========='); // Separator

        // Mark that the function has been executed to prevent further executions
        define('OMNI_CUSTOM_FUNCTION_EXECUTED', true);
    }

    private function handle_post($fields_array, $post_type, $post_id, $status, bool $deactivateAjax = true): void
    {
        if (isset($fields_array[$post_type])) {
            $exclude_from_omni = get_post_meta($post_id, '_exclude_from_omni', true);
            if ($status == 'publish') {
                $this->api->delete_post($post_id);
                if ('1' !== $exclude_from_omni) {
                    $this->api->send_post($post_id, $deactivateAjax);
                }
            }
            if ($status == 'draft' || $status == 'trash') {
                $this->api->delete_post($post_id);
            }
        }
    }


}