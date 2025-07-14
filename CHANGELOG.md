# Change Log
All notable changes to this project will be documented in this file, formatted via [this recommendation](http://keepachangelog.com/).

## [1.11.0] = 2025-07-14
- Add filter `be_media_from_production_local_site_url` for customizing the local site URL, see #31, props @Levdbas
- Fix PHP warning if srcset is empty, see #30, props 

## [1.10.0] = 2025-06-10
- Add support for WPML, see #26, props @delwin
- Allow plugin to be updated when in mu-plugins directory, see #27, props @delwin
- Filter image srcset, see #29, props @hansschuijff

## [1.9.0] = 2025-04-23
- Update image URLs inside `get_avatar`, useful when using locally hosted avatars (ex: Simple Local Avatars plugin)

## [1.8.0] = 2024-10-14
### Changed
- Plugin updates come from GitHub now instead of WordPress.org
- Updated the example in README showing how to install via wp cli

## [1.7.0] = 2023-07-19
### Changed
- Update image URLs inside the block editor, see #20
- Add support for site editor, see #19
- Updated the WP Migrate information in the readme
- Update WordPress.org assets (banner + icon), add CultivateWP as an author
