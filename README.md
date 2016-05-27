# BE Media from Production

Contributors: billerickson  
Requires at least: 4.3
Tested up to: 4.5.2  
Stable tag: 1.2.0  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

Uses local media when available, and uses the production server for the rest.

## Description

When redesigning a website with a large uploads directory, you might not need all those uploads in your development 
or staging environment, but you also don't want to see broken images throughout the site. 

This plugin lets you use the production server for some or all of the media.

The simplest method (Option 1) is to use production for all media - you simply define the production URL.

You can also migrate over a few months of uploads, and use the production site's URL for all other images. You can manually provide a list of directories to include (Option 2). Or you can set the start month and year of the oldest upload directory you carried over, and the plugin will automatically create the list of all directories following that (Option 3). 

This last option is useful if you'll be creating content in your development environment, so that each month you won't have to edit the list of local upload directories.

Available Filters:
* `be_media_from_production_url` - Specify the Production URL
* `be_media_from_production_start_month` - Specify the Start Month
* `be_media_from_production_start_year` - Specify the Start Year
* `be_media_from_production_directories` - Manually set the upload directories to use

## Installation

Option 1: In your theme or core functionality plugin, specify the Production URL. This will use the production server for ALL media. [Example](https://gist.github.com/billerickson/74b71dae3adccd2d478c77c5a5dbe00a)

Option 2: In your theme or core functionality plugin, specify the Production URL and specific directories using the provided filters. [Example](https://gist.github.com/billerickson/d4365166ba004bb45e9a)

Option 3: In your theme or core functionality plugin, specify the Production URL, Start Month and End Month using the provided filters. [Example](https://gist.github.com/billerickson/dd6639cc11e4464512e4)
