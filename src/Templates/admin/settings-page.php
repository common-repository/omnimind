<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$api_key_status = $data->form['api_key_status'];
$omni_api_key = $data->form['omni_api_key'];
$project_name = $data->form['project_name'];
$projects_list = $data->form['projects_list'];
$project_id = $data->form['project_id'];
$selected_post_types = $data->form['selected_post_types'];

$ai_search_answer = $data->form['ai_search_answer'];
$ai_search_content = $data->form['ai_search_content'];
$ai_search_autocomplete = $data->form['ai_search_autocomplete'];
$ai_search_cache = $data->form['ai_search_cache'];
$ai_search_results_limit = $data->form['ai_search_results_limit'];
$ai_search_trust_level = $data->form['ai_search_trust_level'] ?? 0.6;
$ai_cache = $data->form['ai_cache'];
$settings = $data->form['ai_omni_setting'];
$logs = $data->form['search_log'];

$custom_answer_prompt = $data->form['custom_answer_prompt'];
$custom_search_prompt = $data->form['custom_search_prompt'];

?>
    <div class="omni-config wrap">

        <!-- Create Project Modal -->
        <div id="omni_modal" class="omni-form-modal">
            <!-- Modal content -->
            <div class="omni-project-modal-content">
                <?php wp_nonce_field('project_create_nonce_action', 'project_create_nonce'); ?>
                <input type="text" id="projectName" placeholder="<?php esc_html_e('Project name', 'omnimind'); ?>"
                       class="omni-input">
                <button id="submitProject" title="<?php esc_html_e('Create project', 'omnimind'); ?>"
                        class="btn-omni btn-omni--primary">
                    <?php esc_html_e('Create project', 'omnimind'); ?>
                </button>

                <span class="omni-modal-close">&times;</span>
            </div>
        </div>
        <div class="omni-config__container">
            <h2><?php esc_html_e('Omnimind Configuration', 'omnimind'); ?></h2>
            <div class="tabset">
                <ul class="tab-control">
                    <li><a class="tab-opener" href="#"><?php esc_html_e('General', 'omnimind'); ?></a></li>
                    <li><a class="tab-opener" href="#"><?php esc_html_e('Content types', 'omnimind'); ?></a></li>
                    <li><a class="tab-opener" href="#"><?php esc_html_e('Indexing', 'omnimind'); ?></a></li>
                    <li><a class="tab-opener" href="#"><?php esc_html_e('Requests', 'omnimind'); ?></a></li>
                    <li><a class="tab-opener" href="#"><?php esc_html_e('Custom Prompts', 'omnimind'); ?></a></li>
                    <li><a class="tab-opener" href="#"><?php esc_html_e('Info', 'omnimind'); ?></a></li>
                </ul>
                <div class="tabs-list">
                    <div class="tab-item">
                        <?php if ($api_key_status): ?>
                            <div class="form-row">
                                <div class="form-row__label">
                                    <div class="form-label"><?php esc_html_e('API Key', 'omnimind'); ?></div>
                                </div>
                                <div class="form-row__item">
                                    <div class="api-status">
                                        <input class="form-input" type="text" name="verify_api_key"
                                               value="<?php echo esc_html($omni_api_key); ?>">
                                        <div class="status"><?php echo (esc_attr($api_key_status)) ? '<span class="dashicons dashicons-yes-alt"></span>' : '<span class="dashicons dashicons-dismiss"></span>'; ?></div>
                                    </div>
                                    <div class="form-info"><?php esc_html_e('Your Omnimind API key', 'omnimind'); ?></div>
                                </div>
                            </div>
                            <form method="post">
                                <?php wp_nonce_field('project_name_nonce_action', 'project_name_nonce'); ?>
                                <div class="form-row">
                                    <div class="form-row__label">
                                        <div class="form-label"><?php esc_html_e('Project', 'omnimind'); ?></div>
                                    </div>
                                    <div class="form-row__item">
                                        <div class="inputs-wrap">

                                            <select  <?php if(empty($projects_list)) { echo 'disabled'; } ?> name="existing_project" class="form-input"
                                                    id="existing_project">
                                                <?php foreach ($projects_list as $project) {
                                                    $selected = $project_id === $project->id ? 'selected' : '';
                                                    ?>
                                                    <option <?php echo esc_html($selected); ?>
                                                            value="<?php echo esc_html($project->id); ?>">
                                                        <?php echo esc_html($project->name); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>

                                            <a <?php echo !$project_id ? 'style="display:none"' : ''; ?>
                                                    href="<?php echo esc_attr($settings) ?>" target="_blank"
                                                    class="btn-omni btn-omni--primary"><span
                                                        class="dashicons dashicons-external"></span> <?php esc_html_e('Settings', 'omnimind') ?>
                                            </a>
                                            <button <?php echo $project_id ? 'style="display:none"' : ''; ?>
                                                    type="submit"
                                                    name="select_project"
                                                    class="btn-omni btn-omni--primary"><span
                                                        class="dashicons dashicons-yes"></span><?php esc_html_e('Select', 'omnimind') ?>
                                            </button>
                                            <button id="openModal" title="<?php esc_html_e('Create project', 'omnimind'); ?>" class="btn-omni btn-omni--primary"><span
                                                        class="dashicons dashicons-plus-alt"></span></button>

                                        </div>
                                        <div class="form-info"><?php esc_html_e('Your Omnimind Project name', 'omnimind') ?></div>
                                    </div>
                                </div>
                            </form>
                            <form method="post">
                                <?php wp_nonce_field('project_config_nonce_action', 'project_config_nonce'); ?>
                                <div class="form-row">
                                    <div class="form-row__label">
                                        <div class="form-label"><?php esc_html_e('Options', 'omnimind') ?></div>
                                    </div>
                                    <div class="form-row__item">
                                        <ul class="checkbox-list">
                                            <li>
                                                <label class="checkbox-holder">
                                                    <input name="ai_search_answer" type="checkbox"
                                                           class="checkbox" <?php checked(1, esc_html($ai_search_answer)); ?> />
                                                    <span class="checkbox-item">&nbsp;</span>
                                                    <span class="checkbox-label"><?php esc_html_e('AI answer', 'omnimind') ?></span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="checkbox-holder">
                                                    <input name="ai_search_content" type="checkbox"
                                                           class="checkbox" <?php checked(1, esc_html($ai_search_content)); ?> />
                                                    <span class="checkbox-item">&nbsp;</span>
                                                    <span class="checkbox-label"><?php esc_html_e('Content', 'omnimind') ?></span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="checkbox-holder">
                                                    <input name="ai_search_autocomplete" type="checkbox"
                                                           class="checkbox" <?php checked(1, esc_html($ai_search_autocomplete)); ?> />
                                                    <span class="checkbox-item">&nbsp;</span>
                                                    <span class="checkbox-label"><?php esc_html_e('Autocomplete', 'omnimind') ?></span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="checkbox-holder">
                                                    <input name="ai_search_cache" type="checkbox"
                                                           class="checkbox" <?php checked(1, esc_html($ai_search_cache)); ?> />
                                                    <span class="checkbox-item">&nbsp;</span>
                                                    <span class="checkbox-label"><?php esc_html_e('Use caching', 'omnimind') ?></span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="form-columns">
                                    <div class="form-col">
                                        <div class="form-row">
                                            <div class="form-row__label">
                                                <div class="form-label"><?php esc_html_e('Results limit', 'omnimind') ?></div>
                                            </div>
                                            <div class="form-row__item">
                                                <input class="form-input" type="number"
                                                       name="ai_search_results_limit"
                                                       value="<?php echo (esc_attr($ai_search_results_limit)) ? esc_attr($ai_search_results_limit) : '5'; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-col">
                                        <div class="form-row">
                                            <div class="form-row__label">
                                                <div class="form-label"><?php esc_html_e('Proof level', 'omnimind') ?></div>
                                            </div>
                                            <div class="form-row__item">
                                                <input class="form-input" type="number" step=".1" max=".9" min=".1"
                                                       name="ai_search_trust_level"
                                                       value="<?php echo esc_html($ai_search_trust_level) ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-block">
                                    <div class="form-block__title">
                                        <span><?php esc_html_e('Cache', 'omnimind') ?></span></div>
                                    <div class="form-block__frame">
                                        <div class="form-block__wrap">
                                            <div class="form-block__content">
                                                <p><?php esc_html_e('Output results can be cached to prevent numerous requests to AI and save
                                                the costs. If you set it to 0 no cache is going to be applied', 'omnimind') ?></p>
                                                <div class="cache-input">
                                                    <div class="form-label"><?php esc_html_e('Cache period', 'omnimind') ?></div>
                                                    <input class="form-input" type="number" min="1" name="ai_cache"
                                                           value="<?php echo(esc_attr($ai_cache) ? esc_attr($ai_cache) : '24'); ?>">
                                                    <div class="cache-input__info"><?php esc_html_e('hours', 'omnimind') ?></div>
                                                </div>
                                            </div>
                                            <div class="form-block__button">
                                                <button name="purge_cache" id="purge_cache_button"
                                                        class="btn-omni btn-omni--warning btn-omni--block">
                                                    <svg class="svg-icon" width="16" height="16">
                                                        <use xlink:href="<?php echo esc_html(plugins_url('../../assets/images/icons.svg#icon-purge', dirname(__FILE__))); ?>"></use>
                                                    </svg>
                                                    <span><?php esc_html_e('Purge cache', 'omnimind') ?></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button name="save_general" type="submit" class="btn-omni btn-omni--primary">
                                    <svg class="svg-icon" width="16" height="16">
                                        <use xlink:href="<?php echo esc_html(plugins_url('../../assets/images/icons.svg#icon-save', dirname(__FILE__))); ?>"></use>
                                    </svg>
                                    <span><?php esc_html_e('Save', 'omnimind') ?></span>
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="post">
                                <?php wp_nonce_field('project_apikey_nonce_action', 'project_apikey_nonce'); ?>
                                <div class="form-row">
                                    <div class="form-row__label">
                                        <div class="form-label"><?php esc_html_e('API Key', 'omnimind') ?></div>
                                    </div>
                                    <div class="form-row__item">
                                        <div class="api-verify">
                                            <input class="form-input" type="text" name="verify_api_key"
                                                   value="<?php echo esc_html($omni_api_key); ?>">
                                            <div class="status"><?php echo (esc_attr($api_key_status)) ? '<span class="dashicons dashicons-yes-alt"></span>' : '<span class="dashicons dashicons-dismiss"></span>'; ?></div>
                                            <button type="submit" name="check_api_key"
                                                    class="btn-omni btn-omni--primary">
                                                <?php esc_html_e('Verify', 'omnimind') ?>
                                            </button>
                                        </div>
                                        <div class="form-info">
                                            <ol>
                                                <li><a href="https://app.omnimind.ai/signup"
                                                       target="_blank"><?php esc_html_e('Sign Up', 'omnimind'); ?></a>
                                                    <?php esc_html_e('at Omnimind', 'omnimind'); ?></li>
                                                <li><?php esc_html_e('Get a key at', 'omnimind'); ?>
                                                    <strong><?php esc_html_e('Settings - Profile - API Keys', 'omnimind'); ?></strong> <?php esc_html_e('section', 'omnimind'); ?>
                                                </li>
                                                <li><?php esc_html_e('Copy and paste it here and clock Verify', 'omnimind'); ?></li>
                                                <li><?php esc_html_e('By happy with a new AI omni search :-)', 'omnimind'); ?></li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        <?php endif ?>
                    </div>
                    <div class="tab-item">
                        <form method="post">
                            <?php wp_nonce_field('project_content_nonce_action', 'project_content_nonce'); ?>

                            <p><?php esc_html_e('Select the content types in your project that will be included in the search results and be
                            used to construct AI answers', 'omnimind') ?></p>

                            <?php
                            $args = array(
                                'public' => true,
                                // '_builtin' => false,
                            );
                            $output = 'objects';
                            $operator = 'and';
                            $post_types = get_post_types($args, $output, $operator);
                            unset($post_types['attachment']);
                            $selected_fields = get_option('_omni_selected_fields_option');

                            if ($post_types) {

                                ?>
                                <ul class="content-types">
                                    <?php foreach ($post_types as $post_type): ?>
                                        <?php $additional_fields = array(
                                            'Title' => array(),
                                            'Content' => array(),
                                            'Author' => array(),
                                        ); ?>
                                        <li class="content-types__item">
                                            <?php


                                            $post_count = wp_count_posts($post_type->name);
                                            $label_with_count = $post_type->label . ' (' . $post_count->publish . ')';

                                            if (is_array($selected_post_types)) {
                                                unset($selected_post_types['ID']);
                                                unset($selected_post_types['filter']);
                                                $checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
                                            } else {
                                                $checked = '';
                                            }
                                            // $selected_post_types_array = explode(',', $selected_post_types);

                                            // $checked = in_array($post_type->name, $selected_post_types_array) ? 'checked' : '';
                                            ?>
                                            <label class="checkbox-holder content-type-head">
                                                <input name="post_types[]"
                                                       value="<?php echo esc_attr($post_type->name); ?>"
                                                       type="checkbox"
                                                       class="checkbox" <?php echo esc_attr($checked); ?> />
                                                <span class="checkbox-item">&nbsp;</span>
                                                <span class="checkbox-label"><?php echo esc_html($label_with_count); ?></span>
                                            </label>
                                            <div class="attributes-wrap">
                                                <?php
                                                $post_ids = get_posts(
                                                    [
                                                        'numberposts' => -1,
                                                        'post_type' => $post_type->name,
                                                    ]
                                                );
                                                $custom_fields = [];

                                                foreach ($post_ids as $post) {
                                                    $post_id = $post->ID;
                                                    $post_custom_fields = get_post_custom($post_id);

                                                    foreach ($post_custom_fields as $key => $values) {
                                                        if (!isset($custom_fields[$key])) {
                                                            $custom_fields[$key] = $values;
                                                        } else {
                                                            $custom_fields[$key] = array_merge($custom_fields[$key], $values);
                                                        }
                                                    }
                                                }
                                                if (is_array($custom_fields)) {
                                                    $jsonData = wp_json_encode(array_keys($custom_fields));
                                                } else {
                                                    $jsonData = wp_json_encode([]);
                                                }
                                                ?>
                                                <div class="autocomplete-data"
                                                     data-post-type="<?php echo esc_attr($post_type->name); ?>"
                                                     style="display:none;">
                                                    <?php echo esc_attr($jsonData); ?>
                                                </div>
                                                <table class="attributes-table">
                                                    <tr>
                                                        <th><?php esc_html_e('Attribute', 'omnimind'); ?></th>
                                                        <th><?php esc_html_e('Searchable', 'omnimind'); ?></th>
                                                        <th><?php esc_html_e('Label', 'omnimind'); ?></th>
                                                    </tr>


                                                    <?php foreach ($additional_fields as $key => $values): ?>
                                                        <tr>
                                                            <td><?php echo esc_html($key); ?></td>
                                                            <td>

                                                                <input class="form-input" type="hidden"
                                                                       name="post_type_fields[<?php echo esc_attr($post_type->name); ?>][<?php echo esc_attr($key); ?>][name]"
                                                                       value="<?php echo esc_attr($key); ?>">

                                                                <?php
                                                                if (isset($selected_fields[$post_type->name][$key]['status']) && $selected_fields[$post_type->name][$key]['status'] == 1) {
                                                                    echo '<input name="post_type_fields[' . esc_attr($post_type->name) . '][' . esc_attr($key) . '][status]" value="1" type="checkbox" class="checkbox" checked />';
                                                                } else {
                                                                    echo '<input name="post_type_fields[' . esc_attr($post_type->name) . '][' . esc_attr($key) . '][status]" value="1" type="checkbox" class="checkbox" />';
                                                                }
                                                                ?>

                                                            </td>
                                                            <td><input class="form-input" type="text"
                                                                       name="post_type_fields[<?php echo esc_attr($post_type->name); ?>][<?php echo esc_attr($key); ?>][label]"
                                                                       value="<?php echo isset($selected_fields[$post_type->name][$key]['label']) ? esc_attr($selected_fields[$post_type->name][$key]['label']) : ''; ?>">
                                                            </td>

                                                        </tr>

                                                    <?php endforeach ?>
                                                    <?php
                                                    // additional fields
                                                    if (isset($selected_fields[$post_type->name])) {
                                                        foreach ($selected_fields[$post_type->name] as $key => $values) {
                                                            $key = esc_attr($key);
                                                            if ($key == 'advanced-title-columns' || $key == 'advanced-metadata-columns' || $key == 'Title' || $key == 'Content' || $key == 'Author') {
                                                                continue;
                                                            }
                                                            echo '<tr>';
                                                            echo '<td>' . esc_html($key) . '</td>';
                                                            echo '<td>';
                                                            echo '<input class="form-input" type="hidden" name="post_type_fields[' . esc_attr($post_type->name) . '][' . esc_attr($key) . '][name]" value="' . esc_attr($key) . '">';
                                                            $checked = (isset($values['status']) && $values['status'] == 1) ? 'checked' : '';
                                                            echo '<input name="post_type_fields[' . esc_attr($post_type->name) . '][' . esc_attr($key) . '][status]" value="1" type="checkbox" class="checkbox" ' . esc_html($checked) . ' />';
                                                            echo '</td>';
                                                            echo '<td><input class="form-input" type="text" name="post_type_fields[' . esc_attr($post_type->name) . '][' . esc_attr($key) . '][label]" value="' . (isset($values['label']) ? esc_attr($values['label']) : '') . '"></td>';
                                                            echo '</tr>';
                                                        }
                                                    }
                                                    ?>

                                                </table>
                                                <div class="custom-field">
                                                    <input type="text" class="new-field-name"
                                                           data-post-type="<?php echo esc_attr($post_type->name); ?>">

                                                    <button type="button"
                                                            class="add-field btn-omni btn-omni--success"
                                                            data-post-type="<?php echo esc_attr($post_type->name); ?>"><?php esc_html_e('add
                                                    field', 'omnimind'); ?>
                                                    </button>
                                                </div>

                                                <div class="advanced-settings">
                                                    <button class="advanced-settings__opener btn-omni btn-omni--primary">
                                                        <?php esc_html_e('Advanced Settings', 'omnimind'); ?>
                                                    </button>
                                                    <div class="advanced-settings__content">
                                                        <div class="advanced-settings__row">
                                                            <div class="advanced-settings__label"> <?php esc_html_e('Select Title Columns', 'omnimind'); ?></div>
                                                            <div class="advanced-settings__input">
                                                                <select class="js-example-basic-multiple"
                                                                        name="post_type_fields[<?php echo esc_attr($post_type->name); ?>][advanced-title-columns][]"
                                                                        multiple="multiple">
                                                                    <?php
                                                                    $saved_title_columns = isset($selected_fields[$post_type->name]['advanced-title-columns']) ? $selected_fields[$post_type->name]['advanced-title-columns'] : array();
                                                                    foreach ($additional_fields as $key => $values):
                                                                        $selected = in_array($key, $saved_title_columns) ? 'selected' : '';
                                                                        ?>
                                                                        <option value="<?php echo esc_html($key); ?>" <?php echo esc_html($selected); ?>><?php echo esc_html($key); ?></option>
                                                                    <?php endforeach ?>
                                                                    <?php if (isset($selected_fields[$post_type->name])): ?>
                                                                        <?php foreach ($selected_fields[$post_type->name] as $key => $values): ?>
                                                                            <?php
                                                                            if ($key == 'advanced-title-columns' || $key == 'advanced-metadata-columns' || $key == 'Title' || $key == 'Content' || $key == 'Author') {
                                                                                continue;
                                                                            }
                                                                            $selected = in_array($key, $saved_title_columns) ? 'selected' : '';
                                                                            ?>
                                                                            <option value="<?php echo esc_html($key); ?>" <?php echo esc_html($selected); ?>><?php echo esc_html($key); ?></option>
                                                                        <?php endforeach ?>
                                                                    <?php endif ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="advanced-settings__row">
                                                            <div class="advanced-settings__label"> <?php esc_html_e('Select Metadata Columns', 'omnimind'); ?>
                                                            </div>
                                                            <div class="advanced-settings__input">
                                                                <select class="js-example-basic-multiple"
                                                                        name="post_type_fields[<?php echo esc_attr($post_type->name); ?>][advanced-metadata-columns][]"
                                                                        multiple="multiple">
                                                                    <?php
                                                                    $saved_title_columns = isset($selected_fields[$post_type->name]['advanced-metadata-columns']) ? $selected_fields[$post_type->name]['advanced-metadata-columns'] : array();
                                                                    foreach ($additional_fields as $key => $values):
                                                                        $selected = in_array($key, $saved_title_columns) ? 'selected' : '';
                                                                        ?>
                                                                        <option value="<?php echo esc_html($key); ?>" <?php echo esc_html($selected); ?>><?php echo esc_html($key); ?></option>
                                                                    <?php endforeach ?>

                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach ?>
                                </ul>
                                <?php
                            }
                            ?>
                            <p><?php esc_html_e('After making changes, it is highly advisable to run a re-synchronization at the', 'omnimind'); ?>
                                <strong><?php esc_html_e('Indexing', 'omnimind'); ?> </strong>
                                <?php esc_html_e('tab.', 'omnimind'); ?></p>
                            <button <?php echo ($api_key_status) ? '' : 'disabled'; ?> type="submit"
                                                                                       name="save_post_types"
                                                                                       class="btn-omni btn-omni--primary">
                                <svg class="svg-icon" width="16" height="16">
                                    <use xlink:href="<?php echo esc_html(plugins_url('../../assets/images/icons.svg#icon-save', dirname(__FILE__))); ?>"></use>
                                </svg>
                                <span><?php esc_html_e('Save Post Types', 'omnimind'); ?></span>
                            </button>
                        </form>
                    </div>
                    <div class="tab-item">
                        <?php
                        $selected_fields = get_option('_omni_selected_fields_option');
                        $selected_post_types = get_option('_omni_selected_post_types');
                        ?>

                        <form method="post" id="syncForm">
                            <?php wp_nonce_field('project_sync_nonce_action', 'project_sync_nonce'); ?>

                            <div class="form-block">
                                <div class="form-block__title">
                                    <span><?php esc_html_e('Sync Settings', 'omnimind'); ?></span>
                                </div>

                                <div class="form-block__frame">
                                    <div class="form-block__wrap">
                                        <div class="form-block__content">
                                            <p><?php esc_html_e('If you notice missing information in your search outcomes or if you\'ve
                                            recently incorporated new custom content categories to your platform, it\'s
                                            advisable to initiate a synchronization to update these modifications.', 'omnimind'); ?></p>

                                            <?php // Get last Sync Date
                                            $sync_date = get_option('_omni_last_sync_date'); ?>

                                            <p>Last sync status:
                                                <span id="last-sync-date"
                                                      style="<?php echo !empty($sync_date) ? 'color: green;' : '' ?>">
                                                <?php echo !empty($sync_date) ? esc_html($sync_date) : 'N/A'; ?>
                                            </span>
                                            </p>
                                            <!-- Progress bar -->
                                            <div class="progress-bar__wrap omni-progress--hide"
                                                 style="margin: 20px 0;width:100%;">
                                                <p>Progress: <span id="remaining_time"></span></p>
                                                <progress id="progress-bar" value="0" max="100"></progress>
                                            </div>
                                            <div id="progress-bar__res"></div>
                                        </div>
                                        <div class="form-block__button">
                                            <button <?php echo ($api_key_status) ? '' : 'disabled'; ?>
                                                    name="send_post_types" type="submit" id="sync-button"
                                                    class="btn-omni btn-omni--primary btn-omni--block">
                                                <svg class="svg-icon" width="16" height="16">
                                                    <use xlink:href="<?php echo esc_html(plugins_url('../../assets/images/icons.svg#icon-sync', dirname(__FILE__))); ?>"></use>
                                                </svg>
                                                <span><?php esc_html_e('Sync Now', 'omnimind'); ?></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <form method="post">
                            <?php wp_nonce_field('project_wipe_nonce_action', 'project_wipe_nonce'); ?>

                            <div class="form-block">
                                <div class="form-block__title">
                                    <span><?php esc_html_e('Clear and Reinitialize', 'omnimind'); ?></span>
                                </div>
                                <div class="form-block__frame">
                                    <div class="form-block__wrap">
                                        <div class="form-block__content">
                                            <p><?php esc_html_e('Should you continue to face discrepancies in your search outcomes, consider
                                            starting a thorough re-synchronization.', 'omnimind'); ?> </p>
                                            <p>
                                                <?php esc_html_e('Executing this will remove all indexed information from OmniMind, but your
                                            WordPress site remains unaffected. The entire process might span several
                                            hours, contingent on the volume of content awaiting', 'omnimind'); ?></p>
                                        </div>
                                        <div class="form-block__button">
                                            <button
                                                <?php echo ($api_key_status) ? '' : 'disabled'; ?>name="reindex_project"
                                                type="submit"
                                                class="btn-omni btn-omni--warning btn-omni--block">
                                                <svg class="svg-icon" width="16" height="16">
                                                    <use xlink:href="<?php echo esc_html(plugins_url('../../assets/images/icons.svg#icon-purge', dirname(__FILE__))); ?>"></use>
                                                </svg>
                                                <span><?php esc_html_e('Re-Index', 'omnimind'); ?></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <form method="post">
                            <?php wp_nonce_field('project_purge_nonce_action', 'project_purge_nonce'); ?>

                            <div class="form-block">
                                <div class="form-block__title">
                                    <span><?php esc_html_e('Purge and change API key', 'omnimind'); ?></span>
                                </div>
                                <div class="form-block__frame">
                                    <div class="form-block__wrap">
                                        <div class="form-block__content">
                                            <p><?php esc_html_e('Clicking it you', 'omnimind'); ?> <span
                                                        style="color: red;"><?php esc_html_e('remove', 'omnimind'); ?> </span> <?php esc_html_e('your project and
                                            purge all indexes at Omnimind. It doesn\'t affect you Wordpress data. This
                                            action is not reversible.', 'omnimind'); ?></p>
                                            <p><?php esc_html_e('You can setup a new API key and start from', 'omnimind'); ?></p>
                                        </div>
                                        <div class="form-block__button">
                                            <button <?php echo ($api_key_status) ? '' : 'disabled'; ?>
                                                    name="delete_project"
                                                    type="submit"
                                                    class="btn-omni btn-omni--danger btn-omni--block">
                                                <svg class="svg-icon" width="16" height="16">
                                                    <use xlink:href="<?php echo esc_html(plugins_url('../../assets/images/icons.svg#icon-delete', dirname(__FILE__))); ?>"></use>
                                                </svg>
                                                <span><?php esc_html_e('Delete', 'omnimind'); ?></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tab-item">
                        <p><?php esc_html_e('Here you can see you users search requests', 'omnimind'); ?></p>

                        <table id="request_table" class="display" style="width:100%">
                            <thead>
                            <tr>
                                <th><?php esc_html_e('Date', 'omnimind') ?></th>
                                <th><?php esc_html_e('Question', 'omnimind') ?></th>
                                <th><?php esc_html_e('Answer', 'omnimind') ?></th>
                                <th><?php esc_html_e('Content', 'omnimind') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ($logs as $log) {
                                $datetime = gmdate("d/m/Y H:i", $log['date'] / 1000);
                                $content = [];

                                echo '<tr>';
                                echo '<td class="data-date">' . esc_html($datetime) . '</td>';
                                echo '<td>' . esc_html($log['question']) . '</td>';
                                echo '<td class="data-answer">' . wp_kses_post($log['answer']) . '</td>'; // kses used for esc html
                                echo '<td class="data-links">';
                                if (!empty($log['data'])) {
                                    $data = $log['data']->results ?? $log['data'];
                                    foreach ($data as $datum) {
                                        echo '<a href="' . esc_html($datum->url) . '">' . esc_html($datum->title) . '</a>, ';
                                    }
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
                            </tbody>
                            <tfoot>
                            <tr>
                                <th><?php esc_html_e('Date', 'omnimind') ?></th>
                                <th><?php esc_html_e('Question', 'omnimind') ?></th>
                                <th><?php esc_html_e('Answer', 'omnimind') ?></th>
                                <th><?php esc_html_e('Content', 'omnimind') ?></th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="tab-item">
                        <form method="post" id="custom_prompts">
                            <?php wp_nonce_field('custom_prompts_nonce_action', 'custom_prompts_nonce'); ?>

                            <div class="form-block">
                                <div class="form-block__title">
                                    <span><?php esc_html_e('Custom Answer Prompt', 'omnimind'); ?></span>
                                </div>
                                <div class="form-block__frame">
                                    <div class="form-block__wrap">
                                        <div class="form-block__content">
                                            <textarea name="custom_answer_prompt"
                                                      id="custom_asnwer_prompt"><?php echo esc_html(trim($custom_answer_prompt)); ?></textarea>
                                        </div>
                                        <div class="form-block__button">
                                            <button
                                                    type="submit"
                                                    class="btn-omni btn-omni--primary btn-omni--block">

                                                <span><?php esc_html_e('Save', 'omnimind'); ?></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <form method="post" id="custom_prompts">
                            <?php wp_nonce_field('custom_prompts_nonce_action', 'custom_prompts_nonce'); ?>

                            <div class="form-block">
                                <div class="form-block__title">
                                    <span><?php esc_html_e('Custom Search Prompt', 'omnimind'); ?></span>
                                </div>
                                <div class="form-block__frame">
                                    <div class="form-block__wrap">
                                        <div class="form-block__content">
                                            <textarea name="custom_search_prompt"
                                                      id="custom_search_prompt"><?php echo esc_html($custom_search_prompt); // This is an option ?></textarea>
                                        </div>
                                        <div class="form-block__button">
                                            <button
                                                    type="submit"
                                                    class="btn-omni btn-omni--primary btn-omni--block">

                                                <span><?php esc_html_e('Save', 'omnimind'); ?></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <h3>What is a Custom Prompt?</h3>
                        <p>
                            A custom prompt is a specific set of instructions or a query designed to guide an AI model,
                            like me, to generate a desired response.
                            It allows you to tailor the interaction to suit your needs, whether you're looking for
                            information, creative writing, problem-solving, or any other specific task.
                        </p>

                        <h3>How to Use a Custom Prompt:</h3>
                        <ul>
                            <li><strong>Define Your Goal:</strong> Clearly identify what you want to achieve with the
                                prompt. This could be anything from generating a story, answering a question, or solving
                                a problem.
                            </li>
                            <li><strong>Be Specific:</strong> Provide clear and detailed instructions. The more specific
                                you are, the better the AI can understand and respond to your request.
                            </li>
                            <li><strong>Use Context:</strong> Include any relevant context or background information
                                that can help the AI understand your request better.
                            </li>
                            <li><strong>Structure the Prompt:</strong> Format your prompt in a way that is easy to read
                                and understand. Use bullet points, numbered lists, or paragraphs as needed.
                            </li>
                            <li><strong>Test and Refine:</strong> Try out your prompt and see the response. If it’s not
                                exactly what you wanted, refine your instructions and try again.
                            </li>
                        </ul>

                    </div>
                    <div class="tab-item">
                        <p><?php esc_html_e('Use the shortcode [omni_search] to display the search field on the website page.', 'omnimind'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="omniAlertModal" class="omni-modal">
    <span class="omni-modal__close">
        <svg class="svg-icon" width="16" height="16">
            <use xlink:href="<?php echo esc_html(plugins_url('../../assets/images/icons.svg#icon-close', dirname(__FILE__))); ?>"></use>
        </svg>
    </span>

        <div class="omni-modal-content">
            <p class="omni-modal__text"></p>
        </div>
    </div>

<?php
if (isset($data->form) && $data->form['popup']) { ?>
    <input name="omni_alert" id="omni_alert" type="hidden"
           data-status="<?php echo esc_html($data->form['popup']['status']) ?>"
           data-message="<?php echo esc_html($data->form['popup']['message']) ?>"/>
<?php } ?>