<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpControllerAbstractModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpControllerModuleAnnounceMultisite' ) ) :

final class MywpControllerModuleAnnounceMultisite extends MywpControllerAbstractModule {

  static protected $id = 'announce_multisite';

  static protected $network = true;

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

    add_action( 'current_screen' , array( __CLASS__ , 'current_screen' ) );

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

    $mywp_transient = new MywpTransient( 'announce_multisite_get_announces' , 'controller' , true );

    if( ! empty( $timeout_min ) ) {

      $transient_announces = $mywp_transient->get_data();

      if( ! empty( $transient_announces ) ) {

        self::$announces = $transient_announces;

        return self::$announces;

      }

    }

    $args = array(
      'post_status' => array( 'publish' ),
      'post_type' => 'mywp_announce_sites',
      'order' => 'ASC',
      'orderby' => 'menu_order',
      'posts_per_page' => -1,
    );

    $args = apply_filters( 'mywp_controller_announce_multisite_get_announce_args' , $args );

    $switch_blog = false;

    if( ! is_main_site() ) {

      switch_to_blog( 1 );

      $switch_blog = true;

    }

    $posts = MywpController::get_posts( $args , self::$id );

    if( $switch_blog ) {

      restore_current_blog();

    }

    $announces = apply_filters( 'mywp_controller_announce_multisite_get_announce' , $posts );

    self::$announces = $announces;

    if( ! empty( $timeout_min ) && ! empty( $announces ) ) {

      $announces_strlen = strlen( maybe_serialize( self::$announces ) );

      if( $announces_strlen < MywpHelper::get_max_allowed_packet_size() ) {

        $mywp_transient->update_data( self::$announces , $timeout_min * MINUTE_IN_SECONDS );

      }

    }

    return $announces;

  }

  public static function current_screen() {

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

    global $pagenow;

    if( ! self::is_do_function( __FUNCTION__ ) ) {

      return false;

    }

    $announces = self::get_announces();

    if( ! empty( $announces ) ) {

      foreach( $announces as $key => $announce ) {

        if( $announce->item_screen === 'all' ) {

          continue;

        }

        $announce_screen = MywpAnnounceApi::get_announce_screen( $announce->item_screen );

        if( empty( $announce_screen ) ) {

          unset( $announces[ $key ] );

          continue;

        }

        if( $pagenow !== $announce_screen['page_id'] ) {

          unset( $announces[ $key ] );

          continue;

        }

      }

    }

    if( empty( $announces ) ) {

      return false;

    }

    foreach( $announces as $announce ) {

      self::print_announce( $announce );

    }

    self::after_do_function( __FUNCTION__ );

  }

  private static function print_announce( $item ) {

    $item = apply_filters( 'mywp_controller_announce_multisite_print_announce_item' , $item );

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

        if( empty( $timestamp ) ) {

          return false;

        }

        if( $current_time_stamp > $timestamp ) {

          return false;

        }

      }

    }

    if( ! empty( $item->item_hide_sites ) ) {

      if( strpos( $item->item_hide_sites , ',' ) === false ) {

        $item_hide_sites = array( intval( $item->item_hide_sites ) );

      } else {

        $item_hide_sites = explode( ',' , $item->item_hide_sites );

        foreach( $item_hide_sites as $key => $val ) {

          if( empty( $val ) ) {

            unset( $item_hide_sites[ $key ] );

          } else {

            $item_hide_sites[ $key ] = intval( $val );

          }

        }

      }

      $current_blog_id = get_current_blog_id();

      if( in_array( $current_blog_id , $item_hide_sites ) ) {

        return false;

      }

    }

    MywpAnnounceApi::print_announce( $item );

  }

}

MywpControllerModuleAnnounceMultisite::init();

endif;
