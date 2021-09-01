<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpAbstractSettingAnnounceModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpSettingScreenAnnounceMultisite' ) ) :

final class MywpSettingScreenAnnounceMultisite extends MywpAbstractSettingAnnounceModule {

  static protected $id = 'announce_multisite';

  static protected $menu = 'add_on_announce_multisite';

  static protected $post_type = 'mywp_announce_sites';

  static protected $current_setting_announce_items;

  protected static function after_init() {

    if( is_multisite() ) {

      add_action( 'mywp_setting_announce_print_item_content' , array( __CLASS__ , 'mywp_setting_announce_print_item_content' ) , 10 , 3 );

    }

  }

  public static function mywp_setting_announce_print_item_content( $item ) {

    if( ! is_network_admin() ) {

      return false;

    }

    if( empty( $item ) ) {

      return false;

    }

    ?>

    <table class="form-table hide-sites-setting">
      <tbody>
        <tr>
          <th><?php _e( 'Announce Hide Sites' , 'mywp-announce' ); ?></th>
          <td>
            <?php self::print_item_content_field( 'item_hide_sites' , $item->item_hide_sites , $item ); ?>
          </td>
        </tr>
      </tbody>
    </table>

    <?php

  }

  protected static function get_post( $post_id ) {

    $is_switch_to_blog = MywpAnnounceApi::is_switch_to_blog();

    $post = MywpPostType::get_post( $post_id );

    if( $is_switch_to_blog ) {

      restore_current_blog();

    }

    return $post;

  }

  protected static function delete_post( $post_id ) {

    $is_switch_to_blog = MywpAnnounceApi::is_switch_to_blog();

    wp_delete_post( $post_id , true );

    if( $is_switch_to_blog ) {

      restore_current_blog();

    }

  }

  protected static function update_post( $update_post ) {

    $is_switch_to_blog = MywpAnnounceApi::is_switch_to_blog();

    wp_update_post( $update_post );

    if( $is_switch_to_blog ) {

      restore_current_blog();

    }

  }

  protected static function update_post_meta( $post_id , $meta_key , $meta_value ) {

    $is_switch_to_blog = MywpAnnounceApi::is_switch_to_blog();

    update_post_meta( $post_id , $meta_key , $meta_value );

    if( $is_switch_to_blog ) {

      restore_current_blog();

    }

  }

  protected static function get_posts( $args ) {

    $is_switch_to_blog = MywpAnnounceApi::is_switch_to_blog();

    $posts = MywpSetting::get_posts( $args );

    if( $is_switch_to_blog ) {

      restore_current_blog();

    }

    return $posts;

  }

  protected static function insert_post( $insert_post ) {

    $is_switch_to_blog = MywpAnnounceApi::is_switch_to_blog();

    $post_id = wp_insert_post( $insert_post );

    if( $is_switch_to_blog ) {

      restore_current_blog();

    }

    return $post_id;

  }

  protected static function add_post_metas( $post_id , $post_metas ) {

    $is_switch_to_blog = MywpAnnounceApi::is_switch_to_blog();

    $insert_post_metas = self::insert_post_metas( $post_id , $post_metas );

    if( $is_switch_to_blog ) {

      restore_current_blog();

    }

    return $insert_post_metas;

  }

  public static function mywp_setting_menus( $setting_menus ) {

    if( is_multisite() ) {

      $setting_menus[ self::$menu ] = array(
        'menu_title' => __( 'Announce' , 'mywp-announce' ),
        'multiple_screens' => false,
        'network' => true,
      );

    }

    return $setting_menus;

  }

  public static function mywp_setting_screens( $setting_screens ) {

    if( is_multisite() ) {

      $setting_screens[ self::$id ] = array(
        'title' => __( 'Announce' , 'mywp-announce' ),
        'menu' => self::$menu,
        'controller' => 'announce_multisite',
        'use_advance' => true,
        'document_url' => self::get_document_url( 'add_ons/add-on-announce/' ),
      );

    }

    return $setting_screens;

  }

  public static function mywp_ajax_network_manager() {

    self::add_action_ajax();

  }

  public static function mywp_current_setting_screen_header() {

    ?>

    <h3 class="mywp-setting-screen-subtitle"><?php _e( 'Announce for all sites' , 'mywp-announce' ); ?></h3>

    <?php

  }

  public static function mywp_current_setting_screen_after_footer() {

    if( ! is_multisite() ) {

      return false;

    }

    self::show_plugin_info();

  }

  protected static function delete_transient_controller_get_announces() {

    do_action( 'mywp_setting_announce_multisite_before_delete_transient_controller_get_announces' );

    $mywp_transient = new MywpTransient( 'announce_multisite_get_announces' , 'controller' , true );

    $return = $mywp_transient->remove_data();

    do_action( 'mywp_setting_announce_multisite_after_delete_transient_controller_get_announces' );

    return $return;

  }

}

MywpSettingScreenAnnounceMultisite::init();

endif;
