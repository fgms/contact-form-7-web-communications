<?php
/**
 * Plugin Name: Contact Form 7 Web Communciations
 * Plugin URI: https://github.com/fgms/contact-form-7-web-communications/
 * Description: Wordpress plugin to add communications to Contact 7 Forms
 * Version: 0.0.1
 * Author: Shawn Turple
 * Author URI: https://github.com/fgms/
 * License: GPL-3.0
 * Plugin Type: Contact Form 7
 */
if ( file_exists( $composer_autoload = __DIR__ . '/vendor/autoload.php' ) /* check in self */
    || file_exists( $composer_autoload = WP_CONTENT_DIR.'/vendor/autoload.php') /* check in wp-content */
    || file_exists( $composer_autoload = WP_CONTENT_DIR .'/../vendor/autoload.php') /* check in root directory */
    || file_exists( $composer_autoload = plugin_dir_path( __FILE__ ).'vendor/autoload.php') /* check in plugin directory */
    || file_exists( $composer_autoload = get_stylesheet_directory().'/vendor/autoload.php') /* check in child theme */
    || file_exists( $composer_autoload = get_template_directory().'/vendor/autoload.php') /* check in parent theme */
) {

    require_once $composer_autoload;
    require_once(__DIR__.'/shortcodes/wpcf7-communications-action.php');
    require_once(__DIR__.'/shortcodes/wpcf7-communications-results.php');

}
call_user_func(function () {
  global $wpdb;
  $controller=new \Fgms\Communications\Controller(new \Fgms\WordPress\WordPressImpl(), $wpdb);
  $now = new \DateTime();
  $date = new \DateTime();
  $date = $date->setTimestamp(wp_next_scheduled('wp_web_communications_cron'));
  $interval = $date->diff($now);
  error_log('Next cron in ' .$interval->format('%i minutes'). ' '. $date->format('Y-m-d H:i:s'). ' ' .wp_next_scheduled('wp_web_communications_cron'));
  register_activation_hook( __FILE__, function() use (&$controller){
      $model = $controller->get_model();
      $model->create_db('submissions', '\Fgms\Communications\Model::create_communication_db');
      $model->create_db('results_auth', '\Fgms\Communications\Model::create_results_auth_db');
      wp_schedule_event(time(),'hourly', 'wp_web_communications_cron');
  });

  register_deactivation_hook( __FILE__, function() use (&$controller){
      wp_clear_scheduled_hook('wp_web_communications_cron');
  });

  add_action('wp_web_communications_cron', function(){
    error_log('cron_action_outside:: ' );
  });

});
