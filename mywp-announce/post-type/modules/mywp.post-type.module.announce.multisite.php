<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpPostTypeAbstractModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpPostTypeModuleAnnounceMultisite' ) ) :

final class MywpPostTypeModuleAnnounceMultisite extends MywpPostTypeAbstractModule {

  protected static $id = 'mywp_announce_sites';

  protected static function get_regist_post_type_args() {

    $args = array(
      'label' => 'My WP Announce Multisite',
      'supports' => array( 'title' , 'editor' , 'custom-fields' ),
    );

    return $args;

  }

  public static function current_mywp_post_type_get_post( $post ) {

    $post_id = $post->ID;

    $post->item_type = strip_tags( MywpPostType::get_post_meta( $post_id , 'item_type' ) );
    $post->item_screen = strip_tags( MywpPostType::get_post_meta( $post_id , 'item_screen' ) );

    $post->item_is_user_roles = strip_tags( MywpPostType::get_post_meta( $post_id , 'item_is_user_roles' ) );
    $post->item_user_roles = MywpPostType::get_post_meta( $post_id , 'item_user_roles' );

    $post->item_is_date_start = strip_tags( MywpPostType::get_post_meta( $post_id , 'item_is_date_start' ) );
    $post->item_date_start = strip_tags( MywpPostType::get_post_meta( $post_id , 'item_date_start' ) );

    $post->item_is_date_end = strip_tags( MywpPostType::get_post_meta( $post_id , 'item_is_date_end' ) );
    $post->item_date_end = strip_tags( MywpPostType::get_post_meta( $post_id , 'item_date_end' ) );

    $post->item_hide_sites = strip_tags( MywpPostType::get_post_meta( $post_id , 'item_hide_sites' ) );

    return $post;

  }

  public static function current_manage_posts_columns( $posts_columns ) {

    $old_columns = $posts_columns;

    $posts_columns = array();

    $posts_columns['cb'] = $old_columns['cb'];
    $posts_columns['order'] = 'Order';
    $posts_columns['id'] = 'ID';
    $posts_columns['type'] = 'Type';
    $posts_columns['screen'] = 'Screen';
    $posts_columns['hide_sites'] = 'Hide Sites';
    $posts_columns['title'] = $old_columns['title'];
    $posts_columns['info'] = 'Info';

    return $posts_columns;

  }

  public static function current_manage_posts_custom_column( $column_name , $post_id ) {

    $mywp_post = MywpPostType::get_post( $post_id );

    if( empty( $mywp_post ) ) {

      return false;

    }

    if( $column_name === 'order' ) {

      if( $mywp_post->menu_order ) {

        echo $mywp_post->menu_order;

      }

    } elseif( $column_name === 'id' ) {

      if( $mywp_post->ID ) {

        echo $mywp_post->ID;

      }

    } elseif( $column_name === 'type' ) {

      if( $mywp_post->item_type ) {

        echo $mywp_post->item_type;

      }

    } elseif( $column_name === 'screen' ) {

      if( $mywp_post->item_screen ) {

        echo $mywp_post->item_screen;

      }

    } elseif( $column_name === 'hide_sites' ) {

      if( $mywp_post->item_hide_sites ) {

        echo $mywp_post->item_hide_sites;

      }

    } elseif( $column_name === 'info' ) {

      printf( '<textarea readonly="readonly">%s</textarea>' , print_r( $mywp_post , true ) );

    }

  }

}

MywpPostTypeModuleAnnounceMultisite::init();

endif;
