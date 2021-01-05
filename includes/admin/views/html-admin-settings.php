<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab_exists        = isset( $tabs[ $current_tab ] ) || has_action( 'easyreservations_sections_' . $current_tab ) || has_action( 'easyreservations_settings_' . $current_tab );
$current_tab_label = isset( $tabs[ $current_tab ] ) ? $tabs[ $current_tab ] : '';

if ( ! $tab_exists ) {
	wp_safe_redirect( admin_url( 'admin.php?page=er-settings' ) );
	exit;
}
?>
<div class="wrap easyreservations easy-ui">
	<?php do_action( 'easyreservations_before_settings' . $current_tab ); ?>

    <form method="post" id="mainform" action="" enctype="multipart/form-data">
        <nav class="nav-tab-wrapper er-nav-tab-wrapper">
			<?php

			foreach ( $tabs as $slug => $label ) {
				echo '<a href="' . esc_url( admin_url( 'admin.php?page=er-settings&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>';
			}

			do_action( 'easyreservations_settings_tabs' );

			?>
        </nav>
        <h1 class="screen-reader-text"><?php echo esc_html( $current_tab_label ); ?></h1>
		<?php
		do_action( 'easyreservations_sections_' . $current_tab );

		//self::show_messages();

		do_action( 'easyreservations_settings_' . $current_tab );
		?>
		<?php if ( ! isset( $GLOBALS['hide_save_button'] ) || empty( $GLOBALS['hide_save_button'] ) ) : ?>
            <p class="submit">
                <button name="save" class="button-primary easyreservations-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'easyreservations' ); ?>"><?php esc_html_e( 'Save changes', 'easyreservations' ); ?></button>
				<?php wp_nonce_field( 'easyreservations-settings' ); ?>
            </p>
		<?php endif; ?>
    </form>
	<?php do_action( 'easyreservations_after_settings' . $current_tab ); ?>
</div>