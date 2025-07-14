<?php
/**
 * Plugin Name: BE Media from Production
 * Plugin URI:  http://www.github.com/billerickson/be-media-from-production
 * Description: Uses local media when it's available, and uses the production server for rest.
 * Author:      Bill Erickson
 * Author URI:  http://www.billerickson.net
 * Version:     1.11.0
 * Text Domain: be-media-from-production
 * Domain Path: languages
 *
 * BE Media from Production is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * BE Media from Production is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BE Media from Production. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    BE_Media_From_Production
 * @author     Bill Erickson
 * @since      1.0.0
 * @license    GPL-2.0+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Class
 * @since 1.0.0
 * @package BE_Media_From_Production
 */
class BE_Media_From_Production {

	/**
	 * Production URL
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $production_url = '';

	/**
	 * Holds list of upload directories
	 * Can set manually here, or allow function below to automatically create it
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $directories = array();

	/**
	 * Start Month
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $start_month = false;

	/**
	 * Start Year
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $start_year = false;

	/**
	 * Primary constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// Update Image URLs
		add_filter( 'wp_get_attachment_image_src',        array( $this, 'image_src'              )        );
		add_filter( 'wp_calculate_image_srcset',          array( $this, 'image_srcset'           ), 99    );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'image_attr'             ), 99    );
		add_filter( 'wp_prepare_attachment_for_js',       array( $this, 'image_js'               ), 10, 3 );
		add_filter( 'wp_content_img_tag',       	      array( $this, 'image_tag'              ), 10, 3 );
		add_filter( 'the_content',                        array( $this, 'image_content'          )        );
		add_filter( 'wp_get_attachment_url',              array( $this, 'update_image_url'       )        );
		add_filter( 'the_post',                           array( $this, 'update_post_content'    )        );
		add_filter( 'get_avatar',                         array( $this, 'image_content'          )        );

		// Plugin updates
		add_action( 'init', array( $this, 'updates' ) );

	}

	/**
	 * Modify Main Image URL
	 *
	 * @since 1.0.0
	 * @param array $image
	 * @return array $image
	 */
	function image_src( $image ) {

		if( isset( $image[0] ) )
			$image[0] = $this->update_image_url( $image[0] );
		return $image;

	}

	/**
	 * Modify Image Sources
	 *
	 * @since 1.10.0
	 * @param array|false $srcset
	 * @return array|false $srcset
	 */
	function image_srcset( $srcset ) {
		if( isset( $srcset ) && $srcset !== false ) {
			foreach( $srcset as $i => $src ) {
				$srcset[ $i ]['url'] = $this->update_image_url( $src['url'] );
			}
		}

		return $srcset;
	}

	/**
	 * Modify Image Attributes
	 *
	 * @since 1.0.0
	 * @param array $attr
	 * @return array $attr
	 */
	function image_attr( $attr ) {

		if( isset( $attr['srcset'] ) ) {
			$srcset = explode( ' ', $attr['srcset'] );
			foreach( $srcset as $i => $image_url ) {
				$srcset[ $i ] = $this->update_image_url( $image_url );
			}
			$attr['srcset'] = join( ' ', $srcset );
		}
		return $attr;

	}

	/**
	 * Modify Image for Javascript
	 * Primarily used for media library
	 *
	 * @since 1.3.0
	 * @param array      $response   Array of prepared attachment data
	 * @param int|object $attachment Attachment ID or object
	 * @param array      $meta       Array of attachment metadata
	 * @return array     $response   Modified attachment data
	 */
	function image_js( $response, $attachment, $meta ) {

		if( isset( $response['url'] ) )
			$response['url'] = $this->update_image_url( $response['url'] );

		foreach( $response['sizes'] as &$size ) {
			$size['url'] = $this->update_image_url( $size['url'] );
		}

		return $response;
	}

	/**
	 * Modify Image Tags
	 *
	 * @since 1.7.0
	 * @param string $filtered_image Full img tag with attributes that will replace the source img tag.
	 * @param string $context        Additional context, like the current filter name or the function name from where this was called.
	 * @param int    $attachment_id  The image attachment ID. May be 0 in case the image is not an attachment.
	 */
	function image_tag( $filtered_image, $context, $attachment_id ) {
		$upload_locations = wp_upload_dir();

		$regex = '/https?\:\/\/[^\" ]+/i';
		preg_match_all($regex, $filtered_image, $matches);

		foreach( $matches[0] as $url ) {
			if( false !== strpos( $url, $upload_locations[ 'baseurl' ] ) ) {
				$new_url = $this->update_image_url( $url );
				$filtered_image = str_replace( $url, $new_url, $filtered_image );
			}
		}

		return $filtered_image;
	}

	/**
	 * Modify Images in Content
	 *
	 * @since 1.2.0
	 * @param string $content
	 * @return string $content
	 */
	function image_content( $content ) {
		$upload_locations = wp_upload_dir();

		$regex = '/https?\:\/\/[^\" ]+/i';
		preg_match_all($regex, $content, $matches);

		foreach( $matches[0] as $url ) {
			if( false !== strpos( $url, $upload_locations[ 'baseurl' ] ) ) {
				$new_url = $this->update_image_url( $url );
				$content = str_replace( $url, $new_url, $content );
			}
		}
		return $content;
	}

	/**
	 * Convert a URL to a local filename
	 *
	 * @since 1.4.0
	 * @param string $url
	 * @return string $local_filename
	 */
	function local_filename( $url ) {
		$upload_locations = wp_upload_dir();
		$local_filename = str_replace( $upload_locations[ 'baseurl' ], $upload_locations[ 'basedir' ], $url );
		return $local_filename;
	}

	/**
	 * Determine if local image exists
	 *
	 * @since 1.4.0
	 * @param string $url
	 * @return boolean
	 */
	function local_image_exists( $url ) {
		return file_exists( $this->local_filename( $url ) );
	}

	/**
	 * Update Image URL
	 *
	 * @since 1.0.0
	 * @param string $image_url
	 * @return string $image_url
	 */
	function update_image_url( $image_url ) {

		if( ! $image_url )
			return $image_url;

		if ( $this->local_image_exists( $image_url ) ) {
			return $image_url;
		}

		$production_url = esc_url( $this->get_production_url() );
		if( empty( $production_url ) )
			return $image_url;
		
	       /**
		* Filters the home/site url of the local website
		* 
		* You can use this filter to modify the local site url if site_url() does not resolve to the actual website. This can be the case in a Bedrock based setup or similar.
		*
		* @param string $local_site_url the result of the site_url() function.
		*
		* @return string Updated local site url.
		*/
		$site_url  = apply_filters('be_media_from_production_local_site_url', site_url());
 		$image_url = str_replace(trailingslashit($site_url), trailingslashit($production_url), $image_url);
		return $image_url;
	}

	/**
	 * Update Post Content
	 */
	function update_post_content( $post ) {
		$post->post_content = $this->image_content( $post->post_content );
		return $post;
	}

	/**
	 * Return the production URL
	 *
	 * First, this method checks if constant `BE_MEDIA_FROM_PRODUCTION_URL`
	 * exists and non-empty. Than applies a filter `be_media_from_production_url`.
	 *
	 * @since 1.5.0
	 * @return string
	 */
	public function get_production_url() {
		$production_url = $this->production_url;
		if ( defined( 'BE_MEDIA_FROM_PRODUCTION_URL' ) && BE_MEDIA_FROM_PRODUCTION_URL ) {
			$production_url = BE_MEDIA_FROM_PRODUCTION_URL;
		}

		return apply_filters( 'be_media_from_production_url', $production_url );
	}

	/**
	 * Plugin updates via GitHub
	 */
	public function updates() {
		require dirname( __FILE__ ) . '/updater/plugin-update-checker.php';

		$myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://github.com/billerickson/be-media-from-production',
			__FILE__,
			'be-media-from-production',
			12,
			'',
			__FILE__
		);

		//Set the branch that contains the stable release.
		$myUpdateChecker->setBranch('master');
	}

}

new BE_Media_From_Production;
