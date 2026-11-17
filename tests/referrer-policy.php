<?php
/**
 * Read-only integration regression: run with WP-CLI, skipping plugins and themes.
 * wp eval-file tests/referrer-policy.php --skip-plugins --skip-themes
 * An optional argument selects a baseline plugin file.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run this file with wp eval-file.\n" );
}
if ( ! class_exists( 'BE_Media_From_Production' ) ) {
	require isset( $args[0] ) ? $args[0] : dirname( __DIR__ ) . '/be-media-from-production.php';
}

$production = 'https://production.example.test/subsite';
$filter = static function () use ( &$production ) { return $production; };
add_filter( 'be_media_from_production_url', $filter );
$uploads = wp_get_upload_dir();
$remote = str_replace( trailingslashit( site_url() ), trailingslashit( $production ), trailingslashit( $uploads['baseurl'] ) );
$checks = 0;
$failures = array();
$check = static function ( $condition, $message ) use ( &$checks, &$failures ) {
	$checks++;
	if ( ! $condition ) {
		$failures[] = $message;
	}
};
try {
	foreach ( array( 'the_content', 'get_avatar', 'wp_content_img_tag', 'wp_get_attachment_image' ) as $hook ) {
		$html = '<img src="' . $remote . 'photo.jpg" alt="A &amp; B" referrerpolicy="origin">';
		$result = apply_filters( $hook, $html, 'test', 0 );
		$check( false !== strpos( $result, 'referrerpolicy="same-origin"' ), "$hook: production image policy missing" );
		$check( false !== strpos( $result, 'alt="A &amp; B"' ), "$hook: image alt changed" );
	}
	$html = '<img src="data:image/gif;base64,R0lGODlh" srcset="' . $remote . 'photo.jpg 2x">';
	$check( false !== strpos( apply_filters( 'wp_get_attachment_image', $html ), 'referrerpolicy="same-origin"' ), 'Production srcset candidate not handled' );
	$html = '<img src="' . $uploads['baseurl'] . '/be-media-missing-referrer.jpg">';
	$result = apply_filters( 'the_content', $html );
	$check( false !== strpos( $result, $remote . 'be-media-missing-referrer.jpg' ) && false !== strpos( $result, 'referrerpolicy="same-origin"' ), 'Rewritten content image policy missing' );

	foreach ( array( $uploads['baseurl'] . '/local.jpg', 'https://unrelated.example.test/photo.jpg', 'https://production.example.test.evil/subsite/wp-content/uploads/photo.jpg', 'https://production.example.test/subsite/wp-content/uploads-extra/photo.jpg' ) as $url ) {
		$html = '<img src="' . $url . '" referrerpolicy="origin">';
		$check( $html === apply_filters( 'wp_get_attachment_image', $html ), 'Local or unrelated image was changed: ' . $url );
	}
	$html = '<a href="' . $remote . 'photo.jpg">Download</a>';
	$check( $html === apply_filters( 'wp_get_attachment_image', $html ), 'Non-image markup changed' );
	$production = '';
	$html = '<img src="' . $remote . 'photo.jpg">';
	$check( $html === apply_filters( 'wp_get_attachment_image', $html ), 'Disabled fallback changed markup' );
	$production = site_url();
	$html = '<img src="' . $uploads['baseurl'] . '/photo.jpg">';
	$check( $html === apply_filters( 'wp_get_attachment_image', $html ), 'Same-site production changed markup' );
} finally {
	remove_filter( 'be_media_from_production_url', $filter );
}
if ( $failures ) {
	WP_CLI::error( implode( "\n", $failures ) );
}
WP_CLI::success( "$checks production image referrer checks passed." );
