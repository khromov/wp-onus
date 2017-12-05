<?php
/*
Plugin Name: Project Onus
Plugin URI:
Description: One composer dependency for your entire site
Version: 1.0
Author: khromov
Author URI: http://profiles.wordpress.org/khromov/
GitHub Plugin URI: khromov/wp-one
License: GPL2
*/

use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

define('WP_ONUS_DIR', untrailingslashit(dirname(__FILE__)));

/**
 * Load resolved dependencies if available
 */
if(file_exists(WP_ONUS_DIR . '/resolved/vendor/autoload.php')) {
  require WP_ONUS_DIR . '/resolved/vendor/autoload.php';
}

/**
 * Class WP_Onus
 */
class WP_Onus {
  static $plugins = [];
  static $dependencies;

  function __construct() {
    //Mark for plugin
    add_filter('wp_dependency_management_enabled', '__return_true', 1);

    //Trigger for testing
    add_action('template_redirect', array('WP_Onus', 'register_trigger'));
  }

  /**
   * Test trigger
   */
  static function register_trigger() {
    if(isset($_GET['rebuild_deps'])) {
      WP_Onus::resolve();
    }
  }

  /**
   * Function for registering plugin paths
   * @param $root
   */
  static function register($root) {
    self::$plugins[] = $root;
  }

  /**
   * Main functionality for building dependencies
   */
  static function resolve() {
    $deps = self::build_dependencies();
    $deps_resolved = self::resolve_versions($deps);
    $composer_json = self::build_composer_json($deps_resolved);

    //Save new json
    file_put_contents(WP_ONUS_DIR . '/resolved/composer.json', $composer_json);

    //Run Composer!
    self::run_composer();

    var_dump("Built vendor directory."); //TODO: Error checking
    die();
  }

  /**
   * Builds a list of all registered plugins and any versions they have
   *
   * @return array
   */
  static function build_dependencies() {

    $deps = [];

    foreach(self::$plugins as $plugin_path) {
      if(file_exists($plugin_path . '/composer.json')) {
        $composer_json = json_decode(file_get_contents($plugin_path . '/composer.json'));

        foreach($composer_json->require as $package => $version) {
          if(!isset($deps[$package])) {
            $deps[$package] = [];
          }

          $deps[$package][] = $version;
        }
      }
    }

    return $deps;
  }

  /**
   * Resolve which version of each plugin we should use
   *
   * @param $deps
   *
   * @return array
   * @throws Exception
   */
  static function resolve_versions($deps) {

    $deps_fixed = [];
    $errors = [];

    foreach($deps as $package => $versions) {

      if(sizeof($versions) === 1) {
        $deps_fixed[$package] = $versions[0];
      }

      //FIXME: Here we need to check whether two directives are compatible and come up with a working directive, for example:
      // ^3.5 && 3.6.2 = 3.6.2
      // ^4.2 && 3.8.* = error
      // 3.* && 3.8.* = 3.8.X (X = latest minor)
      //For now, let's just pick the first one.
      $deps_fixed[$package] = $versions[0]; //or false on error

      //No package resolved
      if(!$deps_fixed[$package]) {
        //TODO: Handle these exceptions higher up in the stack.
        $errors[] = "Dependency error: Could not find a working combination for the plugin {$package} with versions: " . implode(" OR ", $versions);
      }
    }

    if($errors) {
      throw new Exception(implode(" / ", $errors));
    }

    return $deps_fixed;
  }

  /**
   * Build the JSON for composer
   *
   * @param $deps
   *
   * @return false|string
   */
  static function build_composer_json($deps) {
    $composer_struct = new stdClass();
    $composer_struct->require = $deps;
    return wp_json_encode($composer_struct);
  }

  /**
   * Run composer!
   */
  static function run_composer() {

    //Load WP Onus dependencies, which is basically just Composer
    require_once WP_ONUS_DIR . '/vendor/autoload.php';

    // Composer\Factory::getHomeDir() method
    // needs COMPOSER_HOME environment variable set
    putenv('COMPOSER_HOME=' . __DIR__ . '/vendor/bin/composer');

    // call `composer install` command programmatically
    $params = array(
        'command' => 'update',
        '--working-dir' => WP_ONUS_DIR . '/resolved', //TODO: Place this somewhere nicer.
        '--optimize-autoloader' => true,
        '--no-suggest' => true,
        '--no-interaction' => true,
        '--no-progress' => true,
        //'--verbose' => true
    );

    try {
      $input       = new ArrayInput($params);
      $application = new Application();
      $application->setAutoExit(false); // prevent `$application->run` method from exitting the script
      $application->run($input);
    }
    catch (Exception $ex) {
      $message = $ex->getMessage(); //TODO: Put these somewhere useful.
    }
  }
}

new WP_Onus();