<?php
//Prevent direct access to file
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class ER_Abstract_Order extends ER_Receipt {

    /**
     * Order Data array.
     *
     * @var array
     */
    protected $data = array(
        'parent_id'          => 0,
        'status'             => '',
        'prices_include_tax' => false,
        'date_created'       => null,
        'date_modified'      => null,
        'discount_total'     => 0,
        'discount_tax'       => 0,
        'total'              => 0,
        'total_tax'          => 0,
    );

    /**
     * Stores meta in cache for future reads.
     *
     * A group must be set to to enable caching.
     *
     * @var string
     */
    protected $cache_group = 'orders';

    /**
     * Which data store to load.
     *
     * @var string
     */
    protected $data_store_name = 'order';

    /**
     * This is the name of this object type.
     *
     * @var string
     */
    protected $object_type = 'order';

    /**
     * ER_Abstract_Order constructor.
     *
     * @param int|array|ER_Order $order
     */
    public function __construct( $order ) {
        parent::__construct( $order );

        if ( is_numeric( $order ) && $order > 0 ) {
            $this->set_id( $order );
        } elseif ( $order instanceof self ) {
            $this->set_id( $order->get_id() );
        } elseif ( !empty( $order->ID ) ) {
            $this->set_id( $order->ID );
        } else {
            $this->set_object_read( true );
        }

        $this->data_store = ER_Data_Store::load( $this->data_store_name );

        if ( $this->get_id() > 0 ) {
            $this->data_store->read( $this );
        }
    }

    /**
     * Get internal type.
     *
     * @return string
     */
    public function get_type() {
        return 'easy_order';
    }

    /**
     * Get all class data in array format.
     *
     * @return array
     */
    public function get_data() {
        return array_merge(
            array(
                'id' => $this->get_id(),
            ),
            $this->data,
            array(
                'meta_data'      => $this->get_meta_data(),
                'line_items'     => $this->get_items(),
                'tax_lines'      => $this->get_items( 'tax' ),
                'fee_lines'      => $this->get_items( 'fee' ),
                'coupon_lines'   => $this->get_items( 'coupon' ),
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    /**
     * Get parent order ID.
     *
     * @param  string $context View or edit context.
     * @return integer
     */
    public function get_parent_id( $context = 'view' ) {
        return $this->get_prop( 'parent_id', $context );
    }

    /**
     * Get user ID. Used by orders, not other order types like refunds.
     *
     * @param  string $context View or edit context.
     * @return int
     */
    public function get_user_id( $context = 'view' ) {
        return 0;
    }

    /**
     * Get user. Used by orders, not other order types like refunds.
     *
     * @return WP_User|false
     */
    public function get_user() {
        return false;
    }

    /**
     * Gets order total - formatted for display.
     *
     * @return string
     */
    public function get_formatted_total() {
        $formatted_total = er_price( $this->get_total(), true );

        return apply_filters( 'easyreservations_get_formatted_order_total', $formatted_total, $this );
    }

    /*
    |--------------------------------------------------------------------------
    | Setters
    |--------------------------------------------------------------------------
    */

    /**
     * Set parent order ID.
     *
     * @param int $value Value to set.
     * @throws ER_Data_Exception Exception thrown if parent ID does not exist or is invalid.
     */
    public function set_parent_id( $value ) {
        if ( $value && ( $value === $this->get_id() || !er_get_order( $value ) ) ) {
            $this->error( 'order_invalid_parent_id', __( 'Invalid parent ID', 'easyReservations' ) );
        }
        $this->set_prop( 'parent_id', absint( $value ) );
    }

    /**
     * Set prices_include_tax.
     *
     * @param bool $value Value to set.
     * @throws ER_Data_Exception Exception may be thrown if value is invalid.
     */
    public function set_prices_include_tax( $value ) {
        $this->set_prop( 'prices_include_tax', (bool) $value );
    }

    /**
     * Set discount_total.
     *
     * @param string $value Value to set.
     * @throws ER_Data_Exception Exception may be thrown if value is invalid.
     */
    public function set_discount_total( $value ) {
        $this->set_prop( 'discount_total', er_format_decimal( $value ) );
    }

    /**
     * Set discount_tax.
     *
     * @param string $value Value to set.
     * @throws ER_Data_Exception Exception may be thrown if value is invalid.
     */
    public function set_discount_tax( $value ) {
        $this->set_prop( 'discount_tax', er_format_decimal( $value ) );
    }

    /**
     * Sets tax (sum of cart and shipping tax). Used internally only.
     *
     * @param string $value Value to set.
     * @throws ER_Data_Exception Exception may be thrown if value is invalid.
     */
    public function set_total_tax( $value ) {
        $this->set_prop( 'total_tax', er_format_decimal( $value ) );
    }

    /**
     * Set total.
     *
     * @param string $value Value to set.
     * @throws ER_Data_Exception Exception may be thrown if value is invalid.
     */
    public function set_total( $value ) {
        $this->set_prop( 'total', er_format_decimal( $value, er_get_price_decimals() ) );
    }

    /*
    |--------------------------------------------------------------------------
    | Other functions
    |--------------------------------------------------------------------------
    */

    /**
     * Find item of a reservation
     *
     * @param int $reservation_id
     * @return bool|ER_Receipt_Item_Reservation
     */
    public function find_reservation( $reservation_id ) {
        foreach ( $this->get_items( 'reservation' ) as $item ) {
            $id = false;

            if ( method_exists( $item, 'get_reservation_id' ) ) {
                $id = $item->get_reservation_id();
            }

            if ( $id === $reservation_id ) {
                return $item;
            }
        }

        return false;
    }

    /**
     * Find item of a reservation
     *
     * @param $object_id
     */
    public function find_custom( $object_id ) {
        foreach ( $this->get_items( 'reservation' ) as $item ) {
            $id = false;

            if ( method_exists( $item, 'get_reservation_id' ) ) {
                $id = $item->get_reservation_id();
            }

            if ( $id === $object_id ) {
                return $item;
            }
        }

        return false;
    }

    /**
     * Get used coupon codes only.
     *
     * @return array
     */
    public function get_coupon_codes() {
        $coupon_codes = array();
        $coupons      = $this->get_items( 'coupon' );

        if ( $coupons ) {
            foreach ( $coupons as $coupon ) {
                $coupon_codes[] = $coupon->get_code();
            }
        }

        return $coupon_codes;
    }

    /**
     * Check and records coupon usage tentatively so that counts validation is correct. Display an error if coupon usage limit has been reached.
     *
     * If you are using this method, make sure to `release_held_coupons` in case an Exception is thrown.
     *
     * @throws Exception When not able to apply coupon.
     *
     * @param string $billing_email Billing email of order.
     */
    public function hold_applied_coupons( $billing_email ) {
        $held_keys          = array();
        $held_keys_for_user = array();
        $error              = null;

        try {
            foreach ( ER()->cart->get_applied_coupons() as $code ) {
                $coupon = new ER_Coupon( $code );
                if ( !$coupon->get_data_store() ) {
                    continue;
                }

                // Hold coupon for when global coupon usage limit is present.
                if ( 0 < $coupon->get_usage_limit() ) {
                    $held_key = $this->hold_coupon( $coupon );
                    if ( $held_key ) {
                        $held_keys[$coupon->get_id()] = $held_key;
                    }
                }

                // Hold coupon for when usage limit per customer is enabled.
                if ( 0 < $coupon->get_usage_limit_per_user() ) {

                    if ( !isset( $user_ids_and_emails ) ) {
                        $user_alias          = get_current_user_id() ? wp_get_current_user()->ID : sanitize_email( $billing_email );
                        $user_ids_and_emails = $this->get_billing_and_current_user_aliases( $billing_email );
                    }

                    $held_key_for_user = $this->hold_coupon_for_users( $coupon, $user_ids_and_emails, $user_alias );

                    if ( $held_key_for_user ) {
                        $held_keys_for_user[$coupon->get_id()] = $held_key_for_user;
                    }
                }
            }
        } catch ( Exception $e ) {
            $error = $e;
        } finally {
            // Even in case of error, we will save keys for whatever coupons that were held so our data remains accurate.
            // We save them in bulk instead of one by one for performance reasons.
            if ( 0 < count( $held_keys_for_user ) || 0 < count( $held_keys ) ) {
                $this->get_data_store()->set_coupon_held_keys( $this, $held_keys, $held_keys_for_user );
            }
            if ( $error instanceof Exception ) {
                throw $error;
            }
        }
    }

    /**
     * Hold coupon if a global usage limit is defined.
     *
     * @param ER_Coupon $coupon Coupon object.
     *
     * @return string    Meta key which indicates held coupon.
     * @throws Exception When can't be held.
     */
    private function hold_coupon( $coupon ) {
        $result = $coupon->get_data_store()->check_and_hold_coupon( $coupon );
        if ( false === $result ) {
            // translators: Actual coupon code.
            throw new Exception( sprintf( __( 'An unexpected error happened while applying the Coupon %s.', 'easyReservations' ), $coupon->get_code() ) );
        } elseif ( 0 === $result ) {
            // translators: Actual coupon code.
            throw new Exception( sprintf( __( 'Coupon %s was used in another transaction during this checkout, and coupon usage limit is reached. Please remove the coupon and try again.', 'easyReservations' ), $coupon->get_code() ) );
        }

        return $result;
    }

    /**
     * Hold coupon if usage limit per customer is defined.
     *
     * @param ER_Coupon $coupon Coupon object.
     * @param array     $user_ids_and_emails Array of user Id and emails to check for usage limit.
     * @param string    $user_alias User ID or email to use to record current usage.
     *
     * @return string    Meta key which indicates held coupon.
     * @throws Exception When coupon can't be held.
     */
    private function hold_coupon_for_users( $coupon, $user_ids_and_emails, $user_alias ) {
        $result = $coupon->get_data_store()->check_and_hold_coupon_for_user( $coupon, $user_ids_and_emails, $user_alias );
        if ( false === $result ) {
            // translators: Actual coupon code.
            throw new Exception( sprintf( __( 'An unexpected error happened while applying the Coupon %s.', 'easyReservations' ), $coupon->get_code() ) );
        } elseif ( 0 === $result ) {
            // translators: Actual coupon code.
            throw new Exception( sprintf( __( 'You have used this coupon %s in another transaction during this checkout, and coupon usage limit is reached. Please remove the coupon and try again.', 'easyReservations' ), $coupon->get_code() ) );
        }

        return $result;
    }

    /**
     * Helper method to get all aliases for current user and provide billing email.
     *
     * @param string $billing_email Billing email provided in form.
     *
     * @return array     Array of all aliases.
     * @throws Exception When validation fails.
     */
    private function get_billing_and_current_user_aliases( $billing_email ) {
        $emails = array( $billing_email );
        if ( get_current_user_id() ) {
            $emails[] = wp_get_current_user()->user_email;
        }
        $emails              = array_unique(
            array_map( 'strtolower', array_map( 'sanitize_email', $emails ) )
        );
        $customer_data_store = ER_Data_Store::load( 'customer' );
        $user_ids            = $customer_data_store->get_user_ids_for_billing_email( $emails );

        return array_merge( $user_ids, $emails );
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Token Handling
    |--------------------------------------------------------------------------
    |
    | Payment tokens are hashes used to take payments by certain gateways.
    |
    */

    /**
     * Add a payment token to an order
     *
     * @param ER_Payment_Token $token Payment token object.
     * @return boolean|int The new token ID or false if it failed.
     */
    public function add_payment_token( $token ) {
        if ( empty( $token ) || !( $token instanceof ER_Payment_Token ) ) {
            return false;
        }

        $token_ids   = $this->data_store->get_payment_token_ids( $this );
        $token_ids[] = $token->get_id();
        $this->data_store->update_payment_token_ids( $this, $token_ids );

        do_action( 'easyreservations_payment_token_added_to_order', $this->get_id(), $token->get_id(), $token, $token_ids );

        return $token->get_id();
    }

    /**
     * Returns a list of all payment tokens associated with the current order
     *
     * @return array An array of payment token objects
     */
    public function get_payment_tokens() {
        return array_map( 'intval', $this->get_meta( 'payment_tokens' ) );
    }
}
