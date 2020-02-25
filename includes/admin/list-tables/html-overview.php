<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

$resources = ER()->resources()->get_accessible();
?>
<div class="er-overview-tooltip"></div>
<div class="er-overview">
    <div class="header">
        <input type="text" class="er-datepicker" id="overview-datepicker">
        <span class="date"></span>
    </div>
    <div class="container">
        <div class="labels">
            <div style="height:38px;border-bottom:1px solid #e4e8f1">
            </div>
            <?php foreach ( $resources as $resource ): ?>
                <div class="resource">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=resource&resource=' . $resource->get_id() ) ); ?>"><?php echo esc_html( $resource->get_title() ); ?></a>
                </div>
                <ul>
                    <?php for ( $i = 1; $i <= ( $resource->availability_by( 'unit' ) ? $resource->get_quantity() : 1 ); $i++ ): ?>
                        <li><?php echo esc_html( $resource->get_space_name( $i ) ); ?></li>
                    <?php endfor; ?>
                </ul>
            <?php endforeach; ?>
        </div>
        <div class="overview">
            <div class="prev"></div>
            <div class="next"></div>
            <div class="timeline">
                <table>
                    <thead>
                    <tr class="main"></tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $resources as $resource ): ?>
                        <tr class="header"></tr>
                        <?php for ( $i = 1; $i <= ( $resource->availability_by( 'unit' ) ? $resource->get_quantity() : 1 ); $i++ ): ?>
                            <tr></tr>
                        <?php endfor; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="shadow">&nbsp;</div>
        </div>
    </div>
</div>