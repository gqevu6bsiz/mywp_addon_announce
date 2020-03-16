<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpAbstractSettingModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpSettingScreenAnnounceMultisite' ) ) :

final class MywpSettingScreenAnnounceMultisite extends MywpAbstractSettingModule {

  static protected $id = 'announce_multisite';

  static protected $priority = 50;

  static private $menu = 'add_on_announce_multisite';

  static private $post_type = 'mywp_announce_sites';

  static private $current_setting_announce_items;

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

    if( ! MywpAnnounceApi::is_network_manager() ) {

      return false;

    }

    if( ! is_multisite() ) {

      return false;

    }

    add_action( 'wp_ajax_' . MywpSetting::get_ajax_action_name( self::$id , 'check_latest' ) , array( __CLASS__ , 'check_latest' ) );

    add_action( 'wp_ajax_' . MywpSetting::get_ajax_action_name( self::$id , 'add_item' ) , array( __CLASS__ , 'ajax_add_item' ) );

    add_action( 'wp_ajax_' . MywpSetting::get_ajax_action_name( self::$id , 'remove_item' ) , array( __CLASS__ , 'ajax_remove_item' ) );

    add_action( 'wp_ajax_' . MywpSetting::get_ajax_action_name( self::$id , 'update_item' ) , array( __CLASS__ , 'ajax_update_item' ) );

    add_action( 'wp_ajax_' . MywpSetting::get_ajax_action_name( self::$id , 'update_item_order' ) , array( __CLASS__ , 'ajax_update_item_order' ) );

    add_action( 'wp_ajax_' . MywpSetting::get_ajax_action_name( self::$id , 'remove_cache' ) , array( __CLASS__ , 'ajax_remove_cache' ) );

  }

  public static function check_latest() {

    $action_name = MywpSetting::get_ajax_action_name( self::$id , 'check_latest' );

    if( empty( $_POST[ $action_name ] ) ) {

      return false;

    }

    check_ajax_referer( $action_name , $action_name );

    delete_site_transient( 'mywp_announce_updater' );
    delete_site_transient( 'mywp_announce_updater_remote' );

    $is_latest = MywpControllerModuleAnnounceUpdater::is_latest();

    if( is_wp_error( $is_latest ) ) {

      wp_send_json_error( array( 'error' => $is_latest->get_error_message() ) );

    }

    if( ! $is_latest ) {

      wp_send_json_success( array( 'is_latest' => 0 ) );

    } else {

      wp_send_json_success( array( 'is_latest' => 1 , 'message' => sprintf( '<p>%s</p>' , '<span class="dashicons dashicons-yes"></span> ' . __( 'Using a latest version.' , 'mywp-announce' ) ) ) );

    }

  }

  public static function ajax_add_item() {

    $action_name = MywpSetting::get_ajax_action_name( self::$id , 'add_item' );

    if( empty( $_POST[ $action_name ] ) ) {

      return false;

    }

    check_ajax_referer( $action_name , $action_name );

    $add_item = array(
      'post_title' => 'Example Announce',
      'post_content' => 'Example Announce Content',
      'menu_order' => 1000,
    );

    $add_meta_data = array(
      'item_type' => 'default',
      'item_screen' => 'dashboard',
      'item_is_user_roles' => 1,
      'item_user_roles' => array( 'administrator' ),
      'item_is_date_start' => false,
      'item_date_start' => false,
      'item_is_date_end' => false,
      'item_date_end' => false,
      'item_hide_sites' => false,
    );

    $result_html = '';

    $post_id = self::add_post( $add_item , $add_meta_data );

    if ( empty( $post_id ) or is_wp_error( $post_id ) ) {

      return false;

    }

    $switch_blog = self::is_switch_blog();

    $post = MywpPostType::get_post( $post_id );

    do_action( 'mywp_setting_announce_multisite_ajax_add_item' , $post_id , $add_item );

    if( $switch_blog ) {

      restore_current_blog();

    }

    wp_send_json_success();

  }

  public static function ajax_remove_item() {

    $action_name = MywpSetting::get_ajax_action_name( self::$id , 'remove_item' );

    if( empty( $_POST[ $action_name ] ) ) {

      return false;

    }

    check_ajax_referer( $action_name , $action_name );

    if( empty( $_POST['remove_item'] ) ) {

      return false;

    }

    $post_id = intval( $_POST['remove_item'] );

    $switch_blog = self::is_switch_blog();

    $post = MywpPostType::get_post( $post_id );

    if( empty( $post )  or ! is_object( $post ) or $post->post_type !== self::$post_type ) {

      return false;

    }

    wp_delete_post( $post_id , true );

    do_action( 'mywp_setting_announce_multisite_ajax_remove_item' , $post_id );

    if( $switch_blog ) {

      restore_current_blog();

    }

    self::delete_transient_controller_get_announces();

    wp_send_json_success();

  }

  public static function ajax_update_item() {

    $action_name = MywpSetting::get_ajax_action_name( self::$id , 'update_item' );

    if( empty( $_POST[ $action_name ] ) ) {

      return false;

    }

    check_ajax_referer( $action_name , $action_name );

    if( empty( $_POST['update_item'] ) or ! is_array( $_POST['update_item'] ) ) {

      return false;

    }

    $update_item = $_POST['update_item'];

    if( empty( $update_item['item_id'] ) ) {

      return false;

    }

    $post_id = intval( $update_item['item_id'] );

    unset( $update_item['item_id'] );

    $switch_blog = self::is_switch_blog();

    $post = MywpPostType::get_post( $post_id );

    if( empty( $post )  or ! is_object( $post ) or $post->post_type !== self::$post_type ) {

      return false;

    }

    $post_title = wp_unslash( $update_item['post_title'] );

    unset( $update_item['post_title'] );

    $post_content = wp_unslash( $update_item['post_content'] );

    unset( $update_item['post_content'] );

    wp_update_post(
      array(
        'ID' => $post_id,
        'post_title' => $post_title,
        'post_content' => $post_content,
      )
    );

    foreach( $update_item as $meta_key => $meta_value ) {

      $meta_key = wp_unslash( strip_tags( $meta_key ) );

      if( in_array( $meta_key , array( 'item_type' , 'item_screen' , 'item_date_start' , 'item_date_end' ) ) ) {

        $meta_value = strip_tags( $meta_value );
        $meta_value = trim( $meta_value );

      } elseif( in_array( $meta_key , array( 'item_is_user_roles' , 'item_is_date_start' , 'item_is_date_end' ) ) ) {

        $meta_value = strip_tags( $meta_value );
        $meta_value = trim( $meta_value );

        if( $meta_value === 'true' ) {

          $meta_value = 1;

        } else {

          $meta_value = 0;

        }

      } elseif( in_array( $meta_key , array( 'item_user_roles' ) ) ) {

        if( ! empty( $meta_value ) ) {

          foreach( $meta_value as $k => $val ) {

            $meta_value[ $k ] = strip_tags( $val );

          }

        }

      } elseif( in_array( $meta_key , array( 'item_hide_sites' ) ) ) {

        if( ! empty( $meta_value ) ) {

          if( strpos( $meta_value , ',' ) === false ) {

            $meta_value = intval( $meta_value );

          } else {

            $tmp_meta_value = explode( ',' , $meta_value );

            $meta_value = '';

            foreach( $tmp_meta_value as $tmv ) {

              if( empty( $tmv ) ) {

                continue;

              }

              $meta_value .= intval( $tmv ) . ',';

            }

            $meta_value = rtrim( $meta_value , ',' );

          }

        }

      } else {

        continue;

      }

      update_post_meta( $post_id , $meta_key , $meta_value );

    }

    do_action( 'mywp_setting_announce_multisite_ajax_update_item' , $post_id , $update_item );

    if( $switch_blog ) {

      restore_current_blog();

    }

    self::delete_transient_controller_get_announces();

    wp_send_json_success();

  }

  public static function ajax_remove_cache() {

    $action_name = MywpSetting::get_ajax_action_name( self::$id , 'remove_cache' );

    if( empty( $_POST[ $action_name ] ) ) {

      return false;

    }

    check_ajax_referer( $action_name , $action_name );

    self::delete_transient_controller_get_announces();

    wp_send_json_success();

  }

  public static function ajax_update_item_order() {

    $action_name = MywpSetting::get_ajax_action_name( self::$id , 'update_item_order' );

    if( empty( $_POST[ $action_name ] ) ) {

      return false;

    }

    check_ajax_referer( $action_name , $action_name );

    if( empty( $_POST['item_order'] ) or ! is_array( $_POST['item_order'] ) ) {

      return false;

    }

    $saved = false;

    $switch_blog = self::is_switch_blog();

    foreach( $_POST['item_order'] as $key => $post_item ) {

      if( !isset( $post_item['order'] ) or empty( $post_item['item_id'] ) ) {

        continue;

      }

      $post_id = intval( $post_item['item_id'] );

      $post = MywpPostType::get_post( $post_id );

      if( empty( $post )  or ! is_object( $post ) or $post->post_type !== self::$post_type ) {

        continue;

      }

      $menu_order = intval( $post_item['order'] );

      $post_data = array(
        'ID' => $post_id,
        'menu_order' => $menu_order,
        'post_status' => 'publish',
      );

      wp_update_post( $post_data );

      $saved = true;

    }

    if( $switch_blog ) {

      restore_current_blog();

    }

    self::delete_transient_controller_get_announces();

    if( $saved ) {

      wp_send_json_success();

    }

  }

  private static function is_switch_blog() {

    $switch_blog = false;

    if( ! is_main_site() ) {

      switch_to_blog( 1 );

      $switch_blog = true;

    }

    return $switch_blog;

  }

  public static function mywp_current_admin_enqueue_scripts() {

    $scripts = array( 'jquery-ui-sortable' );

    foreach( $scripts as $script ) {

      wp_enqueue_script( $script );

    }

    wp_register_style( 'mywp_announce_admin_setting' , MYWP_ANNOUNCE_PLUGIN_URL . 'assets/css/admin-setting.css' , array() , MYWP_ANNOUNCE_VERSION );

    wp_enqueue_style( 'mywp_announce_admin_setting' );

  }

  public static function mywp_current_setting_screen_header() {

    ?>
    <div id="setting-screen-announce-item-add">

      <button type="button" id="announce-item-add-button" class="button button-primary"><span class="dashicons dashicons-plus"></span> <?php _e( 'Add Announce' , 'mywp-announce' ); ?></button>

      <span class="spinner"></span>

    </div>

    <p>&nbsp;</p>

    <?php

  }

  public static function mywp_current_setting_screen_content() {

    $announce_items = self::find_items();

    ?>
    <h3 class="mywp-setting-screen-subtitle"><?php _e( 'Announcements' , 'mywp-announce' ); ?></h3>

    <p><?php _e( 'Announce for all sites.' , 'mywp-announce' ); ?>

    <p><?php _e( 'Drag announce items to edit and reorder announces.' , 'mywp-announce' ); ?></p>

    <div id="setting-screen-announces">

      <div id="setting-screen-announce-items" class="sortable-items">

        <?php if( ! empty( $announce_items ) ) : ?>

          <?php foreach( $announce_items as $key => $item ) : ?>

            <?php self::print_item( $item ); ?>

          <?php endforeach; ?>

        <?php endif; ?>

      </div>

    </div>

    <p>&nbsp;</p>
    <?php

  }

  public static function mywp_current_setting_screen_advance_content() {

    $setting_data = self::get_setting_data();

    ?>
    <table class="form-table">
      <tbody>
        <tr>
          <th><?php _e( 'Cache Timeout' , 'mywp-announce' ); ?></th>
          <td>
            <label>
              <input type="number" name="mywp[data][cache_timeout]" class="cache_timeout small-text" value="<?php echo esc_attr( $setting_data['cache_timeout'] ); ?>" />
              <?php _e( 'Minute' ); ?>
            </label>
            <button type="button" class="button button-secondary" id="remove-cache"><span class="dashicons dashicons-trash"></span> <?php _e( 'Remove Cache' , 'my-wp' ); ?></button>
            <span class="spinner"></span>
          </td>
        </tr>
      </tbody>
    </table>
    <p>&nbsp;</p>
    <?php

  }

  public static function mywp_current_setting_screen_after_footer() {

    if( ! is_multisite() ) {

      return false;

    }

    $is_latest = MywpControllerModuleAnnounceUpdater::is_latest();

    $have_latest = false;

    if( ! is_wp_error( $is_latest ) && ! $is_latest ) {

      $have_latest = MywpControllerModuleAnnounceUpdater::get_latest();

    }

    $plugin_info = MywpAnnounceApi::plugin_info();

    $class_have_latest = '';

    if( $have_latest ) {

      $class_have_latest = 'have-latest';

    }

    ?>
    <p>&nbsp;</p>
    <h3><?php _e( 'Plugin info' , 'my-wp' ); ?></h3>
    <table class="form-table <?php echo esc_attr( $class_have_latest ); ?>" id="version-check-table">
      <tbody>
        <tr>
          <th><?php printf( __( 'Version %s' ) , '' ); ?></th>
          <td>
            <code><?php echo MYWP_ANNOUNCE_VERSION; ?></code>
            <a href="<?php echo esc_url( $plugin_info['github'] ); ?>" target="_blank" class="button button-primary link-latest"><?php printf( __( 'Get Version %s' ) , $have_latest ); ?></a>
            <p class="already-latest"><span class="dashicons dashicons-yes"></span> <?php _e( 'Using a latest version.' , 'mywp-announce' ); ?></p>
            <br />
          </td>
        </tr>
        <tr>
          <th><?php _e( 'Check latest' , 'mywp-announce' ); ?></th>
          <td>
            <button type="button" id="check-latest-version" class="button button-secondary check-latest"><span class="dashicons dashicons-update"></span> <?php _e( 'Check latest version' , 'mywp-announce' ); ?></button>
            <span class="spinner"></span>
            <div id="check-latest-result"></div>
          </td>
        </tr>
        <tr>
          <th><?php _e( 'Documents' , 'my-wp' ); ?></th>
          <td>
            <a href="<?php echo esc_url( $plugin_info['document_url'] ); ?>" class="button button-secondary" target="_blank"><span class="dashicons dashicons-book"></span> <?php _e( 'Documents' , 'my-wp' ); ?>
          </td>
        </tr>
      </tbody>
    </table>

    <p>&nbsp;</p>
    <?php

  }

  public static function mywp_current_admin_print_footer_scripts() {

?>
<script>
jQuery(document).ready(function($){

  $('#check-latest-version').on('click', function() {

    var $version_check_table = $(this).parent().parent().parent().parent();

    $version_check_table.addClass('checking');

    PostData = {
      action: '<?php echo MywpSetting::get_ajax_action_name( self::$id , 'check_latest' ); ?>',
      <?php echo MywpSetting::get_ajax_action_name( self::$id , 'check_latest' ); ?>: '<?php echo wp_create_nonce( MywpSetting::get_ajax_action_name( self::$id , 'check_latest' ) ); ?>'
    };

    $.ajax({
      type: 'post',
      url: ajaxurl,
      data: PostData
    }).done( function( xhr ) {

      $version_check_table.removeClass('checking');

      if( typeof xhr !== 'object' || xhr.success === undefined ) {

        alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

        return false;

      }

      if( ! xhr.success ) {

        alert( xhr.data.error );

        return false;

      }

      if( xhr.data.is_latest ) {

        $('#check-latest-result').html( xhr.data.message );

        return false;

      }

      $version_check_table.addClass('checking');

      location.reload();

      return true;

    }).fail( function( xhr ) {

      $version_check_table.removeClass('checking');

      alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

      return false;

    });

  });

  $('.sortable-items').sortable({
    placeholder: 'sortable-placeholder',
    handle: '.item-header',
    connectWith: '.sortable-items',
    distance: 2,
    stop: function( ev , ui ) {

      var $sorted_item = ui.item;

      $sorted_item.children().find('> .item-title-wrap .spinner').css('visibility', 'visible');

      var item_order = [];

      $(document).find('#setting-screen-announce-items .setting-screen-announce-item').each( function( index , el ) {

        var $item = $(el)

        var post_id = $item.find('> .item-content .id').val();

        var item_order_parent = { item_id: post_id, order: index };

        item_order.push( item_order_parent );

      });

      if( item_order.length == 0 ) {

        return false;

      }

      PostData = {
        action: '<?php echo MywpSetting::get_ajax_action_name( self::$id , 'update_item_order' ); ?>',
        <?php echo MywpSetting::get_ajax_action_name( self::$id , 'update_item_order' ); ?>: '<?php echo wp_create_nonce( MywpSetting::get_ajax_action_name( self::$id , 'update_item_order' ) ); ?>',
        item_order: item_order
      };

      $.ajax({
        type: 'post',
        url: ajaxurl,
        data: PostData
      }).done( function( xhr ) {

        $sorted_item.children().find('> .item-title-wrap .spinner').css('visibility', 'hidden');

        if( typeof xhr !== 'object' || xhr.success === undefined ) {

          alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

          return false;

        }

        return true;

      }).fail( function( xhr ) {

        $sorted_item.children().find('> .item-title-wrap .spinner').css('visibility', 'hidden');

        alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

        return false;

      });

    }
  });

  $('#announce-item-add-button').on('click', function() {

    var $add_item = $(this).parent();

    PostData = {
      action: '<?php echo MywpSetting::get_ajax_action_name( self::$id , 'add_item' ); ?>',
      <?php echo MywpSetting::get_ajax_action_name( self::$id , 'add_item' ); ?>: '<?php echo wp_create_nonce( MywpSetting::get_ajax_action_name( self::$id , 'add_item' ) ); ?>'
    };

    $add_item.find('.spinner').css('visibility', 'visible');

    $.ajax({
      type: 'post',
      url: ajaxurl,
      data: PostData
    }).done( function( xhr ) {

      $add_item.find('.spinner').css('visibility', 'hidden');

      if( typeof xhr !== 'object' || xhr.success === undefined ) {

        alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

        return false;

      }

      $add_item.find('.spinner').css('visibility', 'visible');

      location.reload();

    }).fail( function( xhr ) {

      $add_item.find('.spinner').css('visibility', 'hidden');

      alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

      return false;

    });

  });

  $(document).on('click', '#setting-screen-announce-items .item-active-toggle', function() {

    $(this).parent().parent().toggleClass('active');

  });

  $(document).on('click', '#setting-screen-announce-items .button-item-content-show-details', function() {

    $(this).parent().parent().toggleClass('show-details');

  });

  $(document).on('click', '#setting-screen-announce-items .item-remove', function() {

    var $item = $(this).parent().parent().parent();

    $item.find('.spinner').css('visibility', 'visible');

    remove_item = $item.find('.item-content-fields .id').val();

    PostData = {
      action: '<?php echo MywpSetting::get_ajax_action_name( self::$id , 'remove_item' ); ?>',
      <?php echo MywpSetting::get_ajax_action_name( self::$id , 'remove_item' ); ?>: '<?php echo wp_create_nonce( MywpSetting::get_ajax_action_name( self::$id , 'remove_item' ) ); ?>',
      remove_item: remove_item
    };

    $.ajax({
      type: 'post',
      url: ajaxurl,
      data: PostData
    }).done( function( xhr ) {

      $item.find('.spinner').css('visibility', 'hidden');

      if( typeof xhr !== 'object' || xhr.success === undefined ) {

        alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

        return false;

      }

      $item.slideUp( 'normal' , function() {

        $item.remove();

      });

    }).fail( function( xhr ) {

      $item.find('.spinner').css('visibility', 'hidden');

      alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

      return false;

    });

  });

  $(document).on('click', '#setting-screen-announce-items .item-update', function() {

    var $item = $(this).parent().parent().parent();
    var $item_content_field = $(this).parent();

    $item_content_field.find('.spinner').css('visibility', 'visible');

    var update_item_id = $item_content_field.find('.id').val();

    var update_item = {
      item_id: $item_content_field.find('.id').val(),
      post_title: $item_content_field.find('.post_title').val(),
      item_type: $item_content_field.find('.item_type').val(),
      item_screen: $item_content_field.find('.item_screen').val(),
      post_content: wp.editor.getContent('post_content_' + update_item_id ),
      item_is_user_roles: $item_content_field.find('.item_is_user_roles').prop('checked'),
      item_user_roles: $item_content_field.find('.item_user_roles').val(),
      item_is_date_start: $item_content_field.find('.item_is_date_start').prop('checked'),
      item_date_start: $item_content_field.find('.item_date_start').val(),
      item_is_date_end: $item_content_field.find('.item_is_date_end').prop('checked'),
      item_date_end: $item_content_field.find('.item_date_end').val(),
      item_hide_sites: $item_content_field.find('.item_hide_sites').val()
    };

    PostData = {
      action: '<?php echo MywpSetting::get_ajax_action_name( self::$id , 'update_item' ); ?>',
      <?php echo MywpSetting::get_ajax_action_name( self::$id , 'update_item' ); ?>: '<?php echo wp_create_nonce( MywpSetting::get_ajax_action_name( self::$id , 'update_item' ) ); ?>',
      update_item: update_item
    };

    $.ajax({
      type: 'post',
      url: ajaxurl,
      data: PostData
    }).done( function( xhr ) {

      $item_content_field.find('.spinner').css('visibility', 'hidden');

      if( typeof xhr !== 'object' || xhr.success === undefined ) {

        alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

        return false;

      }

    }).fail( function( xhr ) {

      $item_content_field.find('.spinner').css('visibility', 'hidden');

      alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

      return false;

    });

  });

  function is_designate_active( $item ) {

    var $item_is_user_roles = $item.find('.item-content-details .form-table .item_is_user_roles');

    var item_is_user_roles = $item_is_user_roles.prop('checked');

    if( item_is_user_roles ) {

      $item_is_user_roles.parent().parent().addClass('active');

    } else {

      $item_is_user_roles.parent().parent().removeClass('active');

    }

    var $item_is_date_start = $item.find('.item-content-details .form-table .item_is_date_start');

    var item_is_date_start = $item_is_date_start.prop('checked');

    if( item_is_date_start ) {

      $item_is_date_start.parent().parent().addClass('active');

    } else {

      $item_is_date_start.parent().parent().removeClass('active');

    }

    var $item_is_date_end = $item.find('.item-content-details .form-table .item_is_date_end');

    var item_is_date_end = $item_is_date_end.prop('checked');

    if( item_is_date_end ) {

      $item_is_date_end.parent().parent().addClass('active');

    } else {

      $item_is_date_end.parent().parent().removeClass('active');

    }

  }

  $('.setting-screen-announce-item').each( function( index , el) {

    is_designate_active( $(el) );

  });

  $(document).on('change', '.item_is_user_roles, .item_is_date_start, .item_is_date_end', function() {

    var $item = $(this).parent().parent().parent().parent().parent().parent().parent().parent().parent();

    is_designate_active( $item );

  });

  $('#remove-cache').on('click', function() {

    var $spinner = $(this).parent().find('.spinner').css('visibility', 'visible');

    PostData = {
      action: '<?php echo MywpSetting::get_ajax_action_name( self::$id , 'remove_cache' ); ?>',
      <?php echo MywpSetting::get_ajax_action_name( self::$id , 'remove_cache' ); ?>: '<?php echo wp_create_nonce( MywpSetting::get_ajax_action_name( self::$id , 'remove_cache' ) ); ?>'
    };

    $.ajax({
      type: 'post',
      url: ajaxurl,
      data: PostData
    }).done( function( xhr ) {

      $spinner.css('visibility', 'hidden');

      if( typeof xhr !== 'object' || xhr.success === undefined ) {

        alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

        return false;

      }

    }).fail( function( xhr ) {

      $spinner.css('visibility', 'hidden');

      alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

      return false;

    });

  });

});
</script>
<?php

  }

  private static function get_current_setting_announce_item_posts() {

    $args = array(
      'post_status' => array( 'publish' , 'draft' ),
      'post_type' => self::$post_type,
      'order' => 'ASC',
      'orderby' => 'menu_order',
      'posts_per_page' => -1,
    );

    $args = apply_filters( 'mywp_setting_announce_multisite_get_current_setting_announce_item_posts_args' , $args );

    $switch_blog = self::is_switch_blog();

    $current_setting_announce_item_posts = MywpSetting::get_posts( $args );

    if( $switch_blog ) {

      restore_current_blog();

    }

    return $current_setting_announce_item_posts;

  }

  private static function get_current_setting_announce_items() {

    if( ! empty( self::$current_setting_announce_items ) ) {

      return self::$current_setting_announce_items;

    }

    $current_setting_announce_items = self::get_current_setting_announce_item_posts();

    if( empty( $current_setting_announce_items ) ) {

      return false;

    }

    self::$current_setting_announce_items = apply_filters( 'mywp_setting_announce_multisite_get_current_setting_announce_items' , $current_setting_announce_items );

    return $current_setting_announce_items;

  }

  private static function find_items() {

    $current_setting_announce_items = self::get_current_setting_announce_items();

    return $current_setting_announce_items;

  }

  private static function print_item( $item = false ) {

    if( empty( $item ) or ! is_object( $item ) ) {

      return false;

    }

    ?>

    <div class="setting-screen-announce-item item-id-<?php echo esc_attr( $item->ID ); ?>">

      <?php self::print_item_header( $item ); ?>

      <?php self::print_item_content( $item ); ?>

      <?php do_action( 'mywp_setting_announce_multisite_print_item' , $item ); ?>

    </div>

    <?php

  }

  private static function print_item_header( $item ) {

    $pre_add_title = apply_filters( 'mywp_setting_announce_multisite_print_item_header_pre_add_title' , '' , $item );

    $pre_title = apply_filters( 'mywp_setting_announce_multisite_print_item_header_pre_title' , '' , $item );

    ?>

    <div class="item-header">

      <a href="javascript:void(0);" class="item-active-toggle">&nbsp;</a>

      <div class="item-title-wrap">

        <?php echo $pre_add_title; ?>

        <?php if( ! empty( $pre_title ) ) : ?>

          <?php echo $pre_title; ?>

        <?php else : ?>

          <?php if( $item->post_status !== 'publish' ) : ?>

            <span class="item-state"><?php _post_states( $item ); ?></span>

          <?php endif; ?>

          <span class="item-type type-<?php echo esc_attr( $item->item_type ); ?>"></span>

          <span class="item-title"><?php echo strip_tags( strip_shortcodes( $item->post_title ) ); ?></span>

        <?php endif; ?>

        <span class="spinner"></span>

      </div>

    </div>

    <?php

  }

  private static function print_item_content( $item ) {

    $item_type = $item->item_type;

    ?>

    <div class="item-content item-type-<?php echo esc_attr( $item_type ); ?>">

      <div class="item-content-fields">

        <?php self::print_item_content_field( 'id' , $item->ID , $item ); ?>
        <?php self::print_item_content_field( 'menu_order' , $item->menu_order , $item ); ?>

        <?php do_action( 'mywp_setting_announce_multisite_print_item_content' , $item ); ?>

        <div class="item-content-hidden-fields">

        </div>

        <table class="form-table">
          <tbody>
            <tr>
              <th><?php _e( 'Announce Title' , 'mywp-announce' ); ?></th>
              <td>
                <?php self::print_item_content_field( 'post_title' , $item->post_title , $item ); ?>
              </td>
            </tr>
            <tr>
              <th><?php _e( 'Announce Type' , 'mywp-announce' ); ?></th>
              <td>
                <?php self::print_item_content_field( 'item_type' , $item->item_type , $item ); ?>
              </td>
            </tr>
            <tr>
              <th><?php _e( 'Announce Screen' , 'mywp-announce' ); ?></th>
              <td>
                <?php self::print_item_content_field( 'item_screen' , $item->item_screen , $item ); ?>
              </td>
            </tr>
            <tr>
              <th><?php _e( 'Announce Content' , 'mywp-announce' ); ?></th>
              <td>
                <?php self::print_item_content_field( 'post_content' , $item->post_content , $item ); ?>
              </td>
            </tr>
            <tr>
              <th><?php _e( 'Announce Hide Sites' , 'mywp-announce' ); ?></th>
              <td>
                <?php self::print_item_content_field( 'item_hide_sites' , $item->item_hide_sites , $item ); ?>
              </td>
            </tr>
          </tbody>
        </table>

        <p class="item-content-show-details"><a href="javascript:void(0);" class="button-item-content-show-details"><?php _e( 'More Details' ); ?></a></p>

        <div class="item-content-details">

          <table class="form-table">
            <tbody>
              <tr>
                <th><?php _e( 'User Roles' ); ?></th>
                <td>
                  <?php self::print_item_content_field( 'item_is_user_roles' , $item->item_is_user_roles , $item ); ?>
                  <?php self::print_item_content_field( 'item_user_roles' , $item->item_user_roles , $item ); ?>
                </td>
              </tr>
              <tr>
                <th><?php _e( 'Start Date' , 'mywp-announce' ); ?></th>
                <td>
                  <?php self::print_item_content_field( 'item_is_date_start' , $item->item_is_date_start , $item ); ?>
                  <?php self::print_item_content_field( 'item_date_start' , $item->item_date_start , $item ); ?>
                </td>
              </tr>
              <tr>
                <th><?php _e( 'End Date' , 'mywp-announce' ); ?></th>
                <td>
                  <?php self::print_item_content_field( 'item_is_date_end' , $item->item_is_date_end , $item ); ?>
                  <?php self::print_item_content_field( 'item_date_end' , $item->item_date_end , $item ); ?>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <?php do_action( 'mywp_setting_announce_multisite_print_item_content_after' , $item ); ?>

        <div class="clear"></div>

        <a href="javascript:void(0);" class="item-update button button-primary"><?php _e( 'Update' ); ?></a>

        <a href="javascript:void(0);" class="item-remove button button-secondary button-caution"><span class="dashicons dashicons-no-alt"></span> <?php _e( 'Remove' ); ?></a>

        <span class="spinner"></span>

      </div>

    </div>

    <?php

  }

  private static function print_item_content_field( $field_name = false , $value = '' , $item = false , $args = false ) {

    if( empty( $field_name ) or ! is_object( $item ) ) {

      return false;

    }

    $all_user_roles = MywpAnnounceApi::get_all_user_roles();

    $announce_types = MywpAnnounceApi::get_announce_types();

    $announce_screens = MywpAnnounceApi::get_announce_screens();

    $all_sites = MywpAnnounceApi::get_all_sites();

    $field_name = strip_tags( $field_name );

    if( $field_name === 'id' ) {

      printf( '<input type="hidden" class="id" value="%s" />' , esc_attr( $value ) );

    } elseif( $field_name === 'menu_order' ) {

      printf( '<input type="hidden" class="menu_order" value="%d" placeholder="0" />' , esc_attr( $value ) );

    } elseif( $field_name === 'post_title' ) {

      printf( '<input type="text" class="post_title large-text" value="%s" placeholder="%s" />' , esc_attr( $value ) , esc_attr( __( 'Announce Title' , 'mywp-announce' ) ) );

    } elseif( $field_name === 'post_content' ) {

      echo wp_editor( $value , 'post_content_' . $item->ID , array( 'editor_class' => 'post_content' , 'media_buttons' => false ) );

    } elseif( $field_name === 'item_type' ) {

      echo '<select class="item_type">';

      foreach( $announce_types as $type => $announce_type ) {

        printf( '<option value="%s" %s>[%s] %s</option>' , esc_attr( $type ) , selected( $type , $value , false ) , esc_attr( $announce_type['color'] ) , esc_attr( $announce_type['label'] ) );

      }

      echo '</select>';

    } elseif( $field_name === 'item_screen' ) {

      echo '<select class="item_screen">';

      foreach( $announce_screens as $screen => $announce_screen ) {

        printf( '<option value="%s" %s>%s</option>' , esc_attr( $screen ) , selected( $screen , $value , false ) , esc_attr( $announce_screen['label'] ) );

      }

      echo '</select>';

    } elseif( $field_name === 'item_is_user_roles' ) {

      echo '<label>';

      printf( '<input type="checkbox" class="item_is_user_roles" value="1" %s />' , checked( 1 , $value , false ) );

      _e( 'Designate' , 'mywp-announce' );

      echo '</label>';

    } elseif( $field_name === 'item_user_roles' ) {

      echo '<div class="designate-detail">';

      echo '<select class="item_user_roles" multiple="multiple">';

      foreach( $all_user_roles as $user_role => $user_role_data ) {

        $selected = false;

        if( ! empty( $value ) && in_array( $user_role , $value ) ) {

          $selected = true;

        }

        printf( '<option value="%s" %s>%s</option>' , esc_attr( $user_role ) , selected( $selected , true , false ) , esc_attr( $user_role_data['label'] ) );

      }

      echo '</select>';

      echo '</div>';

    } elseif( $field_name === 'item_is_date_start' ) {

      echo '<label>';

      printf( '<input type="checkbox" class="item_is_date_start" value="1" %s />' , checked( 1 , $value , false ) );

      _e( 'Designate' , 'mywp-announce' );

      echo '</label>';

    } elseif( $field_name === 'item_date_start' ) {

      echo '<div class="designate-detail">';

      printf( '<input type="text" class="item_date_start regular-text" value="%s" placeholder="YYYY-MM-DD HH:MM:SS" />' , esc_attr( $value ) );

      echo '</div>';

    } elseif( $field_name === 'item_is_date_end' ) {

      echo '<label>';

      printf( '<input type="checkbox" class="item_is_date_end" value="1" %s />' , checked( 1 , $value , false ) );

      _e( 'Designate' , 'mywp-announce' );

      echo '</label>';

    } elseif( $field_name === 'item_date_end' ) {

      echo '<div class="designate-detail">';

      printf( '<input type="text" class="item_date_end regular-text" value="%s" placeholder="YYYY-MM-DD HH:MM:SS" />' , esc_attr( $value ) );

      echo '</div>';

    } elseif( $field_name === 'item_hide_sites' ) {

      printf( '<p class="mywp-description">%s</p>' , __( 'Please enter the Blogs ID with comma if you want hide announce.' , 'mywp-announce' ) );

      printf( '<input type="text" class="item_hide_sites large-text" value="%s" placeholder="%s" />' , esc_attr( $value ) , esc_attr( $item->item_hide_sites ) );

      if( ! empty( $item->item_hide_sites ) ) {

        $item_hide_sites = explode( ',' , $item->item_hide_sites );

        echo '<ul>';

        foreach( $item_hide_sites as $site_id ) {

          echo '<li>';

          $blog_detail = get_blog_details( $site_id );

          if( ! empty( $blog_detail ) ) {

            printf( '[%d] %s' , $site_id , $blog_detail->blogname );

          } else {

            printf( '[%d] %s' , $site_id , __( 'Not found Blog ID' , 'mywp-announce' ) );

          }

          echo '</li>';

        }

        echo '</ul>';

      }

    } else {

      do_action( 'mywp_setting_announce_multisite_print_item_content_field' , $field_name , $value , $item );

    }

  }

  private static function add_post( $args = array() , $post_metas = array() ) {

    global $wpdb;

    $default_args = array(
      'post_type' => self::$post_type,
      'post_status' => 'draft',
      'post_parent' => 0,
    );

    $post = wp_parse_args( $args , $default_args );

    $switch_blog = self::is_switch_blog();

    $post_id = wp_insert_post( $post );

    if ( empty( $post_id ) or is_wp_error( $post_id ) ) {

      return $post_id;

    }

    $post_id = intval( $post_id );

    $post_metas = apply_filters( 'mywp_setting_announce_multisitei_add_post_metas' , $post_metas , $args );

    if( ! empty( $post_metas ) ) {

      $add_meta_data = array();

      foreach( $post_metas as $meta_key => $meta_value ) {

        $meta_key = strip_tags( $meta_key );

        $add_meta_data[] = $wpdb->prepare( "(NULL, %d, %s, %s)" , $post_id , wp_unslash( $meta_key ) , maybe_serialize( wp_unslash( $meta_value ) ) );

      }

      $query = "INSERT INTO $wpdb->postmeta (meta_id, post_id, meta_key, meta_value) VALUES " . join( ',' , $add_meta_data );

      $wpdb->query( $query );

    }

    if( $switch_blog ) {

      restore_current_blog();

    }

    return $post_id;

  }

  private static function delete_transient_controller_get_announces() {

    do_action( 'mywp_setting_announce_multisite_before_delete_transient_controller_get_announces' );

    $mywp_transient = new MywpTransient( 'announce_multisite_get_announces' , 'controller' , true );

    $return = $mywp_transient->remove_data();

    do_action( 'mywp_setting_announce_multisite_after_delete_transient_controller_get_announces' );

    return $return;

  }

  public static function mywp_current_setting_post_data_format_update( $formatted_data ) {

    $mywp_model = self::get_model();

    if( empty( $mywp_model ) ) {

      return $formatted_data;

    }

    $new_formatted_data = $mywp_model->get_initial_data();

    $new_formatted_data['advance'] = $formatted_data['advance'];

    if( ! empty( $formatted_data['cache_timeout'] ) ) {

      $new_formatted_data['cache_timeout'] = intval( $formatted_data['cache_timeout'] );

    }

    self::delete_transient_controller_get_announces();

    $current_setting_announce_item_posts = self::get_current_setting_announce_item_posts();

    if( empty( $current_setting_announce_item_posts ) ) {

      return false;

    }

    $switch_blog = self::is_switch_blog();

    foreach( $current_setting_announce_item_posts as $key => $current_setting_announce_item_post ) {

      $post_id = $current_setting_announce_item_post->ID;

      $post = MywpPostType::get_post( $post_id );

      if( empty( $post )  or ! is_object( $post ) or $post->post_type !== self::$post_type ) {

        continue;

      }

      $post = array(
        'ID' => $post_id,
        'post_status' => 'publish',
      );

      wp_update_post( $post );

    }

    if( $switch_blog ) {

      restore_current_blog();

    }

    return $new_formatted_data;

  }

  public static function mywp_current_setting_before_post_data_action_remove( $validated_data ) {

    if( empty( $validated_data['remove'] ) ) {

      return false;

    }

    self::delete_transient_controller_get_announces();

    $current_setting_announce_item_posts = self::get_current_setting_announce_item_posts();

    if( empty( $current_setting_announce_item_posts ) ) {

      return false;

    }

    $switch_blog = self::is_switch_blog();

    foreach( $current_setting_announce_item_posts as $key => $current_setting_announce_item_post ) {

      $post_id = $current_setting_announce_item_post->ID;

      $post = MywpPostType::get_post( $post_id );

      if( empty( $post )  or ! is_object( $post ) or $post->post_type !== self::$post_type ) {

        continue;

      }

      wp_delete_post( $post_id );

    }

    if( $switch_blog ) {

      restore_current_blog();

    }

  }

}

MywpSettingScreenAnnounceMultisite::init();

endif;
