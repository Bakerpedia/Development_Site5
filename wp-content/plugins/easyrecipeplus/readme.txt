=== Easy Recipe Plus ===
Contributors: Jayce53
Tags: recipe, seo, hrecipe, Recipe View, microformatting, easy recipe, rich snippet, microdata
Requires at least: 3.6
Tested up to: 4.2.2
Stable tag: 3.3.3077

EasyRecipe makes it easy to enter, format and print recipes, as well as automagically doing the geeky stuff needed for Google's Recipe View.

== Description ==

Thanks for using EasyRecipe PLUS.

If you have comments, questions or problems, we want to help.

The best way to contact us is from the Support tab in the EasyRecipe settings.

You can also visit [EasyRecipe Support](http://support.easyrecipeplugin.com/)

== Changelog ==
= 3.3.3077 =
* Update: Work with the Content Aware Sidebar plugin

= 3.3 Build 3074 =
* Update: Tested with Wordpress 4.2.2
* Update: Explicitly set the character encoding on print
* Update: Handle non-standard jQuery versions better in live Formatting
* Update: Change snippet test tool URL to the current location on Google
* Bug fix: Clear link URL when inserting links (plus version)

= 3.3 Build 3070 =
* Update: Tested with Wordpress 4.2.1
* Bug fix: Fixed link insert on WP 4.2+ (Plus version)
* Bug fix: Fix Ziplist error (Free version)

= 3.3 Build 2998 =
* Update: tested with Wordpress 4.2
* Update: Change print code to fix blank recipes on print
* Update: Added optional debug logging
* Update: Added setting for object cache update
* Update: Updated to allow import from MacGourmet4 (Plus version)
* Update: Allow up to 40Mb import files (Plus version)
* Bug fix: <script> tags inserted in the editor Text mode were corrupted when switching back to Visual mode
* Bug fix: Inhibit Live Formatting while theme customizer is active
* Bug fix: Allow Save option to be set off in free version
* Bug fix: Fix Recipe Card automatic conversion (Plus version)
* Bug fix: Fix carbohydrates on Mastercook import (Plus version)

= 3.2 Build 2925 =
* Enhancement: Added BigOven save button (Plus version)
* Bug fix: Don't try to insert duplicate taxonomy terms if multiple recipes on a post use the same cuisine/course
* Update: Attempt to recover corrupted recipes in the recipe editor

= 3.2 Build 2885 =
* Enhancement: Enable Recipage recipe conversion
* Enhancement: Display Recipage recipes as EasyRecipe recipes (Plus version)
* Update: Better handling of shortcodes within a recipe
* Update: Handle links in instruction section headings
* Update: Don't open formatting popup if a link in a recipe is clicked when logged in as admin
* Update: Cater for sites that have huge numbers of registered users (Plus version)
* Update: Convert hard coded links and images in Ziplist recipes (Plus version)
* Update: Remove Ziplist Save button functionality (Plus version)
* Bug fix: Fix microformatting on Ziplist style when times missing and on nutrition data (Plus version)
* Bug fix: Supress times display when times missing on Ziplist recipes (Plus version)
* Bug fix: Live Formatting handling of quoted font names
* Bug fix: Improve popup window stacking handling on recipe entry

= 3.2 Build 2807 =
* Bug fix: Settings were cleared and not being saved if there were settings from very early versions of EasyRecipe still in the options file

= 3.2 Build 2802 =
* Enhancement: Generate taxonomies that can be used by the EasyIndex plugin
* Update: Changes for Wordpress 4.0
* Update: Use the more correct "ratingCount" rather than "reviewCount"
* Update: re-label "Serves" as the more correct "Yield"
* Bug fix: Self ratings weren't being displayed in some circumstances

= 3.2 Build 2708 =
* Update: Display Get Me Cooking and WP Ultimate Recipe recipes even if other plugin deactivated

= 3.2 Build 2704 =
* Bug fix: Fixed Ziplist recipe display

= 3.2 Build 2691 =
* Enhancement: Display Get Me Cooking recipes without the need to convert (Plus version)
* Enhancement: Display Recipe Card recipes without the need to convert (Plus version)
* Enhancement: Display WP Ultimate Recipe recipes without the need to convert (Plus version)
* Enhancement: Strip Jetpack and TinyMCE spellcheck plugin HTML before editing recipes (Plus version)
* Update: Changes to live Google snippet preview: timeout and format (Plus version)
* Bug fix: Some Live Formatting CSS saved with earlier versions crashed Live Formatting
* Bug fix: Captions on images inside recipes were not processed in some circumstances

= 3.2 Build 2646 =
* Bug fix: Oops again! Fix Live Formatting sections not being displayed

= 3.2 Build 2644 =
* Bug fix: Oops! The link fix for IE11 in the previous version broke link inserts outside recipes.

= 3.2 Build 2641 =
* Update: Strip blank lines from Ziplist ingredients and instructions (Plus version)
* Update: Allow for non-breaking spaces in [img], [url] shortcodes
* Update: Remove some old unused code
* Update: Standardise Live Formatting popup layout across more themes
* Update: Changes to handle themes that globally set <div> spacing (recipe entry)
* Update: Handle Genesis grid items better (Plus version)
* Bug fix: Fix recipe displaying as a shortcode when some other plugins present
* Bug fix: Process [br] shortcodes in Ziplist recipes correctly (Plus version)
* Bug fix: Remove extra "!important" from Live Formatting CSS
* Bug fix: Strip slashes from quoted extra CSS
* Bug fix: Remove spurious <a> tag when addding links in IE11

= 3.2 Build 2499 =
* Bug fix: Forgot to do Ziplist links in Summary and Notes!  (Plus version)
* Bug fix: Ratings on Ziplist recipes now work properly (Plus version)

= 3.2 Build 2496 =
* Bug fix: Handle embedded links properly in Ziplist recipes (Plus version)
* Bug fix: Suppress rating display properly if ratings disabled or no ratings present
* Bug fix: Handle nested formatting shortcodes better
* Bug fix: Do external shortcode processing for shortcodes in recipes

= 3.2 Build 2491 =
* Enhancement: Display Ziplist recipes without the need to convert (Plus version)
* Enhancement: Added "Self Rating" option
* Bug fix: Fix popup mask overlaying Settings page on WP versions prior to 3.9

= 3.2 Build 2429 =
* Enhancement: Added option to suppress warning when editor switched to Text mode
* Bug fix: Fixed missing photo microdata under WP 3.9
* Bug fix: Removed photo processing that caused the recipe print to crash on some servers

= 3.2 Build 2419 =
* Enhancement: Convert italic and bold formatting and image links in Ziplist recipes
* Enhancement: Use latest WP media manager
* Enhancement: Get caption, alt text and title data for images inserted into recipes (Plus version)
* Update: Changes for Wordpress 3.9

= 3.2 Build 2310 =
* Update: Changed support/diganostics to use the EasySupport plugin

= 3.2 Build 2298 =
* Enhancement: Use total time when converting from Ziplist if there is no prep or cooking time
* Update: Changes for Wordpress 3.9
* Bug fix: Javascript error on custom post pages that don't have an editor (e.g. Soliloquy slider, EasyIndex)
* Bug fix: Minor display error on Ziplist conversion popup
* Bug fix: Fix [br] shortcodes on print
* Bug fix: Print showing blank page in some circumstances

= 3.2 Build 2265 =
* Bug fix: Filter excerpt option messed up formatting on some themes in some circumstances

= 3.2 Build 2260 =
* Enhancement: Cleaner display of settings page
* Enhancement: Add EasyRecipe button on text editor toolbar
* Enhancement: Add option to filter non-display items from excerpts
* Enhancement: Suppress empty Ingredient and Instruction sections
* Enhancement: Add new version details popup on Support tab
* Enhancement: Added link to retrieve license key on Support tab
* Bug fix: Make the email link on guest post details on posts page a "mailto" link
* Bug fix: Increase the z-order on guest post details to ensure it pops up over the top of the posts page
* Bug fix: Error popups not being displayed on top
* Bug fix: Diagnostics sent to support did not include settings
* Bug fix: Style setting was lost when saved from Live Formatting and permalinks not used
* Bug fix: Fractions not converted to HTML entities on print
* Bug fix: Display diagnostics data when no permalinks on Windows servers

= 3.2 Build 2208 =
* Bug fix: Recipe entry:  Image insertion and save messed up by featured images

= 3.2 Build 2206 =
* Better notification of update failure when license key has not been entered
* Fixed minor javascript error on guest post recipe entry pge

= 3.2 Build 2199 =
* Tested with WP 3.8
* Workaround for WP bug that generates invalid HTML for multiple line breaks
* Recipe editor now recognises post thumbnails (featured image)
* Added Author, Recipe type, Cuisine and Yield to Live Formatting on Tastefully Simple styles
* Fix incorrect times when one recipe on multiple recipe page has no times
* Fixed PHP warning on diagnostics
* Confirm when closing a recipe entry popup withoput saving
* Fix for popups opening behind some themes' elements
* Fix print when more than 9 recipes on a page
* Fixed incompatiblity with Pinterest Pin It for Images plugin that disabled Print and Save buttons
* Convert 3/8 to HTML enitity fraction
* Clean up 16 pixel chef icon
* Allow for variations in .mxp import files

= 3.2 Build 2158 =
* Tested with WP 3.7.1
* Added option for extra <head> content on print
* Fix for secure admin URLs
* Fix for jQuery UI 1.10 differences in Live Formatting
* Added more explicit notification of missing licence key
* Fix for guest post image select on WP 3.5 when using Firefox
* Fix for "grey overlay" on recipe entry caused by some other plugins (e.g. Easy Rotator)

= 3.2 Build 2124 =
* Workaround for bad "title" shortcode replacement done in some themes
* Tested with WP 3.6

= 3.2 Build 2089 =
* Tested with WP 3.5.2
* Retain line breaks in Notes
* Workaround for tinyMCE/Chrome bug that caused notes and some nutrition fields to get dropped after an autosave
* Better protection from inadvertent delete of recipe data in post edit

= 3.2 Build 2045 =
* Added [easyrecipe_page] shortcode to force loading of EasyRecipe css/scripts on pages that don't normally trigger them

= 3.2 Build 2042 =
* Fix for image upload in guest posts

= 3.2 Build 2029 =
* Fix for bad ratings

= 3.2 Build 2028 =
* Added live Google snippet test
* Added ReciPress conversion
* Add Get Me Cooking conversion
* Workaround for javascript library incompatibility for Bootstrap based themes
* Improved the efficiency of ratings retrieval
* Added live Google snippet test
* Added custom labels (translations) for times
* Added custom labels (translations) for guest post pages
* Added import from MacGourmet and Yummy Soup

= 3.2 Build 1753 =
* Added import from Paprika multiple recipe files

= 3.2 Build 1737 =
* Added import from Paprika recipes
* Added import from Meal-Master recipes
* Added conversion from Recipe Card recipes
* Added Recipe Card to the plugins Fooderific recognizes
* Added underlining to basic formatting
* Fix missing image markup on Provencale style
* Workaround for glitch in the Wordpress SEO plugin
* Reduced minimum capability for style changes from edit_plugins to edit_theme_options

= 3.2 Build 1682 =
* Better handling of non UTF-8 encoding on Mastercook .mx2 files
* Styles with images changed to better handle responsive themes
* Javascript workarounds for themes that hijack jQuery.widget (e.g. Nevada)

= 3.2 Build 1652 =
* Tested with WP 3.5.1
* Changes to better handle some Mastercook inputs
* Supress photo section on the Celebration style if no image
* Display error message if diagnostic send fails
* Only show "Format" link on the admin toolbar if user has "edit_plugins" capability
* Fix for 7/8ths display
* Fix for Live Formatting on print
* Fix for Live Formatting with theme "Camber"


= 3.2 Build 1596 =
* Improvements to the Fooderific scan
* Added custom labels for Print and Ziplist Save
* Workaround for TinyMCE non-editable plugin bug
* Fix for some styles not marking up images correctly
* Fix for print/diagnostics where there's a 404 handler
* Fix for custom notes header in old Legacy style
* Fix for special characters in Notes
* Prevent Wordpress stripping times and images on scheduled posts

= 3.2 Build 1337 =
* Fix for the Modish style that had incorrect nutrition markup

= 3.2 Build 1336 =
* Fixed for extra space being added to Notes in Wordpress 3.5

= 3.2 Build 1330 =
* Changed guest post form field names to prevent conflicts with plugins that add an "email" shortcode
* Workaround for old themes and plugins that hijack jQuery.widget()

= 3.2 Build 1317 =
* Fix for diagnostics URL wrong

= 3.2 Build 1311 =
* Minor changes for errors during install

= 3.2 Build 1307 =
* Added the Fooderific.com interface
* Fix print for sites not installed in the root directory
* Fix print for browsers/themes that hijack the 404 page
* Fix fisplay of non-ASCII characters in custom labels
* Fix for Mastercook files that don't specify an encoding
* Fix problems with link and image inserts on WP 3.5
* Made the URL field on guest post details optional

= 3.1.09 =
* Converting from plain text now recognises custom labels as recipe markers (Ingredients, Instructions and Notes)
* Styles now override a theme's custom background on bullets
* Changes to better handle badly behaved themes and plugins
* Clean up "Tastefully Simple" style when there are no times present
* Fix for glitches when previewing
* Fix for Live Formatting resetting formats if a section was missing in the receipe used to format
* Changes to better handle broken Mastercook import files (Plus)

= 3.1.08 =
* Added Ziplist save button option
* Added configurable title on guest post details page
* Added "Force jQuery library load" option to handle badly behaved themes and plugins
* Allow blank custom labels
* Workaround for Internet Explorer bugs when displaying errors on the Settings page
* Various CSS tweaks to better handle more themes
* Fix for print and preview pages when W3 Total Cache Object cache is enabled
* Fix headings displaying when they shouldn't when multiple recipes in a single post
* Fix for custom labels for Ingredients and Instructions on the Legacy display style
* Fix for apostrophes in settings

= 3.1.07 =
* Only display warning once when switching to HTML editor
* Fix previews

= 3.1.05 =
* Fix recipe updates in Chrome and Safari

= 3.1.04 =
* Styles can now be trialled on previews and blogs not using permalinks
* Added EasyRecipe entry for editors, authors and contibutors
* Workaround to pick up all instructions on recipes that have been manually modified and have a non standard EasyRecipe structure
* Minor tweak or the Tastefully Simple print style
* Fixed print for blogs that don't use permalinks
* Fix for Notes Heading not opening in live formatting for the Celebration style
* Fix excerpt and other fields inadvertently being written on a save from the HTML editor
* Fix for link insert on Chrome and Safari

= 3.1.03 =
* Fix print not working on some blogs
* Fix weird stuff happening when W3 Total Cache installed
* Workaround for recipes that have been manually modified and have a non EasyRecipe standard structure
* Made live formatting CSS more specific so themes are less likely to override custom formatting

= 3.1.01 =
* Fix for themes that ignore modification of posts by plugins and displayed unformatted recipes (Thanks Nicole!)
* Fix for print on blogs with non-root Wordpress installs
* Fixed the ratings markup on some styles
* Fix is_file() warning when open_basedir is restricted

= 3.1 =
First production release

