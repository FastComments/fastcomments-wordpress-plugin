=== FastComments ===
Contributors: winrid
Tags: comments, commenting system, live comments, disqus, live commenting, prevent spam
Requires at least: 4.6
Tested up to: 6.9.2
Stable tag: 3.17.0
Requires PHP: 5.2.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A live, fast, privacy-focused commenting system with advanced spam prevention capabilities.

FastComments prioritizes speed and user experience above all else. It doesn't spy on you or your users and has the features you care about.

== Description ==

**FastComments is the fast, privacy-first commenting system trusted by 4,100+ organizations serving over 541 million page loads per year.** Drop-in replacement for WordPress default comments, Disqus, Jetpack Comments, and wpDiscuz -- with zero ads, zero data harvesting, and load times measured in milliseconds.

= Why Site Owners Switch to FastComments =

* **No Ads, Ever** -- Unlike Disqus, FastComments never injects sponsored content or ads into your comment threads.
* **No Data Harvesting** -- Your visitors' data is never sold to third parties. Full GDPR compliance with optional EU data residency.
* **Blazing Fast** -- Comments load in milliseconds. Users report significant reductions in page weight and fewer dependencies vs. other comment systems.
* **Comments Stay In Sync** -- FastComments keeps your WordPress comment database in sync. Cancel anytime and your comments stay intact.
* **Migrate In Minutes** -- One-click import from Disqus, Hyvor, WordPress native comments, and more. Avatars and images migrate automatically.

= Live, Real-Time Commenting =

* Comments appear instantly for all viewers -- no page refresh needed
* Live moderation: approve, delete, and edit comments in real time
* Real-time reply notifications keep discussions active
* Streaming Chat mode for live events and AMAs

= Powerful Moderation & Spam Prevention =

* Automated spam detection blocks bot submissions before they appear
* Unverified/anonymous comments auto-removed after configurable period
* Full moderation dashboard with search, filter, and bulk actions
* Comment flagging and user blocking

= Built for Engagement =

* Threaded reply-to-reply conversations with unlimited nesting depth
* Upvoting and downvoting with smart duplicate prevention
* Image and GIF attachments in comments
* Full-text comment search for readers
* @mentions and reply notifications
* Commenter ranking system

= Developer & Site Admin Friendly =

* Single Sign-On (SSO) for seamless authentication with your existing users
* Works without JavaScript -- accessible commenting for all visitors
* Full REST API and webhook support
* Custom CSS and JavaScript injection for complete design control
* LearnDash LMS compatibility
* Block-based theme support (FSE)
* Password-protected post support

= Localization =

Fully localized with automatic browser locale detection. Currently available in English, French, and Spanish. Community translations welcome.

= Trusted By Many =

* 4,100+ organizations
* 1,775,632+ registered users
* 541 million+ page loads served in the past year
* 5-star rating on WordPress.org

= Pricing =

FastComments offers flexible, traffic-based pricing starting well under $5/month for most sites. No per-comment charges. No surprise fees. See full pricing at [fastcomments.com/traffic-pricing](https://fastcomments.com/traffic-pricing).

= Support =

Responsive, hands-on support from the development team. Migration assistance included. Most issues resolved within hours.

== Screenshots ==

1. An example comment thread
2. The admin area

== Changelog ==

= 3.17.0 =
* Admin theme update.
* Admin notice prompting setup completion now appears on all WP admin pages until setup is done.

= 3.16.2 =
* Improved initial sync reliability
* Further improvements to prevent bots from submitting spam around the plugin, but in a way that interferes with other themes/plugins less.

= 3.16.1 =
* Prevent bots from submitting spam directly to the WP endpoint when the plugin is installed

= 3.16.0 =
* Improved compatibility with some other plugins like LearnDash LMS.
* Adds some flexibility around SSO Setup.

= 3.15.0 =
* Support for the Streaming Chat widget. You can change widgets under Advanced Settings.

= 3.14.0 =
* Support for newer block-based themes.

= 3.13.0 =
* Comment section now respects post password protection requirements (it will not show).

= 3.12.7 =
* WordPress 6.0.4

= 3.12.6 =
* Compatibility improvements for some themes that might move the FastComments javascript around on the page.

= 3.12.5 =
* Performance improvements for the initial sync.

= 3.12.4 =
* Fixing Malformed SSO message shown when SSO is enabled and nobody is logged in. The "login to comment" option is now shown as expected.

= 3.12.3 =
* Decreasing time to wait in some cases after setup complete.

= 3.12.2 =
* Bugfixes on initial sync after changing data location.

= 3.12.1 =
* Improved support for keeping your data in the EU.

= 3.12.0 =
* Support for keeping your data in the EU.

= 3.11.1 =
* WordPress 6

= 3.11.0 =
* Option to opt out of initial sync during setup.
* Ability to perform full sync manually at any time.
* Sync improvements (no longer misses first comment in database...).

= 3.10.5 =
* Improvements to the chunk splitting algorithm for initial setup. No longer gets stuck on sites with very large number of large comments.

= 3.10.4 =
* Support for syncing all of your Comment data from FastComments back to WordPress has been added.

= 3.10.3 =
* Comment count accuracy has been improved.

= 3.10.2 =
* Friendly setup note.

= 3.10.1 =
* JavaScript is no longer required to leave or view comments.

= 3.9.10 =
* When using SSO, Admins and Moderators now have the appropriate tags shown on with comments. Additionally, configuration has been added for the FastComments log level.

= 3.9.9 =
* Sync now supports sites more comments for a more reliable migration. They do not have to fit in memory during sync.

= 3.9.8 =
* Improved support for re-running the sync multiple times without creating duplicate data.

= 3.9.7 =
* Dynamically adjust request size on initial sync to ensure all comments are migrated while still keeping the initial sync fast.

= 3.9.6 =
* Improved accuracy on initial sync with parent/child comments.

= 3.9.5 =
* Improved comment date accuracy on reverse sync + switching to WordPress built in mechanisms for HTTP requests.

= 3.9.4 =
* Support for custom integrations to pass custom values for "comment thread ids" while retaining the WordPress Post and User IDs on sync.

= 3.9.3 =
* Stability improvements and sync-related bugfixes.

= 3.9.2 =
* Stability improvements and sync-related bugfixes. Initial setup made more intuitive in some areas.

= 3.9.1 =
* Initial sync has been sped up and some bugs fixed.

= 3.9 =
* Sync of comments from WordPress to FastComments when there are many comments improved.

= 3.8 =
* Improvements for when upgrading from 2.1.

= 3.7 =
* Improvements for when upgrading from 2.1.

= 3.6 =
* Tested with WordPress 5.8.

= 3.5 =
* When upgrading from 2.1, ensure user goes through setup process properly, but allow comments to still load.

= 3.4 =
* Improves the process of upgrading from 2.1.

= 3.3 =
* Fixes an issue causing some sites to not load.

= 3.2 =
* Sync related improvements (don't try to sync when no token set).

= 3.1 =
* Improvements to how the plugin syncs with the FastComments backend. Improved support for our customers with strict firewalls and DDOS protection.

= 2.1 =
* Latest version of FastComments. A completely new look!

= 1.9 =
* WordPress 5.7

= 1.8 =
* Support for async-javascript like plugins.

= 1.7 =
* Updated branding.

= 1.6 =
* Enabling reply notifications for SSO users.

= 1.5 =
* Improved compatibility with other plugins

= 1.4 =
* Support (Diagnostic) Improvements

= 1.3 =
* Sync Improvements
    - Deletes from the FastComments moderation page put WordPress comments in the trash.
* Tested with latest WP Version (5.5)

= 1.2 =
* Sync Improvements

= 1.1 =
* SSO Support!
* Admin area bug fixes/improvements
* Sync fixes

= 1.0 =
* Initial release! Full, fast syncing support with good user experience. Hello world!
