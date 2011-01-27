<?php
/*
Plugin Name: Email Before Download
Plugin URI: http://www.mandsconsulting.com/
Description: This plugin seamlessly integrates two popular plugins (Contact Form 7 and Download Monitor) to create a simple shortcode for requesting an end-user to fill out a form before providing the download URL.  You can use an existing Contact Form 7 form, where you might typically request contact information like an email address, but the questions in the form are completely up to you.  Once the end user completes the form, you can choose to either show a link directly to the download or send an email with the direct link to the email provided in the contact form.
Author: M&S Consulting
Version: 0.5
Author URI: http://www.mandsconsulting.com

============================================================================================================
This software is provided "as is" and any express or implied warranties, including, but not limited to, the
implied warranties of merchantibility and fitness for a particular purpose are disclaimed. In no event shall
the copyright owner or contributors be liable for any direct, indirect, incidental, special, exemplary, or
consequential damages(including, but not limited to, procurement of substitute goods or services; loss of
use, data, or profits; or business interruption) however caused and on any theory of liability, whether in
contract, strict liability, or tort(including negligence or otherwise) arising in any way out of the use of
this software, even if advised of the possibility of such damage.

For full license details see license.txt
============================================================================================================
*/

function emailreqtag_func($atts) {
 extract(shortcode_atts(array(
  'download_id' => 'something',
  'contact_form_id' => 'something else',
 ), $atts));

  $dl = get_downloads('include='.$dowload_id.'&limit=5');

  global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $dlm_url, $downloadurl, $downloadtype, $wp_dlm_db_meta;
  $dl = $wpdb->get_row( "SELECT * FROM $wp_dlm_db  WHERE id = ".$wpdb->escape($download_id).";" );
  $d = new downloadable_file($dl);
  $str = '';

  $title = '';

  $url = '';

  if (!empty($d)) {
        $date = date("jS M Y", strtotime($d->date));
        $title = $d->title;
        $url = $d->url;
  }

  $contact_form = do_shortcode("[contact-form $contact_form_id \"$title\"]");

  $hf = '<input type="hidden" name="_wpcf7_download_id" value="' . $download_id. '" /></form>';

  $contact_form = str_replace("</form>", $hf, $contact_form);

  $wrap_in_div =  get_option('email_before_download_wrap_in_div');
  $div_class = '';
  if(strlen(trim($wrap_in_div)) > 0 ){
    $div_class = 'class="' .  trim($wrap_in_div) . '"';
  }
  return "<br/>" . $contact_form .  '<div id="wpm_download_' . $download_id . '" ' . $div_class . ' style="display:none;">  </div> ';
}
add_shortcode('emailreq', 'emailreqtag_func');
add_shortcode('email-download', 'emailreqtag_func');

function process_email_form( $cf7 ) {

  if(isset( $_POST['_wpcf7_download_id'] )){
    global $wpdb,$wp_dlm_root,$wp_dlm_db,$wp_dlm_db_taxonomies, $def_format, $dlm_url, $downloadurl, $downloadtype, $wp_dlm_db_meta;
    $dl = $wpdb->get_row( "SELECT * FROM $wp_dlm_db  WHERE id = ".$wpdb->escape($_POST['_wpcf7_download_id']).";" );
    $d = new downloadable_file($dl);
    $dId = $_POST['_wpcf7_download_id'];
    $cf7->posted_data['your-message'] = 'The downloaded file name: ' . $d->title;
    if(strpos($cf7->mail['body'], "[your-message]") === false ){
      $cf7->posted_data['your-message'] =  $d->title;
      $cf7->mail['body']  = $cf7->mail['body'] ."\nThe downloaded file name: [your-message]";

    }

    $title = '';

    $url = '';

    if (!empty($d)) {
      $title = $d->title;
      $url = $d->url;
    }
    $target = '_blank';
    $target = get_option('email_before_download_link_target');
    $html_before = get_option('email_before_download_html_before_link');
    $html_after = get_option('email_before_download_html_after_link');
    $email_template = get_option('email_before_download_email_template');
    $message = '';
    if(strlen(trim($email_template)) > 0){
     $message = str_replace(array('[requesting_name]', '[file_url]', '[file_name]'), array($cf7->posted_data['your-name'], $url, $title), trim($email_template));
    }
    else  $message = '<a class="icon-button download-icon" target="' . $target . '" href="' . $url .'">' . $title . '</a>';

    $innerHtml = $html_before . '<a class="icon-button download-icon" target="' . $target . '" href="' . $url .'"><span class="et-icon"><span>' . $title . '</span></span></a><br clear="both" />' . $html_after;

    if(get_option('email_before_download_send_email') == 'Send Email') {
      @wp_mail( $cf7->posted_data['your-email'], 'Requested URL for the file: '. $title , $message, "Content-Type: text/html\n");
      $cf7->additional_settings = "on_sent_ok: \"document.getElementById('wpm_download_$dId').style.display = 'inline'; document.getElementById('wpm_download_$dId').innerHTML='The link to the file has been emailed to you.'; \"";
    }
    else if (get_option('email_before_download_send_email') == 'Both'){
      @wp_mail( $cf7->posted_data['your-email'], 'Requested URL for the file: '. $title , $message, "Content-Type: text/html\n");
      $cf7->additional_settings = "on_sent_ok: \"document.getElementById('wpm_download_$dId').style.display = 'inline'; document.getElementById('wpm_download_$dId').innerHTML='$innerHtml'; \"";
    }
    else $cf7->additional_settings = "on_sent_ok: \"document.getElementById('wpm_download_$dId').style.display = 'inline'; document.getElementById('wpm_download_$dId').innerHTML='$innerHtml'; \"";
  }
  return $cf7;
}
add_action( 'wpcf7_before_send_mail', 'process_email_form' );

add_action('admin_menu', 'ebd_plugin_menu');

function ebd_plugin_menu() {

  add_options_page('Email Before Download Options', 'Email Before Download', 'manage_options', 'email-before-download', 'email_before_download_options');
  add_action( 'admin_init', 'register_email_before_download_settings' );

}

add_filter( 'plugin_action_links', 'ebd_plugin_action_links', 10, 2 );

function ebd_plugin_action_links( $links, $file ) {
	if ( $file != plugin_basename( __FILE__ ))
		return $links;

	$settings_link = '<a href="options-general.php?page=email-before-download">' . __( 'Settings', 'email-before-download' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

function register_email_before_download_settings() {
  register_setting( 'email-before-download-group', 'email_before_download_link_target' );
  register_setting( 'email-before-download-group', 'email_before_download_wrap_in_div' );
  register_setting( 'email-before-download-group', 'email_before_download_html_before_link' );
  register_setting( 'email-before-download-group', 'email_before_download_html_after_link' );
  register_setting( 'email-before-download-group', 'email_before_download_send_email' );
  register_setting( 'email-before-download-group', 'email_before_download_email_template' );

}

function email_before_download_options() {

  if (!current_user_can('manage_options'))  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }

?>
<style type="text/css">
.ebd th, .ebd td
{
vertical-align:top;
}
.ebd th
{
	text-align: left;
	padding-right:8px;
}
.ebd .alert
{
	padding:8px;
	font:bold 12pt Arial;
	border:1px dashed red;
}
</style>

<div class="wrap">
<h2>Email Before Download Options</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'email-before-download-group' ); ?>
    <table class="optiontable ebd">
        <tr valign="top">
        <th scope="row"><p>1. Delivery Format</p></th>
        <td><p>
        <select name="email_before_download_send_email">
         <option value="Inline Link" <?php if(get_option('email_before_download_send_email') == 'Inline Link') echo 'selected="selected"'; ?> >Inline Link</option>
         <option value="Send Email" <?php if(get_option('email_before_download_send_email') == 'Send Email') echo 'selected="selected"'; ?> >Send Email</option>
         <option value="Both" <?php if(get_option('email_before_download_send_email') == 'Both') echo 'selected="selected"'; ?> >Both</option>
        </select>
        </p>
       </td>
        </tr>

        <tr valign="top"><td colspan="2"><p class="alert">#2 through #5 only apply if you selected "Inline Link" or "Both" as the Deliver Format in #1</p></td></tr>
        <tr valign="top">
        <th scope="row"><p>2. Inline Link Target</p></th>
        <td><p>
        <select name="email_before_download_link_target">
         <option value="_blank" <?php if(get_option('email_before_download_link_target') == '_blank') echo 'selected="selected"'; ?> >_blank</option>
         <option value="_self" <?php if(get_option('email_before_download_link_target') == '_self') echo 'selected="selected"'; ?> >_self</option>
        </select> <br />
         <font size="-1"><i>If "_self" is selected link will open in the same browser window/tab, if "_blank" is selected the link will open in the new browser window/tab</i></font>
         </p>
        </td>
        </tr>

        <tr valign="top">
        <th scope="row"><p>3. Inline Link Custom CSS</p></th>
        <td><p><input type="text" size="40" name="email_before_download_wrap_in_div" value="<?php echo get_option('email_before_download_wrap_in_div'); ?>" />
        <br /> <font size="-1"><i>CSS class used to render the div and the link (this is only used if you choose to display the link inline in #5)</i></font>
        </td>
        </tr>

        <tr valign="top">
        <th scope="row"><p>4. HTML Before Inline Link</p></th>
        <td><p><input type="text" name="email_before_download_html_before_link" size="40" value="<?php echo get_option('email_before_download_html_before_link'); ?>" /><br />
         <font size="-1"><i>HTML you want to be added before the link</i></font>
        </p>
        </td>
        </tr>

        <tr valign="top">
        <th scope="row"><p>5. HTML After Inline Link</p></th>
        <td> <p><input type="text" size="40" name="email_before_download_html_after_link" value="<?php echo get_option('email_before_download_html_after_link'); ?>" />
        <br /><font size="-1"><i>HTML you want to be added after the link</i></font>
        </p>
        </td>
        </tr>

        <tr valign="top" class="alert"><td colspan="2"><p class="alert">#6 only applies if you selected "Send Email" or "Both" as the Deliver Format in #1</p></td></tr>
        <tr valign="top">
        <th scope="row"><p>6. Email Template</p></th>
        <td><textarea cols="40" rows="10" name="email_before_download_email_template">  <?php echo get_option('email_before_download_email_template'); ?> </textarea><br />
 <font size="-1"><i>You can use the following placeholders: [requesting_name], [file_url] and [file_name]. </i></font> <br />
<font size="-1"><i>The placeholders: [requesting_name], [file_url] and [file_name] are used to generate the link.
<br /> So if you, for example, don't provide the [file_url] placeholder
<br />user will not receive any link. Here is an example of the template:  <br />
<b> Hello [requesting_name], <br />

Here is the download for &lt;a href="[file_url]"&gt;[file_name]&lt;/a&gt; that you requested.<br />

Sincerely,<br />

My Company name </b>
<br /> Note. If you leave this field empty, an email containing only the file URL will be sent.

 </i>
        </td>
        </tr>

    </table>

    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php

}
?>