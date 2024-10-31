<?php

namespace Procoders\Omni\Includes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Procoders\Omni\Includes\debugger as debugger;

class api
{
    private $debug;

    public function __construct()
    {
        $this->debug = new debugger();
    }

    /**
     * Verifies the validity of an API key by making a request to the Omni API.
     *
     * @param string $api_key The API key to be verified.
     * @return bool Returns `true` if the API key is valid, `false` otherwise.
     */
    public function verify_api_key($api_key): bool
    {
        $url = OMNI_ENV_URL . '/v1/functions/users/me';
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key
        );
        $args = array(
            'headers' => $headers,
        );
        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            return false;
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function get_projects(): array
    {
        $url = OMNI_ENV_URL . '/rest/v1/projects/';
        $omni_api_key = get_option('_omni_api_key');

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $omni_api_key,
            ),
            'method' => 'GET',
            'timeout' => '10000',
        );
        if ($omni_api_key) {
            $response = wp_remote_request($url, $args);
        } else {
            return [];
        }
        if (is_wp_error($response)) {
            $this->debug->omni_error_log('Get projects error code: ' . $response->get_error_message());
            return [];

        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                $body = wp_remote_retrieve_body($response);

                return json_decode($body);
            } else {
                return [];
            }
        }
    }

    /**
     * Creates a project in the Omni API with the given project name.
     *
     * @param string $project_name The name of the project to be created.
     * @return bool Returns `true` if the project is created successfully, `false` otherwise.
     */
    public function create_project($project_name): array
    {
        $url = OMNI_ENV_URL . '/rest/v1/projects/';
        $omni_api_key = get_option('_omni_api_key');
        $data = array(
            'omni_key' => $omni_api_key,
            'name' => sanitize_text_field($project_name),
        );
        $args = array(
            'body' => wp_json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        );
        $response = wp_safe_remote_post($url, $args);
        if (is_wp_error($response)) {
            return ['status' => 'error', 'message' => $response->get_error_message()];
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            switch ($response_code) {
                case 200:
                    $response_data = json_decode(wp_remote_retrieve_body($response), true);

                    $project_id = $response_data['id'];
                    $project_name = $response_data['name'];
                    update_option('_omni_project_id', $project_id);
                    update_option('_omni_project_name', $project_name);
                    return ['status' => 'success', 'message' => __('Project created successfully.', 'omnimind')];

                case 403:
                    $response_data = json_decode(wp_remote_retrieve_body($response), true);
                    $this->debug->omni_error_log('Create project error: ' . $response_data['message']);
                    return ['status' => 'error', 'message' => $response_data['message']];

                case 500:
                    $this->debug->omni_error_log('Server error: 500');
                    return ['status' => 'error', 'message' => __('Server error.', 'omnimind')];

                default:
                    return [];
            }

        }
    }

    /**
     * Makes a search request to the Omni API.
     *
     * @param string $query The search query.
     * @return bool|array Returns an array of search results if successful, false otherwise.
     */
    public function make_search_req(string $query)
    {
        $omni_api_key = get_option('_omni_api_key');
        $project_id = get_option('_omni_project_id');
        $limit = (int)get_option('_omni_ai_search_results_limit');
        $proof_level = (float)get_option('_omni_ai_search_trust_level') ?? 0.6;
        $lang = get_locale();
        $url = OMNI_ENV_URL . '/rest/v1/projects/' . $project_id . '/actions/ask';

        $customPrompt = get_option('_omni_ai_custom_search_prompt');

        $patternSufix = " in a VALID JSON FORMAT, do not use markdown for JSON. Use pattern [{\"url\": \"url 1\", \"short_description\": \"short_description 1\", \"title\": \"title 1\"},
                                 {\"url\": \"url 2\", \"short_description\": \"short_description 2\", \"title\": \"title 2\"}
                                ...
                                 ,{\"url\": \"url n\", \"short_description\": \"short_description n\", \"title\": \"title n\"}
                                ] JSON object literal count limit is $limit. Answers should only contain the essential key terms or phrases directly relevant to the question, without elaborating. 
                                If products are being searched, present the answer as a product search result with title, price, description and other information.";
        if ($customPrompt) {
            $customPrompt = str_replace(['[question]', '[query]', '[lang]', '[limit]'], [$query, $query, $lang, $limit], $customPrompt . $patternSufix);
        } else {
            $customPrompt = "You are a search engine like google. You must return suitable items that regarding the user question. 
                                Generate in $lang language and user search question '$query', sort by relevance in descending order, 
                                in a VALID JSON FORMAT. Use pattern 
                                [{\"url\": \"url 1\", \"short_description\": \"short_description 1\", \"title\": \"title 1\"},
                                 {\"url\": \"url 2\", \"short_description\": \"short_description 2\", \"title\": \"title 2\"}
                                ...
                                 ,{\"url\": \"url n\", \"short_description\": \"short_description n\", \"title\": \"title n\"}
                                ]  JSON object literal count limit is $limit.
                                Answers should only contain the essential key terms or phrases directly relevant to the question, without elaborating. 
                                If products are being searched, present the answer as a product search result with title, price, description and other information.";
        }
        $data = array(
            'language' => get_locale(),
            "hybrid" => 0,
            "limit" => 16,
            "offset" => 0,
            'proofLevel' => $proof_level,
            'query' => $customPrompt,
            "prompt" => array(
                'formatting' => 'markdown',
                "tone" => "creative",
                "role" => "Search engine"
            ),
        );

        $headers = array(
            'Authorization' => 'Bearer ' . $omni_api_key,
            'Content-Type' => 'application/json',
        );
        $args = array(
            'body' => wp_json_encode($data),
            'headers' => $headers,
            'timeout' => '10000',
        );
        $response = wp_safe_remote_post($url, $args);

        if (is_wp_error($response)) {
            $this->debug->omni_error_log('Search req error: ' . $response->get_error_message());
            return false;
        } else {
            return json_decode(wp_remote_retrieve_body($response), true);
        }
    }

    /**
     * Makes a request to the Omni API to generate a general answer for the given query.
     *
     * @param string $query The query for which the general answer is to be generated.
     * @return bool|array Returns `false` if there is an error in the request or decoding the response,
     *                   otherwise returns the decoded JSON response as an array.
     */
    public function make_answer_req(string $query)
    {
        $omni_api_key = get_option('_omni_api_key');
        $project_id = get_option('_omni_project_id');
        $proof_level = (float)get_option('_omni_ai_search_trust_level') ?? 0.6;
        $lang = get_locale();
        $url = OMNI_ENV_URL . '/rest/v1/projects/' . $project_id . '/actions/ask';

        $customPrompt = get_option('_omni_ai_custom_answer_prompt');

        if ($customPrompt) {
            $customPrompt = str_replace(['[question]', '[lang]'], [$query, $lang], $customPrompt);
        } else {
            $customPrompt = "You must return a general answer to this question: \"" . $query . "\". Generate in $lang language. 
                               If products are being searched, present the answer as a product search result with title, price, description and other information and 
                               work like shop consultant.
                               Answers should only contain the essential key terms or phrases directly 
                               relevant to the question, without elaborating. Use markdown, make additional offers in answer";
        }


//        $data = array(
//            'language' => get_locale(),
//            'hybrid' => 0,
//            'proofLevel' => $proof_level,
//            'customPrompt' => $customPrompt
//        );

        $data = array(
            'language' => get_locale(),
            "hybrid" => 0,
            "limit" => 16,
            "offset" => 0,
            'proofLevel' => $proof_level,
            'query' => $customPrompt,
            "prompt" => array(
                'formatting' => 'markdown',
                "tone" => "creative",
                "role" => "Sales assistant"
            ),
        );

        $headers = array(
            'Authorization' => 'Bearer ' . $omni_api_key,
            'Content-Type' => 'application/json',
        );
        $args = array(
            'body' => wp_json_encode($data),
            'headers' => $headers,
            'timeout' => '10000',
        );
        $response = wp_safe_remote_post($url, $args);

        if (is_wp_error($response)) {
            $this->debug->omni_error_log('Search req error: ' . $response->get_error_message());
            return false;
        } else {
            return json_decode(wp_remote_retrieve_body($response), true);
        }
    }

    /**
     * Deletes the Omni project and updates the corresponding options if the deletion is successful.
     *
     * @return bool Returns `true` if the project is deleted successfully and options are updated.
     *              Returns `false` if there is an error in the request or the response status code is not 200.
     */
    public function delete_project(): bool
    {
        $omni_api_key = get_option('_omni_api_key');
        $project_id = get_option('_omni_project_id');
        $url = OMNI_ENV_URL . '/rest/v1/projects/' . $project_id;

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $omni_api_key,
            ),
            'method' => 'DELETE',
            'timeout' => '10000',
        );

        $response = wp_remote_request($url, $args);


        if (is_wp_error($response)) {
            return false;
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            //  if ($response_code === 200) {
            update_option('_omni_api_key', '');
            update_option('_omni_project_id', '');
            update_option('_omni_project_name', '');
            update_option('_omni_selected_post_types', '');
            update_option('_omni_selected_fields_option', '');
            update_option('_omni_uploaded_fields_option', '');
            update_option('_omni_last_sync_date', '');
            update_option('_omni_api_key_status', false);

            return true;
//            } else {
//                return false;
//            }
        }
    }


    /**
     * Retrieves the list of resources (URLs) associated with a project from the Omni API.
     *
     * @param string $project_id The ID of the project for which the resources are to be retrieved.
     * @return bool|array Returns `false` if there is an error in the request or decoding the response,
     *                   otherwise returns the decoded JSON response as an array.
     */
    public function get_resources(string $project_id)
    {
        $url = OMNI_ENV_URL . '/rest/v1/projects/' . $project_id . '/resources/urls/';
        $omni_api_key = get_option('_omni_api_key');
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $omni_api_key,
            ),
            'timeout' => '20000',
            'method' => 'GET'
        );
        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $this->debug->omni_error_log('Reindex error code: ' . $response->get_error_message());
            return false;
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                $body = wp_remote_retrieve_body($response);
                return json_decode($body);
            } else {
                return false;
            }
        }
    }

    public function del_resources(string $data_url, string $project_id)
    {
        $omni_api_key = get_option('_omni_api_key');
        $new_url = OMNI_ENV_URL . '/rest/v1/projects/' . $project_id . '/resources/urls/';

        $new_data = array(
            'omni_key' => $omni_api_key,
            'url' => $data_url,
        );

        $new_args = array(
            'body' => wp_json_encode($new_data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => '20000',
            'method' => 'DELETE'
        );
        $delete_response = wp_remote_request($new_url, $new_args);
        if (is_wp_error($delete_response)) {
            $this->debug->omni_error_log('Error in DELETE request in reindex_project: ' . $delete_response->get_error_message());
            return false;
        } else {
            $delete_response_code = wp_remote_retrieve_response_code($delete_response);
            if ($delete_response_code === 200) {
                $this->debug->omni_error_log('Successful deletion in reindex_project.');
                return true;
            } else {
                $this->debug->omni_error_log('Error in DELETE request: Response code ' . $delete_response_code);
                return false;
            }
        }
    }

    /**
     * @param $chains
     * @param $omni_api_key
     * @param $project_id
     * @param $fields_array
     *
     * @return bool|void
     */
    public function send_requests($chains, $omni_api_key, $project_id, $fields_array)
    {
        // ToDo: $project_id unused

        $json_data = array("chains" => [$chains]);
        $json_body = wp_json_encode($json_data);
        $endpoint = OMNI_ENV_URL . '/v1/functions/chain/template/run-multiple';


        $response = wp_safe_remote_post($endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $omni_api_key,
            ),
            'timeout' => '20000',
            'body' => $json_body,
            'method' => 'POST'
        ));

        if (is_wp_error($response)) {
            $this->debug->omni_error_log('An error occurred when sending data to a remote server: ' . wp_remote_retrieve_response_code($response));
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                update_option('_omni_uploaded_fields_option', $fields_array);
                $this->debug->omni_error_log('Data synced');
                return true;
            } else {
                $this->debug->omni_error_log('Error when sending data: server response with code: ' . $response_code);

                return false;
            }
        }
    }


    /**
     * @param $post_id
     *
     * @return bool|void
     */
    public function delete_post($post_id)
    {
        $omni_api_key = get_option('_omni_api_key');
        $project_id = get_option('_omni_project_id');
        $fields_array = get_option('_omni_selected_fields_option');
        $post_type = get_post_type($post_id);

        if (isset($fields_array[$post_type])) {
            $chains = array();

            $chain_item = array(
                "chain" => "basic-delete",
                "payload" => array(
                    "indexName" => $project_id,
                    "where" => wp_json_encode(array(
                        "operator" => "Equal",
                        "path" => array("eid"),
                        "valueNumber" => $post_id
                    ))
                )
            );

            $chains[] = $chain_item;

            $json_data = array(
                "chains" => $chains
            );
            $json_body = wp_json_encode($json_data);
            $endpoint = OMNI_ENV_URL . '/v1/functions/chain/template/run-multiple';
            $response = wp_safe_remote_post($endpoint, array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $omni_api_key,
                ),
                'body' => $json_body,
                'method' => 'POST'
            ));

            if (is_wp_error($response)) {
                $this->debug->omni_error_log('An error occurred when deleting post: ' . wp_remote_retrieve_response_code($response));

                return false;
            } else {
                $response_code = wp_remote_retrieve_response_code($response);
                if ($response_code === 200) {

                    $this->debug->omni_error_log('post: ' . $post_id . ' deleted');

                    return true;
                } else {
                    $this->debug->omni_error_log('Error when sending post deleting: server response with code: ' . $response_code);

                    return false;
                }
            }
        }
    }


    /**
     * @param $post_id
     *
     * @return bool
     */
    public function send_post($post_id)
    {
        $omni_api_key = get_option('_omni_api_key');
        $project_id = get_option('_omni_project_id');
        $fields_array = get_option('_omni_selected_fields_option');
        $post_exclude = get_post_meta($post_id, '_exclude_from_omni', true);

        if ($post_exclude == '1') {
            return false;
        }
        $chains = array();

        $post_title = get_the_title($post_id);
        $post_content = get_post_field('post_content', $post_id);
        $post_url = get_permalink($post_id);
        $author_id = get_post_field('post_author', $post_id);
        $post_author = get_the_author_meta('display_name', $author_id);
        $post_type = get_post_type($post_id);

        // remove all tags and br from content
        $post_content = wp_strip_all_tags($post_content, false);
        if ($post_title == '') {
            $post_title = $post_url;
        }
        $post_data = '';
        if (isset($fields_array[$post_type])) {
            foreach ($fields_array[$post_type] as $field) {
                if (isset($field['status']) && $field['status'] == 1) {
                    if ($field['label']) {
                        $label = $field['label'];
                    } else {
                        $label = $field['name'];
                    }
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
                        default:
                            break;
                    }

                    if (is_array($content)) {
                        $content = $this->createAttributeStrings($content);
                    }

                    $post_data .= $label . ": " . $content . "\n\n";

                }
            }
        }
        $post_data .= "url: " . $post_url . "\n";

        $chain_item = array(
            "chain" => "basic-informer",
            "payload" => array(
                "indexName" => $project_id,
                "no-wait" => true,
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

        $json_data = array(
            "chains" => $chains
        );

        $json_body = wp_json_encode($json_data);
        $endpoint = OMNI_ENV_URL . '/v1/functions/chain/template/run-multiple';
        $response = wp_safe_remote_post($endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $omni_api_key,
            ),
            'body' => $json_body,
            'method' => 'POST'
        ));

        if (is_wp_error($response)) {
            $this->debug->omni_error_log('An error occurred when sending post to a remote server: ' . $response->get_error_message());

            return false;
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code === 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body);
                if ($data && isset($data->id)) {
                    $id = $data->id;
                    update_option('_omni_chain_id', $id);
                }
                $this->debug->omni_error_log('Post updated: ' . $post_title . ' - Post type: ' . $post_type);

                return true;
            } else {
                $this->debug->omni_error_log('Error when sending post: server response with code: ' . $response_code);

                return false;
            }
        }
    }


    /**
     * Create attribute strings from the provided content array.
     *
     * @param array $content The content array containing attribute details.
     *                       Each key represents an attribute name and the corresponding value can be either a string or an associative array.
     *                       If the value is an array, it should contain 'name' and 'value' keys representing the name and value of the attribute respectively.
     *                       If the value is a string, it will be used as the attribute value with no name.
     *                       Examples:
     *                        - ['class' => 'my-class', 'id' => 'my-id'] will produce 'class: my-class, id: my-id'
     *                        - ['class' => ['name' => 'class', 'value' => 'my-class'], 'id' => 'my-id'] will produce 'class: my-class, id: my-id'
     *                        - ['disabled', 'readonly'] will produce 'disabled, readonly'
     * @return string The attribute strings joined together with a comma separator.
     *                Example: 'class: my-class, id: my-id'
     */
    public function createAttributeStrings(array $content): string
    {
        $attributesStrings = array();

        foreach ($content as $attribute => $details) {
            if (is_array($details)) {
                $name = $details['name'] ?? '';
                $value = $details['value'] ?? '';
                if (!empty($name) && !empty($value)) {
                    $attributesStrings[] = $name . ": " . $value;
                } else {
                    $attributesStrings[] = implode(", ", $details);
                }
            } else {
                $attributesStrings[] = $details;
            }
        }

        return implode(', ', $attributesStrings);
    }
}






