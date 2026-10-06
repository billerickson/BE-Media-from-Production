<?php
/**
 * Integration regressions. Run with WP-CLI against a disposable WordPress install:
 * wp eval-file tests/attachment-url-to-postid.php --skip-plugins --skip-themes
 *
 * An optional first argument selects a baseline plugin file for regression checks.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run this file with wp eval-file.\n" );
}

require isset( $args[0] ) ? $args[0] : dirname( __DIR__ ) . '/be-media-from-production.php';

$production_url = 'https://production.example.test';
$production_filter = static function () use ( &$production_url ) {
	return $production_url;
};
add_filter( 'be_media_from_production_url', $production_filter );

global $wpdb;
$uploads = wp_get_upload_dir();
$directory = 'be-media-tests-' . wp_generate_password( 12, false, false );
$local_base = trailingslashit( $uploads['baseurl'] ) . $directory . '/';
$remote_base = str_replace( trailingslashit( site_url() ), trailingslashit( $production_url ), $local_base );
$ids = array();
$failures = array();
$checks = 0;
$lookup_queries = array();

$record_query = static function ( $query ) use ( &$lookup_queries ) {
	if ( false !== strpos( $query, "meta_key = '_wp_attached_file'" ) ) {
		$lookup_queries[] = $query;
	}
	return $query;
};
add_filter( 'query', $record_query );

$check = static function ( $condition, $message ) use ( &$checks, &$failures ) {
	$checks++;
	if ( ! $condition ) {
		$failures[] = $message;
		WP_CLI::warning( $message );
	}
};

$create_attachment = static function ( $filename, $metadata = array() ) use ( &$ids, $uploads, $directory ) {
	$id = wp_insert_attachment(
		array( 'post_title' => $filename, 'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit' ),
		trailingslashit( $uploads['basedir'] ) . $directory . '/' . $filename
	);
	if ( is_wp_error( $id ) ) {
		throw new RuntimeException( $id->get_error_message() );
	}
	$ids[] = $id;
	if ( $metadata ) {
		wp_update_attachment_metadata( $id, $metadata );
	}
	return $id;
};

try {
	$photo = $create_attachment( 'photo.jpg', array(
		'file' => $directory . '/photo.jpg',
		'sizes' => array(
			'thumbnail' => array( 'file' => 'photo-150x150.jpg', 'width' => 150, 'height' => 150 ),
			'card' => array( 'file' => 'photo-378x378.jpg', 'width' => 378, 'height' => 378 ),
		),
	) );
	$scaled = $create_attachment( 'wide-scaled.jpg', array(
		'file' => $directory . '/wide-scaled.jpg',
		'original_image' => 'wide.jpg',
		'sizes' => array(
			'card' => array( 'file' => 'wide-378x378.jpg', 'width' => 378, 'height' => 378 ),
			'thumbnail' => array( 'file' => 'wide-scaled-150x150.jpg', 'width' => 150, 'height' => 150 ),
		),
	) );
	$literal_size = $create_attachment( 'photo-150x150.jpg' );

	$cases = array(
		'original' => array( 'photo.jpg', $photo ),
		'registered size' => array( 'photo-378x378.jpg', $photo ),
		'exact filename beats a size fallback' => array( 'photo-150x150.jpg', $literal_size ),
		'scaled main file' => array( 'wide-scaled.jpg', $scaled ),
		'original of a scaled attachment' => array( 'wide.jpg', $scaled ),
		'size of a scaled attachment' => array( 'wide-378x378.jpg', $scaled ),
		'size retaining the scaled suffix' => array( 'wide-scaled-150x150.jpg', $scaled ),
		'unregistered size is not guessed' => array( 'photo-999x999.jpg', 0 ),
		'missing attachment' => array( 'missing.jpg', 0 ),
	);
	foreach ( $cases as $label => $case ) {
		list( $filename, $expected ) = $case;
		$lookup_queries = array();
		$actual = attachment_url_to_postid( $remote_base . $filename );
		$check( $expected === $actual, "$label: expected $expected, got $actual" );
		$check( 1 === count( $lookup_queries ), "$label: expected one attachment-file query" );
		$check( false === strpos( implode( '\n', $lookup_queries ), 'production.example.test' ), "$label: queried a production URL instead of an attachment path" );

		$queries_before = $wpdb->num_queries;
		$check( $expected === attachment_url_to_postid( $remote_base . $filename ), "$label: repeated lookup changed its result" );
		$check( $queries_before === $wpdb->num_queries, "$label: repeated lookup added database queries" );
	}

	$queries_before = $wpdb->num_queries;
	$check( $photo === attachment_url_to_postid( $remote_base . 'photo.jpg?version=2#image' ), 'query string and fragment should resolve to the same attachment' );
	$check( $photo === attachment_url_to_postid( str_replace( 'https://', 'http://', $remote_base ) . 'photo.jpg' ), 'HTTP and HTTPS should resolve to the same attachment' );
	$check( $queries_before === $wpdb->num_queries, 'equivalent URLs should use the cached result' );

	$unhandled = array(
		'local original' => array( $local_base . 'photo.jpg', $photo ),
		'unrelated origin' => array( 'https://unrelated.example.test/wp-content/uploads/' . $directory . '/photo.jpg', 0 ),
		'lookalike origin' => array( 'https://production.example.test.evil/wp-content/uploads/' . $directory . '/photo.jpg', 0 ),
		'lookalike uploads path' => array( 'https://production.example.test/wp-content/uploads-extra/' . $directory . '/photo.jpg', 0 ),
	);
	foreach ( $unhandled as $label => $case ) {
		$lookup_queries = array();
		$check( $case[1] === attachment_url_to_postid( $case[0] ), "$label: normal WordPress result changed" );
		$check( 1 === count( $lookup_queries ), "$label: added a lookup beyond WordPress's normal query" );
	}

	// Another resolver's ID or explicit miss must short-circuit without BE querying.
	foreach ( array( 0, $photo ) as $earlier_result ) {
		$earlier_filter = static function () use ( $earlier_result ) {
			return $earlier_result;
		};
		add_filter( 'pre_attachment_url_to_postid', $earlier_filter, 5 );
		$queries_before = $wpdb->num_queries;
		$check( $earlier_result === attachment_url_to_postid( $remote_base . 'another.jpg' ), 'an earlier resolver result was overwritten' );
		$check( $queries_before === $wpdb->num_queries, 'an earlier resolver result caused extra queries' );
		remove_filter( 'pre_attachment_url_to_postid', $earlier_filter, 5 );
	}

	$production_url = '';
	$lookup_queries = array();
	$check( 0 === attachment_url_to_postid( $remote_base . 'photo.jpg' ), 'disabled production fallback should leave the URL unhandled' );
	$check( 1 === count( $lookup_queries ), 'disabled production fallback added lookup queries' );

	foreach ( array( site_url(), set_url_scheme( site_url(), 'http' ) ) as $same_origin ) {
		$production_url = $same_origin;
		$lookup_queries = array();
		$check( $photo === attachment_url_to_postid( $local_base . 'photo.jpg' ), 'identical production and local origins changed normal resolution' );
		$check( 1 === count( $lookup_queries ), 'identical production and local origins should retain the core lookup' );
	}

	$production_url = 'https://new-production.example.test/subsite';
	$new_remote_base = str_replace( trailingslashit( site_url() ), trailingslashit( $production_url ), $local_base );
	$check( $photo === attachment_url_to_postid( $new_remote_base . 'photo.jpg' ), 'production URL changes or subdirectories did not resolve' );
	$check( 0 === attachment_url_to_postid( $remote_base . 'photo.jpg' ), 'the old production origin was still handled after configuration changed' );

	// Warm normal WordPress caches before checking BE's ordinary rewriting path.
	get_post( $photo );
	get_post_meta( $photo );
	$queries_before = $wpdb->num_queries;
	$check( $new_remote_base . 'photo.jpg' === wp_get_attachment_url( $photo ), 'missing local media was not rewritten to production' );
	$check( $queries_before === $wpdb->num_queries, 'normal media rewriting added database queries' );

	// Custom uploads must use the same local-site mapping as outgoing rewriting.
	$custom_uploads = static function ( $upload_dir ) {
		$upload_dir['baseurl'] = 'https://local-media.example.test/install/assets/uploads';
		return $upload_dir;
	};
	add_filter( 'upload_dir', $custom_uploads );
	$lookup_queries = array();
	$custom_remote_base = trailingslashit( $production_url ) . 'assets/uploads/' . $directory . '/';
	$check( 0 === attachment_url_to_postid( $custom_remote_base . 'photo.jpg' ), 'uploads outside the configured local site prefix were incorrectly handled' );
	$check( 1 === count( $lookup_queries ), 'unmapped custom uploads added queries' );
	$custom_local_site = static function () {
		return 'https://local-media.example.test/install';
	};
	add_filter( 'be_media_from_production_local_site_url', $custom_local_site );
	$check( $photo === attachment_url_to_postid( $custom_remote_base . 'photo.jpg' ), 'custom uploads and local-site URL mapping did not resolve' );
	remove_filter( 'be_media_from_production_local_site_url', $custom_local_site );
	remove_filter( 'upload_dir', $custom_uploads );

	// A real local file remains local, and still does not trigger an ID lookup.
	$local_file = trailingslashit( $uploads['basedir'] ) . $directory . '/photo.jpg';
	wp_mkdir_p( dirname( $local_file ) );
	file_put_contents( $local_file, 'Local file presence fixture.' );
	try {
		$queries_before = $wpdb->num_queries;
		$check( $local_base . 'photo.jpg' === wp_get_attachment_url( $photo ), 'available local media was rewritten to production' );
		$check( $queries_before === $wpdb->num_queries, 'available local media added database queries' );
	} finally {
		unlink( $local_file );
		rmdir( dirname( $local_file ) );
	}
} finally {
	remove_filter( 'query', $record_query );
	remove_filter( 'be_media_from_production_url', $production_filter );
	foreach ( $ids as $id ) {
		wp_delete_attachment( $id, true );
	}
}

if ( $failures ) {
	WP_CLI::error( count( $failures ) . " of $checks checks failed." );
}
WP_CLI::success( "$checks attachment resolution and query-count checks passed." );
