<form method="get" id="pdc-products-filter-form">
	<input type="hidden" name="page" value="<?php echo isset( $_GET['page'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : ''; ?>" />
	
	<label for="pdc-search-input">Search Products:</label>
	<input type="text" id="pdc-search-input" name="s" placeholder="Search by name..." value="<?php echo isset( $_GET['s'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : ''; ?>" style="min-width: 250px;" />
	
	<input type="submit" class="button" value="Filter" />
	<a href="?page=<?php echo isset( $_GET['page'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : ''; ?>" class="button">Reset</a>
</form>
