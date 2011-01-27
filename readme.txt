=== Plugin Name ===
Contributors: mandsconsulting
Donate link: http://www.mandsconsulting.com/
Tags: email, download
Requires at least: 3.x
Tested up to: 3.0.4
Stable tag: trunk

Email Before Download presents your users with a form where they submit information, like their email address, prior to receiving a download. This plugin integrates with the popular Contact Form 7 and Download Monitor plugins. Prior to installing Email Before Download, please confirm each of these dependent plugins is already installed and in use.

Plugin homepage: http://www.mandsconsulting.com/products/wp-email-before-download

== Description ==

Email Before Download presents your users with a form where they submit information, like their email address, prior to receiving a download. This plugin integrates with the popular Contact Form 7 and Download Monitor plugins. Prior to installing Email Before Download, please confirm each of these dependent plugins is already installed and in use.

Usage

1. Create a contact form used by Email Before Download using Contact Form 7 and note the Contact Form ID
1. Upload a file using Download Monitor and note the Download ID
1. Navigate to the Post (or Page) you wish to include
1. Add the following short code using the IDs collected in the first two steps
   [email-download download_id="X" contact_form_id="Y"]

== Installation ==

1. Download from http://wordpress.org/extend/plugins
1. Upload the entire email-before-download folder to the /wp-content/plugins/ directory.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Locate the "Email Before Download" menu item in your WordPress Admin panel under "Settings" to configure.


== Frequently Asked Questions ==

= Does this plugin add any database objects? =

We do not create any database objects.  We simply integrate with the Contact Form 7 and Download Monitor plugins' capabilities.

= What if I don't use the Contact Form 7 and/or Download Monintor Plugins? =

You will not be able to use this version of Email Before Download without these dependent plugins.  If you have specific reasons to avoid using the dependent plugins, please contact us and let us know the reason so we can take it into consideration.

= Anything special I need to do with my contact form? =

If you decide to configure the Email Before Download option to send the user an email with a link to the download, then you will want to name the email field "your-email" as shown in the example screenshots.  Outside of that, nothing special.

= What happens after the user completes the form? =

By default, the user is presented with a link to download their file.  There is also an option to email a link to the file, which can be done instead of (or in addition to) displaying the link inline.

= Are you changing any of my file or directory permissions? =

WordPress allows direct access to any files in your upload directories using a direct URL and we do not change those permissions.

= So someone can still download my files directly without providing their email? =

Users generally do not have a desire to put in the work required to determine your direct upload filenames.  This plugin provides a quick way to know who is downloading information that you might feel to be more premium content like whitepapers, images, etc. from sincere users who are visiting your site, with the understanding the user can share the file itself or the URL if they desire.

= What if I don't want the user to be able to share the file? =

We can't help you prevent a user from sharing a file they have downloaded with other people.  If you have very valuable content that want to secure from being hacked or shared 100% of the time without collecting an email address, please let us know if you find anything elegant.  Also, there is a chance you fit more into the category of needing to charge for the content.



== Screenshots ==

1. Note the ID of a file you have uploaded to Download Monitor.
2. Note the ID of a contact form you have created using Contact Form 7.
3. Use the following shortcode in your page or post: [email-download download_id="X" contact_form_id="Y"].
4. Upon installation and use of the plugin on a post/page, an end-user will see your contact form.
5. User will be required to enter valid data in accordance with Contact Form 7 validation rules.
6. Upon submission, user will either see a direct link below the form.  (Note: there is also an option to only email the link to the user.)

== Changelog ==

= 0.5 =
* First release.

== Upgrade Notice ==

= 0.5 =
First release.

