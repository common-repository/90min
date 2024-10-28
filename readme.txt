=== 90min Football News ===
Contributors: 90min, maor 
Tags: news, Football, opt-in
Requires at least: 3.5
Tested up to: 4.1.1
Stable tag: 3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


== Description ==

The 90min Platform connects your website and applications with the richest digital content in global football.  We support 10 leagues, over 200 teams and all major tournaments in 10 different languages so you can get the most relevant content for your readership. 

Once installing the plugin, you can set up to automatically fetch the content of your choice directly into your posts and pages.

Activating the plugin requires a partner ID and API Key, provided upon request. To receive access, send us a request at WPsupport@90min.com.


== Installation ==

1. Upload the plugin files to your plugin folder, or install using WordPress' built-in ‘Add New Plugin’ installer
2. Activate the plugin
3. Go to the plugin settings page (under Settings > 90min Settings)
4. Enter your Partner ID and API key (Credentials are provided upon request, contact us at wpsupport@90min to receive access). 
5. Click ‘Authenticate’ to verify the submitted credentials 
6. Select your desired content and customize it in accordance to your editorial needs 
7. Click Save Changes.


= Posts injection =
Posts are fetched every 30 minutes when the WP-Cron function is being prompted. This function executes scheduled events in your WordPress install and is not prompted when there is no traffic. In order to avoid missing posts we recommend you to replace WP-Cron with a real Cron job by following these steps:

1. Disable `wp-cron.php`

You can disable WP-cron by modifying the `wp-config.php` (located in the folder where WordPress is installed). Open the `wp-config.php` file and add the following line to the top of the page:

`define('DISABLE_WP_CRON', true);`


2. Set Up a Real Cron

Note: It is important that you familiarize yourself with how cron jobs work. You need to have a working knowledge of Linux commands before you can use cron jobs effectively.
To set up a real cron job:

Log into your cPanel.
In the Advanced section, click Cron jobs.
Under Add New Cron Job, select the time interval. HostGator recommends that you do not set the interval lower than 15 minutes.
Set the cron command to the following, replacing yourwebsite.com with your actual domain name:

`wget -q -O - http://yourwebsite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1`

The above command tells the Linux server to run wp-cron via wget, which will trigger the `wp-cron.php` script to do it's job on your schedule instead of on each page view. This will lessen the amount of processes on the server. 
Click Add New Cron Job to set the cron.

In order to test out the new cron, simply wait for the elapsed time period for the cron to run. In the event that the cron does not run, please review the steps listed above to ensure that you have completed all steps correctly.

== Screenshots ==

1. Overview
2. Account Details
3. Post title
4. Views Counter
5. Featured image
6. Post Customization
7. Content Selection
8. Categories

== Frequently Asked Questions ==

= What is 90min? =
[90min](http://www.90min.com) is the biggest fan generated football media company in the world. We provide football content across 10 over 200 teams and all major tournaments in 10 different languages, so you can get the most relevant content for your readership.


= Do I need a 90min account to use this plugin? =
Yes, this plugin requires a 90min account. Sign up to 90min at: www.90min.com/writers


= Where can I find the Partner ID and API Key? =
Credentials are provided upon request, contact us with a link to your 90min profile page at wpsupport@90min to receive access.


== Changelog ==

= 1.0 =
* Initial version. 

= 1.1 = 
* Updated promoted and relegated teams

= 1.2 = 
* New stable tag

= 2.0 =
Bugs fixes and new capabilities

= 3.0 = 
* Dynamically update the plugin's content offering