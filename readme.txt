=== BE Media from Production ===
Contributors: billerickson
Tags: image, images, media, staging, local, development
Requires at least: 4.3
Tested up to: 5.4
Stable tag: 1.6.0

For developers - Uses local media when available, and uses the production server for the rest.

== Description ==

When redesigning a website with a large uploads directory, you might not need all those uploads in your development
or staging environment, but you also don't want to see broken images throughout the site.

This plugin lets you use the production server for missing media. Define the production URL using a constant `BE_MEDIA_FROM_PRODUCTION_URL` or filter `be_media_from_production_url`.

In all cases, if a local file exists, it will be used in preference to the remote file.

== Installation ==

Once the plugin is installed, add the following constant to wp-config.php with your production URL.

```
define( 'BE_MEDIA_FROM_PRODUCTION_URL', 'https://www.billerickson.net' );
```

Alternatively, you can use the filter in your theme's functions.php file, a core functionality plugin, or a mu-plugin:

```
add_filter( 'be_media_from_production_url', function() {
	return 'https://www.billerickson.net';
});
```

**Installation via WP-CLI and constants**

```
wp plugin install --activate be-media-from-production
wp config set BE_MEDIA_FROM_PRODUCTION_URL https://www.billerickson.net --type=constant
```
