<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

$resources = ER()->resources()->get_accessible();
?>
<div class="er-timeline-tooltip"></div>
<div class="er-timeline">
    <div class="sidebar">
        <div class="day">
            <div class="er-datepicker" id="timeline-datepicker"></div>
        </div>
        <div class="pending"></div>
    </div>
    <div class="content">
        <div class="header">
            <div class="left">
                <span class="expand-sidebar"></span>
                <span class="pending"></span>
            </div>
            <div class="middle easy-ui">
                <span class="input-wrapper"><span class="input-box clickable date"></span></span>
                <span class="input-wrapper"><span class="input-box clickable today"><?php esc_html_e( 'Today', 'easyReservations' ); ?></span></span>
            </div>
            <div class="right easy-ui">
                <span class="input-wrapper">
                    <span class="input-box clickable hourly"><?php esc_html_e( 'Hourly', 'easyReservations' ); ?></span>
                    <span class="input-box clickable daily"><?php esc_html_e( 'Daily', 'easyReservations' ); ?></span>
                </span>
            </div>
        </div>
        <div class="container">
            <div class="resources">
                <table>
                    <thead class="main">
                        <tr>
                            <th></th>
                        </tr>
                    </thead>
                    <?php foreach ( $resources as $resource ): ?>
                        <thead class="resource">
                            <tr>
                                <th>
                                    <span class="resource-handler" data-resource="<?php echo esc_attr( $resource->get_id() ); ?>"></span>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=resource&resource=' . $resource->get_id() ) ); ?>"><?php echo esc_html( $resource->get_title() ); ?></a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ( $i = 1; $i <= ( $resource->availability_by( 'unit' ) ? $resource->get_quantity() : 1 ); $i++ ): ?>
                                <tr>
                                    <td><?php echo esc_html( $resource->get_space_name( $i ) ); ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    <?php endforeach; ?>
                </table>
            </div>
            <div class="timeline-container">
                <div class="prev"></div>
                <div class="next"></div>
                <div class="timeline">
                    <table>
                        <thead class="main">
                            <tr>

                            </tr>
                        </thead>
                        <?php foreach ( $resources as $resource ): ?>
                            <thead class="resource">
                                <tr>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ( $i = 1; $i <= ( $resource->availability_by( 'unit' ) ? $resource->get_quantity() : 1 ); $i++ ): ?>
                                    <tr>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>