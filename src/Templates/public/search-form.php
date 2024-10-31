<?php
/**
 * Template file for displaying BrainySearch form.
 *
 * This template file is loaded by the `brainy_search_handle_query()` function.
 *
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
$search_answer = esc_attr($data->form['search_answer']);
?>

<div class="omni-search <?php $search_answer ?? 'search_answer' ?>">
    <form id="omni_search_form">
        <div class="omni-search__search-wrp">
            <div class="query-wrp">
                <input type="text" id="query" placeholder="<?php esc_html_e('Type here your question...', 'omnimind') ?>">
                <div id="query_autocomplete"></div>
            </div>
            <button id="get_results" type="submit"><?php esc_html_e('Search', 'omnimind') ?></button>

        </div>
        <div class="omni-search__result-wrp" id="results"></div>
    </form>
</div>
