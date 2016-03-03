# BE Media from Production

Contributors: billerickson  
Requires at least: 3.9  
Tested up to: 4.4.1  
Stable tag: 1.2.0  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

Uses local media when available, and uses the production server for the rest.

## Description

When redesigning a website with a large uploads directory, you might not need all those uploads in your development 
or staging environment, but you also don't want to see broken images throughout the site. 

Migrate over only a few months of uploads, and use this plugin to use the production site's URL for all other images.

The simplest method is to set the start month and year of the oldest upload directory you carried over. The plugin will automatically create the list of all directories following that. Alternatively, you can manually provide a list of directories to include.

Available Filters:
* `be_media_from_production_url` - Specify the Production URL
* `be_media_from_production_start_month` - Specify the Start Month
* `be_media_from_production_start_year` - Specify the Start Year
* `be_media_from_production_directories` - Manually set the upload directories to use

## Installation

Option 1: In your theme or core functionality plugin, specify the Production URL, Start Month and End Month using the provided filters. [Example](https://gist.github.com/billerickson/dd6639cc11e4464512e4)

Option 2: In your theme or core functionality plugin, specify the Production URL and specific directories using the provided filters. [Example](https://gist.github.com/billerickson/d4365166ba004bb45e9a)
