# BE Media from Production

Contributors: billerickson  
Requires at least: 3.9  
Tested up to: 4.4.1  
Stable tag: 1.0.0  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

Uses local media when available, and uses the production server for the rest.

## Description

When redesigning a website with a large uploads directory, you might not need all those uploads in your development 
or staging environment, but you also don't want to see broken images throughout the site. 

Migrate over only a few months of uploads, and use this plugin to use the production site's URL for all other images.

## Installation

1. Edit the `$production_url` variable to contain the production URL of your website. 
2. Edit the `$directories` variable to provide an array of upload directories ([example](https://gist.github.com/billerickson/bbfb0d2e467dc5591310)).
**OR**
Edit the `$start_month` and `$start_year` variables to specify the oldest upload directories you carried over. It will 
automatically create `$directories` if left empty. This is useful if you'll be creating new content, so you don't have to keep
editing the $directories variable with new months as the project goes on.
3. Activate the plugin.
 
