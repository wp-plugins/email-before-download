=== Plugin Name ===
Contributors: mandsconsulting
Donate link: http://www.mandsconsulting.com/
Tags: email, download
Requires at least: 3.x
Tested up to: 3.1
Stable tag: trunk

Email Before Download presents your users with a form where they submit information, like their name and email address, prior to receiving a download.

Plugin homepage: http://www.mandsconsulting.com/products/wp-email-before-download

== Description ==

Email Before Download presents your users with a form where they submit information, like their name and email address, prior to receiving a download. This plugin integrates with the popular [Contact Form 7](http://bit.ly/dNzVJd) and [Download Monitor](http://bit.ly/ifff4y) plugins, allowing you to create any form you like and manage/monitor your file downloads.  You can also EXPORT a list of users that have downloaded files from the plug-in's settings page.  Prior to installing Email Before Download, please confirm each of these dependent plugins is already installed and working independently.

As an option, you can configure Email Before Download to:

1. Display a link to your file directly under the contact form once it is submitted.  This happens dynamically, inline of your post/page.
1. Send the user an email with a link and/or attachment to download your file.
1. Both #1 and #2


Usage

Note: You can see screenshots at [http://wordpress.org/extend/plugins/email-before-download/screenshots/](http://bit.ly/g4r1w2)

1. Create a contact form used by Email Before Download using Contact Form 7 and note the Contact Form ID
1. Upload a file using Download Monitor and note the Download ID
1. Navigate to the Post (or Page) you wish to include
1. Add the following short code using the IDs collected in the first two steps
   [email-download download_id="X" contact_form_id="Y"]
   


Plugin homepage: [http://www.mandsconsulting.com/products/wp-email-before-download](http://www.mandsconsulting.com/products/wp-email-before-download)


== Installation ==

1. Download from [http://wordpress.org/extend/plugins/email-before-download/] (http://bit.ly/dF9AxV)
1. Upload the entire email-before-download folder to the /wp-content/plugins/ directory.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Locate the "Email Before Download" menu item in your WordPress Admin panel under "Settings" to configure.


== Frequently Asked Questions ==

= Can I export a list of download requests people have made? =

Yes.  We store a log of the downloads and you can export a CSV file of this from the Email Before Download settings page in your admin screens.

= What if I don't use the Contact Form 7 and/or Download Monintor Plugins? =

You will not be able to use this version of Email Before Download without these dependent plugins.  If you have specific reasons to avoid using the dependent plugins, please contact us and let us know the reason so we can take it into consideration.

= Anything special I need to do with my contact form? =

If you decide to configure the Email Before Download option to send the user an email with a link to the download, then you will want to name the email field "your-email" as shown in the example screenshots.  Outside of that, nothing special.

= What happens after the user completes the form? =

By default, the user is presented with a link to download their file.  There is also an option to email the user (with a link to the file and/or attachment) if you choose that route.  You can even provide both the inline link as well as the email if you choose.

= Are you changing any of my file or directory permissions? =

WordPress allows direct access to files in your upload directories using a direct URL and we do not change those permissions.  We do provide an option to mask the URL to your downloads if you have cURL enabled.



== Screenshots ==

1. Note the ID of a file you have uploaded to Download Monitor.
2. Note the ID of a contact form you have created using Contact Form 7.
3. Use the following shortcode in your page or post: [email-download download_id="X" contact_form_id="Y"].
4. Upon installation and use of the plugin on a post/page, an end-user will see your contact form.
5. User will be required to enter valid data in accordance with Contact Form 7 validation rules.
6. Upon submission, user will either see a direct link below the form.  (Note: there is also an option to only email the link to the user.)

== Changelog ==

= 1.0 =
* Added ability to export log in CSV format from admin settings page.
* Added ability to mask download file's URL if cURL is enabled.
* Added ability to expire the download link after a given timeframe.
* In addition to emailing a link to the file, added ability to email the file as an attachment.
* Added ability to download files outside of Download Monitor.

= 0.5 =
* First release.

== Upgrade Notice ==

= 1.0 =
Automatically upgrade the plugin and all previous settings should remain intact.

= 0.5 =
First release.

