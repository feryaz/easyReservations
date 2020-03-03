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
            <div class="middle">
                <button class="date"></button>
                <button class="today"><?php esc_html_e( 'Today', 'easyReservations' ); ?></button>
            </div>
            <div class="right easy-ui">
                <span class="input-wrapper">
                    <span class="input-box clickable"><?php esc_html_e( 'Hourly', 'easyReservations' ); ?></span>
                    <span class="input-box clickable"><?php esc_html_e( 'Daily', 'easyReservations' ); ?></span>
                </span>
                <button class="hourly"><?php esc_html_e( 'Hourly', 'easyReservations' ); ?></button>
                <button class="daily"><?php esc_html_e( 'Daily', 'easyReservations' ); ?></button>
            </div>
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
            <div class="timeline-container">
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
                                <tr class="line"></tr>
                            <?php endfor; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>