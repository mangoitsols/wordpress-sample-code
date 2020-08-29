<?php
/**
 * Plugin Name: WordPress Uscreen API
 * Description: Connect WordPress With Uscreen API.
 * Version: 1.0
 * Author:  MIT
 * Author URI: http://www.mangoitsolutions.com/
 * License: GPLv2
 * Text Domain: wp-uscreen-api
 */
define( 'WP_USCREEN_VERSION', '1.0.0' );
define( 'WP_USCREEN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_USCREEN_PLUGIN_URL_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_USCREEN_WEBHOOK_PREFIX', 'uapi_');


include_once WP_USCREEN_PLUGIN_URL_DIR.'/admin/class.uscreen.logger.php';
include_once WP_USCREEN_PLUGIN_URL_DIR.'/admin/class.uscreen.admin.php';
include_once WP_USCREEN_PLUGIN_URL_DIR.'/admin/class.uscreen.api.php';
include_once WP_USCREEN_PLUGIN_URL_DIR.'/admin/class.uscreen.import.data.php';

if (!class_exists('Initialize_Uscreen')) {
   class Initialize_Uscreen {
        function run_cron_for_uscreen() {
            add_filter( 'cron_schedules', array( &$this, 'update_uscreen_data' ) );
        }

        function update_uscreen_data( $schedules ) {
           $schedules['every_24_hours'] = array(
               'interval'  => 86400,
               'display'   => __( 'Every 24 Hours', 'textdomain' )
            );
        return $schedules;
       }
   }
}

$Initialize_Uscreen = new Initialize_Uscreen();
$Initialize_Uscreen->run_cron_for_uscreen();

// Schedule an action if it's not already scheduled
if (!wp_next_scheduled('update_uscreen_data')) {
   wp_schedule_event(time(), 'every_24_hours', 'update_uscreen_data');
}

add_action('update_uscreen_data', 'import_uscreen_data');
function import_uscreen_data() {
    $importData = new importData();
    $uscreen_api = new uscreenAPI();
    $i = 1;

    while ($uscreen_api->getPrograms($i)) {
   		$importData->importPrograms($i);
   		$i++;
   	}
}