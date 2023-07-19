=== BE Media from Production ===
Contributors: billerickson
Tags: image, images, media, staging, local, development
Requires at least: 4.3
Tested up to: 6.3
Stable tag: 1.7.0

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

**Using with WP Migrate**

[WP Migrate](https://deliciousbrains.com/wp-migrate-db-pro/) is my preferred tool for pushing/pulling databases between environments. The media files functionality allows you to transfer media between environments along with the database.

When redesigning a website, I keep all the media on my development server and push up new media uploads along with the database.

Set up a "push" profile to push your local database to the development server. Make sure "Media Files" is checked and select "Compare, then upload".

Set up a "pull" profile to pull the development database locally. Do not include media in your pull. Any missing media will be handled by BE Media from Production.
