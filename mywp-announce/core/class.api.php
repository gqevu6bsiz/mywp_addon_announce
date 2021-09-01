<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'MywpAnnounceApi' ) ) :

final class MywpAnnounceApi {

  public static function plugin_info() {

    $plugin_info = array(
      'admin_url' => admin_url( 'admin.php?page=mywp_add_on_announce' ),
      'document_url' => 'https://mywpcustomize.com/add_ons/add-on-announce/',
      'website_url' => 'https://mywpcustomize.com/',
      'github' => 'https://github.com/gqevu6bsiz/mywp_addon_announce',
      'github_tags' => 'https://api.github.com/repos/gqevu6bsiz/mywp_addon_announce/tags',
    );

    if( is_multisite() ) {

      $plugin_info['multisite_admin_url'] = network_admin_url( 'admin.php?page=mywp_add_on_announce_multisite' );

    }

    $plugin_info = apply_filters( 'mywp_announce_plugin_info' , $plugin_info );

    return $plugin_info;

  }

  public static function is_network_manager() {

    return MywpApi::is_network_manager();

  }

  public static function is_manager() {

    return MywpApi::is_manager();

  }

  public static function get_announce_types() {

    $announce_types = array(
      'normal' => array(
        'color' => __( 'Gray' , 'mywp-announce' ),
        'label' => __( 'Normal' , 'mywp-announce' ),
      ),
      'updated' => array(
        'color' => __( 'Green' , 'mywp-announce' ),
        'label' => __( 'Update' , 'mywp-announce' ),
      ),
      'error' => array(
        'color' => __( 'Red' , 'mywp-announce' ),
        'label' => __( 'Error' , 'mywp-announce' ),
      ),
      'default' => array(
        'color' => '',
        'label' => __( 'Default' , 'mywp-announce' ),
      ),
      'custom' => array(
        'color' => '',
        'label' => __( 'Custom' ),
      ),
    );

    $announce_types = apply_filters( 'mywp_announce_get_announce_types' , $announce_types );

    return $announce_types;

  }

  public static function get_announce_screens() {

    $announce_screens = array(
      'dashboard' => array(
        'label' => __( 'Dashboard' ),
        'page_id' => 'index.php',
      ),
      'all' => array(
        'label' => __( 'All admin screens' , 'mywp-announce' ),
        'page_id' => '',
      ),
    );

    $announce_screens = apply_filters( 'mywp_announce_get_announce_screens' , $announce_screens );

    return $announce_screens;

  }

  public static function get_announce_screen( $announce_screen_id = false ) {

    $announce_screen_id = strip_tags( $announce_screen_id );

    if( empty( $announce_screen_id ) ) {

      return false;

    }

    $announce_screens = self::get_announce_screens();

    if( isset( $announce_screens[ $announce_screen_id ] ) ) {

      return $announce_screens[ $announce_screen_id ];

    }

    return false;

  }

  public static function get_all_user_roles() {

    return MywpApi::get_all_user_roles();

  }

  public static function get_all_sites() {

    return MywpHelper::get_all_sites();

  }

  public static function is_switch_to_blog() {

    $is_switch_to_blog = false;

    if( ! is_multisite() ) {

      return $is_switch_to_blog;

    }

    if( is_main_site() ) {

      return $is_switch_to_blog;

    }

    $main_site_id = get_main_site_id();

    if( empty( $main_site_id ) ) {

      return $is_switch_to_blog;

    }

    switch_to_blog( $main_site_id );

    $is_switch_to_blog = true;

    return $is_switch_to_blog;

  }

}

endif;
