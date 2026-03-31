<?php if (!defined('ABSPATH')) exit;
$real_post = $post;
$ent_attrs = get_option('software_issue_manager_attr_list');
?>
<div id="single-emd-project-<?php echo get_the_ID(); ?>" class="emd-container emd-project-wrap single-wrap">
<?php $is_editable = 0; ?>
<div class="notfronteditable">
    <div style="padding-bottom:10px;clear:both;text-align:right;" id="modified-info-block" class=" modified-info-block">
        <div class="textSmall text-muted modified" style="font-size:75%"><span class="last-modified-text"><?php esc_html_e('Last modified', 'software-issue-manager'); ?> </span><span class="last-modified-author"><?php esc_html_e('by', 'software-issue-manager'); ?> <?php echo get_the_modified_author(); ?> - </span><span class="last-modified-datetime"><?php echo human_time_diff(strtotime(get_the_modified_date('Y-m-d') . " " . get_the_modified_time('H:i')) , current_time('timestamp')); ?> </span><span class="last-modified-dttext"><?php esc_html_e('ago', 'software-issue-manager'); ?></span></div>
    </div>
    <div class="panel panel-info" >
        <div class="panel-heading" style="position:relative; ">
            <div class="panel-title">
                <div class='single-header header'>
                    <h1 class='single-entry-title entry-title' style='color:inherit;padding:0;margin-bottom: 15px;border:0 none;word-break:break-word;font-size:24px;'>
                        <?php if (emd_is_item_visible('title', 'software_issue_manager', 'attribute', 0)) { ?><span class="single-content title"><?php echo get_the_title(); ?></span><?php } ?>
                    </h1>
                </div>
            </div>
        </div>
        <div class="panel-body" style="clear:both">
            <div class="single-well well emd-project">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="slcontent emdbox">
                            <?php if (emd_is_item_visible('tax_project_priority', 'software_issue_manager', 'taxonomy', 0)) { ?>
                            <div class="segment-block tax-project-priority">
                                <div style="font-size:95%" class="segtitle"><?php esc_html_e('Priority', 'software-issue-manager'); ?></div>
                                <div class="segvalue"><span data-tax-project-priority="<?php echo emd_get_tax_slugs(get_the_ID() , 'project_priority') ?>"><?php echo emd_get_tax_vals(get_the_ID() , 'project_priority'); ?></span></div>
                            </div>
                            <?php } ?><?php if (emd_is_item_visible('tax_project_status', 'software_issue_manager', 'taxonomy', 0)) { ?>
                            <div class="segment-block tax-project-status">
                                <div style="font-size:95%" class="segtitle"><?php esc_html_e('Status', 'software-issue-manager'); ?></div>
                                <div class="segvalue"><span data-tax-project-status="<?php echo emd_get_tax_slugs(get_the_ID() , 'project_status') ?>"><?php echo emd_get_tax_vals(get_the_ID() , 'project_status'); ?></span></div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="srcontent emdbox">
                            <?php if (emd_is_item_visible('ent_prj_start_date', 'software_issue_manager', 'attribute', 0)) { ?>
                            <div class="segment-block ent-prj-start-date">
                                <div style="font-size:95%" class="segtitle"><?php esc_html_e('Start Date', 'software-issue-manager'); ?></div>
                                <div class="segvalue"><span><?php if (!empty(emd_mb_meta('emd_prj_start_date'))) { echo date_i18n(get_option('date_format') , strtotime(emd_mb_meta('emd_prj_start_date'))); } ?></span></div>
                            </div>
                            <?php } ?><?php if (emd_is_item_visible('ent_prj_target_end_date', 'software_issue_manager', 'attribute', 0)) { ?>
                            <div class="segment-block ent-prj-target-end-date">
                                <div style="font-size:95%" class="segtitle"><?php esc_html_e('Target End Date', 'software-issue-manager'); ?></div>
                                <div class="segvalue"><span><?php if (!empty(emd_mb_meta('emd_prj_target_end_date'))) { echo date_i18n(get_option('date_format') , strtotime(emd_mb_meta('emd_prj_target_end_date'))); } ?></span></div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="emd-body-content">
                <div class="tab-container emd-project-tabcontainer" style="padding:0 5px 60px;">
                    <ul class="nav nav-tabs" role="tablist" style="margin: 20px 0px 10px;visibility: visible;padding-bottom:0px;">
                        <li class=" active "><a id="description-tablink" href="#description" role="tab" data-toggle="tab"><?php esc_html_e('Description', 'software-issue-manager'); ?></a></li>
                        <li><a id="details-tablink" href="#details" role="tab" data-toggle="tab"><?php esc_html_e('Details', 'software-issue-manager'); ?></a></li>
                    </ul>
                    <div class="tab-content emd-project-tabcontent">
                        <div class="tab-pane fade in active" id="description">
                            <?php if (emd_is_item_visible('content', 'software_issue_manager', 'attribute', 0)) { ?>
                            <div class="single-content segment-block content"><?php echo wp_kses_post($post->post_content); ?></div>
                            <?php } ?>
                        </div>
                        <div class="tab-pane fade in " id="details">
                            <?php if (emd_is_item_visible('ent_prj_actual_end_date', 'software_issue_manager', 'attribute', 0)) { ?>
                            <div class="segment-block ent-prj-actual-end-date">
                                <div data-has-attrib="false" class="row">
                                    <div class="col-sm-6"><div class="segtitle"><?php esc_html_e('Actual End Date', 'software-issue-manager'); ?></div></div>
                                    <div class="col-sm-6"><div class="segvalue"><?php if (!empty(emd_mb_meta('emd_prj_actual_end_date'))) { echo date_i18n(get_option('date_format') , strtotime(emd_mb_meta('emd_prj_actual_end_date'))); } ?></div></div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-group" id="accordion">
<?php if (emd_is_item_visible('entrelcon_project_issues', 'software_issue_manager', 'relation')) { ?>
<?php
    $post     = get_post();
    $rel_filter = "";
    $res      = emd_get_p2p_connections('connected', 'project_issues', 'std', $post, 1, 0, '', 'software_issue_manager', $rel_filter);
    $rel_list = get_option('software_issue_manager_rel_list');
?>
<div style="overflow-x:auto;" class="single-relpanel emd-issue project-issues">
<?php if (emd_check_rel_count('entrelcon_project_issues', 'software_issue_manager', $rel_filter)) { ?>
<div class="panel panel-default relseg">
 <div class="panel-heading">
  <div class="panel-title">
   <a class="btn-block accor-title-link collapsed" data-toggle="collapse" data-parent="#accordion" href="#rel-1088">
    <div class="accor-title"><?php esc_html_e('Project Issues', 'software-issue-manager'); ?></div>
   </a>
  </div>
 </div>
 <div id="rel-1088" class="panel-collapse collapse in">
  <div data-has-attrib="false" class="panel-body emd-table-container">
<?php
// ── Pre-collect unique filter values ────────────────────────────────────────
$_filter_containers  = [];
$_filter_severities  = [];
$_filter_priorities  = [];
$_filter_categories  = [];
$_filter_statuses    = [];
foreach ($res['rels'] as $_frel) {
    $_fid = $_frel->ID;
    $_fc  = get_post_meta($_fid, '_container', true);
    $_fs  = get_post_meta($_fid, '_severity',  true);
    if ($_fc) $_filter_containers[$_fc] = true;
    if ($_fs) $_filter_severities[$_fs] = true;
    $_fp = wp_strip_all_tags(emd_get_tax_vals($_fid, 'issue_priority'));
    $_fcat = wp_strip_all_tags(emd_get_tax_vals($_fid, 'issue_cat'));
    $_fst  = wp_strip_all_tags(emd_get_tax_vals($_fid, 'issue_status'));
    if ($_fp)   $_filter_priorities[$_fp]  = true;
    if ($_fcat) $_filter_categories[$_fcat] = true;
    if ($_fst)  $_filter_statuses[$_fst]   = true;
}
ksort($_filter_containers);
$_show_issue_id  = emd_is_item_visible('ent_iss_id',        'software_issue_manager', 'attribute', 1);
$_show_priority  = emd_is_item_visible('tax_issue_priority', 'software_issue_manager', 'taxonomy',  1);
$_show_cat       = emd_is_item_visible('tax_issue_cat',      'software_issue_manager', 'taxonomy',  1);
$_show_status    = emd_is_item_visible('tax_issue_status',   'software_issue_manager', 'taxonomy',  1);
?>
<style>
#pif-toolbar{display:flex;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap;}
#pif-count{font-size:0.85em;color:#64748b;}
#pif-clear{font-size:0.8em;padding:3px 10px;border:1px solid #cbd5e1;border-radius:4px;background:#f8fafc;cursor:pointer;color:#3D405B;}
#pif-clear:hover{background:#E07A5F;color:#fff;border-color:#E07A5F;}
.pif-filter-input{width:100%;box-sizing:border-box;padding:4px 6px;font-size:0.78em;border:1px solid #cbd5e1;border-radius:4px;background:#f8fafc;color:#3D405B;}
.pif-filter-input:focus{outline:none;border-color:#81B29A;background:#fff;}
thead tr.pif-filter-row th{padding:4px 6px;background:#f1f5f9;vertical-align:top;}
</style>

<div id="pif-toolbar">
    <span id="pif-count"></span>
    <button id="pif-clear" type="button">&#x2715; Clear filters</button>
</div>

<table id="table-project-issues-con" class="table emd-table table-bordered table-hover" style="background-color:#ffffff">
<thead>
<tr>
<th style="min-width:200px;">Issue #</th>
<th>Description</th>
<th data-sortable="true" data-align="center">Container</th>
<th data-sortable="true" data-align="center">Severity</th>
<?php if ($_show_priority) { ?>
<th data-sortable="true" data-align="center"><?php esc_html_e('Priority', 'software-issue-manager'); ?></th>
<?php } ?>
<?php if ($_show_cat) { ?>
<th data-sortable="true" data-align="center"><?php esc_html_e('Category', 'software-issue-manager'); ?></th>
<?php } ?>
<?php if ($_show_status) { ?>
<th data-sortable="true" data-align="center"><?php esc_html_e('Status', 'software-issue-manager'); ?></th>
<?php } ?>
</tr>
<tr class="pif-filter-row">
<th><input class="pif-filter-input" id="pif-f-cve" type="text" placeholder="Filter CVE…"></th>
<th><input class="pif-filter-input" id="pif-f-desc" type="text" placeholder="Filter description…"></th>
<th>
    <select class="pif-filter-input" id="pif-f-container">
        <option value="">All containers</option>
        <?php foreach (array_keys($_filter_containers) as $_c) : ?>
        <option value="<?php echo esc_attr($_c); ?>"><?php echo esc_html($_c); ?></option>
        <?php endforeach; ?>
    </select>
</th>
<th>
    <select class="pif-filter-input" id="pif-f-severity">
        <option value="">All severities</option>
        <?php foreach (['Critical','High'] as $_s) : if (isset($_filter_severities[$_s])) : ?>
        <option value="<?php echo esc_attr($_s); ?>"><?php echo esc_html($_s); ?></option>
        <?php endif; endforeach; ?>
    </select>
</th>
<?php if ($_show_priority) { ?>
<th>
    <select class="pif-filter-input" id="pif-f-priority">
        <option value="">All priorities</option>
        <?php foreach (array_keys($_filter_priorities) as $_p) : ?>
        <option value="<?php echo esc_attr($_p); ?>"><?php echo esc_html($_p); ?></option>
        <?php endforeach; ?>
    </select>
</th>
<?php } ?>
<?php if ($_show_cat) { ?>
<th>
    <select class="pif-filter-input" id="pif-f-cat">
        <option value="">All categories</option>
        <?php foreach (array_keys($_filter_categories) as $_fc2) : ?>
        <option value="<?php echo esc_attr($_fc2); ?>"><?php echo esc_html($_fc2); ?></option>
        <?php endforeach; ?>
    </select>
</th>
<?php } ?>
<?php if ($_show_status) { ?>
<th>
    <select class="pif-filter-input" id="pif-f-status">
        <option value="">All statuses</option>
        <?php foreach (array_keys($_filter_statuses) as $_fst2) : ?>
        <option value="<?php echo esc_attr($_fst2); ?>"><?php echo esc_html($_fst2); ?></option>
        <?php endforeach; ?>
    </select>
</th>
<?php } ?>
</tr>
</thead>
<tbody>
<?php
    echo $res['before_list'];
    $real_post    = $post;
    $rel_count_id = 1;
    $rel_eds      = [];
    foreach ($res['rels'] as $myrel) {
        $post       = $myrel;
        $issue_id   = $myrel->ID;
        $container  = get_post_meta($issue_id, '_container', true);
        $severity   = get_post_meta($issue_id, '_severity',  true);

        switch ($severity) {
            case 'Critical': $sev_bg = '#E07A5F'; $sev_fg = '#fff';    break;
            case 'High':     $sev_bg = '#F2CC8F'; $sev_fg = '#3D405B'; break;
            default:         $sev_bg = '#e2e8f0'; $sev_fg = '#3D405B';
        }

        echo $res['before_item']; ?>
<?php
        $cve_id      = get_post_meta($issue_id, '_cve_id', true) ?: get_the_title($issue_id);
        $post_content = get_post_field('post_content', $issue_id);
        // Extract the CVE description from the first <p> in the header div
        preg_match('/<p[^>]*color:#4a5568[^>]*>(.*?)<\/p>/s', $post_content, $m);
        $raw_desc    = isset($m[1]) ? wp_strip_all_tags($m[1]) : '';
        $description = mb_strlen($raw_desc) > 200
            ? mb_substr($raw_desc, 0, 200) . '…'
            : $raw_desc;
        $priority_val     = emd_get_tax_vals($issue_id, 'issue_priority');
        $priority_val_txt = wp_strip_all_tags($priority_val);
        $cat_val          = emd_get_tax_vals($issue_id, 'issue_cat');
        $cat_val_txt      = wp_strip_all_tags($cat_val);
        $status_val       = emd_get_tax_vals($issue_id, 'issue_status');
        $status_val_txt   = wp_strip_all_tags($status_val);
?>
<tr
    data-cve="<?php echo esc_attr(strtolower($cve_id)); ?>"
    data-desc="<?php echo esc_attr(strtolower($description)); ?>"
    data-container="<?php echo esc_attr($container); ?>"
    data-severity="<?php echo esc_attr($severity); ?>"
    data-priority="<?php echo esc_attr($priority_val_txt); ?>"
    data-cat="<?php echo esc_attr($cat_val_txt); ?>"
    data-status="<?php echo esc_attr($status_val_txt); ?>"
>
<td><a href="<?php echo esc_url(get_permalink($issue_id)); ?>" title="<?php echo esc_attr(get_the_title($issue_id)); ?>"><?php echo esc_html($cve_id); ?></a></td>
<td style="font-size:0.82em;color:#4a5568;max-width:320px;"><?php echo esc_html($description); ?></td>
<td data-align="center">
    <?php if ($container) : ?>
    <span style="display:inline-block;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:2px 9px;border-radius:12px;font-size:0.82em;font-weight:600;">
        <?php echo esc_html($container); ?>
    </span>
    <?php endif; ?>
</td>
<td data-align="center">
    <?php if ($severity) : ?>
    <span style="display:inline-block;background:<?php echo $sev_bg; ?>;color:<?php echo $sev_fg; ?>;padding:2px 9px;border-radius:12px;font-size:0.82em;font-weight:700;">
        <?php echo esc_html($severity); ?>
    </span>
    <?php endif; ?>
</td>
<?php if ($_show_priority) { ?>
<td><span data-tax-issue-priority="<?php echo emd_get_tax_slugs($issue_id, 'issue_priority'); ?>" class="taxlabel taxvalue" style="overflow-wrap:break-word"><?php echo $priority_val; ?></span></td>
<?php } ?>
<?php if ($_show_cat) { ?>
<td><span data-tax-issue-cat="<?php echo emd_get_tax_slugs($issue_id, 'issue_cat'); ?>" class="taxlabel taxvalue" style="overflow-wrap:break-word"><?php echo $cat_val; ?></span></td>
<?php } ?>
<?php if ($_show_status) { ?>
<td><span data-tax-issue-status="<?php echo emd_get_tax_slugs($issue_id, 'issue_status'); ?>" class="taxlabel taxvalue" style="overflow-wrap:break-word"><?php echo $status_val; ?></span></td>
<?php } ?>
</tr>
<?php
        echo $res['after_item'];
        $rel_count_id++;
    }
    $post = $real_post;
    echo $res['after_list'];
?>
</tbody>
</table>

<script>
(function(){
    var tbl   = document.getElementById('table-project-issues-con');
    if (!tbl) return;
    var rows  = Array.from(tbl.querySelectorAll('tbody tr'));
    var count = document.getElementById('pif-count');

    var filters = {
        cve:       { el: document.getElementById('pif-f-cve'),       key: 'cve',       type: 'text'   },
        desc:      { el: document.getElementById('pif-f-desc'),      key: 'desc',      type: 'text'   },
        container: { el: document.getElementById('pif-f-container'), key: 'container', type: 'select' },
        severity:  { el: document.getElementById('pif-f-severity'),  key: 'severity',  type: 'select' },
        priority:  { el: document.getElementById('pif-f-priority'),  key: 'priority',  type: 'select' },
        cat:       { el: document.getElementById('pif-f-cat'),       key: 'cat',       type: 'select' },
        status:    { el: document.getElementById('pif-f-status'),    key: 'status',    type: 'select' }
    };

    function applyFilters() {
        var visible = 0;
        rows.forEach(function(row) {
            var show = true;
            Object.values(filters).forEach(function(f) {
                if (!f.el) return;
                var val = f.el.value.trim();
                if (!val) return;
                var cell = (row.dataset[f.key] || '').toLowerCase();
                if (f.type === 'text') {
                    if (cell.indexOf(val.toLowerCase()) === -1) show = false;
                } else {
                    if (cell !== val.toLowerCase()) show = false;
                }
            });
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (count) count.textContent = visible + ' of ' + rows.length + ' issues';
    }

    Object.values(filters).forEach(function(f) {
        if (!f.el) return;
        f.el.addEventListener(f.type === 'text' ? 'input' : 'change', applyFilters);
    });

    document.getElementById('pif-clear').addEventListener('click', function() {
        Object.values(filters).forEach(function(f) {
            if (!f.el) return;
            f.el.value = '';
        });
        applyFilters();
    });

    applyFilters();
})();
</script>
  </div>
 </div>
</div>
<?php } ?>
</div>
<?php } ?>
            </div>
        </div>
        <div class="panel-footer"></div>
    </div>
</div>
</div><!--container-end-->
