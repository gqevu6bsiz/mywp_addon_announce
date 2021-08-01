<?php
/*
Plugin Name: My WP Add-on Announce
Plugin URI: https://mywpcustomize.com/
Description: My WP Add-on Announce is announcement to the admin Dashboard for users.
Version: 1.2.0
Author: gqevu6bsiz
Author URI: http://gqevu6bsiz.chicappa.jp/
Text Domain: mywp-announce
Domain Path: /languages/
My WP Test working: 1.18
*/

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! class_exists( 'MywpAnnounce' ) ) :

final class MywpAnnounce {

  public static function init() {

    self::define_constants();
    self::include_core();

    add_action( 'mywp_start' , array( __CLASS__ , 'mywp_start' ) );

  }

  private static function define_constants() {

    define( 'MYWP_ANNOUNCE_NAME' , 'My WP Add-on Announce' );
    define( 'MYWP_ANNOUNCE_VERSION' , '1.2.0' );
    define( 'MYWP_ANNOUNCE_PLUGIN_FILE' , __FILE__ );
    define( 'MYWP_ANNOUNCE_PLUGIN_BASENAME' , plugin_basename( MYWP_ANNOUNCE_PLUGIN_FILE ) );
    define( 'MYWP_ANNOUNCE_PLUGIN_DIRNAME' , dirname( MYWP_ANNOUNCE_PLUGIN_BASENAME ) );
    define( 'MYWP_ANNOUNCE_PLUGIN_PATH' , plugin_dir_path( MYWP_ANNOUNCE_PLUGIN_FILE ) );
    define( 'MYWP_ANNOUNCE_PLUGIN_URL' , plugin_dir_url( MYWP_ANNOUNCE_PLUGIN_FILE ) );

  }

  private static function include_core() {

    $dir = MYWP_ANNOUNCE_PLUGIN_PATH . 'core/';

    require_once( $dir . 'class.api.php' );

  }

  public static function mywp_start() {

    add_action( 'mywp_plugins_loaded', array( __CLASS__ , 'mywp_plugins_loaded' ) );

    add_action( 'init' , array( __CLASS__ , 'wp_init' ) );

  }

  public static function mywp_plugins_loaded() {

    add_filter( 'mywp_post_type_plugins_loaded_include_modules' , array( __CLASS__ , 'mywp_post_type_plugins_loaded_include_modules' ) );

    add_filter( 'mywp_controller_plugins_loaded_include_modules' , array( __CLASS__ , 'mywp_controller_plugins_loaded_include_modules' ) );

    add_filter( 'mywp_setting_plugins_loaded_include_modules' , array( __CLASS__ , 'mywp_setting_plugins_loaded_include_modules' ) );

  }

  public static function wp_init() {

    load_plugin_textdomain( 'mywp-announce' , false , MYWP_ANNOUNCE_PLUGIN_DIRNAME . '/languages' );

  }

  public static function mywp_post_type_plugins_loaded_include_modules( $includes ) {

    $dir = MYWP_ANNOUNCE_PLUGIN_PATH . 'post-type/modules/';

    $includes['announce']           = $dir . 'mywp.post-type.module.announce.php';
    $includes['announce_multisite'] = $dir . 'mywp.post-type.module.announce.multisite.php';

    return $includes;

  }

  public static function mywp_controller_plugins_loaded_include_modules( $includes ) {

    $dir = MYWP_ANNOUNCE_PLUGIN_PATH . 'controller/modules/';

    $includes['announce_main_general']      = $dir . 'mywp.controller.module.main.general.php';
    $includes['announce_setting']           = $dir . 'mywp.controller.module.announce.php';
    $includes['announce_multisite_setting'] = $dir . 'mywp.controller.module.announce.multisite.php';
    $includes['announce_updater']           = $dir . 'mywp.controller.module.updater.php';

    return $includes;

  }

  public static function mywp_setting_plugins_loaded_include_modules( $includes ) {

    $dir = MYWP_ANNOUNCE_PLUGIN_PATH . 'setting/modules/';

    $includes['announce_setting']           = $dir . 'mywp.setting.announce.php';
    $includes['announce_multisite_setting'] = $dir . 'mywp.setting.announce.multisite.php';

    return $includes;

  }

}

MywpAnnounce::init();

endif;
