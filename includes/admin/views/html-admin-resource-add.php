<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$title = sprintf( esc_html__( 'Add %s', 'easyReservations' ), esc_html__( 'resource', 'easyReservations' ) );

if ( isset( $_GET['dopy'] ) ) {
	$title = sprintf( esc_html__( 'Copy %s', 'easyReservations' ), esc_html__( 'resource', 'easyReservations' ) ) . ' #' . intval( $_GET['dopy'] );
}

?>
<form method="post" action="" name="add_resource" id="add_resource">
	<?php wp_nonce_field( 'easy-resource-add', 'easy-resource-add' ); ?>
    <table class="widefat" style="width:440px;">
        <thead>
        <tr>
            <th colspan="2"><?php echo esc_html( $title ); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr class="alternate">
            <td colspan="2">
                <i><?php esc_html_e( 'You can change this later on in the post view', 'easyReservations' ); ?></i></td>
        </tr>
        <tr>
            <td class="label"><?php esc_html_e( 'Title', 'easyReservations' ); ?></td>
            <td><input type="text" size="32" name="add_resource_title"></td>
        </tr>
        <tr class="alternate">
            <td class="label"><?php esc_html_e( 'Content', 'easyReservations' ); ?></td>
            <td><textarea name="add_resource_content" rows="5" cols="23" style="min-height: 50px"></textarea></td>
        </tr>
        <tr>
            <td class="label"><?php esc_html_e( 'Image', 'easyReservations' ); ?></td>
            <td>
                <label for="upload_image">
                    <input id="upload_image" type="text" size="32" name="upload_image" value=""/>
                    <a id="upload_image_button"><img src="<?php echo esc_url( admin_url( 'images/media-button-image.gif' ) ); ?>"></a>
                </label>
            </td>
        </tr>
        </tbody>
    </table>
	<?php if ( isset( $_GET['dopy'] ) ) {
		echo '<input type="hidden" name="dopy" value="' . esc_attr( $_GET['dopy'] ) . '">';
	} ?>
    <input
        type="submit"
        style="margin-top:4px;"
        class="button-primary easyreservations-save-button"
        value="<?php esc_html_e( 'Submit', 'easyReservations' ); ?>">
</form>
<script>
	jQuery( document ).ready( function( $ ) {
		$( '#upload_image_button' ).on( 'click', function() {
			tb_show( '', 'media-upload.php?type=image&amp;TB_iframe=true' );
			return false;
		} );

		window.send_to_editor = function( html ) {
			var imgurl,
				srcCheck = $( html ).attr( 'src' );

			if ( srcCheck && typeof srcCheck !== 'undefined' ) {
				imgurl = srcCheck;
			} else {
				imgurl = $( 'img', html ).attr( 'src' );
			}

			$( '#upload_image' ).val( imgurl );
			tb_remove();
		}
	} );
</script>