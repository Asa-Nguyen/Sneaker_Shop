<?php

class SVW_Ultility {

    // Array code, name tỉnh/ thành phố
    public static function get_province() {
        global $wpdb;
        $table   = $wpdb->prefix . 'svw_province_district_ward';
        $query   = $wpdb->prepare( "SELECT code, name FROM $table WHERE is_province = %s", true );
        $results = $wpdb->get_results($query);
        if ( isset( $results ) ) {
            $province[] = esc_html__( 'Chọn Tỉnh/ Thành Phố', 'svw' );
            foreach( $results as $item ) {
                $province[$item->code] = $item->name;
            }
            return $province;
        }
    }

    // Array code, name quận/ huyện
    public static function get_district( $province_id ) {
        if ( isset( $province_id ) && $province_id ) {
            global $wpdb;
            $table   = $wpdb->prefix . 'svw_province_district_ward';
            $query   = $wpdb->prepare( "SELECT code, name FROM $table WHERE is_district = %s AND parent = %s", true, $province_id );
            $results = $wpdb->get_results($query);
            if ( isset( $results ) ) {
                foreach( $results as $item ) {
                    $province[$item->code] = $item->name;
                }
                return $province;
            }
        } else {
            return array( '' => esc_html__( 'Chọn Quận/ Huyện', 'svw' ) );
        }
    }

    // Array code, name xã/ phường
    public static function get_ward( $district_id ) {
        if ( isset( $district_id ) && $district_id ) {
            global $wpdb;
            $table   = $wpdb->prefix . 'svw_province_district_ward';
            $query   = $wpdb->prepare( "SELECT code, name FROM $table WHERE is_ward = %s AND parent = %s", true, $district_id );
            $results = $wpdb->get_results($query);
            if ( isset( $results ) ) {
                foreach( $results as $item ) {
                    $province[$item->code] = $item->name;
                }
                return $province;
            }
        } else {
            return array( '' => esc_html__( 'Chọn Phường/ Xã', 'svw' ) );
        }
    }

    // Option quận huyện
    public static function show_option_district( $province_id ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'svw_province_district_ward';
        $query   = $wpdb->prepare( "SELECT code, name FROM $table WHERE parent = %s AND is_district = %s ", $province_id, true );
        $results = $wpdb->get_results($query);

        if ( isset( $results ) ) {
            echo '<option value="">'.esc_html__( 'Chọn Quận/ Huyện', 'svw' ).'</option>';
            foreach( $results as $item ) {
                echo '<option value="'.esc_attr( $item->code ).'">'.esc_attr( $item->name ).'</option>';
            }
        }
    }

    // Option quận huyện
    public static function show_option_ward( $district_id ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'svw_province_district_ward';
        $query   = $wpdb->prepare( "SELECT code, name FROM $table WHERE parent = %s AND is_ward = %s ", $district_id, true );
        $results = $wpdb->get_results($query);

        if ( isset( $results ) ) {
            echo '<option value="">'.esc_html__( 'Chọn Xã/ Phường', 'svw' ).'</option>';
            foreach( $results as $item ) {
                echo '<option value="'.esc_attr( $item->code ).'">'.esc_attr( $item->name ).'</option>';
            }
        }
    }

    // Lấy tên một tỉnh/ thành phố từ province_id
    public static function get_detail_province( $province_id ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'svw_province_district_ward';
        $query   = $wpdb->prepare( "SELECT name FROM $table WHERE is_province = %s AND code = %s", true, $province_id );
        $results = $wpdb->get_row($query);
        return $results->name;
    }

    // Lấy tên một quận/huyện từ district_id
    public static function get_detail_district( $district_id ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'svw_province_district_ward';
        $query   = $wpdb->prepare( "SELECT name FROM $table WHERE is_district = %s AND code = %s", true, $district_id );
        $results = $wpdb->get_row($query);
        return $results->name;
    }

    // Lấy tên môt phường/xã từ ward_id
    public static function get_detail_ward( $ward_id ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'svw_province_district_ward';
        $query   = $wpdb->prepare( "SELECT name FROM $table WHERE is_ward = %s AND code = %s", true, $ward_id );
        $results = $wpdb->get_row($query);
        return $results->name;
    }

}
