<?php 
class uscreenAdminInterface {
	private static $_instance = null;
	private static $_endpoint;
	private static $_key;

	public static function getInstance() {
		if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

	private function __construct() {
		add_action( 'admin_menu', array(&$this, 'addPluginOptionsPage'), 8);
		add_action( 'admin_init', array(&$this, 'registerPluginSetting') );
	}

	/**
     * Get API endpoint
     */	
	public static function getEndpoint(){
		return get_option('uscreen_api_endpoint');
	}

	/**
     * Get API Login Email
     */	
	public static function getUscreenEmail(){
		return get_option('uscreen_api_email');
	}

	/**
     * Get API Login Password
     */	
	public static function getUscreenPassword(){
		return get_option('uscreen_api_password');
	}

	/**
     * Get API Key
     */	
	public static function getKey() {
		return get_option('uscreen_api_key');
	}

	/**
     * Get Post Type
     */	
	public static function getUscreenPostType() {
		return get_option('uscreen_post_type');
	}

	/**
     * register settings for plugin
     */
    public function registerPluginSetting() {
        register_setting( 'wp_uscreen_api', 'uscreen_api_key' ); 
        register_setting( 'wp_uscreen_api', 'uscreen_api_endpoint' ); 
        register_setting( 'wp_uscreen_api', 'uscreen_logging' );
        register_setting( 'wp_uscreen_api', 'uscreen_post_type' );
        register_setting( 'wp_uscreen_api', 'uscreen_api_email' );
        register_setting( 'wp_uscreen_api', 'uscreen_api_password' );
    }

    /**
     * register options page for plugin
     */
    public function addPluginOptionsPage() {
        add_submenu_page('options-general.php', __('WP Uscreen API', 'wpdiscuz'), __('WP Uscreen API', 'wpdiscuz'), 'manage_options', 'wp-uscreen-api', array(&$this, 'addUscreenOptions') );
    }

    /**
     * Options for Uscreen Page.
     */
    public function addUscreenOptions() {
    ?>
		<div class="wrap">
			<h1>Uscreen API Settings</h1>
			<form method="post" action="options.php">
			    <?php settings_fields( 'wp_uscreen_api' ); ?>
			    <?php do_settings_sections( 'wp_uscreen_api' ); ?>
			    <table class="form-table">
			        <tr valign="top">
				        <th scope="row">API Key</th>
				        <td><input type="text" name="uscreen_api_key" value="<?php echo esc_attr(get_option('uscreen_api_key')); ?>" /></td>
			        </tr>
			         
			        <tr valign="top">
				        <th scope="row">API Endpoint</th>
				        <td><input type="text" name="uscreen_api_endpoint" value="<?php echo esc_attr(get_option('uscreen_api_endpoint')); ?>" /></td>
			        </tr>

			        <tr valign="top">
				        <th scope="row">Uscreen Login Email</th>
				        <td><input type="text" name="uscreen_api_email" value="<?php echo esc_attr(get_option('uscreen_api_email')); ?>" /></td>
			        </tr>

			        <tr valign="top">
				        <th scope="row">Uscreen Login Password</th>
				        <td><input type="password" name="uscreen_api_password" value="<?php echo esc_attr(get_option('uscreen_api_password') ); ?>" /></td>
			        </tr>

			        <tr valign="top">
				        <th scope="row">Select Post Type</th><?php
				        $args = array(
				        	'_builtin' => true,
				        	'public' => true
				        );

				        $output = 'objects';
				        $post_types = get_post_types($args, $output);
				        ksort($post_types, SORT_ASC); ?>
				        <td>
				        	<select name="uscreen_post_type"><?php
				        		foreach ($post_types as $post_type) { 
				        			$exclude = array('attachment', 'revision', 'nav_menu_item', 'custom_css', 'oembed_cache', 'user_request', 'customize_changeset');
				        			if(TRUE === in_array($post_type->name, $exclude))
                						continue;
                					if (post_type_exists($post_type->name)) : ?>
				        				<option value="<?php echo $post_type->name; ?>" <?php if(get_option('uscreen_post_type') == $post_type->name ) { echo 'selected="selected"'; } ?>><?php echo $post_type->label; ?></option><?php
				        			endif;
				        		} ?>
				        	</select>
				        </td>
			        </tr>

			        <tr valign="top">
				        <th scope="row">Enable Logging</th>
				        <td><input type="checkbox" name="uscreen_logging"  value='yes' <?php if( get_option('uscreen_logging') == 'yes' ) { echo 'checked="checked"'; } ?> /></td>
			        </tr>
			    </table>
			    <?php submit_button(); ?>
			</form>
		</div>
		<?php /*
		<div class="wrap">
			<h2>Uscreen Webhook URLs</h2>
			<table class="form-table">
			<?php 
				$WP_USCREEN_WEBHOOK =  array(
				    "user_created"          => "User Created",
				    "order_paid"            => "Order Paid",
				    "subscription_canceled" => "Access canceled",
				    "success_recurring"     => "Recurring Payment Successful",
				    "video_play"            => "Video Played",
				    "video_watched"         => "Watched video",
				    "quiz_finished"         => "Quiz Finished",
				    "user_signed_in"        => "User signed in",
				    "purchased_offer"       => "Purchased offer",
				    "added_to_favorites"    => "Added to favorites",
				    "document_download"     => "Document Download",
				    "invoice_overdue"       => "Invoice Overdue",
				    "program_completed"     => "Program Completed",
				    "ownership_created"     => "Assigned Offer"
				);
				
				foreach($WP_USCREEN_WEBHOOK as $key=>$value) {
					echo '<tr valign="top">';
					echo '<th scope="row">'.$value.'</th>';
					echo '<td>'.get_bloginfo('url').'/wp-admin/wp-ajax.php?action='.WP_USCREEN_WEBHOOK_PREFIX.$key.'</td>';
					echo '</tr>';
				}
			?>				
			</table>
		</div> 
	<?php
		*/
    }
}

uscreenAdminInterface::getInstance();

?>