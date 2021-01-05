<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h1><?php esc_html_e( $resource->get_title() ); ?></h1>

<div class="resource-header">
	<div class="main">
		<div class="resource-thumbnail">
			<?php if ( $thumbnail ): ?>
			<?php echo $thumbnail; ?>
			<?php else: ?>
			<a class="thumbnail-placeholder" href="post.php?post=<?php esc_attr_e( $resource->get_id() ); ?>&action=edit"> </a>
			<?php endif; ?>
		</div>

		<div class="content"><?php esc_html_e( strip_shortcodes( __( $resource->get_content() ) ) ); ?></div>
		<a href="post.php?post=<?php esc_attr_e( $resource->get_id() ); ?>&action=edit">
			<?php esc_html_e( 'Post view', 'easyReservations' ); ?>
		</a> |
		<a href="admin.php?page=resource&add_resource=resource&dopy=<?php esc_attr_e( $resource->get_id() ); ?>">
			<?php esc_html_e( 'Copy', 'easyReservations' ); ?>
		</a> |
		<a href="#" onclick="if(confirm('<?php echo addslashes( esc_html__( 'Really delete this resource and all its reservations?', 'easyReservations' ) ); ?>')){window.location = '<?php echo esc_url( wp_nonce_url( 'admin.php?page=resource&delete=' . $resource->get_id(), 'easy-resource-delete' ) ); ?>';
			}">
			<?php esc_attr_e( __( 'Delete', 'easyReservations' ) ); ?>
		</a>
	</div>
</div>
<nav class="nav-tab-wrapper er-nav-tab-wrapper easy-navigation resource-navigation">
	<a href="#" target="settings" class="nav-tab"><?php esc_html_e( 'Settings', 'easyReservations' ); ?></a>
	<a href="#" target="filters" class="nav-tab"><?php esc_html_e( 'Filter', 'easyReservations' ); ?></a>
	<a href="#" target="slots" class="nav-tab"><?php esc_html_e( 'Slots', 'easyReservations' ); ?></a>
	<?php do_action( 'easy_resource_navigation' ); ?>
</nav>
<script>
	jQuery( document ).ready( function( $ ) {
		$( '.resource-navigation' ).easyNavigation( { value: 'settings', hash: true } );
	} );

	jQuery( 'li#toplevel_page_reservations, li#toplevel_page_reservations > a' ).addClass( 'wp-has-current-submenu wp-menu-open' ).removeClass( 'wp-not-current-submenu' );
</script>