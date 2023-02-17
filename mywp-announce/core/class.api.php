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
      'github_raw' => 'https://raw.githubusercontent.com/gqevu6bsiz/mywp_addon_announce/',
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

  public static function get_announce_screen_groups() {

    $announce_screen_groups = array(
      'general' => array(
        'label' => __( 'General' , 'my-wp' ),
      ),
      'post_type' => array(
        'label' => __( 'Post Type' , 'my-wp' ),
      ),
      'taxonomy' => array(
        'label' => __( 'Taxonomy' , 'my-wp' ),
      ),
    );

    $announce_screen_groups = apply_filters( 'mywp_announce_get_announce_screen_groups' , $announce_screen_groups );

    return $announce_screen_groups;

  }

  public static function get_announce_screens() {

    $announce_screens = array(
      'all' => array(
        'label' => __( 'All admin screens' , 'mywp-announce' ),
        'group' => 'general',
      ),
      'dashboard' => array(
        'label' => __( 'Dashboard' ),
        'page_id' => 'index.php',
        'group' => 'general',
      ),
    );

    $args = array( 'show_ui' => true );

    $post_types_object = get_post_types( $args , 'objects' );

    $mywp_post_types = MywpPostType::get_post_types();

    if( ! empty( $post_types_object ) ) {

      foreach( $post_types_object as $post_type => $post_type_object ) {

        if( ! empty( $mywp_post_types ) ) {

          $is_mywp_post_type = false;

          foreach( $mywp_post_types as $mywp_post_type => $mywp_post_type_object ) {

            if( $mywp_post_type === $post_type ) {

              $is_mywp_post_type = true;

              break;

            }

          }

          if( $is_mywp_post_type ) {

            continue;

          }

        }

        $announce_screen_id = sprintf( 'posts-%s' , $post_type );

        $all_item_label = sprintf( '%s items' , $post_type_object->label );

        if( ! empty( $post_type_object->labels->all_items ) ) {

          $all_item_label = $post_type_object->labels->all_items;

        }

        $announce_screens[ $announce_screen_id ] = array(
          'label' => sprintf( '[%s] %s' , $announce_screen_id , $all_item_label ),
          'group' => 'post_type',
          'post_type' => $post_type,
        );

        $announce_screen_id = sprintf( 'post_edit_add-%s' , $post_type );

        $edit_item_label = sprintf( '%s edit item' , $post_type_object->label );

        if( ! empty( $post_type_object->labels->edit_item ) ) {

          $edit_item_label = $post_type_object->labels->edit_item;

        }

        $announce_screens[ $announce_screen_id ] = array(
          'label' => sprintf( '[%s] %s' , $announce_screen_id , $edit_item_label ),
          'group' => 'post_type',
          'post_type' => $post_type,
        );

      }

    }

    $all_taxonomies = MywpApi::get_all_taxonomies();

    $mywp_taxonomy_types = MywpTaxonomy::get_taxonomy_types();

    if( ! empty( $all_taxonomies ) ) {

      foreach( $all_taxonomies as $taxonomy_name => $taxonomy_object ) {

        if( ! empty( $mywp_taxonomy_types ) ) {

          $is_mywp_taxonomy = false;

          foreach( $mywp_taxonomy_types as $mywp_taxonomy_type => $mywp_taxonomy_object ) {

            if( $mywp_taxonomy_type === $taxonomy_name ) {

              $is_mywp_taxonomy = true;

              break;

            }

          }

          if( $is_mywp_taxonomy ) {

            continue;

          }

        }

        $announce_screen_id = sprintf( 'terms-%s' , $taxonomy_name );

        $all_item_label = sprintf( '%s items' , $taxonomy_object->label );

        if( ! empty( $taxonomy_object->labels->all_items ) ) {

          $all_item_label = $taxonomy_object->labels->all_items;

        }

        $announce_screens[ $announce_screen_id ] = array(
          'label' => sprintf( '[%s] %s' , $announce_screen_id , $all_item_label ),
          'group' => 'taxonomy',
          'taxonomy' => $taxonomy_name,
        );

        $announce_screen_id = sprintf( 'term_edit-%s' , $taxonomy_name );

        $edit_item_label = sprintf( '%s edit item' , $taxonomy_object->label );

        if( ! empty( $taxonomy_object->labels->edit_item ) ) {

          $edit_item_label = $taxonomy_object->labels->edit_item;

        }

        $announce_screens[ $announce_screen_id ] = array(
          'label' => sprintf( '[%s] %s' , $announce_screen_id , $edit_item_label ),
          'group' => 'taxonomy',
          'taxonomy' => $taxonomy_name,
        );

      }

    }

    $announce_screens = apply_filters( 'mywp_announce_get_announce_screens' , $announce_screens );

    $defaults = array(
      'screen_id' => '',
      'label' => '',
      'page_id' => '',
      'group' => '',
      'post_type' => '',
      'taxonomy' => '',
    );

    if( ! empty( $announce_screens ) ) {

      foreach( $announce_screens as $announce_screen_id => $announce_screen ) {

        $announce_screen['screen_id'] = $announce_screen_id;

        $announce_screens[ $announce_screen_id ] = wp_parse_args( $announce_screen , $defaults );

      }

    }

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
