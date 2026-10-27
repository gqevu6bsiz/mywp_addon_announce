<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if( ! class_exists( 'MywpControllerAbstractModule' ) ) {
  return false;
}

if ( ! class_exists( 'MywpControllerModuleAnnounceUpdater' ) ) :

final class MywpControllerModuleAnnounceUpdater extends MywpControllerAbstractModule {

  static protected $id = 'announce_updater';

  static private $schedule_hook = 'mywp_announce_version_check';

  protected static function after_init() {

    add_filter( 'mywp_controller_pre_get_model_' . self::$id , array( __CLASS__ , 'mywp_controller_pre_get_model' ) );

    add_filter( 'site_transient_update_plugins' , array( __CLASS__ , 'site_transient_update_plugins' ) );

    add_filter( 'plugins_api' , array( __CLASS__ , 'plugins_api' ) , 10 , 3 );

  }

  public static function mywp_controller_pre_get_model( $pre_model ) {

    $pre_model = true;

    return $pre_model;

  }

  public static function site_transient_update_plugins( $site_transient ) {

    $called_text = sprintf( '%s::%s( $site_transient )' , __CLASS__ , __FUNCTION__ );

    if( empty( $site_transient ) ) {

      return $site_transient;

    }

    if( ! isset( $site_transient->response ) ) {

      return $site_transient;

    }

    $is_latest = self::is_latest();

    if( is_wp_error( $is_latest ) ) {

      MywpHelper::error_returned_message( '$is_latest' , $is_latest->get_error_message() , $called_text );

      return $site_transient;

    }

    if( $is_latest ) {

      MywpHelper::error_not_found_message( '$is_latest' , $called_text );

      return $site_transient;

    }

    $latest = self::get_latest();

    if( is_wp_error( $latest ) ) {

      MywpHelper::error_returned_message( '$latest' , $latest->get_error_message() , $called_text );

      return $site_transient;

    }

    $plugin_info = MywpAnnounceApi::plugin_info();

    $remote_json = self::get_remote_json();

    if( empty( $remote_json ) ) {

      MywpHelper::error_not_found_message( '$remote_json' , $called_text );

      return $site_transient;

    }

    $update_plugin = array(
      'id' => MYWP_ANNOUNCE_PLUGIN_BASENAME,
      'slug' => MYWP_ANNOUNCE_PLUGIN_DIRNAME,
      'plugin' => MYWP_ANNOUNCE_PLUGIN_BASENAME,
      'new_version' => $latest,
      'url' => $plugin_info['github'],
      'package' => self::get_remote_download_link(),
      'icons' => array(),
      'banners' => array(),
      'banners_rtl' => array(),
      'requires' => false,
      'tested' => false,
      'compatibility' => false,
    );

    $site_transient->response[ MYWP_ANNOUNCE_PLUGIN_BASENAME ] = (object) $update_plugin;

    return $site_transient;

  }

  public static function get_remote() {

    $called_text = sprintf( '%s::%s()' , __CLASS__ , __FUNCTION__ );

    $transient_key = 'mywp_' . self::$id . '_remote';

    $transient = get_site_transient( $transient_key );

    if( ! empty( $transient ) ) {

      return $transient;

    }

    $plugin_info = MywpAnnounceApi::plugin_info();

    $remote_args = array();

    $error = new WP_Error();

    $remote_result = wp_remote_get( $plugin_info['github_release_latest'] , $remote_args );

    if( empty( $remote_result ) ) {

      MywpHelper::error_not_found_message( '$remote_result' , $called_text );

      $error->add( 'not_results' , __( 'Connection lost or the server is busy. Please try again later.' , 'mywp-announce' ) );

      return $error;

    }

    if( is_wp_error( $remote_result ) ) {

      MywpHelper::error_returned_message( '$remote_result' , $remote_result->get_error_message() , $called_text );

      $error->add( 'invalid_remote' , $remote_result->get_error_message() );

      return $error;

    }

    $remote_code = wp_remote_retrieve_response_code( $remote_result );

    $remote_body = wp_remote_retrieve_body( $remote_result );

    set_site_transient( $transient_key , $remote_body , DAY_IN_SECONDS );

    if( $remote_code !== 200 ) {

      MywpHelper::error_invalid_message( '$remote_code' , '200' , $called_text );

      if( ! empty( $remote_body ) ) {

        $maybe_json = json_decode( $remote_body );

        if( ! empty( $maybe_json ) && ! empty( $maybe_json->message ) ) {

          $error->add( 'invalid_connection' , sprintf( '[%d] %s' , $remote_code , $maybe_json->message ) );

        } else {

          $error->add( 'invalid_json' , sprintf( '[%d] %s' , $remote_code , __( 'An error has occurred. Please reload the page and try again.' , 'mywp-announce' ) ) );

        }

      } else {

        $error->add( 'invalid_connection' , sprintf( '[%d] %s' , $remote_code , __( 'Connection lost or the server is busy. Please try again later.' , 'mywp-announce' ) ) );

      }

      return $error;

    }

    if( empty( $remote_body ) ) {

      MywpHelper::error_not_found_message( '$remote_body' , $called_text );

      $error->add( 'invalid_remote_body' , __( 'An error has occurred. Please reload the page and try again.' , 'mywp-announce' ) );

      return $error;

    }

    return $remote_body;

  }

  public static function get_remote_json() {

    $called_text = sprintf( '%s::%s()' , __CLASS__ , __FUNCTION__ );

    $remote = self::get_remote();

    if( empty( $remote ) ) {

      MywpHelper::error_not_found_message( '$remote' , $called_text );

      return false;

    }

    if( is_wp_error( $remote ) ) {

      MywpHelper::error_returned_message( '$remote' , $remote->get_error_message() , $called_text );

      return false;

    }

    $remote_json = json_decode( $remote );

    if( empty( $remote_json->assets ) ) {

      MywpHelper::error_not_found_message( '$remote_json->assets' , $called_text );

      return false;

    }

    if( ! is_object( $remote_json ) ) {

      MywpHelper::error_invalid_message( '$remote_json' , 'is_object()' , $called_text );

      return false;

    }

    if( ! isset( $remote_json->tag_name ) ) {

      MywpHelper::error_not_found_message( '$remote_json->tag_name' , $called_text );

      return false;

    }

    if( ! is_array( $remote_json->assets ) ) {

      MywpHelper::error_invalid_message( '$remote_json->assets' , 'is_array()' , $called_text );

      return false;

    }

    return $remote_json;

  }

  private static function get_remote_download_link() {

    $called_text = sprintf( '%s::%s()' , __CLASS__ , __FUNCTION__ );

    $remote_json = self::get_remote_json();

    if( empty( $remote_json ) ) {

      MywpHelper::error_not_found_message( '$remote_json' , $called_text );

      return false;

    }

    $download_link = '';

    $download_file_path = sprintf( '%s.%s.zip' , MYWP_ANNOUNCE_PLUGIN_DIRNAME , self::get_latest() );

    foreach( $maybe_remote_json->assets as $asset ) {

      if( ! isset( $asset->name ) ) {

        MywpHelper::error_not_found_message( '$asset->name' , $called_text );

        continue;

      }

      if( $asset->name !== $download_file_path ) {

        continue;

      }

      if( empty( $asset->browser_download_url ) ) {

        MywpHelper::error_not_found_message( '$asset->browser_download_url' , $called_text );

        continue;

      }

      $download_link = $asset->browser_download_url;

      break;

    }

    return $download_link;

  }

  public static function get_latest() {

    $called_text = sprintf( '%s::%s()' , __CLASS__ , __FUNCTION__ );

    $transient_key = 'mywp_' . self::$id;

    $transient = get_site_transient( $transient_key );

    if( ! empty( $transient['latest'] ) ) {

      return $transient['latest'];

    }

    $remote_json = self::get_remote_json();

    if( empty( $remote_json ) ) {

      MywpHelper::error_not_found_message( '$remote_json' , $called_text );

      return false;

    }

    $latest = $remote_json->tag_name;

    $transient = array(
      'latest' => $latest,
    );

    set_site_transient( $transient_key , $transient , DAY_IN_SECONDS );

    if( ! function_exists( 'wp_clean_plugins_cache' ) ) {

      require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    }

    wp_clean_plugins_cache();

    return $latest;

  }

  public static function is_latest() {

    $called_text = sprintf( '%s::%s()' , __CLASS__ , __FUNCTION__ );

    $latest = self::get_latest();

    if( empty( $latest ) ) {

      MywpHelper::error_not_found_message( '$latest' , $called_text );

      $error = new WP_Error();

      $error->add( 'invalid_latest' , __( 'Failed to check for the latest version.' , 'mywp-accounce' ) );

      return $error;

    }

    $latest_compare = version_compare( $latest , MYWP_ANNOUNCE_VERSION , '<=' );

    return $latest_compare;

  }

  public static function plugins_api( $false , $action , $args ) {

    $called_text = sprintf( '%s::%s( $false , $action , $args )' , __CLASS__ , __FUNCTION__ );

    if( ! in_array( $action , array( 'query_plugins' , 'plugin_information' ) , true ) ) {

      return $false;

    }

    if( empty( $args->slug ) ) {

      return $false;

    }

    if( $args->slug !== MYWP_ANNOUNCE_PLUGIN_DIRNAME ) {

      return $false;

    }

    $remote_json = self::get_remote_json();

    if( empty( $remote_json ) ) {

      MywpHelper::error_not_found_message( '$remote_json' , $called_text );

      return $false;

    }

    $plugin_info = MywpAnnounceApi::plugin_info();

    $plugin_api = array(
      'name' => MYWP_ANNOUNCE_NAME,
      'slug' => MYWP_ANNOUNCE_PLUGIN_BASENAME,
      'version' => self::get_latest(),
      'author' => sprintf( '<a href="%s" target="_blank">%s</a>' , esc_url( $plugin_info['document_url'] ) , esc_html( 'gqevu6bsiz' ) ),
      'author_profile' => $plugin_info['website_url'],
      //'contributors' => array(),
      //'requires' => '',
      //'tested' => '',
      //'requires_php' => '',
      //'requires_plugins' => array(),
      //'rating' => '',
      //'ratings' => array(),
      //'num_ratings' => '',
      //'support_url' => '',
      //'support_threads' => '',
      //'support_threads_resolved' => '',
      //'active_installs' => '',
      'last_updated' => $maybe_remote_json->published_at,
      //'added' => '',
      'homepage' => $plugin_info['website_url'],
      'sections' => array(
        //'description' => '',
        //'faq' => '',
        'changelog' => sprintf( '<a href="%s" target="_blank">%s</a>' , esc_url( $plugin_info['github_releases'] ) , esc_html( 'See Releases.' ) ),
        //'screenshots' => '',
        //'reviews' => '',
      ),
      'download_link' => self::get_remote_download_link(),
      //'upgrade_notice' => array(),
      //'screenshots' => array(),
      //'tags' => array(),
      //'versions' => array(),
      //'business_model' => '',
      //'repository_url' => $plugin_info['github'],
      //'commercial_support_url' => '',
      //'donate_link' => '',
      //'banners' => array(),
      //'preview_link' => '',
    );

    $result = (object) $plugin_api;

    return $result;

  }

  public static function mywp_wp_loaded() {

    if( is_multisite() ) {

      if( ! is_main_site() ) {

        return false;

      }

    }

    self::schedule_hook();

    add_action( self::$schedule_hook , array( __CLASS__ , 'version_check' ) );

  }

  public static function schedule_hook() {

    if( wp_next_scheduled( self::$schedule_hook ) ) {

      return false;

    }

    $next_scheduled_date = time() + DAY_IN_SECONDS;

    wp_schedule_single_event( $next_scheduled_date , self::$schedule_hook );

  }

  public static function version_check() {

    self::get_latest();

  }

}

MywpControllerModuleAnnounceUpdater::init();

endif;
