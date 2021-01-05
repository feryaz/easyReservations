<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="er-tax-rates-search" id="rates-search">
    <input type="search" class="er-tax-rates-search-field" placeholder="<?php esc_attr_e( 'Search&hellip;', 'easyReservations' ); ?>" value="<?php echo isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : ''; ?>"/>
</div>

<div id="rates-pagination"></div>
<h3>
	<?php esc_html_e( 'Tax rates', 'easyReservations' ); ?>
</h3>

<table class="er_tax_rates er_input_table widefat">
    <thead>
    <tr>
        <th width="8%"><?php esc_html_e( 'Tax name', 'easyReservations' ); ?>&nbsp;<?php er_print_help( __( 'Enter a name for this tax rate.', 'easyReservations' ) ); ?></th>
        <th width="8%"><?php esc_html_e( 'Rate&nbsp;%', 'easyReservations' ); ?>&nbsp;<?php er_print_help( __( 'Enter a tax rate (percentage) to 4 decimal places.', 'easyReservations' ) ); ?></th>
        <th width="8%"><?php esc_html_e( 'Priority', 'easyReservations' ); ?>&nbsp;<?php er_print_help( __( 'Choose a priority for this tax rate. Only 1 matching rate per priority will be used. To define multiple tax rates you need to specify a different priority per rate.', 'easyReservations' ) ); ?></th>
        <th width="8%"><?php esc_html_e( 'Apply', 'easyReservations' ); ?>&nbsp;<?php er_print_help( __( 'Choose which invoice lines the tax should be applied to.', 'easyReservations' ) ); ?></th>
        <th width="8%"><?php esc_html_e( 'Compound', 'easyReservations' ); ?>&nbsp;<?php er_print_help( __( 'Choose whether or not this is a compound rate. Compound tax rates are applied on top of other tax rates.', 'easyReservations' ) ); ?></th>
        <th width="8%"><?php esc_html_e( 'Flat', 'easyReservations' ); ?>&nbsp;<?php er_print_help( __( 'Choose whether or not this tax rate is a flat fee rather than percentage.', 'easyReservations' ) ); ?></th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <th colspan="9">
            <a href="#" class="button plus insert"><?php esc_html_e( 'Insert row', 'easyReservations' ); ?></a>
            <a href="#" class="button minus remove_tax_rates"><?php esc_html_e( 'Remove selected row(s)', 'easyReservations' ); ?></a>
            <a href="#" download="tax_rates.csv" class="button export"><?php esc_html_e( 'Export CSV', 'easyReservations' ); ?></a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?import=easyreservations_tax_rate_csv' ) ); ?>" class="button import"><?php esc_html_e( 'Import CSV', 'easyReservations' ); ?></a>
        </th>
    </tr>
    </tfoot>
    <tbody id="rates">
    <tr>
        <th colspan="9" style="text-align: center;"><?php esc_html_e( 'Loading&hellip;', 'easyReservations' ); ?></th>
    </tr>
    </tbody>
</table>

<script type="text/html" id="tmpl-er-tax-table-row">
    <tr class="tips" data-tip="<?php printf( esc_attr__( 'Tax rate ID: %s', 'esc_html_e' ), '{{ data.id }}' ); ?>" data-id="{{ data.id }}">
        <td class="name">
            <input type="text" placeholder="*" value="{{ data.title }}" name="tax_rate_name[{{ data.id }}]" data-attribute="title"/>
        </td>

        <td class="rate">
            <input type="text" value="{{ data.rate }}" placeholder="0" name="tax_rate[{{ data.id }}]" data-attribute="rate"/>
        </td>

        <td class="priority">
            <input type="number" step="1" min="1" value="{{ data.priority }}" name="tax_rate_priority[{{ data.id }}]" data-attribute="priority"/>
        </td>

        <td class="apply">
            <select name="tax_rate_apply[{{ data.id }}]" data-attribute="apply">
                <option value="all"
                <# if ( data.apply == 'all' ) { #> selected="selected" <# } #>><?php esc_html_e( 'Any invoice line', 'easyReservations' ); ?></option>
                <option value="custom"
                <# if ( data.apply == 'custom' ) { #> selected="selected" <# } #>><?php esc_html_e( 'Custom fields', 'easyReservations' ); ?></option>
                <option value="resources"
                <# if ( data.apply == 'resources' ) { #> selected="selected" <# } #>><?php esc_html_e( 'All resources', 'easyReservations' ); ?></option>
                <optgroup label="<?php esc_attr_e( 'Single resource', 'easyReservations' ); ?>">
					<?php foreach ( ER()->resources()->get() as $id => $resource ): ?>
                        <option value="<?php echo esc_attr( $resource->get_id() ); ?>" <# if ( data.apply == '<?php echo esc_attr( $resource->get_id() ); ?>' ) { #> selected="selected" <# } #>><?php esc_html_e( $resource->get_title() ); ?></option>
					<?php endforeach; ?>
                </optgroup>
            </select>
        </td>

        <td class="compound">
            <input type="checkbox" class="checkbox" name="tax_rate_compound[{{ data.id }}]" <# if ( parseInt( data.compound, 10 ) ) { #> checked="checked" <# } #> data-attribute="compound" />
        </td>

        <td class="flat">
            <input type="checkbox" class="checkbox" name="tax_rate_flat[{{ data.id }}]" <# if ( parseInt( data.flat, 10 ) ) { #> checked="checked" <# } #> data-attribute="flat" />
        </td>
    </tr>
</script>

<script type="text/html" id="tmpl-er-tax-table-row-empty">
    <tr>
        <th colspan="9" style="text-align:center"><?php esc_html_e( 'No matching tax rates found.', 'easyReservations' ); ?></th>
    </tr>
</script>

<script type="text/html" id="tmpl-er-tax-table-pagination">
    <div class="tablenav">
        <div class="tablenav-pages">
			<span class="displaying-num">
				<?php
				/* translators: %s: number */
				printf(
					__( '%s items', 'easyReservations' ), // %s will be a number eventually, but must be a string for now.
					'{{ data.qty_rates }}'
				);
				?>
			</span>
            <span class="pagination-links">

				<a class="tablenav-pages-navspan" data-goto="1">
					<span class="screen-reader-text"><?php esc_html_e( 'First page', 'easyReservations' ); ?></span>
					<span aria-hidden="true">&laquo;</span>
				</a>
				<a class="tablenav-pages-navspan" data-goto="<# print( Math.max( 1, parseInt( data.current_page, 10 ) - 1 ) ) #>">
					<span class="screen-reader-text"><?php esc_html_e( 'Previous page', 'easyReservations' ); ?></span>
					<span aria-hidden="true">&lsaquo;</span>
				</a>

				<span class="paging-input">
					<label for="current-page-selector" class="screen-reader-text"><?php esc_html_e( 'Current page', 'easyReservations' ); ?></label>
					<?php
					/* translators: 1: current page 2: total pages */
					printf(
						esc_html_x( '%1$s of %2$s', 'Pagination', 'easyReservations' ),
						'<input class="current-page" id="current-page-selector" type="text" name="paged" value="{{ data.current_page }}" size="<# print( data.qty_pages.toString().length ) #>" aria-describedby="table-paging">',
						'<span class="total-pages">{{ data.qty_pages }}</span>'
					);
					?>
				</span>

				<a class="tablenav-pages-navspan" data-goto="<# print( Math.min( data.qty_pages, parseInt( data.current_page, 10 ) + 1 ) ) #>">
					<span class="screen-reader-text"><?php esc_html_e( 'Next page', 'easyReservations' ); ?></span>
					<span aria-hidden="true">&rsaquo;</span>
				</a>
				<a class="tablenav-pages-navspan" data-goto="{{ data.qty_pages }}">
					<span class="screen-reader-text"><?php esc_html_e( 'Last page', 'easyReservations' ); ?></span>
					<span aria-hidden="true">&raquo;</span>
				</a>

			</span>
        </div>
    </div>
</script>
