<?php
/*
Plugin Name: 	Secondary Featured Image
Plugin URI:		https://github.com/mahdiyazdani/Secondary-Featured-Image
Description:	Adds a meta box to posts, pages, and products that will allow you to upload an image using WordPress media uploader and use it as sort of a SECONDARY featured image.
Version:     	1.0.0
Author:      	Mahdi Yazdani
Author URI:  	https://www.mypreview.one
Text Domain: 	secondary-featured-image
Domain Path: 	/languages
License:     	GPL3
License URI: 	https://www.gnu.org/licenses/gpl-3.0.html

Secondary Featured Image is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Secondary Featured Image. If not, see https://www.gnu.org/licenses/gpl-3.0.html.
*/
// Prevent direct file access
defined('ABSPATH') or exit;
define('SECONDARY_FEATURED_IMAGE_SLUG', 'secondary-featured-image');
define('SECONDARY_FEATURED_IMAGE_VERSION', '1.0.0');
if (!class_exists('Secondary_Featured_Image')):
	/**
	 * The Secondary Featured Image - Class
	 */
	final class Secondary_Featured_Image

	{
		private static $_instance = null;
		/**
		 * Main Secondary_Featured_Image instance
		 *
		 * Ensures only one instance of Secondary_Featured_Image is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 */
		public static function instance()

		{
			if (is_null(self::$_instance)) self::$_instance = new self();
			return self::$_instance;
		}
		/**
		 * Setup class
		 *
		 * @since 1.0.0
		 */
		public function __construct()

		{
			define('SECONDARY_FEATURED_IMAGE_BASENAME', plugin_basename(__FILE__));
			define('SECONDARY_FEATURED_IMAGE_ADMIN_ASSETS_URL', esc_url(trailingslashit(plugins_url('admin/', SECONDARY_FEATURED_IMAGE_BASENAME))));
			add_action('admin_enqueue_scripts', array(
				$this,
				'enqueue_admin'
			) , 10);
			add_action('add_meta_boxes', array(
				$this,
				'add_meta_box'
			) , 10);
			add_action('save_post', array(
				$this,
				'save_meta_box'
			) , 10, 1);
		}
		/**
		 * Cloning instances of this class is forbidden.
		 *
		 * @since 1.0.0
		 */
		protected function __clone()
		{
			_doing_it_wrong(__FUNCTION__, __('Cloning instances of this class is forbidden.', 'secondary-featured-image') , SECONDARY_FEATURED_IMAGE_VERSION);
		}
		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __wakeup()

		{
			_doing_it_wrong(__FUNCTION__, __('Unserializing instances of this class is forbidden.', 'secondary-featured-image') , SECONDARY_FEATURED_IMAGE_VERSION);
		}
		/**
		 * Log the plugin version number.
		 *
		 * @since 1.0.0
		 */
		public function _log_version_number()

		{
			update_option(SECONDARY_FEATURED_IMAGE_SLUG . '-version', SECONDARY_FEATURED_IMAGE_VERSION);
		}
		/**
		 * Enqueue scripts and styles.
		 *
		 * @see   https://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
		 * @since 1.0.0
		 */
		public function enqueue_admin($hook)

		{
			wp_enqueue_script('secondary-featured-image-admin-scripts', SECONDARY_FEATURED_IMAGE_ADMIN_ASSETS_URL . '/js/scripts.js', array(
				'jquery'
			) , SECONDARY_FEATURED_IMAGE_VERSION, true);
		}
		/**
		 * Register secondary featured image metabox.
		 *
		 * @link 	 https://developer.wordpress.org/reference/functions/add_meta_box/
		 * @since    1.0.0
		 */
		public function add_meta_box()

		{
			$post_types = apply_filters('secondary_featured_image_post_types', array(
				'post',
				'page',
				'product'
			));
			foreach($post_types as $post_type):
				add_meta_box('secondary-featured-image', __('Secondry Featured Image', 'secondary-featured-image') , array(
					$this,
					'meta_box_html'
				) , $post_type, 'side', 'low');
			endforeach;
		}
		/**
		 * Function that displays the secondary featured image metabox with
		 * the desired WordPress media uploader as part of the larger edit post/page.
		 *
		 * @link 	 https://developer.wordpress.org/reference/functions/add_meta_box/#parameters
		 * @since    1.0.0
		 */
		public function meta_box_html($post)

		{
			global $content_width, $_wp_additional_image_sizes;
			$image_id = get_post_meta($post->ID, '_secondary_featured_image_id', true);
			$old_content_width = $content_width;
			$content_width = 254;
			if ($image_id && get_post($image_id)):
				if (!isset($_wp_additional_image_sizes['post-thumbnail'])):
					$thumbnail_html = wp_get_attachment_image($image_id, array(
						$content_width,
						$content_width
					));
				else:
					$thumbnail_html = wp_get_attachment_image($image_id, 'post-thumbnail');
				endif;
				if (!empty($thumbnail_html)):
					$content = $thumbnail_html;
					$content.= '<p class="hide-if-no-js"><a href="javascript:;" id="remove_secondary_featured_image_button" >' . esc_html__('Remove featured image', 'secondary-featured-image') . '</a></p>';
					$content.= '<input type="hidden" id="upload_secondary_featured_image" name="_secondary_featured_cover_image" value="' . esc_attr($image_id) . '" />';
				endif;
				$content_width = $old_content_width;
			else:
				$content = '<img src="" style="width:' . esc_attr($content_width) . 'px;height:auto;border:0;display:none;" />';
				$content.= '<p class="hide-if-no-js"><a title="' . esc_attr__('Set featured image', 'secondary-featured-image') . '" href="javascript:;" id="upload_secondary_featured_image_button" id="set-secondary-featured-image" data-uploader_title="' . esc_attr__('Featured Image', 'secondary-featured-image') . '" data-uploader_button_text="' . esc_attr__('Set featured image', 'secondary-featured-image') . '">' . esc_html__('Set featured image', 'secondary-featured-image') . '</a></p>';
				$content.= '<input type="hidden" id="upload_secondary_featured_image" name="_secondary_featured_cover_image" value="" />';
			endif;
			echo $content;
		}
		/**
		 * Action which triggers whenever a post or (custom) page is created or updated.
		 *
		 * @link 	 https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
		 * @since    1.0.0
		 */
		public function save_meta_box($post_id)

		{
			if (isset($_POST['_secondary_featured_cover_image'])):
				$image_id = (int)$_POST['_secondary_featured_cover_image'];
				update_post_meta($post_id, '_secondary_featured_image_id', $image_id);
			endif;
		}
	}
endif;
/**
 * Returns the main instance of The Secondary Featured Image.
 * Class to prevent the need to use globals.
 *
 * @since 1.0.0
 */
if (!function_exists('secondary_featured_image_initialization')):
	function secondary_featured_image_initialization()
	{
		return Secondary_Featured_Image::instance();
	}
	secondary_featured_image_initialization();
endif;
/**
 *  Get the image on the front of the page.
 *
 *	$image_id = get_post_meta($post_id, '_secondary_featured_image_id', true);
 *	echo wp_get_attachment_image($image_id, 'post-thumbnail', array(
 * 		'class' => 'img-responsive'
 *  ));
 */
