<?php
/*
Plugin Name: Disable Suite
Plugin URI: https://github.com/modshrink/disable-suite
Description: Disable some of WordPress feature.
Version: 0.1
Author: modshrink
Author URI: http://www.modshrink.com/
Text Domain: disable-suite
Domain Path: /languages
License: GPL2

Copyright 2015 modshrink(email :hello@modshrink.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA	02110-1301	USA
*/

$DisableSuite = new DisableSuite();

class DisableSuite {
	public function __construct() {
		// Text Domain
		load_plugin_textdomain( 'disable-suite', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Plugin Activation
		if ( function_exists( 'register_activation_hook' ) ) {
			register_activation_hook( __FILE__, array(&$this, 'activationHook') );
		}

		// Plugin Uninstall
		if ( function_exists( 'register_uninstall_hook' ) ) {
			register_uninstall_hook( __FILE__, 'DisableSuite::uninstallHook');
		}

	 // Version Check
		global $wp_version;
		if( version_compare( $wp_version, '3.8', '<' ) ) {
			add_action( 'admin_notices', array(&$this, 'notice') );
		}

		add_action( 'admin_init', array(&$this, 'pluginDeactivate') );
		add_action( 'admin_menu', array(&$this, 'add_pages') );

		if( get_option( 'ds_meta_generator' ) ) {
			remove_action( 'wp_head', 'wp_generator' );
		}

		if( get_option( 'ds_author_archive' ) ) {
			add_action( 'query_vars', array( &$this,'disable_author_archive' ) );
		}

		if( get_option( 'ds_comment_form' ) ) {
			add_action( 'template_redirect', array( &$this,'disable_all_comment_form' ) );
		}

		if( get_option( 'ds_xmlrpc' ) ) {
			add_action( 'init', array( &$this,'disable_xml_rpc' ) );
		}

		if( get_option( 'ds_revision' ) ) {
			add_action( 'init', array( &$this,'disable_revision' ) );
		}
	} 

	/**
	 * Plugin Deactivated from Update Message Box.
	 */
	public function pluginDeactivate() {
		if( is_plugin_active( 'disable-suite/disable-suite.php' ) && isset( $_GET['deactivatePluginKey'] ) ) {
			deactivate_plugins( 'disable-suite/disable-suite.php' );
			remove_action( 'admin_notices', array( &$this, 'notice' ) );
			add_action( 'admin_notices', array( &$this, 'pluginDeactivateMessage' ) );
		}
	}

	/**
	 * Message after plugin deactivated.
	 */
	public function pluginDeactivateMessage() { ?>
		<div id="message" class="updated"><p><?php _e( 'Plugin <strong>deactivated</strong>.' ) ?></p></div>
<?php }

	/**
	 * Plugin Activation
	 */
	public function activationHook() {
		// Installed Flag
		if ( !get_option( 'disable_suite_installed' ) ) {
			update_option( 'disable_suite_installed', 1 );
		}
	}

	/**
	 * Plugin Uninstall
	 */
	static function uninstallHook() {
		delete_option( 'disable_suite_installed' );
		delete_option( 'ds_meta_generator' );
		delete_option( 'ds_author_archive' );
		delete_option( 'ds_xmlrpc' );
		delete_option( 'ds_emoji' );
		delete_option( 'ds_comment_form' );
		delete_option( 'ds_revision' );
	}

	/**
	 * Add admin menu.
	 */
	public function add_pages() {
		add_options_page('Disable Suite', 'Disable Suite', 'level_8', __FILE__, array(&$this, 'plugin_options') );
	}

	/**
	 * Update Option
	 */
	public function save_checked( $post_value ) {
		if( isset( $_POST['action'] ) && $_POST['action'] == 'update' ) {
			$this->$post_value = $_POST[ $post_value ];
			if( isset( $_POST[ $post_value ] ) && $_POST[ $post_value ] == 1 ) {
				$this->bool = $_POST[ $post_value ];
			} else {
				$this->bool = '';
			}
			var_dump($this->bool);
			update_option( $post_value, $this->bool );
		}

		$checked = 'checked_' . $post_value;
		if( get_option( $post_value ) ) {
			$this->$checked = ' checked="checked"';
		}
	}

	/**
	 * Disable author archive
	 */
	public function disable_author_archive( $query_vars ) {
		$disable_var = array( 'author', 'author_name' );
		$query_vars = array_diff( $query_vars, $disable_var );
		return $query_vars;

//		if ( is_author() ) :
//			wp_redirect( home_url() );
//			exit;
//		endif;
	}

	/**
	 * Disable all comment form
	 */
	public function disable_all_comment_form() {
		add_filter( 'comments_open', array( &$this, '__return_false' ) );
	}

	/**
	 * Disable XML-RPC
	 */
	public function remove_xmlrpc_pingback_ping( $methods ) {
		unset( $methods['pingback.ping'] );
		return $methods;
	}

	public function disable_xml_rpc() {
		add_filter( 'xmlrpc_methods', array( &$this, 'remove_xmlrpc_pingback_ping' ) );
		add_filter( 'xmlrpc_enabled', array( &$this, '__return_false' ) );
	}

	/**
	 * Disable post revisions
	 */
	public function disable_revision() {
		add_filter( 'wp_revisions_to_keep', array( &$this, 'disable_revision_count' ), 999, 2 );
	}

	public function disable_revision_count( $num, $post ) {
		$num = 0;
		return $num;
	}


	/**
	 * Add admin menu.
	 */
	public function plugin_options() {
		$this->save_checked( 'ds_meta_generator' );
		$this->save_checked( 'ds_author_archive' );
		$this->save_checked( 'ds_xmlrpc' );
		$this->save_checked( 'ds_emoji' );
		$this->save_checked( 'ds_comment_form' );
		$this->save_checked( 'ds_revision' );
	 ?>
<div class="wrap">

	<h2>Disable Suite</h2>

	<form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		<?php wp_nonce_field('update-options'); ?>

		<table class="form-table">

			<tr valign="top">
				<th scope="row"><?php _e( 'Meta generator', 'disable-suite' ); ?></th>
				<td>
					<input type="checkbox" id ="ds_meta_generator" name="ds_meta_generator" value="1"<?php echo $this->checked_ds_meta_generator; ?> />
					<label for="ds_meta_generator"><?php _e( 'disable', 'disable-suite' ); ?></label>
					<p class="description"><?php _e( 'Hidden generator infomation on head meta.', 'disable-suite' ); ?></p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Author archive', 'disable-suite' ); ?></th>
				<td>
					<input type="checkbox" id ="ds_author_archive" name="ds_author_archive" value="1"<?php echo $this->checked_ds_author_archive; ?> />
					<label for="ds_author_archive"><?php _e( 'disable', 'disable-suite' ); ?></label>
					<p class="description"><?php _e( 'Redirect author archive to front page.', 'disable-suite' ); ?></p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'XML-RPC', 'disable-suite' ); ?></th>
				<td>
					<input type="checkbox" id ="ds_xmlrpc" name="ds_xmlrpc" value="1"<?php echo $this->checked_ds_xmlrpc; ?> />
					<label for="ds_xmlrpc"><?php _e( 'disable', 'disable-suite' ); ?></label>
					<p class="description"><?php _e( 'Disable XML-RPC.', 'disable-suite' ); ?></p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Emoji', 'disable-suite' ); ?></th>
				<td>
					<input type="checkbox" id ="ds_emoji" name="ds_emoji" value="1"<?php echo $this->checked_ds_emoji; ?> />
					<label for="ds_emoji"><?php _e( 'disable', 'disable-suite' ); ?></label>
					<p class="description"><?php _e( 'Disable Emoji.', 'disable-suite' ); ?></p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Comment Form', 'disable-suite' ); ?></th>
				<td>
					<input type="checkbox" id ="ds_comment_form" name="ds_comment_form" value="1"<?php echo $this->checked_ds_comment_form; ?> />
					<label for="ds_comment_form"><?php _e( 'disable', 'disable-suite' ); ?></label>
					<p class="description"><?php _e( 'All comment form close.', 'disable-suite' ); ?></p>
				</td>
			</tr>

			<tr valign="top">
				<th scope="row"><?php _e( 'Revisions', 'disable-suite' ); ?></th>
				<td>
					<input type="checkbox" id ="ds_revision" name="ds_revision" value="1"<?php echo $this->checked_ds_revision; ?> />
					<label for="ds_revision"><?php _e( 'disable', 'disable-suite' ); ?></label>
					<p class="description"><?php _e( 'Disable post revisions.', 'disable-suite' ); ?></p>
				</td>
			</tr>

		</table>

		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="page_options" value="new_option_name,some_other_option,option_etc" />

		<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" />
		</p>

	</form>
</div>
	<?php
	}

}
