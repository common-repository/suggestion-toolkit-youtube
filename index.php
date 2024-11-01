<?php

/*
Plugin Name: Suggestion Toolkit - Youtube
Plugin URI: https://erlycoder.com/product/suggestion-toolkit-youtube/
Description: This plugin extends Suggestion Toolkit plugin with the capability to suggest YouTube video & YouTube live streams.
Author: Sergiy Dzysyak
Version: 5.0
Author URI: http://erlycoder.com/
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if( !class_exists('SuggestionToolkit_Youtube') ){
	class SuggestionToolkit_Youtube {
		var $name = 'suggestion-toolkit-youtube';
		var $conf = [];
		var $generated = false;
		
		public $urls = [
			'extensions'	=> "admin.php?page=suggestion-toolkit-extensions",
			'settings'		=> "admin.php?page=suggestion-toolkit-youtube",
			'support'		=> "https://erlycoder.com/support/",
			'docs'			=> "https://erlycoder.com/knowledgebase_category/suggestion-toolkit/",
		];

		/**
		 * Constructor
		 * Sets hooks, actions, shortcodes, filters.
		 *
		 */
		function __construct(){
		
			load_plugin_textdomain( 'suggestion-toolkit', false, basename( __DIR__ ) . '/languages' );
			add_action( 'init', array( $this, 'init_scripts_and_styles' ) );

			add_filter( "relevant_related_posts_style_folders", function($folders){ $folders[] = plugin_dir_path(__FILE__)."styles/"; return $folders; }, 10, 1);
			add_filter('suggestion-toolkit-replace-cells', array($this, 'replace_cells'), 10, 3);
			add_filter("relevant_related_posts_rendered_block", array($this, 'append_html'), 10, 2);
			
			register_activation_hook( __FILE__, [$this, 'plugin_install']);
			register_deactivation_hook( __FILE__, [$this, 'plugin_uninstall']);

			if(is_admin()){
				add_action('admin_init', array($this, 'admin_init'));
				
				$plugin = plugin_basename( __FILE__ );
				add_filter( "plugin_action_links_$plugin", [$this, 'plugin_add_settings_link'] );
				add_filter( 'plugin_row_meta', [$this, 'plugin_appreciation_links'], 10, 4 );
				
				add_filter( "related_posts_post_types", [$this, 'related_posts_post_types'], 10, 1);
				
				add_action('suggestion-toolkit-admin-menu-items', function(){ 
					add_submenu_page('suggestion-toolkit', __( 'YouTube Integration', 'suggestion-toolkit'), __( 'YouTube Integration', 'suggestion-toolkit'),	'manage_options', 'suggestion-toolkit-youtube',	[$this, 'admin_page'], 10); 
				});
			}else{
				
			}

		}
		
		/**
		*	Plugin admin page
		*/
		public function admin_page(){
			settings_errors('suggestion-toolkit-youtube-group');
			$event_types = [''=>'', 'none'=>__("None", 'suggestion-toolkit'), 'upcomming'=>__("Upcomming", 'suggestion-toolkit'), 'live'=>__("Live", 'suggestion-toolkit'), 'completed'=>__("Completed", 'suggestion-toolkit')];
			
			?>
			<!-- Create a header in the default WordPress 'wrap' container -->
			<div class="wrap">
			
				<h1><?php _e("YouTube Integration", 'suggestion-toolkit'); ?></h1>
			
				
				<form method="post" action="options.php">
				<?php
					settings_fields( 'suggestion-toolkit-youtube-group' );
					do_settings_sections( 'suggestion-toolkit-youtube-group' );
				?>
				<div class="sggtool_admin_info">
					<div>
						<p><?php _e("Under this section you can configure YouTub integration for suggestions", 'suggestion-toolkit'); ?>.</p>

						<p><a href="https://erlycoder.com/support/" target="_blank"><?php _e("Let us know", 'suggestion-toolkit'); ?></a> <?php _e("if you are missing some features", 'suggestion-toolkit'); ?>.</p>
					</div>
					<img src="<?php echo plugins_url( 'assets/img/youtube.svg', __FILE__ ); ?>"/>
				</div>

				<h2><?php _e("YouTube API settings", 'suggestion-toolkit'); ?></h2>
				<table class="form-table">
					<tr>
						<td width="15%"><?php _e("YouTube API Key", 'suggestion-toolkit'); ?></td>
						<td width="20%">
							<input type="text" name="suggestion_toolkit_youtube_api_key" id="suggestion_toolkit_youtube_api_key" value="<?php echo get_option('suggestion_toolkit_youtube_api_key'); ?>"/>
						</td>
						<td>
							<?php _e("You should create App and API Key", 'suggestion-toolkit'); ?> <a href="https://console.cloud.google.com/" target="_blank"><?php _e("Google Cloud Platform", 'suggestion-toolkit'); ?></a>
						</td>
					</tr>
					<tr>
						<td><?php _e("Only my videos", 'suggestion-toolkit'); ?> (<?php _e("optional", 'suggestion-toolkit'); ?>)</td>
						<td>
							<input type="checkbox" <?php echo (get_option('suggestion_toolkit_youtube_my'))?"checked='checked'":""; ?> name="suggestion_toolkit_youtube_my" id="suggestion_toolkit_youtube_my" value="true"/>
						</td>
						<td>
							<?php //_e("", 'suggestion-toolkit'); ?>
						</td>
					</tr>
					<tr>
						<td><?php _e("Event Type", 'suggestion-toolkit'); ?> (<?php _e("optional", 'suggestion-toolkit'); ?>)</td>
						<td>
							<select autocomplete="off" name="suggestion_toolkit_youtube_event_type" id="suggestion_toolkit_youtube_event_type">
								<?php foreach($event_types as $key=>$val){ ?>
								<option <?php echo (get_option('suggestion_toolkit_youtube_event_type')==$key)?"selected=\"selected\"":""; ?> value="<?php echo $key; ?>"><?php echo $val; ?></option>
								<?php } ?>
							</select>
						</td>
						<td>
							<?php _e("If you want to promote live broadcasts only", 'suggestion-toolkit'); ?>
						</td>
					</tr>
				</table>
				
				
				
				
				<p>&nbsp;</p>
					

				<?php submit_button(); ?>
				</form>
			</div><!-- /.wrap -->
			<?php
		}
		
		/**
		 * Plugin save settings.
		 * 
		 */
		public static function admin_init() {
			register_setting( 'suggestion-toolkit-youtube-group', 'suggestion_toolkit_youtube_api_key', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => NULL,]);
			register_setting( 'suggestion-toolkit-youtube-group', 'suggestion_toolkit_youtube_my', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => NULL,]);
			register_setting( 'suggestion-toolkit-youtube-group', 'suggestion_toolkit_youtube_event_type');
			
		}
		
		/**
		* Extends supported by Suggestion Toolkit types.
		*
		* @param array $ptypes - types supported by the Suggestion Toolkit.
		* @return array - updated types supported by the Suggestion Toolkit.
		*/
		public function related_posts_post_types($ptypes){
		
			$ptypes[] = (object)['value'=>'youtube', 'label'=>__("YouTube", 'suggestion-toolkit')];
			
			return $ptypes;
		}
		
		/**
		 * Handle suggestions replacement according to active events settings.
		 * 
		 * @param string $type - type/position of the suggestion block.
		 * @param array $cells- 2 dimentional array of suggestions by post type (input).
		 * @return array - 2 dimentional array of suggestions by post type (output).
		 */
		function replace_cells($cells, $post_id, $cfg){
			if(!in_array('youtube', $cfg['ptypes'])) return $cells;
			
			$key = rawurlencode(get_option('suggestion_toolkit_youtube_api_key'));
			$my = rawurlencode(get_option('suggestion_toolkit_youtube_my'));
			$eventType = get_option('suggestion_toolkit_youtube_event_type');
			
			$num = $cfg['num']['youtube'];
			$keyword = rawurlencode($cfg['keyword']);
			if(!empty($cfg['ptypes_key']['youtube'])){ $keyword = rawurlencode($cfg['ptypes_key']['youtube']); }
			
			if(!empty($my)) $_forMine = "&forMine=true"; else $_forMine = "";
			if(!empty($eventType)) $_eventType = "&eventType={$eventType}"; else $_eventType = "";
			
			$url = "https://youtube.googleapis.com/youtube/v3/search?part=snippet{$_forMine}{$_eventType}&maxResults={$num}&q={$keyword}&type=video&key={$key}";
			
			$jsonData = wp_remote_retrieve_body(wp_remote_get($url));
			$rows = @json_decode($jsonData, true);
			
			if(!isset($rows['pageInfo'])) return $cells;
			
			$cells['youtube'] = [];
			if($rows['pageInfo']['resultsPerPage']>0) foreach($rows['items'] as $row){
				$cells['youtube'][] = (object) [
					'post_type'=>'youtube', 
					'ID'=>$row['id']['videoId'], 
					'post_title'=>$row['snippet']['title'], 
					'title'=>$row['snippet']['title'], 
					'image'=>$row['snippet']['thumbnails']['medium']['url'], 
					'url'=>"http://www.youtube.com/watch?v={$row['id']['videoId']}", 
					'date'=>$row['snippet']['publishTime'], 
					'onclick'=>"javascript: openVideo(); return false;" 
				];
			}
			
			return $cells;
		}
	
		/**
		* Append suggestion block with custom html code.
		*
		* @param array $html - suggestions block html code.
		* @return string - html code.
		*/	
		function append_html($html){
			ob_start();
			if(file_exists(get_template_directory()."/{$this->name}/html.youtube.php")){
				include get_template_directory()."/{$this->name}/html.youtube.php";
			}else{
				include plugin_dir_path(__FILE__)."styles/html.youtube.php";
			}
			$html_ext = ob_get_clean();
			
			return $html.$html_ext;
		}

		/**
		 * Plugin settings link.
		 * 
		 * @param array $links - array of plugin settings links.
		 * @return string - links array.
		 */
		function plugin_add_settings_link( $links ) {
			array_unshift( $links, '<a href="'.$this->urls['settings'].'">' . __( 'Settings', 'suggestion-toolkit') . '</a>');
			return $links;
		}
		
		/**
		 * Additional plugin meta.
		 * 
		 * @param array $plugin_meta - array of plugin meta.
		 * @param string $plugin_file - plugin file.
		 * @param array $plugin_data - array of plugin data.
		 * @param string $status - plugin section page.
		 * @return string - array of plugin meta.
		 */
		function plugin_appreciation_links ( $plugin_meta = array(), $plugin_file = '', $plugin_data = array(), $status = '' ) {

			$base = plugin_basename(__FILE__);
			if ($plugin_file == $base) {
				$donate_link = 'https://erlycoder.com/donate/';

				$plugin_meta['docs'] = '<a href="'.$this->urls['docs'].'" target="_blank"><span class="dashicons  dashicons-search"></span>' . __( 'Docs',  'suggestion-toolkit' ) . '</a> ' . __( 'and',  'suggestion-toolkit' ) . ' <a href="'.$this->urls['support'].'" target="_blank"><span class="dashicons  dashicons-admin-users"></span>' . __( 'Support',  'suggestion-toolkit' ) . '</a> ';
				$plugin_meta['ext'] = '<a class="suggestion_toolkit_go_premium" href="'.$this->urls['extensions'].'"><span class="dashicons  dashicons-cart"></span>' . __( 'Extensions',  'suggestion-toolkit' ) . '</a> ';
				
			}

			return $plugin_meta;
		}
		
		/**
		 * Init plugin. Init scripts, styles and blocks.
		 */		
		function init_scripts_and_styles(){
			wp_register_style( 'suggestion-toolkit-youtube', plugins_url( $this->name.'/assets/basic.css' ) );
			wp_enqueue_style( 'suggestion-toolkit-youtube' );
			
			wp_register_script('suggestion-toolkit-youtube',	plugins_url( 'js/popup.js', __FILE__ ));
			wp_enqueue_script('suggestion-toolkit-youtube');
			
			wp_register_script('suggestion-toolkit-youtube-api',	"https://www.youtube.com/iframe_api");
			wp_enqueue_script('suggestion-toolkit-youtube-api');
		}

		/**
		 * Plugin install routines. Check for dependencies.
		 * 
		 * Installation routines.
		 */
		public function plugin_install() {
			global $wpdb;
			$class_name = static::class;
			
			$parent_version = explode(".", get_plugin_data(preg_replace("/\b{$this->name}$/", '',  __DIR__ ) . "suggestion-toolkit/index.php")['Version'])[0];
			$this_version = explode(".", get_plugin_data(__FILE__)['Version'])[0];

			if ( !is_plugin_active( 'suggestion-toolkit/index.php' ) && ($parent_version!=$this_version) && current_user_can( 'activate_plugins' ) ) {
				// Stop activation redirect and show error
				wp_die("Sorry, but this plugin requires the Suggestion Toolkit {$this_version}.* Plugin to be installed and active. <br><a href=\"" . admin_url( 'plugins.php' ) . "\">&laquo; Return to Plugins</a>");
			}
		}
		
		public function plugin_uninstall() {
		}
		
	}
	
	$suggestion_toolkit_youtube_init = new SuggestionToolkit_Youtube();

}



?>
