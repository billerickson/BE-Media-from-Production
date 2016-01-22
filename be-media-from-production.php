<?php
/**
 * Plugin Name: BE Media from Production
 * Plugin URI:  http://www.github.com/billerickson/be-media-from-production
 * Description: Uses local media when it's available, and uses the production server for rest.
 * Author:      Bill Erickson
 * Author URI:  http://www.billerickson.net
 * Version:     1.0.0
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
	public $start_month = 10;
	
	/**
	 * Start Year 
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $start_year = 2015;
	
	/**
	 * Primary constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// Set upload directories
		add_action( 'init',                               array( $this, 'set_upload_directories' )     );
		
		// Update Image URL
		add_filter( 'wp_get_attachment_image_src',        array( $this, 'image_src'              )     );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'image_attr'             ), 99 );
		
	}
	
	/**
	 * Set upload directories
	 *
	 * @since 1.0.0
	 */
	function set_upload_directories() {
		
		if( empty( $this->directories ) )
			$this->directories = $this->get_upload_directories();
		
	}

	/**
	 * Determine Upload Directories
	 *
	 * @since 1.0.0
	 */
	function get_upload_directories() {
	
		// Include all upload directories starting from a specific month and year
		$month = $this->start_month;
		$year = $this->start_year;
	
		$upload_dirs = array();

		for( $i = 0; $year . $month <= date( 'Ym' ); $i++ ) {
			$upload_dirs[] = $year . '/' . $month;
			$month++;
			if( 13 == $month ) {
				$month = 1;
				$year++;
			}
			$month = str_pad( $month, 2, '0', STR_PAD_LEFT );
		}
		
		return $upload_dirs;
			
	}

	/**
	 * Modify Main Image URL
	 *
	 * @since 1.0.0
	 * @param array $image
	 * @return array $image
	 */
	function image_src( $image ) {
	
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
		
		if( isset( $attr['srcset'] ) )
			$attr['srcset'] = $this->update_image_url( $attr['srcset'] );
		return $attr;

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
		
		$upload_dirs = $this->directories;
		if( empty( $upload_dirs ) )
			return $image_url;
			
		if( empty( $this->production_url ) )
			return $image_url;
	
		$exists = false;
		foreach( $upload_dirs as $option )
			if( strpos( $image_url, $option ) )
				$exists = true;
		
		if( ! $exists ) {
			$image_url = str_replace( home_url(), $this->production_url, $image_url );
		}
			
		return $image_url;
	}
	
}

new BE_Media_From_Production;