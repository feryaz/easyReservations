<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

$resources = ER()->resources()->get_accessible();
?>
<div class="er-timeline-tooltip"></div>
<div class="er-timeline">
    <div class="header">
        <input type="text" class="er-datepicker" id="timeline-datepicker">
        <span class="date"></span>
    </div>
    <div class="container">
        <div class="resources">
            <table>
                <thead>
                    <tr>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $resources as $resource ): ?>
                        <tr class="resource">
                            <td class="resource">
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=resource&resource=' . $resource->get_id() ) ); ?>"><?php echo esc_html( $resource->get_title() ); ?></a>
                            </td>
                        </tr>
                        <?php for ( $i = 1; $i <= ( $resource->availability_by( 'unit' ) ? $resource->get_quantity() : 1 ); $i++ ): ?>
                            <tr>
                                <td><?php echo esc_html( $resource->get_space_name( $i ) ); ?></td>
                            </tr>
                        <?php endfor; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
                        <tr class="resource"></tr>
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