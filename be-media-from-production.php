<?php
/**
 * Plugin Name: BE Media from Production
 * Plugin URI:  http://www.github.com/billerickson/be-media-from-production
 * Description: Uses local media when it's available, and uses the production server for rest.
 * Author:      Bill Erickson
 * Author URI:  http://www.billerickson.net
 * Version:     1.6.0
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
	 * Primary constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// Update Image URLs
		add_filter( 'wp_get_attachment_image_src',        array( $this, 'image_src'              )     );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'image_attr'             ), 99 );
		add_filter( 'wp_prepare_attachment_for_js',       array( $this, 'image_js'               ), 10, 3 );
		add_filter( 'the_content',                        array( $this, 'image_content'          )     );
		add_filter( 'wp_get_attachment_url',              array( $this, 'update_image_url'       )     );

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
	 * Modify Images in Content
	 *
	 * @since 1.2.0
	 * @param string $content
	 * @return string $content
	 */
	function image_content( $content ) {
		$upload_locations = wp_upload_dir();

		$regex = '/https?\:\/\/[^\") ]+/i';
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

		$image_url = str_replace( trailingslashit( home_url() ), trailingslashit( $production_url ), $image_url );
		return $image_url;
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
}

new BE_Media_From_Production;
