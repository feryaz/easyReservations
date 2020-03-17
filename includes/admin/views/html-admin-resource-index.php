<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap easyreservations easy-ui" style="width:99%">
	<?php include 'html-admin-resource-header.php'; ?>

    <div id="settings" class="easy-navigation-target">
		<?php include 'html-admin-resource-settings.php'; ?>

		<?php if ( $resource->availability_by( 'unit' ) ): ?>
			<?php include 'html-admin-resource-spaces-names.php'; ?>
		<?php endif; ?>

		<?php do_action( 'easy-resource-settings', $resource ); ?>
    </div>

    <div id="filters" class="easy-navigation-target hidden">
		<?php include 'html-admin-resource-filters.php'; ?>
		<?php include 'html-admin-resource-filter-add.php'; ?>
    </div>

    <div id="slots" class="easy-navigation-target hidden">
		<?php include 'html-admin-resource-slots.php'; ?>
		<?php include 'html-admin-resource-slot-add.php'; ?>
    </div>

	<?php do_action( 'easy-resource-after-settings', $resource ); ?>
</div>