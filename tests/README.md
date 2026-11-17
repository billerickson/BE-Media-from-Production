# Attachment resolution integration checks

Use a **disposable WordPress 6.7+ installation and database** with WP-CLI. The script creates attachment records and a temporary local-file fixture, then removes them. It overrides the production URL only in its own process. Do not run against a live site.

From this checkout, run:

```bash
wp eval-file tests/attachment-url-to-postid.php --path=/path/to/disposable/wordpress --skip-plugins --skip-themes
```

The disposable installation should have no MU-plugins or other lookup customizations. The script loads the checkout's plugin itself; do not activate another copy.

The checks exercise WordPress's real attachment APIs and database. They cover originals, registered sizes, scaled images, exact-filename collisions, false size guesses, query strings, HTTP/HTTPS, request caching of hits and misses, unrelated/local URLs, earlier filter results, configuration changes, custom uploads mappings, and the absence of extra database queries during ordinary local/remote URL rewriting.

To demonstrate the regression against a baseline, export the original plugin file and pass it as the first script argument:

```bash
git show origin/master:be-media-from-production.php > /tmp/be-media-baseline.php
wp eval-file tests/attachment-url-to-postid.php /tmp/be-media-baseline.php --path=/path/to/disposable/wordpress --skip-plugins --skip-themes
```

The baseline should fail the production-URL resolution and repeated-query checks. No PHPUnit installation or mock database is required.

## Production image referrers

`referrer-policy.php` is a read-only WordPress 6.2+ integration check. It exercises the content, avatar, and attachment image filters, production srcset candidates, URL rewriting, and local/unrelated/disabled fallback controls. It does not create media or database records.

```bash
wp eval-file tests/referrer-policy.php --path=/path/to/wordpress --skip-plugins --skip-themes
```

Pass a baseline plugin file as the first argument to demonstrate the missing referrer policy before the fix.
