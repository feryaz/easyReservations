<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( 'ER_Admin_List_Table', false ) ) {
    include_once 'abstract-class-er-admin-list-table.php';
}

/**
 * ER_Admin_List_Table_Orders Class.
 */
class ER_Admin_List_Table_Resources extends ER_Admin_List_Table {

    /**
     * Post type.
     *
     * @var string
     */
    protected $list_table_type = 'easy-rooms';

    /**
     * Render blank state.
     */
    public function maybe_render_blank_state($which) {
    }

    /**
     * Define primary column.
     *
     * @return string
     */
    protected function get_primary_column() {
        return 'reservation_number';
    }

    /**
     * Get row actions to show in the list table.
     *
     * @param array   $actions Array of actions.
     * @param WP_Post $post Current post object.
     * @return array
     */
    protected function get_row_actions( $actions, $post ) {
        return array();
    }

    /**
     * Define bulk actions.
     *
     * @param array $actions Existing actions.
     * @return array
     */
    public function define_bulk_actions( $actions ) {
        return array();
    }

    /**
     * Define hidden columns.
     *
     * @return array
     */
    protected function define_hidden_columns() {
        return array(
            'address',
            'er_actions',
        );
    }

    /**
     * Define which columns are sortable.
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function define_sortable_columns( $columns ) {
        $custom = array(
            'resource_number'   => 'ID',
        );
        unset( $columns['comments'] );

        return wp_parse_args( $custom, $columns );
    }

    /**
     * Define which columns to show on this screen.
     *
     * @param array $columns Existing columns.
     * @return array
     */
    public function define_columns( $columns ) {
        $show_columns                 = array();
        $show_columns['cb']           = $columns['cb'];
        $show_columns['resource_image'] = 'Image';
        $show_columns['resource_number'] = __( 'Resource', 'easyReservations' );
        $show_columns['resource_quantity'] = __( 'Quantity', 'easyReservations' );
        $show_columns['resource_reservations'] = __( 'Reservations', 'easyReservations' );
        $show_columns['resource_price'] = __( 'Price', 'easyReservations' );
        $show_columns['er_actions'] = __( 'Actions', 'easyReservations' );

        return $show_columns;
    }

    /**
     * Pre-fetch any data for the row each column has access to it. the_order global is there for bw compat.
     *
     * @param int $post_id Post ID being shown.
     */
    protected function prepare_row_data( $post_id ) {
        if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
            $this->object = ER()->resources()->get( $post_id );
        }
    }

    /**
     * Render columm: reservation_resource.
     */
    public function render_resource_number_column(){
        $title = $this->object->get_title();

        if ( !$title ) {
            $title = '&ndash;';
        }

        if ( $this->object->get_status() === 'trash' ) {
            echo '<strong>#' . esc_attr( $this->object->get_id() ) . ' ' . esc_html( $title ) . '</strong>';
        } else {
            echo '<a href="' . esc_url( admin_url( 'admin.php?page=resource&resource=' . absint( $this->object->get_id() ) ) . '&action=edit' ) . '"><strong>#' . esc_attr( $this->object->get_id() ) . ' ' . esc_html( $title ) . '</strong></a>';
        }
    }

    /**
     * Render columm: reservation_number.
     */
    public function render_reservation_number_column(){
        $title = $this->object->get_title();

        if( !$title ){
            $title = '&ndash;';
        }

        if ( $this->object->get_status() === 'trash' ) {
            echo '<strong>#' . esc_attr( $this->object->get_id() ) . ' ' . esc_html( $title ) . '</strong>';
        } else {
            echo '<a href="#" class="reservation-preview" data-reservation-id="' . absint( $this->object->get_id() ) . '" title="' . esc_attr( __( 'Preview', 'easyReservations' ) ) . '">' . esc_html( __( 'Preview', 'easyReservations' ) ) . '</a>';
            echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $this->object->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $this->object->get_id() ) . ' ' . esc_html( $title ) . '</strong></a>';
        }
    }

    /**
     * Render columm: reservation_status.
     */
    protected function render_resource_quantity_column() {
        echo esc_html( $this->object->get_quantity() );
    }

    /**
     * Render columm: reservation_status.
     */
    protected function render_resource_price_column() {
        echo er_price( $this->object->get_base_price(), true );
    }

    /**
     * Render columm: reservation_status.
     */
    protected function render_resource_image_column() {
        echo $this->object->get_image( array(40, 40) );
    }

    /**
     * Render columm: reservation_status.
     */
    protected function render_resource_reservations_column() {
        global $wpdb;

        $all_reservations = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}reservations WHERE status='yes' AND resource=%d", $this->object->get_id()
            )
        );

        echo esc_html( $all_reservations );
    }
}
