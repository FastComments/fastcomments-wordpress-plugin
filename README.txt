=== FastComments ===
Contributors: winrid
Tags: live comments, comments, comment spam, comment system, fast comments, live commenting
Requires at least: 4.6
Tested up to: 6.8.3
Stable tag: 3.16.1
Requires PHP: 5.2.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A live, fast, privacy-focused commenting system.

FastComments prioritizes speed and user experience above all else. It doesn't spy on you or your users and has the features you care about.

== Description ==

At a glance, FastComments gives you:

- **Live commenting and moderating.**
- Ad-free experience, no data harvesting.
- Fight Spam - We use automated spam detection. Also, unverified comments can be configured to be automatically removed.
- Ability to moderate content, including with multiple moderators.
- Configurable notifications for you and your users.
- Ability to import from other providers. Automatic image/avatar migration.
- A comment UI that **fetches comments and renders in milliseconds** to prevent disengagement.
- Very Fast Time-To-Engage: **No complex sign up process for your users**.
- Secure Password-less Account Management (email-based login links).
- **Threads & Voting** (replies to replies) along with reply notifications for engagement.
- An unobtrusive UI - no modals or behavior that distracts from your content.
- Users get notified when someone replies, and you get notified of new comments to moderate. We also aggregate emails, so if you receive a hundred notifications in an hour we'll just send one summary email.
- **Image support** Commenters can attach images to their posts.
- **Localization** The client-side widget is fully localized in English, French, and Spanish using browser locale detection.
- **Full-Text Search** through all of your comments.
- **SSO** Give your users a seamless commenting experience with our secure and easy **single-sign-on** system.
- Anonymous commenting (unverified comments auto removed after three days)
- Ability to export your data at any time.
- **JavaScript Not Required** to view or leave comments.

Speed up your WordPress installation with FastComments, by letting us render your comment threads instead of your WordPress servers!

== Installation ==

Installing FastComments is easy. There are two main steps - installing the WordPress plugin and then connecting it with FastComments.

1. Go to "Plugins" > "Add Plugin"
2. Search for "FastComments"
3. Click "Install Now"
4. Activate the Plugin
5. Click FastComments in the left admin panel.
6. Follow the steps to set up and connect your WordPress installation to our servers. Don't worry, it's just a couple clicks.

You can expect the sync to take several minutes if you have tens of thousands of comments or more.

FastComments has performance as a top priority and is designed to ensure it does not put significant load on your site during setup. We use WordPress's default indexes to perform the sync, and even for large
sites the load should be very little and is designed to be spread out as much as possible.

== Frequently Asked Questions ==

= What does it cost to use FastComments? =

FastComments has different tiers available. Whether you're an individual creator or a larger businesses, there's a tier for you.
See our pricing page here: https://fastcomments.com/traffic-pricing

= What is FastComments Faster Than? =

FastComments aims to be the fastest commenting system for you and your users. User experience and performance are very high
on the list of priorities for us.

= Will I lose any comments when I switch to FastComments? =

Fear not, your comments will not disappear! Your site will experience zero loss of data even during the transition as we won't enable FastComments until the sync is done.

= Can I switch back to default WordPress comments? =

By default, FastComments keeps your WordPress installation in sync with our servers. We send very small updates, at most once a minute if needed, for any new comments.

Simply cancel your account and deactivate the plugin to switch back, but we don't think you'll want to!

= Can I customize how FastComments looks? =

You sure can! After installing click the Customize button in the FastComments admin area to configure how your comment threads should look and function, as well as adding your own CSS if desired.

== Screenshots ==

1. An example comment thread
2. The admin area

== Changelog ==

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
