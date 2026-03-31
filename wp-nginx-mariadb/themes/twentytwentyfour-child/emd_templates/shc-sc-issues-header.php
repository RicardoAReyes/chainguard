<?php if (!defined('ABSPATH')) exit;
global $sc_issues_shc_count; ?>
<p><?php esc_html_e('Below are your search results.', 'software-issue-manager'); ?></p>
<div class="results">
    <table id="results-table" class="table table-striped">
        <tbody>
            <tr>
                <?php if (emd_is_item_visible('ent_iss_id', 'software_issue_manager', 'attribute', 1)) { ?>
                <th class="results-header"><?php esc_html_e('ID', 'software-issue-manager'); ?></th>
                <?php } ?>

                <th class="results-header"><?php esc_html_e('Title', 'software-issue-manager'); ?></th>

                <th class="results-header">Container</th>
                <th class="results-header">Severity</th>

                <?php if (emd_is_item_visible('ent_iss_email', 'software_issue_manager', 'attribute', 1)) { ?>
                <th class="results-header"><?php esc_html_e('Email', 'software-issue-manager'); ?></th>
                <?php } ?>
                <?php if (emd_is_item_visible('tax_issue_cat', 'software_issue_manager', 'taxonomy', 1)) { ?>
                <th class="results-header"><?php esc_html_e('Category', 'software-issue-manager'); ?></th>
                <?php } ?>
                <?php if (emd_is_item_visible('tax_issue_status', 'software_issue_manager', 'taxonomy', 1)) { ?>
                <th class="results-header"><?php esc_html_e('Status', 'software-issue-manager'); ?></th>
                <?php } ?>
                <?php if (emd_is_item_visible('tax_issue_priority', 'software_issue_manager', 'taxonomy', 1)) { ?>
                <th class="results-header"><?php esc_html_e('Priority', 'software-issue-manager'); ?></th>
                <?php } ?>
                <?php if (shortcode_exists('wpas_woo_product_woo_issue')) { ?>
                <th data-sortable="true"><?php esc_html_e('Products', 'software-issue-manager'); ?></th>
                <?php } ?>
                <?php if (shortcode_exists('wpas_edd_product_edd_issue')) { ?>
                <th data-sortable="true"><?php esc_html_e('Products', 'software-issue-manager'); ?></th>
                <?php } ?>
            </tr>
