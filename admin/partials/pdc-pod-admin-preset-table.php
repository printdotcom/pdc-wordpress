<?php

/**
 * Product preset mapping page
 *
 * @package Pdc_Pod
 */


$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$per_page = 20;
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;

// First, get total count
$count_args = array(
    'return' => 'ids',
    'limit'  => -1,
    'type'   => array( 'simple', 'variable', 'grouped', 'external', 'variation' )
);

if (! empty($search)) {
    $count_args['s'] = $search;
}

$total_products = count(wc_get_products($count_args));
$total_pages = ceil($total_products / $per_page);

// Get paginated products
$args = array(
    'limit'  => $per_page,
    'offset' => ($paged - 1) * $per_page,
    'type'   => array( 'simple', 'variable', 'grouped', 'external', 'variation' )
);

if (! empty($search)) {
    $args['s'] = $search;
}

$products = wc_get_products($args);
$items = array();
$parent_ids_needed = array();

foreach ($products as $product) {
    $product_type = $product->get_type();
    $parent_id = method_exists($product, 'get_parent_id') ? $product->get_parent_id() : 0;
    
    $items[] = array(
        'id'        => $product->get_id(),
        'name'      => $product->get_name(),
        'type'      => $product_type,
        'parent_id' => $parent_id,
    );
    
    // Track parent IDs for variations
    if ('variation' === $product_type && $parent_id > 0) {
        $parent_ids_needed[$parent_id] = true;
    }
}

// Fetch any parent products that aren't already in the list
$existing_ids = array_column($items, 'id');
$missing_parent_ids = array_diff(array_keys($parent_ids_needed), $existing_ids);

if (! empty($missing_parent_ids)) {
    $parent_products = wc_get_products(array(
        'include' => $missing_parent_ids,
        'limit'   => -1,
    ));
    
    foreach ($parent_products as $parent) {
        $items[] = array(
            'id'        => $parent->get_id(),
            'name'      => $parent->get_name(),
            'type'      => $parent->get_type(),
            'parent_id' => 0,
            'is_ghost'  => true, // Mark as not part of pagination
        );
    }
}

// Sort items: parents first, then their children
usort($items, function ($a, $b) {
    // Group by parent_id (0 for parents, actual ID for children)
    $a_group = $a['parent_id'] > 0 ? $a['parent_id'] : $a['id'];
    $b_group = $b['parent_id'] > 0 ? $b['parent_id'] : $b['id'];
    
    if ($a_group !== $b_group) {
        return $a_group - $b_group;
    }
    
    // Within same group, parents come before children
    if ($a['parent_id'] !== $b['parent_id']) {
        return $a['parent_id'] - $b['parent_id'];
    }
    
    // Same level, sort by ID
    return $a['id'] - $b['id'];
});
?>

<div class="wrap">
    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
    <p><?php esc_html_e('Map your WooCommerce products and variations to Print.com presets.', 'pdc-pod'); ?></p>

    <div class="tablenav top">
        <div class="alignleft actions">
            <?php include plugin_dir_path(__FILE__) . PDC_POD_NAME . '-admin-product-search.php'; ?>
        </div>
        <?php if ($total_pages > 1) : ?>
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php
                    printf(
                        esc_html(_n('%s item', '%s items', $total_products, 'pdc-pod')),
                        number_format_i18n($total_products)
                    );
                    ?>
                </span>
                <?php
                echo paginate_links(array(
                    'base'      => add_query_arg('paged', '%#%'),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $total_pages,
                    'current'   => $paged,
                ));
                ?>
            </div>
        <?php else : ?>
            <div class="tablenav-pages one-page">
                <span class="displaying-num">
                    <?php
                    printf(
                        esc_html(_n('%s item', '%s items', $total_products, 'pdc-pod')),
                        number_format_i18n($total_products)
                    );
                    ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <form method="post" id="pdc-preset-mapping-form">
        <?php wp_nonce_field('pdc_save_preset_mappings', 'pdc_preset_nonce'); ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column check-column">
                        <input type="checkbox" id="pdc-select-all-products">
                    </td>
                    <th class="manage-column" style="width: 50%;">Product</th>
                    <th class="manage-column" style="width: 15%;">Type</th>
                    <th class="manage-column" style="width: 35%;">Preset</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px;">
                            <em><?php esc_html_e('No products found.', 'pdc-pod'); ?></em>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($items as $item) : ?>
                        <?php
                        $is_variation = 'variation' === $item['type'];
                        $is_ghost = isset($item['is_ghost']) && $item['is_ghost'];
                        $row_style = '';
                        
                        if ($is_ghost) {
                            $row_style = 'background-color: #f9f9f9; opacity: 0.7;';
                        }
                        ?>
                        <tr style="<?php echo esc_attr($row_style); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="selected_products[]" value="<?php echo esc_attr($item['id']); ?>" class="pdc-product-checkbox">
                            </th>
                            <td>
                                <?php if ($is_variation) : ?>
                                    <span style="color: #666; margin-left: 20px; margin-right: 5px;">↳</span>
                                <?php endif; ?>
                                <strong><?php echo esc_html($item['name']); ?></strong>
                                <?php if ($is_ghost) : ?>
                                    <span style="color: #999; font-size: 11px; margin-left: 5px;">(parent)</span>
                                <?php endif; ?>
                                <br>
                                <small style="color: #666; <?php echo $is_variation ? 'margin-left: 45px;' : ''; ?>">
                                    ID: <?php echo esc_html($item['id']); ?>
                                    <?php if ($item['parent_id'] > 0) : ?>
                                        | Parent ID: <?php echo esc_html($item['parent_id']); ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <span class="<?php echo esc_attr($is_variation ? 'dashicons dashicons-admin-generic' : 'dashicons dashicons-products'); ?>"></span>
                                <?php echo esc_html(ucfirst($item['type'])); ?>
                            </td>
                            <td>
                                <select name="preset_mapping[<?php echo esc_attr($item['id']); ?>]" style="width: 100%;">
                                    <option value=""><?php esc_html_e('— Select Preset —', 'pdc-pod'); ?></option>
                                    <!-- Presets will be loaded via JS or can be pre-populated -->
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <?php if ($total_pages > 1) : ?>
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php
                        printf(
                            esc_html(_n('%s item', '%s items', $total_products, 'pdc-pod')),
                            number_format_i18n($total_products)
                        );
                        ?>
                    </span>
                    <?php
                    echo paginate_links(array(
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total'     => $total_pages,
                        'current'   => $paged,
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (! empty($items)) : ?>
            <p class="submit">
                <button type="submit" class="button button-primary button-large"><?php esc_html_e('Save Preset Mappings', 'pdc-pod'); ?></button>
            </p>
        <?php endif; ?>
    </form>
</div>
