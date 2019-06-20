<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpControllerAbstractModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpControllerModuleAnnounce' ) ) :

final class MywpControllerModuleAnnounce extends MywpControllerAbstractModule {

  static protected $id = 'announce';

  private static $announces = false;

  public static function mywp_controller_initial_data( $initial_data ) {

    $initial_data['cache_timeout'] = false;

    return $initial_data;

  }

  public static function mywp_controller_default_data( $default_data ) {

    $default_data['cache_timeout'] = 60;

    return $default_data;

  }

  public static function mywp_wp_loaded() {

    if( ! is_admin() ) {

      return false;

    }

    if( is_network_admin() ) {

      return false;

    }

    if( ! self::is_do_controller() ) {

      return false;

    }

    add_action( 'load-index.php' , array( __CLASS__ , 'load_index' ) );

  }

  private static function get_announces() {

    if( ! empty( self::$announces ) ) {

      return self::$announces;

    }

    $setting_data = self::get_setting_data();

    $timeout_min = 0;

    if( ! empty( $setting_data['cache_timeout'] ) ) {

      $timeout_min = intval( $setting_data['cache_timeout'] );

    }

    $mywp_transient = new MywpTransient( 'announce_get_announces' , 'controller' );

    if( ! empty( $timeout_min ) ) {

      $transient_announces = $mywp_transient->get_data();

      if( ! empty( $transient_announces ) ) {

        self::$announces = $transient_announces;

        return self::$announces;

      }

    }

    $args = array(
      'post_status' => array( 'publish' ),
      'post_type' => 'mywp_announce',
      'order' => 'ASC',
      'orderby' => 'menu_order',
      'posts_per_page' => -1,
    );

    $args = apply_filters( 'mywp_controller_announce_get_announce_args' , $args );

    $posts = MywpController::get_posts( $args , self::$id );

    $announces = apply_filters( 'mywp_controller_announce_get_announce' , $posts );

    self::$announces = $announces;

    if( ! empty( $timeout_min ) && ! empty( $announces ) ) {

      $announces_strlen = strlen( maybe_serialize( self::$announces ) );

      if( $announces_strlen < MywpHelper::get_max_allowed_packet_size() ) {

        $mywp_transient->update_data( self::$announces , $timeout_min * MINUTE_IN_SECONDS );

      }

    }

    return $announces;

  }

  public static function load_index() {

    add_action( 'admin_enqueue_scripts' , array( __CLASS__ , 'admin_enqueue_scripts' ) );

    add_action( 'admin_notices' , array( __CLASS__ , 'admin_notices' ) );

  }

  public static function admin_enqueue_scripts() {

    $announces = self::get_announces();

    if( empty( $announces ) ) {

      return false;

    }

    wp_register_style( 'mywp_announce' , MYWP_ANNOUNCE_PLUGIN_URL . 'assets/css/announce.css' , array() , MYWP_ANNOUNCE_VERSION );

    wp_enqueue_style( 'mywp_announce' );

  }

  public static function admin_notices() {

    if( ! self::is_do_function( __FUNCTION__ ) ) {

      return false;

    }

    $announces = self::get_announces();

    if( empty( $announces ) ) {

      return false;

    }

    foreach( $announces as $announce ) {

      self::print_announce( $announce );

    }

    self::after_do_function( __FUNCTION__ );

  }

  private static function print_announce( $item ) {

    $item = apply_filters( 'mywp_controller_announce_print_announce_item' , $item );

    if( empty( $item ) or empty( $item->item_type ) or empty( $item->ID ) ) {

      return false;

    }

    $item_id = $item->ID;

    if( is_numeric( $item_id ) ) {

      $item_id = intval( $item_id );

    } else {

      $item_id = strip_tags( $item_id );

    }

    if( ! empty( $item->item_is_user_roles ) ) {

      if( empty( $item->item_user_roles ) ) {

        return false;

      } else {

        $mywp_user = new MywpUser();

        if( ! in_array( $mywp_user->get_user_role() , $item->item_user_roles ) ) {

          return false;

        }

      }

    }

    $current_time_stamp = current_time( 'timestamp' );

    if( ! empty( $item->item_is_date_start ) ) {

      if( empty( $item->item_date_start ) ) {

        return false;

      } else {

        $timestamp = strtotime( $item->item_date_start );

        if( empty( $timestamp ) ) {

          return false;

        }

        if( $current_time_stamp < $timestamp ) {

          return false;

        }

      }

    }

    if( ! empty( $item->item_is_date_end ) ) {

      if( empty( $item->item_date_end ) ) {

        return false;

      } else {

        $timestamp = strtotime( $item->item_date_end );

        if( $current_time_stamp > $timestamp ) {

          return false;

        }

      }

    }

    MywpAnnounceApi::print_announce( $item );

  }

}

MywpControllerModuleAnnounce::init();

endif;
