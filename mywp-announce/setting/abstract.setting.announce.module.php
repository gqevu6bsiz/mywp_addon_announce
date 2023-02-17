<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpAbstractSettingModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpAbstractSettingAnnounceModule' ) ) :

abstract class MywpAbstractSettingAnnounceModule extends MywpAbstractSettingModule {

  static protected $priority = 50;

  static protected $current_setting_announce_items;

  protected static function add_action_ajax() {

    $class = get_called_class();

    add_action( 'wp_ajax_' . MywpSetting::get_ajax_action_name( static::$id , 'check_latest' ) , array( $class , 'check_latest' ) );

    add_action( 'wp_ajax_' . MywpSetting::get_ajax_action_name( static::$id , 'add_item' ) , array( $class , 'ajax_add_item' ) );

    add_action( 'wp_ajax_' . MywpSetting::get_ajax_action_name( static::$id , 'remove_item' ) , array( $class , 'ajax_remove_item' ) );

    add_action( 'wp_ajax_' . MywpSetting::get_ajax_action_name( static::$id , 'update_item' ) , array( $class , 'ajax_update_item' ) );

    add_action( 'wp_ajax_' . MywpSetting::get_ajax_action_name( static::$id , 'update_item_order' ) , array( $class , 'ajax_update_item_order' ) );

    add_action( 'wp_ajax_' . MywpSetting::get_ajax_action_name( static::$id , 'remove_cache' ) , array( $class , 'ajax_remove_cache' ) );


  }

  public static function check_latest() {

    $action_name = MywpSetting::get_ajax_action_name( static::$id , 'check_latest' );

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

      $message = sprintf( '<p>%s</p>' , '<span class="dashicons dashicons-yes"></span> ' . __( 'Using a latest version.' , 'mywp-announce' ) );

      wp_send_json_success( array( 'is_latest' => 1 , 'message' => $message ) );

    }

  }

  public static function ajax_add_item() {

    $action_name = MywpSetting::get_ajax_action_name( static::$id , 'add_item' );

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
      'item_add_class' => '',
      'item_is_user_roles' => 1,
      'item_user_roles' => array( 'administrator' ),
      'item_is_date_start' => false,
      'item_date_start' => false,
      'item_is_date_end' => false,
      'item_date_end' => false,
      'item_hide_sites' => false,
    );

    $post_id = static::add_post( $add_item , $add_meta_data );

    if ( empty( $post_id ) or is_wp_error( $post_id ) ) {

      return false;

    }

    do_action( 'mywp_setting_announce_ajax_add_item' , $post_id );

    wp_send_json_success();

  }

  public static function ajax_remove_item() {

    $action_name = MywpSetting::get_ajax_action_name( static::$id , 'remove_item' );

    if( empty( $_POST[ $action_name ] ) ) {

      return false;

    }

    check_ajax_referer( $action_name , $action_name );

    if( empty( $_POST['remove_item'] ) ) {

      return false;

    }

    $post_id = intval( $_POST['remove_item'] );

    $post = static::get_post( $post_id );

    if( empty( $post )  or ! is_object( $post ) or $post->post_type !== static::$post_type ) {

      return false;

    }

    static::delete_post( $post_id );

    do_action( 'mywp_setting_announce_ajax_remove_item' , $post_id );

    static::delete_transient_controller_get_announces();

    wp_send_json_success();

  }

  public static function ajax_update_item() {

    $action_name = MywpSetting::get_ajax_action_name( static::$id , 'update_item' );

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

    $post = static::get_post( $post_id );

    if( empty( $post )  or ! is_object( $post ) or $post->post_type !== static::$post_type ) {

      return false;

    }

    $post_title = wp_unslash( $update_item['post_title'] );

    unset( $update_item['post_title'] );

    $post_content = wp_unslash( $update_item['post_content'] );

    unset( $update_item['post_content'] );

    $update_post = array(
      'ID' => $post_id,
      'post_title' => $post_title,
      'post_content' => $post_content,
    );

    static::update_post( $update_post );

    foreach( $update_item as $meta_key => $meta_value ) {

      $meta_key = wp_unslash( strip_tags( $meta_key ) );

      if( in_array( $meta_key , array( 'item_type' , 'item_screen' , 'item_add_class' , 'item_date_start' , 'item_date_end' ) , true ) ) {

        $meta_value = strip_tags( $meta_value );

        $meta_value = trim( $meta_value );

      } elseif( in_array( $meta_key , array( 'item_is_user_roles' , 'item_is_date_start' , 'item_is_date_end' ) , true ) ) {

        $meta_value = strip_tags( $meta_value );

        $meta_value = trim( $meta_value );

        if( $meta_value === 'true' ) {

          $meta_value = 1;

        } else {

          $meta_value = 0;

        }

      } elseif( in_array( $meta_key , array( 'item_user_roles' ) , true ) ) {

        if( ! empty( $meta_value ) ) {

          foreach( $meta_value as $k => $val ) {

            $meta_value[ $k ] = strip_tags( $val );

          }

        }

      } elseif( in_array( $meta_key , array( 'item_hide_sites' ) , true ) ) {

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

      static::update_post_meta( $post_id , $meta_key , $meta_value );

    }

    do_action( 'mywp_setting_announce_ajax_update_item' , $post_id , $update_item );

    static::delete_transient_controller_get_announces();

    wp_send_json_success();

  }

  public static function ajax_remove_cache() {

    $action_name = MywpSetting::get_ajax_action_name( static::$id , 'remove_cache' );

    if( empty( $_POST[ $action_name ] ) ) {

      return false;

    }

    check_ajax_referer( $action_name , $action_name );

    static::delete_transient_controller_get_announces();

    wp_send_json_success();

  }

  public static function ajax_update_item_order() {

    $action_name = MywpSetting::get_ajax_action_name( static::$id , 'update_item_order' );

    if( empty( $_POST[ $action_name ] ) ) {

      return false;

    }

    check_ajax_referer( $action_name , $action_name );

    if( empty( $_POST['item_order'] ) or ! is_array( $_POST['item_order'] ) ) {

      return false;

    }

    $saved = false;

    foreach( $_POST['item_order'] as $key => $post_item ) {

      if( ! isset( $post_item['order'] ) or empty( $post_item['item_id'] ) ) {

        continue;

      }

      $post_id = intval( $post_item['item_id'] );

      $post = static::get_post( $post_id );

      if( empty( $post )  or ! is_object( $post ) or $post->post_type !== static::$post_type ) {

        continue;

      }

      $menu_order = intval( $post_item['order'] );

      $update_post = array(
        'ID' => $post_id,
        'menu_order' => $menu_order,
        'post_status' => 'publish',
      );

      static::update_post( $update_post );

      $saved = true;

    }

    static::delete_transient_controller_get_announces();

    if( $saved ) {

      wp_send_json_success();

    }

  }

  public static function mywp_current_admin_enqueue_scripts() {

    $scripts = array( 'jquery-ui-sortable' );

    foreach( $scripts as $script ) {

      wp_enqueue_script( $script );

    }

    wp_register_style( 'mywp_announce_admin_setting' , MYWP_ANNOUNCE_PLUGIN_URL . 'assets/css/admin-setting.css' , array() , MYWP_ANNOUNCE_VERSION );

    wp_enqueue_style( 'mywp_announce_admin_setting' );

  }

  public static function mywp_current_setting_screen_content() {

    $current_setting_announce_items = static::get_current_setting_announce_items();

    ?>

    <?php if( empty( $current_setting_announce_items ) ) : ?>

      <p><?php _e( 'Announces not found. You can Add Announce.' , 'mywp-announce' ); ?></p>

    <?php endif; ?>

    <div id="setting-screen-announce-item-add">

      <button type="button" id="announce-item-add-button" class="button button-primary">
        <span class="dashicons dashicons-plus"></span> <?php _e( 'Add Announce' , 'mywp-announce' ); ?>
      </button>

      <span class="spinner"></span>

    </div>

    <?php if( ! empty( $current_setting_announce_items ) ) : ?>

      <p><?php _e( 'Drag announce items to edit and reorder announces.' , 'mywp-announce' ); ?></p>

      <div id="setting-screen-announces">

        <div id="setting-screen-announce-items" class="sortable-items">

          <?php foreach( $current_setting_announce_items as $key => $item ) : ?>

            <?php static::print_item( $item ); ?>

          <?php endforeach; ?>

        </div>

      </div>

    <?php endif; ?>

    <p>&nbsp;</p>

    <?php

  }

  public static function mywp_current_setting_screen_advance_content() {

    $setting_data = static::get_setting_data();

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

  protected static function show_plugin_info() {

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

  protected static function get_current_setting_announce_item_posts() {

    $args = array(
      'post_status' => array( 'publish' , 'draft' ),
      'post_type' => static::$post_type,
      'order' => 'ASC',
      'orderby' => 'menu_order',
      'posts_per_page' => -1,
    );

    $args = apply_filters( 'mywp_setting_announce_get_current_setting_announce_item_posts_args' , $args );

    $current_setting_announce_item_posts = static::get_posts( $args );

    return $current_setting_announce_item_posts;

  }

  protected static function get_current_setting_announce_items() {

    if( ! empty( static::$current_setting_announce_items ) ) {

      return static::$current_setting_announce_items;

    }

    $current_setting_announce_items = static::get_current_setting_announce_item_posts();

    if( empty( $current_setting_announce_items ) ) {

      return false;

    }

    static::$current_setting_announce_items = apply_filters( 'mywp_setting_announce_get_current_setting_announce_items' , $current_setting_announce_items );

    return $current_setting_announce_items;

  }

  public static function mywp_current_admin_print_footer_scripts() {

    ?>
    <script>
    jQuery(document).ready(function($) {

      $('#check-latest-version').on('click', function() {

        let $version_check_table = $(this).parent().parent().parent().parent();

        $version_check_table.addClass('checking');

        PostData = {
          action: '<?php echo esc_js( MywpSetting::get_ajax_action_name( static::$id , 'check_latest' ) ); ?>',
          <?php echo esc_js( MywpSetting::get_ajax_action_name( static::$id , 'check_latest' ) ); ?>: '<?php echo esc_js( wp_create_nonce( MywpSetting::get_ajax_action_name( static::$id , 'check_latest' ) ) ); ?>'
        };

        $.ajax({
          type: 'post',
          url: ajaxurl,
          data: PostData
        }).done( function( xhr ) {

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

          alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

          return false;

        }).always( function( xhr ) {

          $version_check_table.removeClass('checking');

        });

      });

      $('.sortable-items').sortable({
        placeholder: 'sortable-placeholder',
        handle: '.item-header',
        connectWith: '.sortable-items',
        distance: 2,
        stop: function( ev , ui ) {

          let $sorted_item = ui.item;

          $sorted_item.children().find('> .item-title-wrap .spinner').css('visibility', 'visible');

          let item_order = [];

          $(document).find('#setting-screen-announce-items .setting-screen-announce-item').each( function( index , el ) {

            let $item = $(el)

            let post_id = $item.find('> .item-content .id').val();

            let item_order_parent = { item_id: post_id, order: index };

            item_order.push( item_order_parent );

          });

          if( item_order.length == 0 ) {

            return false;

          }

          PostData = {
            action: '<?php echo esc_js( MywpSetting::get_ajax_action_name( static::$id , 'update_item_order' ) ); ?>',
            <?php echo esc_js( MywpSetting::get_ajax_action_name( static::$id , 'update_item_order' ) ); ?>: '<?php echo esc_js( wp_create_nonce( MywpSetting::get_ajax_action_name( static::$id , 'update_item_order' ) ) ); ?>',
            item_order: item_order
          };

          $.ajax({
            type: 'post',
            url: ajaxurl,
            data: PostData
          }).done( function( xhr ) {

            if( typeof xhr !== 'object' || xhr.success === undefined ) {

              alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

              return false;

            }

            return true;

          }).fail( function( xhr ) {

            alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

            return false;

          }).always( function( xhr ) {

            $sorted_item.children().find('> .item-title-wrap .spinner').css('visibility', 'hidden');

          });

        }
      });

      $('#announce-item-add-button').on('click', function() {

        let $add_item = $(this).parent();

        PostData = {
          action: '<?php echo esc_js( MywpSetting::get_ajax_action_name( static::$id , 'add_item' ) ); ?>',
          <?php echo esc_js( MywpSetting::get_ajax_action_name( static::$id , 'add_item' ) ); ?>: '<?php echo esc_js( wp_create_nonce( MywpSetting::get_ajax_action_name( static::$id , 'add_item' ) ) ); ?>'
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

        let $item = $(this).parent().parent().parent();

        $item.find('.spinner').css('visibility', 'visible');

        remove_item = $item.find('.item-content-fields .id').val();

        PostData = {
          action: '<?php echo esc_js( MywpSetting::get_ajax_action_name( static::$id , 'remove_item' ) ); ?>',
          <?php echo esc_js( MywpSetting::get_ajax_action_name( static::$id , 'remove_item' ) ); ?>: '<?php echo esc_js( wp_create_nonce( MywpSetting::get_ajax_action_name( static::$id , 'remove_item' ) ) ); ?>',
          remove_item: remove_item
        };

        $.ajax({
          type: 'post',
          url: ajaxurl,
          data: PostData
        }).done( function( xhr ) {

          if( typeof xhr !== 'object' || xhr.success === undefined ) {

            alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

            return false;

          }

          $item.slideUp( 'normal' , function() {

            $item.remove();

          });

        }).fail( function( xhr ) {

          alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

          return false;

        }).always( function( xhr ) {

          $item.find('.spinner').css('visibility', 'hidden');

        });

      });

      $(document).on('click', '#setting-screen-announce-items .item-update', function() {

        let $item = $(this).parent().parent().parent();
        let $item_content_field = $(this).parent();

        $item_content_field.find('.spinner').css('visibility', 'visible');

        let update_item_id = $item_content_field.find('.id').val();

        let update_item = {
          item_id: $item_content_field.find('.id').val(),
          post_title: $item_content_field.find('.post_title').val(),
          item_type: $item_content_field.find('.item_type').val(),
          item_screen: $item_content_field.find('.item_screen').val(),
          item_add_class: $item_content_field.find('.item_add_class').val(),
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
          action: '<?php echo esc_js( MywpSetting::get_ajax_action_name( static::$id , 'update_item' ) ); ?>',
          <?php echo esc_js( MywpSetting::get_ajax_action_name( static::$id , 'update_item' ) ); ?>: '<?php echo esc_js( wp_create_nonce( MywpSetting::get_ajax_action_name( static::$id , 'update_item' ) ) ); ?>',
          update_item: update_item
        };

        $.ajax({
          type: 'post',
          url: ajaxurl,
          data: PostData
        }).done( function( xhr ) {

          if( typeof xhr !== 'object' || xhr.success === undefined ) {

            alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

            return false;

          }

        }).fail( function( xhr ) {

          alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

          return false;

        }).always( function( xhr ) {

          $item_content_field.find('.spinner').css('visibility', 'hidden');

        });

      });

      function is_designate_active( $item ) {

        $item.removeClass('active-user-roles');

        let $item_is_user_roles = $item.find('.item-content-details .form-table .item_is_user_roles');

        let item_is_user_roles = $item_is_user_roles.prop('checked');

        if( item_is_user_roles ) {

          $item_is_user_roles.parent().parent().addClass('active');

          $item.addClass('active-user-roles');

        } else {

          $item_is_user_roles.parent().parent().removeClass('active');

        }

        $item.removeClass('active-date');

        let $item_is_date_start = $item.find('.item-content-details .form-table .item_is_date_start');

        let item_is_date_start = $item_is_date_start.prop('checked');

        if( item_is_date_start ) {

          $item_is_date_start.parent().parent().addClass('active');

          $item.addClass('active-date');

        } else {

          $item_is_date_start.parent().parent().removeClass('active');

        }

        let $item_is_date_end = $item.find('.item-content-details .form-table .item_is_date_end');

        let item_is_date_end = $item_is_date_end.prop('checked');

        if( item_is_date_end ) {

          $item_is_date_end.parent().parent().addClass('active');

          $item.addClass('active-date');

        } else {

          $item_is_date_end.parent().parent().removeClass('active');

        }

        $item.removeClass('active-hide-sites');

        let $item_is_hide_sites = $item.find('.hide-sites-setting .item_hide_sites');

        let item_hide_sites = $item_is_hide_sites.val();

        if( item_hide_sites ) {

          $item.addClass('active-hide-sites');

        }

      }

      $('.setting-screen-announce-item').each( function( index , el) {

        is_designate_active( $(el) );

      });

      $(document).on('change', '.item_is_user_roles, .item_is_date_start, .item_is_date_end', function() {

        let $item = $(this).parent().parent().parent().parent().parent().parent().parent().parent().parent();

        is_designate_active( $item );

      });

      $('#remove-cache').on('click', function() {

        let $spinner = $(this).parent().find('.spinner').css('visibility', 'visible');

        PostData = {
          action: '<?php echo esc_js( MywpSetting::get_ajax_action_name( static::$id , 'remove_cache' ) ); ?>',
          <?php echo esc_js( MywpSetting::get_ajax_action_name( static::$id , 'remove_cache' ) ); ?>: '<?php echo esc_js( wp_create_nonce( MywpSetting::get_ajax_action_name( static::$id , 'remove_cache' ) ) ); ?>'
        };

        $.ajax({
          type: 'post',
          url: ajaxurl,
          data: PostData
        }).done( function( xhr ) {

          if( typeof xhr !== 'object' || xhr.success === undefined ) {

            alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

            return false;

          }

        }).fail( function( xhr ) {

          alert( '<?php _e( 'An error has occurred. Please reload the page and try again.' ); ?>' );

          return false;

        }).always( function( xhr ) {

          $spinner.css('visibility', 'hidden');

        });

      });

    });
    </script>
    <?php

  }

  protected static function print_item( $item = false ) {

    if( empty( $item ) or ! is_object( $item ) ) {

      return false;

    }

    ?>

    <div class="setting-screen-announce-item item-id-<?php echo esc_attr( $item->ID ); ?>">

      <?php static::print_item_header( $item ); ?>

      <?php static::print_item_content( $item ); ?>

      <?php do_action( 'mywp_setting_announce_print_item' , $item ); ?>

    </div>

    <?php

  }

  protected static function print_item_header( $item ) {

    $pre_add_title = apply_filters( 'mywp_setting_announce_print_item_header_pre_add_title' , '' , $item );

    $pre_title = apply_filters( 'mywp_setting_announce_print_item_header_pre_title' , '' , $item );

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

          <span class="icons">

            <span class="dashicons dashicons-admin-users"></span>

            <span class="dashicons dashicons-clock"></span>

            <span class="dashicons dashicons-networking"></span>

          </span>

          <span class="item-type type-<?php echo esc_attr( $item->item_type ); ?>"></span>

          <span class="item-title"><?php echo strip_tags( strip_shortcodes( $item->post_title ) ); ?></span>

        <?php endif; ?>

        <span class="spinner"></span>

      </div>

    </div>

    <?php

  }

  protected static function print_item_content( $item ) {

    $item_type = $item->item_type;

    ?>

    <div class="item-content item-type-<?php echo esc_attr( $item_type ); ?>">

      <div class="item-content-fields">

        <?php static::print_item_content_field( 'id' , $item->ID , $item ); ?>
        <?php static::print_item_content_field( 'menu_order' , $item->menu_order , $item ); ?>

        <?php do_action( 'mywp_setting_announce_print_item_content' , $item ); ?>

        <div class="item-content-hidden-fields">

        </div>

        <table class="form-table">
          <tbody>
            <tr>
              <th><?php _e( 'Announce Title' , 'mywp-announce' ); ?></th>
              <td>
                <?php static::print_item_content_field( 'post_title' , $item->post_title , $item ); ?>
              </td>
            </tr>
            <tr>
              <th><?php _e( 'Announce Type' , 'mywp-announce' ); ?></th>
              <td>
                <?php static::print_item_content_field( 'item_type' , $item->item_type , $item ); ?>
              </td>
            </tr>
            <tr>
              <th><?php _e( 'Announce Screen' , 'mywp-announce' ); ?></th>
              <td>
                <?php static::print_item_content_field( 'item_screen' , $item->item_screen , $item ); ?>
              </td>
            </tr>
            <tr>
              <th><?php _e( 'Announce Content' , 'mywp-announce' ); ?></th>
              <td>
                <?php static::print_item_content_field( 'post_content' , $item->post_content , $item ); ?>
              </td>
            </tr>
          </tbody>
        </table>

        <p class="item-content-show-details"><a href="javascript:void(0);" class="button-item-content-show-details"><?php _e( 'More Details' ); ?></a></p>

        <div class="item-content-details">

          <table class="form-table">
            <tbody>
              <tr>
                <th><?php _e( 'Add Class' , 'mywp-announce' ); ?></th>
                <td>
                  <?php static::print_item_content_field( 'item_add_class' , $item->item_add_class , $item ); ?>
                </td>
              </tr>
              <tr>
                <th><?php _e( 'User Roles' ); ?></th>
                <td>
                  <?php static::print_item_content_field( 'item_is_user_roles' , $item->item_is_user_roles , $item ); ?>
                  <?php static::print_item_content_field( 'item_user_roles' , $item->item_user_roles , $item ); ?>
                </td>
              </tr>
              <tr>
                <th><?php _e( 'Start Date' , 'mywp-announce' ); ?></th>
                <td>
                  <?php static::print_item_content_field( 'item_is_date_start' , $item->item_is_date_start , $item ); ?>
                  <?php static::print_item_content_field( 'item_date_start' , $item->item_date_start , $item ); ?>
                </td>
              </tr>
              <tr>
                <th><?php _e( 'End Date' , 'mywp-announce' ); ?></th>
                <td>
                  <?php static::print_item_content_field( 'item_is_date_end' , $item->item_is_date_end , $item ); ?>
                  <?php static::print_item_content_field( 'item_date_end' , $item->item_date_end , $item ); ?>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <?php do_action( 'mywp_setting_announce_print_item_content_after' , $item ); ?>

        <div class="clear"></div>

        <a href="javascript:void(0);" class="item-update button button-primary"><?php _e( 'Update' ); ?></a>

        <a href="javascript:void(0);" class="item-remove button button-secondary button-caution"><span class="dashicons dashicons-no-alt"></span> <?php _e( 'Remove' ); ?></a>

        <span class="spinner"></span>

      </div>

    </div>

    <?php

  }

  protected static function print_item_content_field( $field_name = false , $value = '' , $item = false , $args = false ) {

    if( empty( $field_name ) or ! is_object( $item ) ) {

      return false;

    }

    $all_user_roles = MywpAnnounceApi::get_all_user_roles();

    $announce_types = MywpAnnounceApi::get_announce_types();

    $announce_screen_groups = MywpAnnounceApi::get_announce_screen_groups();

    $announce_screens = MywpAnnounceApi::get_announce_screens();

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

        printf( '<option value="%s" %s>[%s] %s (%s)</option>' , esc_attr( $type ) , selected( $type , $value , false ) , esc_attr( $type ) , esc_attr( $announce_type['label'] ) , esc_attr( $announce_type['color'] ) );

      }

      echo '</select>';

    } elseif( $field_name === 'item_screen' ) {

      echo '<select class="item_screen">';

      foreach( $announce_screen_groups as $announce_screen_group_key => $announce_screen_group ) {

        if( empty( $announce_screen_group_key ) ) {

          continue;

        }

        printf( '<optgroup label="%s" data-announce_screen_group_key="%s">' , esc_attr( $announce_screen_group['label'] ) , esc_attr( $announce_screen_group_key ) );

        foreach( $announce_screens as $screen_key => $announce_screen ) {

          if( empty( $screen_key ) ) {

            continue;

          }

          if( $announce_screen['group'] !== $announce_screen_group_key ) {

            continue;

          }

          printf( '<option value="%s" %s>%s</option>' , esc_attr( $screen_key ) , selected( $screen_key , $value , false ) , esc_attr( $announce_screen['label'] ) );

        }

        echo '</optgroup>';

      }

      echo '</select>';

    } elseif( $field_name === 'item_add_class' ) {

      printf( '<input type="text" class="item_add_class large-text" value="%s" placeholder="%s" />' , esc_attr( $value ) , esc_attr( 'custom class value' ) );

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

        if( ! empty( $value ) && in_array( $user_role , $value , true ) ) {

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

      printf( '<input type="text" class="item_date_start regular-text" value="%s" placeholder="%s" />' , esc_attr( $value ) , esc_attr( 'YYYY-MM-DD HH:MM:SS' ) );

      echo '</div>';

    } elseif( $field_name === 'item_is_date_end' ) {

      echo '<label>';

      printf( '<input type="checkbox" class="item_is_date_end" value="1" %s />' , checked( 1 , $value , false ) );

      _e( 'Designate' , 'mywp-announce' );

      echo '</label>';

    } elseif( $field_name === 'item_date_end' ) {

      echo '<div class="designate-detail">';

      printf( '<input type="text" class="item_date_end regular-text" value="%s" placeholder="%s" />' , esc_attr( $value ) , esc_attr( 'YYYY-MM-DD HH:MM:SS' ) );

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

      do_action( 'mywp_setting_announce_print_item_content_field' , $field_name , $value , $item );

    }

  }

  protected static function add_post( $args = array() , $post_metas = array() ) {

    $default_args = array(
      'post_type' => static::$post_type,
      'post_status' => 'draft',
      'post_parent' => 0,
    );

    $post = wp_parse_args( $args , $default_args );

    $post_id = static::insert_post( $post );

    if ( empty( $post_id ) or is_wp_error( $post_id ) ) {

      return $post_id;

    }

    $post_id = intval( $post_id );

    $post_metas = apply_filters( 'mywp_setting_announce_add_post_metas' , $post_metas , $args );

    static::add_post_metas( $post_id , $post_metas );

    return $post_id;

  }

  protected static function insert_post_metas( $post_id , $post_metas ) {

    global $wpdb;

    if( empty( $post_id ) ) {

      return false;

    }

    $post_id = (int) $post_id;

    if( empty( $post_metas ) ) {

      return $post_metas;

    }

    $add_meta_data = array();

    foreach( $post_metas as $meta_key => $meta_value ) {

      $meta_key = strip_tags( $meta_key );

      $add_meta_data[] = $wpdb->prepare( "(NULL, %d, %s, %s)" , $post_id , wp_unslash( $meta_key ) , maybe_serialize( wp_unslash( $meta_value ) ) );

    }

    $query = "INSERT INTO $wpdb->postmeta (meta_id, post_id, meta_key, meta_value) VALUES " . implode( ',' , $add_meta_data );

    $wpdb->query( $query );

    return true;

  }

  public static function mywp_current_setting_post_data_format_update( $formatted_data ) {

    $mywp_model = static::get_model();

    if( empty( $mywp_model ) ) {

      return $formatted_data;

    }

    $new_formatted_data = $mywp_model->get_initial_data();

    $new_formatted_data['advance'] = $formatted_data['advance'];

    if( isset( $formatted_data['cache_timeout'] ) ) {

      $new_formatted_data['cache_timeout'] = intval( $formatted_data['cache_timeout'] );

    }

    static::delete_transient_controller_get_announces();

    $current_setting_announce_item_posts = static::get_current_setting_announce_item_posts();

    if( ! empty( $current_setting_announce_item_posts ) ) {

      foreach( $current_setting_announce_item_posts as $key => $current_setting_announce_item_post ) {

        $post_id = $current_setting_announce_item_post->ID;

        $post = static::get_post( $post_id );

        if( empty( $post )  or ! is_object( $post ) or $post->post_type !== static::$post_type ) {

          continue;

        }

        $post = array(
          'ID' => $post_id,
          'post_status' => 'publish',
        );

        static::update_post( $post );

      }

    }

    return $new_formatted_data;

  }

  public static function mywp_current_setting_before_post_data_action_remove( $validated_data ) {

    if( empty( $validated_data['remove'] ) ) {

      return false;

    }

    static::delete_transient_controller_get_announces();

    $current_setting_announce_item_posts = static::get_current_setting_announce_item_posts();

    if( empty( $current_setting_announce_item_posts ) ) {

      return false;

    }

    foreach( $current_setting_announce_item_posts as $key => $current_setting_announce_item_post ) {

      $post_id = $current_setting_announce_item_post->ID;

      $post = static::get_post( $post_id );

      if( empty( $post )  or ! is_object( $post ) or $post->post_type !== static::$post_type ) {

        continue;

      }

      static::delete_post( $post_id );

    }

  }

}

endif;
