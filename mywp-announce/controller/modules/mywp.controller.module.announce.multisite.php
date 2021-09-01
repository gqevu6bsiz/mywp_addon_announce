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

  public static function mywp_controller_initial_data( $initial_data ) {

    $initial_data['cache_timeout'] = false;

    return $initial_data;

  }

  public static function mywp_controller_default_data( $default_data ) {

    $default_data['cache_timeout'] = 60;

    return $default_data;

  }

  public static function get_announces() {

    $setting_data = self::get_setting_data();

    $timeout_min = 0;

    if( ! empty( $setting_data['cache_timeout'] ) ) {

      $timeout_min = intval( $setting_data['cache_timeout'] );

    }

    $mywp_transient = new MywpTransient( 'announce_multisite_get_announces' , 'controller' , true );


    if( ! empty( $timeout_min ) ) {

      $transient_announces = $mywp_transient->get_data();

      if( ! empty( $transient_announces ) ) {

        return $transient_announces;

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

    $is_switch_to_blog = MywpAnnounceApi::is_switch_to_blog();

    $posts = MywpController::get_posts( $args , self::$id );

    if( $is_switch_to_blog ) {

      restore_current_blog();

    }

    $announces = apply_filters( 'mywp_controller_announce_multisite_get_announce' , $posts );

    if( ! empty( $timeout_min ) && ! empty( $announces ) ) {

      $announces_strlen = strlen( maybe_serialize( $announces ) );

      if( $announces_strlen < MywpHelper::get_max_allowed_packet_size() ) {

        $mywp_transient->update_data( $announces , $timeout_min * MINUTE_IN_SECONDS );

      }

    }

    return $announces;

  }

}

MywpControllerModuleAnnounceMultisite::init();

endif;
