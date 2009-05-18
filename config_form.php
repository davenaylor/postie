<?php
//require_once('admin.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'postie-functions.php');
global $wpdb,$wp_roles;

//        if (!TestWPVersion()) {
 //           print("<h1>Warning!</h1>
  //                  <p>Postie only works on on Word Press version 2.0 and above</p>");
   //     exit();
    //    }
$title = __('Postie Options', 'postie');
$parent_file = 'options-general.php';
$config = GetConfig();
$messages[1] = __("Configuration successfully updated!",'postie');
$messages[2] = __("Error - unable to save configuration",'postie');

?>
<?php if (isset($_GET['message'])) : ?>
<div class="updated"><p><?php _e($messages[$_GET['message']], 'postie'); ?></p></div>
<?php endif; ?>
<div class="wrap"> 
<h2><img src="<?php echo '../wp-content/plugins/postie/images/mail.png'; ?>" alt="postie" /><?php _e('Postie Options', 'postie') ?></h2>
<form name="postie-options" method="post" action="<?php echo  get_option('siteurl') . "/wp-content/plugins/postie/config_handler.php"?>"> 
	<input type="hidden" name="action" value="reset" />
            <input name="Submit" value="<?php _e("Reset Settings To Defaults", 'postie')?> &raquo" type="submit" class='button'>
</form>
<form name="postie-options" method="get" action="<?php echo  get_option('siteurl') . "/wp-content/plugins/postie/get_mail.php"?>"> 
            <input name="Submit" value="<?php _e("Run Postie", 'postie');?> &raquo;" type="submit" class='button'>
    <?php _e("(To run the check mail script manually)", 'postie');?>
</form>
<form name="postie-options" method="post" action="<?php echo  get_option('siteurl') . "/wp-content/plugins/postie/config_handler.php"?>"> 
	<input type="hidden" name="action" value="test" />
            <input name="Submit" value="<?php _e("Test Config", 'postie');?>&raquo;" type="submit" class='button'>
    <?php _e("this will run a special script to test your configuration options", 'postie');?>
</form>
<form name="postie-options" method="post" action="<?php echo  get_option('siteurl') . "/wp-content/plugins/postie/config_handler.php"?>"> 
	<input type="hidden" name="action" value="config" />
<div id="simpleTabs">
	<div class="simpleTabs-nav">
	<ul>
	<li id="simpleTabs-nav-1"><?php _e('Mailserver' , 'postie') ?></li>
	<li id="simpleTabs-nav-2"><?php _e('User' , 'postie') ?></li>
	<li id="simpleTabs-nav-3"><?php _e('Message' , 'postie') ?></li>
	<li id="simpleTabs-nav-4"><?php _e('Image' , 'postie') ?></li>
	<li id="simpleTabs-nav-5"><?php _e('Video and Audio' , 'postie') ?></li>
  </ul>
	</div>
	<div id="simpleTabs-content-1" class="simpleTabs-content">
	<table class='form-table'>
    <tr><td colspan=2>
  <?php if (isset($config['CRONLESS']) && $config['CRONLESS']!='') {
  ?>
  <p><?php _e('Cronless postie should check for mail', 'postie') ?>
  <select name='CRONLESS' id='CRONLESS'>
    <option value="weekly" <?php if($config["CRONLESS"] == "weekly") { echo "selected='selected'";} ?>><?php _e('Once weekly', 'postie') ?></option>
    <option value="daily"<?php if($config["CRONLESS"] == "daily") { echo "selected='selected'";} ?>><?php _e('daily', 'postie') ?></option>
    <option value="hourly" <?php if($config["CRONLESS"] == "hourly") { echo "selected='selected'";} ?>><?php _e('hourly', 'postie') ?></option>
    <option value="twiceperhour" <?php if($config["CRONLESS"] == "twiceperhour") { echo "selected='selected'";} ?>><?php _e('twice per hour', 'postie') ?></option>
    <option value="tenminutes" <?php if($config["CRONLESS"] == "tenminutes") { echo "selected='selected'";} ?>><?php _e('every ten minutes', 'postie') ?></option>
  </select>
  </p>
  <?php 
  }
  ?>


	<tr>
        <th scope="row"><?php _e('Mail Protocol:', 'postie') ?></th>
        <td>
        <table>
        <tr><td>
        <select name="INPUT_PROTOCOL" id="INPUT_PROTOCOL">
        <option value="pop3">POP3</option>
        <?php if (HasIMAPSupport(false)):?>
            <option value="imap" <?php if($config["INPUT_PROTOCOL"] == "imap") { echo "SELECTED";} ?>>IMAP</option>
            <option value="pop3-ssl" <?php if($config["INPUT_PROTOCOL"] == "pop3-ssl") { echo "SELECTED";} ?>>POP3-SSL</option>
            <option value="imap-ssl" <?php if($config["INPUT_PROTOCOL"] == "imap-ssl") { echo "SELECTED";} ?>>IMAP-SSL</option>
        <?php else:?>
            <option value="pop3" ><?php _e("IMAP/IMAP-SSL/POP3-SSL unavailable", 'postie');?></option>
        <?php endif;?>
        </select>
        </td><td>
        <code><?php _e("Standard Ports:", 'postie');?><br />
              <?php _e("POP3", 'postie');?> - 110<br />
              <?php _e("IMAP", 'postie');?> - 143<br />
              <?php _e("IMAP-SSL", 'postie');?>- 993 <br />
              <?php _e("POP3-SSL", 'postie');?> - 995 <br />
              </code>
        </td></tr></table></td>
      <tr>
        <th scope="row"><?php _e('Postie Time Correction:', 'postie') ?> </th>
        <td><input name="TIME_OFFSET" type="text" id="TIME_OFFSET" size="2" value="<?php echo $config['TIME_OFFSET']; ?>" /> 
        <?php _e('hours', 'postie') ?> 
            <br />
                <?php _e("Recommended");?>: <code><?php _e("Should be the same as your normal offset - but this lets you adjust it in cases where that doesn't work.", 'postie');?></code>
                <br />

        </td>
      </tr>
	</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Mail Server:', 'postie') ?></th>
			<td><input name="MAIL_SERVER" type="text" id="MAIL_SERVER" value="<?php echo $config["MAIL_SERVER"];?>" size="40" />
			<?php _e('Port:', 'postie') ?> 
			<input name="MAIL_SERVER_PORT" type="text" id="MAIL_SERVER_PORT" value="<?php echo $config["MAIL_SERVER_PORT"];?>" size="6" />
			</td>
		</tr>
		<tr valign="top">
			<th width="33%" scope="row"><?php _e('Mail Userid:', 'postie') ?></th>
			<td><input name="MAIL_USERID" type="text" id="MAIL_USERID" value="<?php echo $config["MAIL_USERID"]; ?>" size="40" /></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php _e('Mail Password:', 'postie') ?></th>
			<td>
				<input name="MAIL_PASSWORD" type="text" id="MAIL_PASSWORD" value="<?php echo $config["MAIL_PASSWORD"]; ?>" size="40" />
			</td>
		</tr>
	</table>
  </div>
	<div id="simpleTabs-content-2" class="simpleTabs-content">
            <table class='form-table'>

            <?php echo BuildBooleanSelect("Allow Anyone To Post Via Email","TURN_AUTHORIZATION_OFF",$config["TURN_AUTHORIZATION_OFF"],"Changing this to yes is NOT RECOMMEDED - anything that gets sent in will automatically be posted. This could make it easier to compromise your server - YOU HAVE BEEN WARNED.");?>
        <tr>
        <th scope="row"><?php _e('Roles That Can Post:', 'postie') ?></th>
        <td>
        <table>
        <tr><th>Administrator role can always post.</th>
        <?php
        foreach($wp_roles->role_names as $roleId => $name) {
            $name=translate_with_context($name);
            $role = &$wp_roles->get_role($roleId);
            if ($role->has_cap("post_via_postie")) {
                $checked = " CHECKED ";
            }
            else {
                $checked = "";
            }
            if ($roleId != "administrator") {
                print("<tr><td><input type='checkbox' value='1' name='ROLE_ACCESS[$roleId]' $checked >".$name."</td></tr>");
            }
        }
        ?>
        </table>
        <br />
        <code><?php _e("This allows you to grant access to other users to post if they have the proper access level", 'postie');?></code>
        </td>
	</tr>
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Post status:', 'postie') ?> </th> 
                <td>
        <select name="POST_STATUS" id="POST_STATUS">
        <option value="publish" <?php if($config["POST_STATUS"] == "publish") { echo
        "SELECTED";} ?>>Published</option>
        <option value="draft" <?php if($config["POST_STATUS"] == "draft") { echo
        "SELECTED";} ?>>Draft</option>
        <option value="pending" <?php if($config["POST_STATUS"] == "pending") { echo
        "SELECTED";} ?>>Pending Review</option>
        </select><br />
                <?php _e("Recommended", 'postie');?>: <code>published</code>
                <br />
                </td> 
            </tr> 
            <?php echo BuildTextArea("Authorized Addresses","AUTHORIZED_ADDRESSES",$config["AUTHORIZED_ADDRESSES"],"Put each email address on a single line. Posts from emails in this list will be treated as if they came from the admin. If you would prefer to have users post under their own name - create a WordPress user with the correct access level.");?>
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Admin
                username:') ?> </th> 
                <td>
                <input name="ADMIN_USERNAME" type="text" id="ADMIN_USERNAME"
                value="<?php echo $config["ADMIN_USERNAME"]; ?>" size="50" /><br />
                <?php _e("Recommended", 'postie');?>: <code>admin</code>
                <br />
                </td> 
            </tr> 
            </table> 
</div>
	<div id="simpleTabs-content-3" class="simpleTabs-content">
  <table class='form-table'>
            <tr valign="top">
                <th scope="row"><?php _e('Default post by mail category:', 'postie') ?></th>
                <td>
                <?php
                $defaultCat=$config['DEFAULT_POST_CATEGORY'];
                wp_dropdown_categories("name=DEFAULT_POST_CATEGORY&hierarchical=1&selected=$defaultCat&hide_empty=0"); ?>
                <!--
                <td><select name="DEFAULT_POST_CATEGORY" id="DEFAULT_POST_CATEGORY">
                        <?php
                        $categories = $wpdb->get_results("SELECT * FROM $wpdb->terms ORDER BY name");
                        foreach ($categories as $category) {
                            $selected = ($category->term_id == $config["DEFAULT_POST_CATEGORY"] ? "SELECTED": NULL); 
                            echo "\n\t<option value='$category->term_id' $selected>$category->name</option>";
                        }
                        ?>
                </select></td>
                -->
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Default post by mail tag(s):
                separated by commas', 'postie') ?></th>
                <td><input type='text' name="DEFAULT_POST_TAGS"
                id="DEFAULT_POST_TAGS" value='<?php echo
                $config["DEFAULT_POST_TAGS"] ?>' />
                </td>
            </tr>
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Default Title:', 'postie') ?> </th> 
                <td>
                <input name="DEFAULT_TITLE" type="text" id="DEFAULT_TITLE" value="<?php echo $config["DEFAULT_TITLE"]; ?>" size="50" /><br />
                <?php _e("Recommended", 'postie');?>: <code>Live from the field</code>
                <br />
                </td> 
            </tr> 
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Prefered Text Type (HTML/plain):', 'postie') ?> </th> 
                <td>
        <select name="PREFER_TEXT_TYPE" id="PREFER_TEXT_TYPE">
        <option value="plain">plain</option>
        <option value="html" <?php if($config["PREFER_TEXT_TYPE"] == "html") { echo "SELECTED";} ?>>html</option>
        </select><br />
                <?php _e("Recommended", 'postie');?>: <code>plain</code>
                <br />
                </td> 
            </tr> 
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Wrap content in pre tags:', 'postie') ?> </th> 
                <td>
                <select name="WRAP_PRE" id="WRAP_PRE">
                <option value="no">no</option>
                <option value="yes" <?php if($config["WRAP_PRE"] == "yes") { echo "SELECTED";} ?>>yes</option>
                </select><br />
                <?php _e("Recommended", 'postie');?>: <code>no</code>
                <br />
                </td> 
            </tr> 
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Add more meta information right before post:', 'postie') ?> </th> 
                <td>
                <select name="ADD_META" id="ADD_META">
                <option value="no">no</option>
                <option value="yes" <?php if($config["ADD_META"] == "yes") { echo "SELECTED";} ?>>yes</option>
                </select><br />
                <?php _e("Recommended", 'postie');?>: <code>no</code>
                <br />
                </td> 
            </tr> 
            <?php echo BuildBooleanSelect("Filter
            newlines","FILTERNEWLINES",$config["FILTERNEWLINES"], "Set to no
            if using markdown or textitle syntax");?>
            <?php echo BuildBooleanSelect("Replace newline characters with
            html line breaks (&lt;br
            /&gt;)","CONVERTNEWLINE",$config["CONVERTNEWLINE"]);?>
            <?php echo BuildBooleanSelect("Forward Rejected Mail","FORWARD_REJECTED_MAIL",$config["FORWARD_REJECTED_MAIL"]);?>
            <?php echo BuildBooleanSelect("Allow Subject In Mail","ALLOW_SUBJECT_IN_MAIL",$config["ALLOW_SUBJECT_IN_MAIL"]);?>
            <?php echo BuildBooleanSelect("Allow HTML In Mail Subject","ALLOW_HTML_IN_SUBJECT",$config["ALLOW_HTML_IN_SUBJECT"]);?>
            <?php echo BuildBooleanSelect("Allow HTML In Mail Body","ALLOW_HTML_IN_BODY",$config["ALLOW_HTML_IN_BODY"]);?>
            <?php echo BuildBooleanSelect("Automatically convert urls to links","CONVERTURLS",$config["CONVERTURLS"]);?>
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Encoding for pages and feeds:', 'postie') ?> </th> 
                <td>
                <input name="MESSAGE_ENCODING" type="text" id="MESSAGE_ENCODING" value="<?php echo $config["MESSAGE_ENCODING"]; ?>" size="50" /><br />
                <?php _e("Recommended", 'postie');?>: <code>UTF-8</code> - it should handle ISO-8859-1 as well
                <br />
                </td> 
            </tr> 
            <?php echo BuildBooleanSelect("Decode Quoted Printable Data","MESSAGE_DEQUOTE",$config["MESSAGE_DEQUOTE"], "Should be yes in most cases.");?>
            <?php echo BuildTextArea("Supported File Types","SUPPORTED_FILE_TYPES",$config["SUPPORTED_FILE_TYPES"],"Put each type on a single line.");?>
            <?php echo BuildTextArea("Banned File
            Names","BANNED_FILES_LIST",$config["BANNED_FILES_LIST"],"Put each
            file name on a single line.Files matching this list will never be
            posted to your blog. You can use wildcards such as *.xls, or *.* for
            all files");?>
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Tag Of Message Start:', 'postie') ?> </th> 
                <td>
                <p>This tag can be used to remove any text from a message that the email provider puts at the top of the message</p>
                <input name="MESSAGE_START" type="text" id="MESSAGE_START" value="<?php echo $config["MESSAGE_START"]; ?>" size="50" /><br />
                <?php _e("Recommended", 'postie');?>: <code>:start</code>
                <br />
                </td> 
            </tr> 
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Tag Of Message End:', 'postie') ?> </th> 
                <td>
                <p>This tag can be used to remove any text from a message that the email provider puts at the bottom of the message</p>
                <input name="MESSAGE_END" type="text" id="MESSAGE_END" value="<?php echo $config["MESSAGE_END"]; ?>" size="50" /><br />
                <?php _e("Recommended", 'postie');?>: <code>:end</code>
                <br />
                </td> 
            </tr> 
            <?php echo BuildBooleanSelect("Drop The Signature From Mail","DROP_SIGNATURE",$config["DROP_SIGNATURE"]);?>
            <?php echo BuildTextArea("Signature Patterns","SIG_PATTERN_LIST",$config["SIG_PATTERN_LIST"],"Put each pattern on a seperate line and make sure to escape any special characters.");?>
            </table> 
            </div>
	<div id="simpleTabs-content-4" class="simpleTabs-content">
  <table class='form-table'>


            <?php echo BuildBooleanSelect("Post Images At
            End","IMAGES_APPEND",$config["IMAGES_APPEND"],"No means they will
            be put before the text of the message.");?>     
            <?php echo BuildBooleanSelect("Start Image Count At 0","START_IMAGE_COUNT_AT_ZERO",$config["START_IMAGE_COUNT_AT_ZERO"]);?>
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Image Place Holder Tag:', 'postie') ?> </th> 
                <td>
                <input name="IMAGE_PLACEHOLDER" type="text" id="IMAGE_PLACEHOLDER" value="<?php echo $config["IMAGE_PLACEHOLDER"]; ?>" size="50" /><br />
                <?php _e("Recommended", 'postie');?>: <code>#img%#</code>
                <br />
                </td> 
            </tr> 
            <?php echo BuildBooleanSelect("Use custom image
            template","USEIMAGETEMPLATE",$config["USEIMAGETEMPLATE"],"If you
            don't like the default html output around images, you can enter
            your own below. My personal template is already there. See the
            readme for more details");?>
            <tr> 
                <th width="33%" valign="top" scope="row">                <td>
                <textarea  cols="50" rows="6" name="IMAGETEMPLATE"
                id="IMAGETEMPLATE"><?php echo $config["IMAGETEMPLATE"]; ?></textarea>
                </td> 
            </tr> 
            <?php echo BuildBooleanSelect("Use custom image
            field","CUSTOM_IMAGE_FIELD",$config["CUSTOM_IMAGE_FIELD"],"When this option is set, images will not appear in the
            post. Instead the url to the image will be input into a custom
            field named 'image'.
Recommended:
            no");?>            
            </table> 
   </div> 
	<div id="simpleTabs-content-5" class="simpleTabs-content">
<table class='form-table'>

            <?php echo BuildBooleanSelect("Embed 3GP videos as QuickTime","3GP_QT",$config["3GP_QT"],"This controls if the video is just a link or embeded in the page using QuickTime");?>
            <?php echo BuildBooleanSelect("Autoplay embedded
            videos?","VIDEO_AUTOPLAY",$config["VIDEO_AUTOPLAY"],"When this is
            set to yes, videos will start to play automatically.");?>
                <tr>
                    <th scope="row"><?php _e('Video width:', 'postie') ?> </th>
                    <td><input name="VIDEO_WIDTH" type="text" id="VIDEO_WIDTH"
                    value="<?php echo $config['VIDEO_WIDTH']; ?>" size="5" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Video height:', 'postie') ?> </th>
                    <td><input name="VIDEO_HEIGHT" type="text" id="VIDEO_HEIGHT"
                    value="<?php echo $config['VIDEO_HEIGHT']; ?>" size="5" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Video player width:', 'postie') ?> </th>
                    <td><input name="PLAYER_WIDTH" type="text" id="PLAYER_WIDTH"
                    value="<?php echo $config['PLAYER_WIDTH']; ?>" size="5" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Video player height:', 'postie') ?> </th>
                    <td><input name="PLAYER_HEIGHT" type="text" id="PLAYER_HEIGHT"
                    value="<?php echo $config['PLAYER_HEIGHT']; ?>" size="5" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Location of ffmpeg:', 'postie') ?> </th>
                    <td><input name="3GP_FFMPEG" type="text" id="3GP_FFMPEG" value="<?php echo $config['3GP_FFMPEG']; ?>" size="30" />
                    <br /><?php _e("Recommended");?>: <code><?php _e("only needed if you are on a Linux server and use 3gp video,and don't embed the video. This allows postie to make thumbnail of the very first frame");?>  <br /><?php _e("should be /usr/bin/ffmpeg", 'postie');?></code>
                    </td>
                </tr>
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('3GP CSS Class:', 'postie') ?> </th> 
                <td>
                <input name="3GPCLASS" type="text" id="3GPCLASS" value="<?php echo $config["3GPCLASS"]; ?>" size="50" /><br />
                <?php _e("Recommended", 'postie');?>: <code>wp-mailvideo</code>
                <br />
                </td> 
            </tr> 
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('3GP Div CSS:', 'postie') ?> </th> 
                <td>
                <input name="3GPDIV" type="text" id="3GPDIV" value="<?php echo $config["3GPDIV"]; ?>" size="50" /><br />
                <?php _e("Recommended", 'postie');?>: <code>postie-3gp-div</code><p>This is the CSS class of a div that wraps each 3GP video. Can be used to style the post</p>
                <br />
                </td> 
            </tr> 
            <?php echo BuildBooleanSelect("Use custom video
            template","USEVIDEOTEMPLATE",$config["USEVIDEOTEMPLATE"],"If you
            don't like the default html output around videos, you can enter
            your own below. The default template is already there. See the
            readme for more details");?>
            <tr> 
                <th width="33%" valign="top" scope="row">                <td>
                <textarea  cols="50" rows="6" name="VIDEOTEMPLATE"
                id="VIDEOTEMPLATE"><?php echo $config["VIDEOTEMPLATE"]; ?></textarea>
                </td> 
            </tr> 
            </table> 
        </fieldset> 
    </td> 
    </tr> 













	</table> 
  </div>
		<input type="submit" name="Submit" value="<?php _e('Update Options', 'postie') ?> &raquo;" class='button' />
</form> 
<div id="w3c">
    <a href="http://validator.w3.org/check?uri=referer"><img src="<?php echo '../wp-content/plugins/postie/images/valid-xhtml10-blue.png'; ?>" alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a>
    <a href="http://jigsaw.w3.org/css-validator/check/referer"><img style="border:0;width:88px;height:31px" src="<?php echo '../wp-content/plugins/postie/images/vcss-blue.gif'; ?>" alt="Valid CSS!" /></a>
</div>
Postie Version:
$Id$
</div>

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#simpleTabs").simpleTabs({
		fadeSpeed: "medium", // @param : low, medium, fast
		defautContent: 1,    // @param : number ( simpleTabs-nav-number)
		autoNav: "false",     // @param : true or false
		closeTabs : "false"   // @param : true or false;
	});

});
</script>
