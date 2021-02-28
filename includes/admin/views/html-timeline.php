<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$resources = ER()->resources()->get_accessible();

?>
<div class="er-timeline-tooltip"></div>
<div class="er-timeline">
    <div class="sidebar easy-ui">
        <div class="calendar visible">
            <div class="er-datepicker" id="timeline-datepicker"></div>
            <h3><?php esc_html_e( 'Arrivals', 'easyReservations' ); ?></h3>
            <div class="reservations arrivals"></div>
            <h3><?php esc_html_e( 'Departures', 'easyReservations' ); ?></h3>
            <div class="reservations departures"></div>
        </div>
        <div class="pending visible">
            <h2><?php esc_html_e( 'Pending reservations', 'easyReservations' ); ?></h2>
            <div class="reservations"></div>
        </div>
        <div class="reservation-details visible">
            <h2>
                <span class="reservation-status"></span>
                <span class="title"></span>
            </h2>
            <div class="row">
                <label><?php esc_html_e( 'Arrival', 'easyReservations' ); ?></label>
                <span class="reservation-arrival"></span>
            </div>
            <div class="row">
                <label><?php esc_html_e( 'Departure', 'easyReservations' ); ?></label>
                <span class="reservation-departure"></span>
            </div>
            <div class="row">
                <label><?php esc_html_e( 'Resource', 'easyReservations' ); ?></label>
                <span class="reservation-resource"></span>
            </div>
            <div class="row">
                <label><?php esc_html_e( 'Space', 'easyReservations' ); ?></label>
                <span class="reservation-space"></span>
            </div>
            <div class="row">
                <label><?php esc_html_e( 'Adults', 'easyReservations' ); ?></label>
                <span class="reservation-adults"></span>
            </div>
            <div class="row">
                <label><?php esc_html_e( 'Children', 'easyReservations' ); ?></label>
                <span class="reservation-children"></span>
            </div>
            <div class="reservation-order row"></div>
            <span class="input-wrapper">
                <span class="input-box clickable reservation-preview" data-reservation-id="0"><?php esc_html_e( 'More details', 'easyReservations' ); ?></span>
            </span>
            <h3><?php esc_html_e( 'Status', 'easyReservations' ); ?></h3>
            <span class="input-wrapper">
                <span class="input-box clickable background status status-approved" data-status="approved"><?php esc_html_e( 'Approved', 'easyReservations' ); ?></span>
            </span>
            <span class="input-wrapper">
                <span class="input-box clickable background status status-checked" data-status="checked"><?php esc_html_e( 'Checked in', 'easyReservations' ); ?></span>
            </span>
            <span class="input-wrapper">
                <span class="input-box clickable background status status-completed" data-status="completed"><?php esc_html_e( 'Completed', 'easyReservations' ); ?></span>
            </span>
            <h3><?php esc_html_e( 'Actions', 'easyReservations' ); ?></h3>
            <span class="input-wrapper">
                <span class="input-box clickable background allow-edit" data-reservation-id=""><?php esc_html_e( 'Allow edit', 'easyReservations' ); ?></span>
            </span>
            <div class="edit-actions">
                <span class="input-wrapper">
                    <span class="input-box background snapping enabled"><?php esc_html_e( 'Snapping', 'easyReservations' ); ?></span>
                </span>
                <span class="input-wrapper">
                    <span class="input-box clickable background revert"><?php esc_html_e( 'Revert changes', 'easyReservations' ); ?></span>
                </span>
            </div>
        </div>
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
                <div class="er-dropdown" style="display: inline-block">
                    <a class="button-primary add dropdown-toggle"><?php esc_html_e( 'Add', 'easyReservations' ); ?></a>
                    <ul class="dropdown-menu right" role="menu" aria-labelledby="menu1">
                        <li role="presentation">
                            <a role="menuitem" class="start-add" data-target="reservation" tabindex="-1" href="#"><?php esc_html_e( 'Reservation', 'easyReservations' ); ?></a>
                        </li>
                        <li role="presentation" class="divider"></li>
                        <li role="presentation">
                            <a role="menuitem" class="start-add" data-target="resource" tabindex="-1" href="#"><?php esc_html_e( 'Block resource', 'easyReservations' ); ?></a>
                        </li>
                        <li role="presentation">
                            <a role="menuitem" class="start-add" data-target="global" tabindex="-1" href="#"><?php esc_html_e( 'Block global', 'easyReservations' ); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="resources">
                <div class="corner">
                    <div class="absolute"></div>
                </div>
                <div class="vertical-scroll">
                    <table>
						<?php foreach ( $resources as $resource ): ?>
                            <thead class="resource">
                            <tr>
                                <th>
                                    <div>
                                        <span class="resource-handler" data-resource="<?php echo esc_attr( $resource->get_id() ); ?>"></span>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=resource&resource=' . $resource->get_id() ) ); ?>"><?php echo esc_html( $resource->get_title() ); ?></a>
                                    </div>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
							<?php for ( $i = 1; $i <= ( $resource->availability_by( 'unit' ) ? $resource->get_quantity() : 1 ); $i ++ ): ?>
                                <tr>
                                    <td data-resource="<?php echo esc_attr( $resource->get_id() ); ?>" data-space="<?php echo esc_attr( $i ); ?>"><?php echo esc_html( $resource->get_space_name( $i ) ); ?></td>
                                </tr>
							<?php endfor; ?>
                            </tbody>
						<?php endforeach; ?>
                    </table>
                </div>
            </div>
            <div class="timeline-container">
                <div class="prev"></div>
                <div class="next"></div>
                <div class="timeline">
                    <div class="horizontal-scroll">
                        <table>
                            <thead class="main">
                                <tr></tr>
                            </thead>
                        </table>
                    </div>
                    <div class="vertical-scroll">
                        <div class="horizontal-scroll">
                            <table>
								<?php foreach ( $resources as $resource ): ?>
                                    <thead class="resource resource-<?php echo $resource->get_id(); ?>">
                                        <tr></tr>
                                    </thead>
                                    <tbody>
									<?php for ( $i = 1; $i <= ( $resource->availability_by( 'unit' ) ? $resource->get_quantity() : 1 ); $i ++ ): ?>
                                        <tr></tr>
									<?php endfor; ?>
                                    </tbody>
								<?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>