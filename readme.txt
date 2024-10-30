=== CFiltering ===
Contributors: 123teru321
Tags: collaborative filtering, recommend, recommendation
Requires at least: 3.9.13
Tested up to: 4.7.3
Stable tag: 1.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Recommendation plugin using collaborative filtering

== Description ==

You can get related post based on user behavior.
This plugin uses "Collaborative Filtering" algorithm.
https://en.wikipedia.org/wiki/Collaborative_filtering
[日本語の説明](https://technote.space/cfiltering "Documentation in Japanese")

This plugin needs PHP5.4 or higher.

== Installation ==

1. Upload the `collaborative-filtering` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Open the setting menu, it is possible to set the execution condition.
4. Use `cf_get_post_id` function that returns recommended post_ids based on user behavior.

== Screenshots ==

1. Settings.
2. Show calculated data.

== Changelog ==

= 1.5.0 =
* 2017-03-21  Tested against WordPress 4.7.3

= 1.4.9 =
* 2017-02-08  Bug fix

= 1.4.8 =
* 2017-02-07
* Removed dependence on jQuery
* Tested against WordPress 4.7.2

= 1.4.7 =
* 2016-12-01  Tested against WordPress 4.7

= 1.4.6 =
* 2016-10-21  Small bug fix

= 1.4.5 =
* 2016-10-20
* Changed ajax check way
* Added check_ajax_url filter

= 1.4.4 =
* 2016-10-18  Modified cron behavior

= 1.4.3 =
* 2016-10-18  Add detail link to settings

= 1.4.2 =
* 2016-10-16
* Modified cron process behavior
* Added http host setting
* Added suppress message setting
* Added ajax access which does not use rewrite rule
* Modified ajax test
* Changed setting page

= 1.4.1 =
* 2016-10-15  Add setting of utilizing wpp ajax access

= 1.4.0 =
* 2016-10-14
* Add URL Scheme Setting
* Translation

= 1.3.9 =
* 2016-10-07
* Add post filter by post_status
* Small bug fix

= 1.3.8 =
* 2016-10-02  Show php version when PHP version < 5.4

= 1.3.7 =
* 2016-10-02  Translation

= 1.3.6 =
* 2016-10-01
* Add test
* Show message if PHP version < 5.4
* Add mode not to consider cache page
* Add changed option filter
* Add mode to check develop update
* Small bug fix

= 1.3.5 =
* 2016-09-28  SERVER_NAME => HTTP_HOST

= 1.3.4 =
* 2016-09-28  Changed default front_admin_ajax setting

= 1.3.3 =
* 2016-09-28  Add filter to decide whether to check ajax referer

= 1.3.2 =
* 2016-09-28
* Add filter of posts column priority
* Small bug fix

= 1.3.1 =
* 2016-09-27  Small bug fix

= 1.3.0 =
* 2016-09-27  Small bug fix

= 1.2.9 =
* 2016-09-27
* Ajax access without using admin-ajax.php
* PHP7

= 1.2.8 =
* 2016-09-26  Small bug fix

= 1.2.7 =
* 2016-09-20  Small bug fix

= 1.2.6 =
* 2016-09-17  Small bug fix

= 1.2.5 =
* 2016-09-17
* Tested against WordPress 4.6.1
* Add action_list filter
* Small bug fix

= 1.2.4 =
* 2016-08-18  Tested against WordPress 4.6

= 1.2.3 =
* 2016-07-30  Fix: Japanese translation on windows

= 1.2.2 =
* 2016-07-30  Add information to calculate data window.

= 1.2.1 =
* 2016-07-30
* Japanese translation
* Small bug fix

= 1.2.0 =
* 2016-07-30
* Add show calculated data button to post list page
* Minify CSS
* Small bug fix
* Clean up code

= 1.1.9 =
* 2016-07-29
* Removed update checker
* Modified API capability behavior
* Clean up code

= 1.1.8 =
* 2016-07-29
* Generate server key function
* Check post types when get result
* Add action to invalidate user cookie
* Add did_action
* Adapt wordpress coding standards
* Small bug fix
* Clean up code

= 1.1.7 =
* 2016-07-28  Changed menu position

= 1.1.6 =
* 2016-07-28  Small bug fix

= 1.1.5 =
* 2016-07-27  Add post type setting

= 1.1.4 =
* 2016-07-27
* Add do_action
* Add menu capability filter
* Small performance improvement
* Small bug fix

= 1.1.3 =
* 2016-07-27  Small bug fix

= 1.1.2 =
* 2016-07-27
* Small performance improvement
* Add max calculate number

= 1.1.1 =
* 2016-07-26  Small performance improvement

= 1.1.0 =
* 2016-07-26  Small bug fix

= 1.0.9 =
* 2016-07-26
* Add default options (threshold of jaccard, min data num)
* Fix: Japanese translation

= 1.0.8 =
* 2016-07-26  Small bug fix

= 1.0.7 =
* 2016-07-26  Small bug fix

= 1.0.6 =
* 2016-07-25  Modified log page

= 1.0.5 =
* 2016-07-25  Add action page

= 1.0.4 =
* 2016-07-25
* Add some functions
* Changed data sampling definition
* Add min, max definition to filter vars
* Add used value column to setting menu
* Add message and elapsed time to access log api
* Modified apply_filter function
* Auto calculate sampling rate
* Fix: auto update
* Fix: permission to access update.json

= 1.0.3 =
* 2016-07-24  Minify JavaScript

= 1.0.2 =
* 2016-07-24  Small performance improvement

= 1.0.1 =
* 2016-07-24
* Add plugin action link.
* Fix: permission to access update.json

= 1.0.0 =
* 2016-07-24  First release
