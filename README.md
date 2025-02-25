# oik-tunes 
![banner](assets/oik-tunes-banner-772x250.jpg)
* Contributors: bobbingwide
* Donate link: https://www.oik-plugins.com/oik/oik-donate/
* Tags: getID3, recordings, tracks, import
* Requires at least: 6.7
* Tested up to: 6.7.1
* Stable tag: 2.0.1
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

## Description 
Import track information into custom post types of Recordings ( CD, DVD ) and Tracks.

The original purpose of this plugin was to create a discography of album tracks by the British Prog Rock band Caravan.

* The files were directly accessible to a locally installed WordPress server running on a Windows machine.
* Each album was loaded by pointing at the folder that contained the album.
* The oik-recordings and oik-tracks custom post types were then exported from the local site,
* The oik-recordings XML file was imported into the target site.
* The oik-tracks XML file was imported into the target site.
* The album art was then uploaded manually.
* A discography page was created using the oik shortcode [bw_pages]

The accuracy of the original data was totally dependent upon the the data obtained by the getID3 library.

## Installation 
1. Upload the contents of the oik-tunes plugin to the `/wp-content/plugins/oik-tunes' directory
1. Activate the oik-tunes plugin through the 'Plugins' menu in WordPress
1. Use the oik options "Import tracks" menu item to import recording and track information from files accessible to your server

## Frequently Asked Questions 
# Does this plugin import the tracks? 
No. This plugin does not enable the tracks to be played, downloaded or purchased.
It only builds the catalogue of recordings.

# What file types are supported? 
This plugin was used to load the information from Windows Media Audio files.
It has not been tested with all the other media types that the getID3() PHP library supports.

# How does it process a folder? 
The plugin should be able to import all the relevant files from a folder and its child folders.
But it will take a long time doing this so it's best to process a single folder at a time.

# What files are not handled? 
Files not handled are:
* .jpg - e.g. the AlbumArt files
* .db - the thumbnail database
* .ini - e.g.
* and any files without file extensions

# How do you recommend using it? 
Load the information on a local server then export it to your hosted server.

* Run it locally importing albums (recordings) one at a time
* Check results by viewing the "discography"
* Export the oik-recordings
* Export the oik-tracks
* Import the oik-recordings
* Import the oik-tracks
* Upload album cover images to the oik-recordings

* Note: You should make it clear to web site visitors that this plugin does not allow the website visitor to listen to the music.
It's only there to help you build the recording catalog.

# What version of getID3 does it use? 
The latest stable version is 1.9.23 - released 2023/10/19
See https://github.com/jamesheinrich/getid3

## Screenshots 
1. oik-tunes in action

## Upgrade Notice 
# 2.0.1 
Update for recording post date in the list of other track versions.

# 2.0.0 
Major update for officialcaravan.co.uk

# 1.0.0 
Update for support for PHP 8.1 and PHP 8.2

# 1.0.0-alpha-20190925 
Upgrade for PHP 7.3 support. This version was never released.

# 0.1.0316 
Needed to import tracks which are missing the UniqueFileIdentifier.

# 0.1.0314 
Dependent upon oik v2.0-alpha and oik fields v1.18

# 0.1.0302 
Dependent upon oik v2.0-alpha and oik fields v1.18.0302

# 0.1.0218 
This plugin is dependent upon oik v1.18 and oik fields v1.18

## Changelog 
# 2.0.1 
* Changed: Other versions: Add recording post date #15
* Changed: Support site editor and front-end #14

# 2.0.0 
* Changed: _oikt_original virtual field - only show when it's the original.
* Changed: Other versions: highlight original recording #12
* Changed: Support Original version update when slug has changed #11
* Changed: Add Wikipedia and Cocacamp links to oik-recording #13
* Changed: Add admin interface to automatically set the Original noderef _oikt_original #11
* Changed: _oikt_original: Display Not set when original track isn't selected #12
* Changed: Refactor oik_tunes_theme_other_versions to include track duration and individiual links #12
* Added: Add _oikt_original noderef to oik-track #11
* Changed: Recording to breadcrumb links #12
* Changed: Update post meta fields #9.
* Changed: Add filter for top-level recordings on the Discography page #10
* Changed: Change oik_tunes_query_recording to find the recording for the selected parent #7
* Changed: Set menu_order for recordings not tracks #7
* Added: Attempt to play the track if TUNES_FOLDER and DVINYL_SYMLINK defined #9
* Changed: Cater for collections #7
* Changed: Hardcoded solution for Composer's aliases #8
* Added: Add logic to play a locally served browser accessible track #9
* Changed: Cater for the box set #7
* Changed: Update oik-tunes admin to cater for a box set #7
* Changed: Support WordPress 6.7.1, the sb-field-block and advanced-query-loop #5
* Tested: With WordPress 6.7.1 and WordPress Multisite
* Tested: With PHP 8.3

# 1.0.0 
* Changed: Add PHPUnit tests for PHP 8.1 and PHP 8.2 #4
* Tested: With WordPress 6.4.1 and WordPress Multisite
* Tested: With Gutenberg 17.1.0
* Tested: With PHP 8.1 and PHP 8.2
* Tested: With PHPUnit 9.6

# 1.0.0-alpha-20190925 
* Fixed: Fix for UncaughtArgumentCountError on manage_edit-CPT_columns filter functions,https://github.com/bobbingwide/oik-tunes/issues/1
* Fixed: Cater for oik update for WordPress 5,https://github.com/bobbingwide/oik-tunes/issues/2
* Fixed: Update getID3 to fix Fatal error attempting import,https://github.com/bobbingwide/oik-tunes/issues/3
* Changed: Update dependencies on oik and oik-fields,https://github.com/bobbingwide/oik-tunes/issues/2
* Tested: With WordPress 5.2 and WordPress Multi Site
* Tested: With PHP 7.3

# 0.1.0316 
* Changed: Added support for creating a UniqueFileIdentifier from Artist;Year;Track

# 0.1.0314 
* Added: Tracks can now be re-imported, updating the fields
* Added: AJAX interface - used by oik-batch

# 0.1.0302 
* Changed: Improvements to allow easier export and import
* Changed: Improvements in identifying a Unique Recording Identifier

# 0.1.0218 
* Added: New code for officialcaravan.co.uk

## Further reading 
If you want to read more about the oik plugins then please visit the
[oik plugin](https://www.oik-plugins.com/oik)
**"the oik plugin - for often included key-information"**
