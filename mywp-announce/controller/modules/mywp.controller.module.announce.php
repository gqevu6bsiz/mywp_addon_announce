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

  private static $all_announces = array();

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

  private static function get_all_announces() {

    if( ! empty( self::$all_announces ) ) {

      return self::$all_announces;

    }

    $all_announces = array();

    $announces = self::get_announces();

    if( ! empty( $announces ) ) {

      foreach( $announces as $announce ) {

        $all_announces[] = $announce;

      }

    }

    if( is_multisite() ) {

      $multisite_announces = MywpControllerModuleAnnounceMultisite::get_announces();

      if( ! empty( $multisite_announces ) ) {

        foreach( $multisite_announces as $announce ) {

          $all_announces[] = $announce;

        }

      }

    }

    self::$all_announces = $all_announces;

    return $all_announces;

  }

  private static function get_announces() {

    $setting_data = self::get_setting_data();

    $timeout_min = 0;

    if( ! empty( $setting_data['cache_timeout'] ) ) {

      $timeout_min = intval( $setting_data['cache_timeout'] );

    }

    $mywp_transient = new MywpTransient( 'announce_get_announces' , 'controller' );

    if( ! empty( $timeout_min ) ) {

      $transient_announces = $mywp_transient->get_data();

      if( ! empty( $transient_announces ) ) {

        return $transient_announces;

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

    if( ! empty( $timeout_min ) && ! empty( $announces ) ) {

      $announces_strlen = strlen( maybe_serialize( $announces ) );

      if( $announces_strlen < MywpHelper::get_max_allowed_packet_size() ) {

        $mywp_transient->update_data( $announces , $timeout_min * MINUTE_IN_SECONDS );

      }

    }

    return $announces;

  }

  public static function current_screen() {

    add_action( 'admin_enqueue_scripts' , array( __CLASS__ , 'admin_enqueue_scripts' ) );

    add_action( 'admin_notices' , array( __CLASS__ , 'admin_notices' ) );

  }

  public static function admin_enqueue_scripts() {

    $announces = self::get_all_announces();

    if( empty( $announces ) ) {

      return false;

    }

    wp_register_style( 'mywp_announce' , MYWP_ANNOUNCE_PLUGIN_URL . 'assets/css/announce.css' , array() , MYWP_ANNOUNCE_VERSION );

    wp_enqueue_style( 'mywp_announce' );

  }

  public static function admin_notices() {

    global $pagenow;
    global $typenow;
    global $taxnow;

    if( ! self::is_do_function( __FUNCTION__ ) ) {

      return false;

    }

    $all_announces = self::get_all_announces();

    $current_announces = array();

    if( ! empty( $all_announces ) ) {

      foreach( $all_announces as $announce_id => $announce ) {

        $announce_screen = MywpAnnounceApi::get_announce_screen( $announce->item_screen );

        if( empty( $announce_screen['screen_id'] ) ) {

          continue;

        }

        if( $announce_screen['group'] === 'general' ) {

          if( $announce->item_screen === 'all' ) {

            $current_announces[ $announce_id ] = $announce;

            continue;

          }

          if( ! empty( $announce_screen['page_id'] ) ) {

            if( $announce_screen['page_id'] === $pagenow ) {

              $current_announces[ $announce_id ] = $announce;

              continue;

            }

          }

        } elseif( $announce_screen['group'] === 'post_type' ) {

          if( empty( $announce_screen['post_type'] ) ) {

            continue;

          }

          if( $announce_screen['post_type'] !== $typenow ) {

            continue;

          }

          if( $announce_screen['screen_id'] === 'posts-' . $typenow && $pagenow === 'edit.php' ) {

            $current_announces[ $announce_id ] = $announce;

            continue;

          } elseif( $announce_screen['screen_id'] === 'post_edit_add-' . $typenow && in_array( $pagenow , array( 'post.php' , 'post-new.php' ) , true ) ) {

            $current_announces[ $announce_id ] = $announce;

            continue;

          } else {

            continue;

          }

        } elseif( $announce_screen['group'] === 'taxonomy' ) {

          if( empty( $announce_screen['taxonomy'] ) ) {

            continue;

          }

          if( $announce_screen['taxonomy'] !== $taxnow ) {

            continue;

          }

          if( $announce_screen['screen_id'] === 'terms-' . $taxnow && $pagenow === 'edit-tags.php' ) {

            $current_announces[ $announce_id ] = $announce;

            continue;

          } elseif( $announce_screen['screen_id'] === 'term_edit-' . $taxnow && in_array( $pagenow , array( 'term.php' ) , true ) ) {

            $current_announces[ $announce_id ] = $announce;

            continue;

          } else {

            continue;

          }

        }

      }

    }

    if( empty( $current_announces ) ) {

      return false;

    }

    foreach( $current_announces as $current_announce ) {

      self::print_announce( $current_announce );

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

        if( ! in_array( $mywp_user->get_user_role() , $item->item_user_roles , true ) ) {

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

      if( in_array( $current_blog_id , $item_hide_sites , true ) ) {

        return false;

      }

    }

    $item->post_title = do_shortcode( $item->post_title );

    add_filter( 'mywp_controller_announce_print_announce_item_content' , 'wptexturize' );
    add_filter( 'mywp_controller_announce_print_announce_item_content' , 'convert_smilies' , 20 );
    add_filter( 'mywp_controller_announce_print_announce_item_content' , 'convert_chars' );
    add_filter( 'mywp_controller_announce_print_announce_item_content' , 'wpautop' );
    add_filter( 'mywp_controller_announce_print_announce_item_content' , 'shortcode_unautop' );
    add_filter( 'mywp_controller_announce_print_announce_item_content' , 'prepend_attachment' );
    add_filter( 'mywp_controller_announce_print_announce_item_content' , 'do_shortcode' , 11 );

    $announce_content = apply_filters( 'mywp_controller_announce_print_announce_item_content' , $item->post_content );

    $add_class = '';

    if( ! empty( $item->item_add_class ) ) {

      $add_class = strip_tags( $item->item_add_class );

    }

    printf( '<div class="mywp-announce updated %s %s" id="announce-%d">' , esc_attr( $item->item_type ) , esc_html( $add_class ) , esc_attr( $item->ID ) );

    if( ! empty( $item->post_title ) ) {

      printf( '<p class="title">%s</p>' , $item->post_title );

    }

    if( ! empty( $announce_content ) ) {

      echo $announce_content;

    }

    echo '</div>';

  }

}

MywpControllerModuleAnnounce::init();

endif;
