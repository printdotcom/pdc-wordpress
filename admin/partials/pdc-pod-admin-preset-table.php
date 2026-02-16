<div class="wrap">
    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>
    <p><?php esc_html_e('Managing products can be a chore. To make it easy, you can use this table to link presets to products in bulk.'); ?></p>
    <div>
        <label for="">1. Select your product</label>
        <select id="product">
            <option>Flyers</option>
        </select>

        <label>2. Select a Print.com Product</label>
        <select id="product">
            <option>Flyers</option>
        </select>
        <label>3. Select a preset</label>
        <select id="product">
            <option>Flyers</option>
        </select>
        <label>4. Choose a file</label>
        <input type="file" />

        <label>Apply</label>
        <button>Apply</button>
    </div>
    <div class="tablenav top">
        <div class="alignleft actions">
            <select>
                <option value>Select a product</option>
            </select>
            <select>
                <option value>All sizes</option>
            </select>
            <select>
                <option value>All materials</option>
            </select>
            <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">
        </div>
    </div>
    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <td class="manage-column column-cb check-column">
                    <input id="cb-select-all-1" type="checkbox">
                </td>
                <th class="manage-column">Product</th>
                <th>Type</th>
                <th>Preset</th>
                <th>File</th>
                <th>
                    apply all
                </th>
            </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
</div>