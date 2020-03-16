<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'MywpAnnounceApi' ) ) :

final class MywpAnnounceApi {

  private static $instance;

  private function __construct() {}

  public static function get_instance() {

    if ( !isset( self::$instance ) ) {

      self::$instance = new self();

    }

    return self::$instance;

  }

  private function __clone() {}

  private function __wakeup() {}

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
    );

    $announce_types = apply_filters( 'mywp_announce_get_announce_types' , $announce_types );

    return $announce_types;

  }

  public static function get_announce_screens() {

    $announce_screens = array(
      'dashboard' => array(
        'label' => __( 'Dashboard' ),
      ),
    );

    $announce_screens = apply_filters( 'mywp_announce_get_announce_screens' , $announce_screens );

    return $announce_screens;

  }

  public static function get_all_user_roles() {

    return MywpApi::get_all_user_roles();

  }

  public static function get_all_sites() {

    return MywpHelper::get_all_sites();

  }

  public static function print_announce( $item ) {

    $item->post_title = do_shortcode( $item->post_title );

    add_filter( 'mywp_announce_print_announce_content' , 'wptexturize' );
    add_filter( 'mywp_announce_print_announce_content' , 'convert_smilies' , 20 );
    add_filter( 'mywp_announce_print_announce_content' , 'convert_chars' );
    add_filter( 'mywp_announce_print_announce_content' , 'wpautop' );
    add_filter( 'mywp_announce_print_announce_content' , 'shortcode_unautop' );
    add_filter( 'mywp_announce_print_announce_content' , 'prepend_attachment' );
    add_filter( 'mywp_announce_print_announce_content' , 'do_shortcode' , 11 );

    $announce_content = apply_filters( 'mywp_announce_print_announce_content' , $item->post_content );

    printf( '<div class="mywp-announce updated %s" id="announce-%d">' , esc_attr( $item->item_type ) , esc_attr( $item->ID ) );

    if( ! empty( $item->post_title ) ) {

      printf( '<p class="title">%s</p>' , $item->post_title );

    }

    if( ! empty( $announce_content ) ) {

      echo $announce_content;

    }

    echo '</div>';

  }

}

endif;
