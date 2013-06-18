=== Plugin Name ===
Contributors: mandsconsulting
Donate link: http://www.mandsconsulting.com/
Tags: email, download
Requires at least: 3.x
Tested up to: 3.5.1
Stable tag: trunk

Email Before Download presents your users with a form where they submit information, like their name and email address, prior to receiving a download.

Plugin homepage: http://www.mandsconsulting.com/products/wp-email-before-download

== Description ==

Email Before Download presents your users with a form where they submit information, like their name and email address, prior to receiving a download. This plugin integrates with the popular [Contact Form 7](http://bit.ly/dNzVJd) and [WordPress Download Monitor](http://bit.ly/ifff4y) plugins, allowing you to create any form you like and manage/monitor your file downloads.  You can also EXPORT a list of users that have downloaded files from the plug-in's settings page.  Prior to installing Email Before Download, please confirm each of the dependent plugins is already installed and working independently.

As an option, you can configure Email Before Download to:

1. Display a link to your file directly under the contact form once it is submitted.  This happens dynamically, inline of your post/page.
1. Send the user an email with a link and/or attachment to download your file.
1. Both #1 and #2

[Plugin Homepage with Live Demos and Test Download](http://www.mandsconsulting.com/products/wp-email-before-download) | [Support Forums](http://bit.ly/lU7Tdt)


Usage

Note: You can see screenshots at [http://wordpress.org/extend/plugins/email-before-download/screenshots/](http://bit.ly/g4r1w2)

1. Create a contact form used by Email Before Download using Contact Form 7 and note the Contact Form ID
1. Upload a file using Download Monitor and note the Download ID
1. Navigate to the Post (or Page) you wish to include
1. Add the following short code using the IDs collected in the first two steps
   [email-download download_id="X" contact_form_id="Y"]
   


Plugin Homepage with Live Demos and Test Download: [http://www.mandsconsulting.com/products/wp-email-before-download](http://www.mandsconsulting.com/products/wp-email-before-download)

Please use the [Support Forums](http://bit.ly/lU7Tdt) for any questions and issues.  We try to monitor that area the best we can and sometimes other users can help you as well.

== Installation ==

1. Download from [http://wordpress.org/extend/plugins/email-before-download/](http://bit.ly/dF9AxV)
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
7. Example Contact Form 7 form code, including tag required to display multiple download selection checkboxes.


== Changelog ==

= 3.2.2 =
* Removed extra spacing in multiple download output

= 3.2.1 =
* Create table SQL script updated (now the title column has utf8 character set and utf8_unicode_ci collation)
* Added a patch that checks this specific column and alters it if needed

= 3.2 =
* Fixed bug related to logging multiple downloads correctly
* Added field to CSV export
* Added PayPal button in Admin Panel
* Added character encoding in case it helps to support languages other than English


= 3.1.7 =
* Default multiple file downloads to pre-selected (checked) by default

= 3.1.6 =
* Minor fix for various multi-file issues and logging

= 3.1.5 =
* fixed event handling
* stubbed email_from, though it is not active

= 3.1 =
* New modification to help support for Contact Form 7 v3.0+

= 3.0 =
* Modification to help support for Contact Form 7 v3.0+
* Added ability to force a file download using attribute in shortcode [email-download download_id="X" contact_form_id="Y" force-download="true"]; Download Monitor Force Download Option is recommended for files stored in Download Monitor
* Added option in admin panel to clear Email Before Download log entries
* Minor fomatting updates to admin panel
* Updates to allow Download Monitor to track clicks/downloads of files accessed using various scenarios of the Email Before Download plugin; Download Monitor still does not track clicks when using the masked URL option of Email Before Download, but the Email Before Download log does track these

= 2.6 =
* Bigger export link
* Support for special characters in filenames like "&"
* Fix for empty page interaction
* Change of function name to avoid conflict with other plugins
* Support for left checkboxes on multiple file download form using "&lt;ebd_left /&gt;"

= 2.5.1 =
* Minor cleanup of admin panel

= 2.5 =
* Added ability to prevent specific domain names
* Fixed download filename issue for .zip files


= 2.0 =
* Support multiple download selection (within shortcut code, use comma-separated list of download IDs: download_id="1,2,3" -- within the contact form 7 form used for multiple download selection, ensure you place the tag "&lt;ebd /&gt;" where you want to checkbox list to be generated) as shown in [screenshot 7](http://wordpress.org/extend/plugins/email-before-download/screenshots/)
* Add more information in the download history EXPORT .csv file
* Added support for Download Monitor format code for the inline link that is displayed (within shortcut code, specify the format code: format="1")
* Allow overriding the default settings with the shortcode (i.e. within shortcode, use delivered_as="Inline Link" even though the general setting in admin panel is setup for "Both" -- options are "Inline Link", "Send Email", "Both")
* Updates to avoid potential conflicts with other plugins
* Added ability to customize subject line when emailing file download

= 1.0 =
* Added ability to export log in CSV format from admin settings page.
* Added ability to mask download file's URL if cURL is enabled.
* Added ability to expire the download link after a given timeframe.
* In addition to emailing a link to the file, added ability to email the file as an attachment.
* Added ability to download files outside of Download Monitor (within shortcode, use file="http://mydomain.com/file.pdf" -- no need to include download_id="X" in this case).

= 0.5 =
* First release.

== Upgrade Notice ==

= 1.0 =
Automatically upgrade the plugin and all previous settings should remain intact.

= 0.5 =
First release.

