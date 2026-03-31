<?php if (!defined('ABSPATH')) exit;
global $sc_issues_count;
$ent_attrs  = get_option('software_issue_manager_attr_list');
$issue_id   = get_the_ID();
$container  = get_post_meta($issue_id, '_container', true);
$severity   = get_post_meta($issue_id, '_severity',  true);

$sev_color = '';
$sev_bg    = '';
switch ($severity) {
    case 'Critical':
        $sev_color = '#fff';
        $sev_bg    = '#E07A5F';
        break;
    case 'High':
        $sev_color = '#3D405B';
        $sev_bg    = '#F2CC8F';
        break;
    default:
        $sev_color = '#3D405B';
        $sev_bg    = '#e2e8f0';
}
?>
<tr>
    <?php if (emd_is_item_visible('ent_iss_id', 'software_issue_manager', 'attribute', 1)) { ?>
    <td class="results-cell">
        <a href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(emd_mb_meta('emd_iss_id')); ?></a>
    </td>
    <?php } ?>

    <td class="results-cell">
        <a href="<?php echo esc_url(get_permalink()); ?>"><?php echo get_the_title(); ?></a>
    </td>

    <td class="results-cell">
        <?php if ($container) : ?>
            <span style="display:inline-block;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:2px 10px;border-radius:12px;font-size:0.82em;font-weight:600;">
                <?php echo esc_html($container); ?>
            </span>
        <?php endif; ?>
    </td>

    <td class="results-cell">
        <?php if ($severity) : ?>
            <span style="display:inline-block;background:<?php echo $sev_bg; ?>;color:<?php echo $sev_color; ?>;padding:2px 10px;border-radius:12px;font-size:0.82em;font-weight:700;">
                <?php echo esc_html($severity); ?>
            </span>
        <?php endif; ?>
    </td>

    <?php if (emd_is_item_visible('ent_iss_email', 'software_issue_manager', 'attribute', 1)) { ?>
    <td class="results-cell"><?php esc_html_e('Email', 'software-issue-manager'); ?></td>
    <?php } ?>
    <?php if (emd_is_item_visible('tax_issue_cat', 'software_issue_manager', 'taxonomy', 1)) { ?>
    <td class="results-cell"><?php echo emd_get_tax_vals($issue_id, 'issue_cat'); ?></td>
    <?php } ?>
    <?php if (emd_is_item_visible('tax_issue_status', 'software_issue_manager', 'taxonomy', 1)) { ?>
    <td class="results-cell"><?php echo emd_get_tax_vals($issue_id, 'issue_status'); ?></td>
    <?php } ?>
    <?php if (emd_is_item_visible('tax_issue_priority', 'software_issue_manager', 'taxonomy', 1)) { ?>
    <td class="results-cell"><?php echo emd_get_tax_vals($issue_id, 'issue_priority'); ?></td>
    <?php } ?>
    <?php if (shortcode_exists('wpas_woo_product_woo_issue')) { ?>
    <td class="search-results-row"><?php echo do_shortcode("[wpas_woo_product_woo_issue con_name='woo_issue' app_name='software_issue_manager' type='list_ol' post=" . $issue_id . "]"); ?></td>
    <?php } ?>
    <?php if (shortcode_exists('wpas_edd_product_edd_issue')) { ?>
    <td class="search-results-row"><?php echo do_shortcode("[wpas_edd_product_edd_issue con_name='edd_issue' app_name='software_issue_manager' type='list_ol' post=" . $issue_id . "]"); ?></td>
    <?php } ?>
</tr>
