<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpAbstractSettingAnnounceModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpSettingScreenAnnounce' ) ) :

final class MywpSettingScreenAnnounce extends MywpAbstractSettingAnnounceModule {

  static protected $id = 'announce';

  static protected $menu = 'add_on_announce';

  static protected $post_type = 'mywp_announce';

  static protected $current_setting_announce_items;

  protected static function get_post( $post_id ) {

    $post = MywpPostType::get_post( $post_id );

    return $post;

  }

  protected static function delete_post( $post_id ) {

    wp_delete_post( $post_id , true );

  }

  protected static function update_post( $update_post ) {

    wp_update_post( $update_post );

  }

  protected static function update_post_meta( $post_id , $meta_key , $meta_value ) {

    update_post_meta( $post_id , $meta_key , $meta_value );

  }

  protected static function get_posts( $args ) {

    $posts = MywpSetting::get_posts( $args );

    return $posts;

  }

  protected static function insert_post( $insert_post ) {

    $post_id = wp_insert_post( $insert_post );

    return $post_id;

  }

  protected static function add_post_metas( $post_id , $post_metas ) {

    $insert_post_metas = self::insert_post_metas( $post_id , $post_metas );

    return $insert_post_metas;

  }

  public static function mywp_setting_menus( $setting_menus ) {

    $setting_menus[ self::$menu ] = array(
      'menu_title' => __( 'Announce' , 'mywp-announce' ),
      'multiple_screens' => false,
    );

    return $setting_menus;

  }

  public static function mywp_setting_screens( $setting_screens ) {

    $setting_screens[ self::$id ] = array(
      'title' => __( 'Announce' , 'mywp-announce' ),
      'menu' => self::$menu,
      'controller' => 'announce',
      'use_advance' => true,
      'document_url' => self::get_document_url( 'add_ons/add-on-announce/' ),
    );

    return $setting_screens;

  }

  public static function mywp_ajax_manager() {

    if( is_network_admin() ) {

      return false;

    }

    self::add_action_ajax();

  }

  public static function mywp_current_setting_screen_header() {

    ?>

    <h3 class="mywp-setting-screen-subtitle"><?php _e( 'Announcements' , 'mywp-announce' ); ?></h3>

    <?php

  }

  public static function mywp_current_setting_screen_after_footer() {

    if( is_multisite() ) {

      return false;

    }

    self::show_plugin_info();

  }

  protected static function delete_transient_controller_get_announces() {

    do_action( 'mywp_setting_announce_before_delete_transient_controller_get_announces' );

    $mywp_transient = new MywpTransient( 'announce_get_announces' , 'controller' );

    $return = $mywp_transient->remove_data();

    do_action( 'mywp_setting_announce_after_delete_transient_controller_get_announces' );

    return $return;

  }

}

MywpSettingScreenAnnounce::init();

endif;
