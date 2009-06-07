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
	<li id="simpleTabs-nav-6"><?php _e('Help' , 'postie') ?></li>
	<li id="simpleTabs-nav-7"><?php _e('FAQ' , 'postie') ?></li>
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
    <th scope="row"><?php _e('Mail Protocol:', 'postie') ?>        </th>
    <td>
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
    </td>
  </tr>
  <tr>
    <th scope="row"><?php _e('Port:', 'postie') ?><br />
      <span class='recommendation'><?php _e("Standard Ports:", 'postie');?><br />
            <?php _e("POP3", 'postie');?> - 110<br />
            <?php _e("IMAP", 'postie');?> - 143<br />
            <?php _e("IMAP-SSL", 'postie');?>- 993 <br />
            <?php _e("POP3-SSL", 'postie');?> - 995 <br />
            </span>
    </th>
    <td>
    <input name="MAIL_SERVER_PORT" type="text" id="MAIL_SERVER_PORT" value="<?php echo $config["MAIL_SERVER_PORT"];?>" size="6" />
    </td>
  </tr>
  <tr>
    <th scope="row"><?php _e('Postie Time Correction:', 'postie') ?>
        <br />
            <span class='recommendation'><?php _e("Should be the same as your normal offset - but this lets you adjust it in cases where that doesn't work.", 'postie');?></span>
     </th>
    <td><input name="TIME_OFFSET" type="text" id="TIME_OFFSET" size="2" value="<?php echo $config['TIME_OFFSET']; ?>" /> 
    <?php _e('hours', 'postie') ?> 

    </td>
  </tr>
		<tr valign="top">
			<th scope="row"><?php _e('Mail Server:', 'postie') ?></th>
			<td><input name="MAIL_SERVER" type="text" id="MAIL_SERVER" value="<?php echo $config["MAIL_SERVER"];?>" size="40" />
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
        <th scope="row"><?php _e('Roles That Can Post:', 'postie') ?>
        <br />
        <span class='recommendation'><?php _e("This allows you to grant access to other users to post if they have the proper access level", 'postie');?></span></th>
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
        </select>                </td> 
            </tr> 
            <?php echo BuildTextArea("Authorized Addresses","AUTHORIZED_ADDRESSES",$config["AUTHORIZED_ADDRESSES"],"Put each email address on a single line. Posts from emails in this list will be treated as if they came from the admin. If you would prefer to have users post under their own name - create a WordPress user with the correct access level.");?>
            <tr> 
                <th width="33%" valign="top" scope="row">
                <?php _e('Admin username:') ?> </th> 
                <td>
                <input name="ADMIN_USERNAME" type="text" id="ADMIN_USERNAME"
                value="<?php echo $config["ADMIN_USERNAME"]; ?>" size="50" />                </td> 
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
            <th scope="row">
            <?php _e('Default post by mail tag(s): separated by commas', 'postie') ?></th>
            <td><input type='text' name="DEFAULT_POST_TAGS"
            id="DEFAULT_POST_TAGS" value='<?php echo
            $config["DEFAULT_POST_TAGS"] ?>' />
            </td>
          </tr>
          <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Default Title:', 'postie') ?> </th> 
                <td>
                <input name="DEFAULT_TITLE" type="text" id="DEFAULT_TITLE" value="<?php echo $config["DEFAULT_TITLE"]; ?>" size="50" /><br />
                <br />
                </td> 
            </tr> 
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Preferred
                Text Type:', 'postie') ?> </th> 
                <td>
        <select name="PREFER_TEXT_TYPE" id="PREFER_TEXT_TYPE">
        <option value="plain">plain</option>
        <option value="html" <?php if($config["PREFER_TEXT_TYPE"] == "html") { echo "SELECTED";} ?>>html</option>
        </select><br />
                </td> 
            </tr> 
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Wrap content in pre tags:', 'postie') ?> </th> 
                <td>
                <select name="WRAP_PRE" id="WRAP_PRE">
                <option value="no">no</option>
                <option value="yes" <?php if($config["WRAP_PRE"] == "yes") { echo "SELECTED";} ?>>yes</option>
                </select><br />
                <br />
                </td> 
            </tr> 
            <!--
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
            -->
            <?php echo BuildBooleanSelect("Filter newlines",
              "FILTERNEWLINES",$config["FILTERNEWLINES"],
              "Set to no if using markdown or textitle syntax");?>
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
                <input name="MESSAGE_ENCODING" type="text" id="MESSAGE_ENCODING" value="<?php echo $config["MESSAGE_ENCODING"]; ?>" size="10" />
                <span class='recommendation'>UTF-8 <?php _e("should handle ISO-8859-1 as well", 'postie');?></span>
                </td> 
            </tr> 
            <?php echo BuildBooleanSelect("Decode Quoted Printable Data","MESSAGE_DEQUOTE",$config["MESSAGE_DEQUOTE"]);?>
            <?php echo BuildTextArea("Supported File Types","SUPPORTED_FILE_TYPES",$config["SUPPORTED_FILE_TYPES"],"Put each type on a single line.");?>
            <?php echo BuildTextArea("Banned File Names","BANNED_FILES_LIST",$config["BANNED_FILES_LIST"],"Put each file name on a single line.Files matching this list will never be posted to your blog. You can use wildcards such as *.xls, or *.* for all files");?>
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Tag Of
                Message Start:', 'postie') ?> <br />
                <span class='recommendation'><?php _e('Use to remove any text from a message that the email provider puts at the top of the message', 'postie') ?></span></th>
                <td>
                <input name="MESSAGE_START" type="text" id="MESSAGE_START" value="<?php echo $config["MESSAGE_START"]; ?>" size="20" /><br />
                </td> 
            </tr> 
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Tag Of
                Message End:', 'postie') ?> <br />
                <span class='recommendation'><?php _e('Use to remove any text from a message that the email provider puts at the end of the message', 'postie') ?></span></th>
                <td>
                <input name="MESSAGE_END" type="text" id="MESSAGE_END" value="<?php echo $config["MESSAGE_END"]; ?>" size="20" /><br />
                </td> 
            </tr> 
            <?php echo BuildBooleanSelect("Drop The Signature From Mail","DROP_SIGNATURE",$config["DROP_SIGNATURE"]);?>
            <?php echo BuildTextArea("Signature Patterns","SIG_PATTERN_LIST",$config["SIG_PATTERN_LIST"],"Put each pattern on a seperate line and make sure to escape any special characters.");?>
            </table> 
            </div>
	<div id="simpleTabs-content-4" class="simpleTabs-content">
  <table class='form-table'>


            <?php echo BuildBooleanSelect("Post Images At
            End","IMAGES_APPEND",$config["IMAGES_APPEND"],"No means they will be put before the text of the message.");?>     
            <?php echo BuildBooleanSelect("Start Image Count At 0","START_IMAGE_COUNT_AT_ZERO",$config["START_IMAGE_COUNT_AT_ZERO"]);?>
            <tr> 
                <th width="33%" valign="top" scope="row"><?php _e('Image Place Holder Tag:', 'postie') ?> </th> 
                <td>
                <input name="IMAGE_PLACEHOLDER" type="text" id="IMAGE_PLACEHOLDER" value="<?php echo $config["IMAGE_PLACEHOLDER"]; ?>" size="50" /><br />
                </td> 
            </tr> 
            <tr>
            <th width="33%" valign="top" scope="row"><?php _e('Image
            Template', 'postie') ?><br />
            <span class='recommendation'><?php _e('Choose a default template,
            then customize to your liking in the text box',
            'postie');?></span><br /><br />
            <span class='recommendation'><?php _e('Sizes for thumbnail, medium, and large images can be chosen in the <a href="options-media.php">Media Settings</a>. The samples here use the default sizes, and will not reflect the sizes you have chosen.', 'postie');?></span>
            </th>
            <td>
  <input type='hidden' id='SELECTED_IMAGETEMPLATE' name='SELECTED_IMAGETEMPLATE'
  value="<?php echo attribute_escape($config['SELECTED_IMAGETEMPLATE']) ?>" />
  <input type='hidden' id='CURRENT_IMAGETEMPLATE' value="<?php echo
attribute_escape($config['IMAGETEMPLATE']) ?>" />
			 <select name='IMAGETEMPLATESELECT' id='IMAGETEMPLATESELECT' 
       onchange="changeStyle('imageTemplatePreview','IMAGETEMPLATE',
       'IMAGETEMPLATESELECT', 'SELECTED_IMAGETEMPLATE','smiling.jpg');" />
			 <?php
			 $styleOptions=unserialize($config['IMAGETEMPLATES']);
			 $selected=$config['SELECTED_IMAGETEMPLATE'];
			 foreach ($styleOptions as $key=>$value) {
			   if ($key!='selected') {
           if ($key==$selected) {
					   $select=' selected=selected ';
					 } else {
						 $select=' ';
					 }
					 echo '<option' .  $select . 'value="'.
					     attribute_escape($value) . '" >'.$key . '</option>';
         }
       }
			 ?>
			 </select>
	     &nbsp;&nbsp;
			 <?php _e('Preview', 'postie'); ?>
			 <span id='imageTemplatePreview' alt='preview'></span>
   <textarea onchange='changeStyle("imageTemplatePreview", "IMAGETEMPLATE",
   "IMAGETEMPLATESELECT", "SELECTED_IMAGETEMPLATE", "smiling.jpg", true);' cols='70' rows='7' id="IMAGETEMPLATE"
   name="IMAGETEMPLATE"><?php echo attribute_escape($config['IMAGETEMPLATE']) ?></textarea>
			 </td>
            </tr> 
            <?php echo BuildBooleanSelect("Use custom image field","CUSTOM_IMAGE_FIELD",$config["CUSTOM_IMAGE_FIELD"],"When true, images will not appear in the post. Instead the url to the image will be input into a custom field named 'image'.");?>            
            </table> 
   </div> 

<!-- 
##########   VIDEO AND AUDIO OPTIONS ###################
-->

	<div id="simpleTabs-content-5" class="simpleTabs-content">
<table class='form-table'>

            <tr><th scope='row'><?php _e('Video template 1', 'postie') ?><br />
            <span class='recommendation'><?php _e('Choose a default template, then customize to your liking in the text box', 'postie') ?></span></th>
<?php $templateDir = get_option('siteurl') . '/' . PLUGINDIR .  '/postie/templates'; ?>
            <td>
  <input type='hidden' id='SELECTED_VIDEO1TEMPLATE' name='SELECTED_VIDEO1TEMPLATE'
  value="<?php echo attribute_escape($config['SELECTED_VIDEO1TEMPLATE']) ?>" />
  <input type='hidden' id='CURRENT_VIDEO1TEMPLATE' value="<?php echo
attribute_escape($config['VIDEO1TEMPLATE']) ?>" />
			 <select name='VIDEO1TEMPLATESELECT' id='VIDEO1TEMPLATESELECT' 
       onchange="changeStyle('video1TemplatePreview','VIDEO1TEMPLATE', 'VIDEO1TEMPLATESELECT', 'SELECTED_VIDEO1TEMPLATE','hi.mp4');" />
			 <?php
			 $styleOptions=unserialize($config['VIDEO1TEMPLATES']);
			 $selected=$config['SELECTED_VIDEO1TEMPLATE'];
			 foreach ($styleOptions as $key=>$value) {
			   if ($key!='selected') {
           if ($key==$selected) {
					   $select=' selected=selected ';
					 } else {
						 $select=' ';
					 }
					 echo '<option' .  $select . 'value="'.
					     attribute_escape($value) . '" >'.$key . '</option>';
         }
       }
			 ?>
			 </select>
	     &nbsp;&nbsp;
			 <?php _e('Preview', 'postie'); ?>
			 <span id='video1TemplatePreview' alt='preview'></span>
   <textarea onchange="changeStyle('video1TemplatePreview','VIDEO1TEMPLATE',
   'VIDEO1TEMPLATESELECT', 'SELECTED_VIDEO1TEMPLATE','hi.mp4',true);" cols='70' rows='7' id="VIDEO1TEMPLATE"
   name="VIDEO1TEMPLATE"><?php echo attribute_escape($config['VIDEO1TEMPLATE']) ?></textarea>
			 </td>
       </tr>
      <tr> 
        <th width="33%" valign="top" scope="row">
        <?php _e('Video 1 file types:') ?><br /><span class='recommendation'>
        <?php _e('Use the video template 1 for these files types (separated by
        commas)', 'postie') ?></span> </th> 
          <td>
          <input name="VIDEO1TYPES" type="text" id="VIDEO1TYPES"
          value="<?php if ($config['VIDEO1TYPES']!='') echo implode(', ', $config["VIDEO1TYPES"]); ?>" size="40" />                </td> 
      </tr> 
            <tr><th scope='row'><?php _e('Video template 2', 'postie') ?><br />
            <span class='recommendation'><?php _e('Choose a default template, then customize to your liking in the text box', 'postie') ?></span></th>
            <td>
  <input type='hidden' id='SELECTED_VIDEO2TEMPLATE' name='SELECTED_VIDEO2TEMPLATE'
  value="<?php echo attribute_escape($config['SELECTED_VIDEO2TEMPLATE']) ?>" />
  <input type='hidden' id='CURRENT_VIDEO2TEMPLATE' value="<?php echo
attribute_escape($config['VIDEO2TEMPLATE']) ?>" />
			 <select name='VIDEO2TEMPLATESELECT' id='VIDEO2TEMPLATESELECT' 
       onchange="changeStyle('video2TemplatePreview','VIDEO2TEMPLATE', 'VIDEO2TEMPLATESELECT', 'SELECTED_VIDEO2TEMPLATE','hi.flv');" />
			 <?php
			 $styleOptions=unserialize($config['VIDEO2TEMPLATES']);
			 $selected=$config['SELECTED_VIDEO2TEMPLATE'];
			 foreach ($styleOptions as $key=>$value) {
			   if ($key!='selected') {
           if ($key==$selected) {
					   $select=' selected=selected ';
					 } else {
						 $select=' ';
					 }
					 echo '<option' .  $select . 'value="'.
					     attribute_escape($value) . '" >'.$key . '</option>';
         }
       }
			 ?>
			 </select>
	     &nbsp;&nbsp;
			 <?php _e('Preview', 'postie'); ?>
			 <span id='video2TemplatePreview' alt='preview'></span>
   <textarea onchange="changeStyle('video2TemplatePreview','VIDEO2TEMPLATE',
   'VIDEO2TEMPLATESELECT', 'SELECTED_VIDEO2TEMPLATE','hi.flv',true);" cols='70' rows='7' id="VIDEO2TEMPLATE"
   name="VIDEO2TEMPLATE"><?php echo attribute_escape($config['VIDEO2TEMPLATE']) ?></textarea>
			 </td>
       </tr>
      <tr> 
        <th width="33%" valign="top" scope="row">
        <?php _e('Video 2 file types:') ?><br /><span class='recommendation'>
        <?php _e('Use the video template 2 for these files types (separated by
        commas)', 'postie') ?></span> </th> 
          <td>
          <input name="VIDEO2TYPES" type="text" id="VIDEO2TYPES"
          value="<?php if ($config['VIDEO2TYPES']!='') echo implode(', ', $config["VIDEO2TYPES"]); ?>" size="40" />                </td> 
      </tr> 
        <tr><th scope='row'><?php _e('Audio template', 'postie') ?><br />
        <span class='recommendation'><?php _e('Choose a default template, then customize to your liking in the text box', 'postie') ?></span></th>
        <td>
  <input type='hidden' id='SELECTED_AUDIOTEMPLATE' name='SELECTED_AUDIOTEMPLATE'
  value="<?php echo attribute_escape($config['SELECTED_AUDIOTEMPLATE']) ?>" />
  <input type='hidden' id='CURRENT_AUDIOTEMPLATE' value="<?php echo
attribute_escape($config['AUDIOTEMPLATE']) ?>" />
			 <select name='AUDIOTEMPLATESELECT' id='AUDIOTEMPLATESELECT' 
       onchange="changeStyle('audioTemplatePreview','AUDIOTEMPLATE',
       'AUDIOTEMPLATESELECT', 'SELECTED_AUDIOTEMPLATE','funky.mp3');" />
			 <?php
			 $styleOptions=unserialize($config['AUDIOTEMPLATES']);
			 $selected=$config['SELECTED_AUDIOTEMPLATE'];
			 foreach ($styleOptions as $key=>$value) {
			   if ($key!='selected') {
           if ($key==$selected) {
					   $select=' selected=selected ';
					 } else {
						 $select=' ';
					 }
					 echo '<option' .  $select . 'value="'.
					     attribute_escape($value) . '" >'.$key . '</option>';
         }
       }
			 ?>
			 </select>
	     &nbsp;&nbsp;
			 <?php _e('Preview', 'postie'); ?>
			 <span id='audioTemplatePreview' alt='preview'></span>
   <textarea onchange="changeStyle('audioTemplatePreview','AUDIOTEMPLATE',
       'AUDIOTEMPLATESELECT', 'SELECTED_AUDIOTEMPLATE','funky.mp3', true);" cols='70' rows='7' id="AUDIOTEMPLATE"
   name="AUDIOTEMPLATE"><?php echo attribute_escape($config['AUDIOTEMPLATE']) ?></textarea>
			 </td>
            </table> 
    </td> 
    </tr> 
	</table> 
  </div>
	<div id="simpleTabs-content-6" class="simpleTabs-content">
  <?php include('readme.html'); ?>
  </div>
	<div id="simpleTabs-content-7" class="simpleTabs-content">
  <?php include('faq.html'); ?>
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
function changeStyle(preview,template,select,selected,sample,custom) {
	var preview = document.getElementById(preview);
	var pageStyles = document.getElementById(select);
	var selectedStyle;
  var hiddenStyle=document.getElementById(selected);
  var pageStyle = document.getElementById(template);
  if (custom==true) {
    selectedStyle=pageStyles.options[pageStyles.options.length-1];
    selectedStyle.value=pageStyle.value;
    selectedStyle.selected=true;
  } else {
    for(i=0; i<pageStyles.options.length; i++) {
      if (pageStyles.options[i].selected == true) {
        selectedStyle=pageStyles.options[i];
      }
    }
  }
  hiddenStyle.value=selectedStyle.innerHTML
  var previewHTML=selectedStyle.value;
  var fileLink = '<?php echo $templateDir ?>/' + sample;
  var thumb = '<?php echo $templateDir ?>/' + sample.replace(/\.jpg/,
  '-150x150.jpg');
  var medium = '<?php echo $templateDir ?>/' + sample.replace(/\.jpg/,
  '-300x200.jpg');
  var large = '<?php echo $templateDir ?>/' + sample.replace(/\.jpg/,
  '-1024x682.jpg');
  previewHTML=previewHTML.replace(/{FILELINK}/g, fileLink);
  previewHTML=previewHTML.replace(/{IMAGE}/g, fileLink);
	previewHTML=previewHTML.replace(/{FILENAME}/, sample);
	previewHTML=previewHTML.replace(/{THUMB(NAIL|)}/, thumb);
	previewHTML=previewHTML.replace(/{MEDIUM}/, medium);
	previewHTML=previewHTML.replace(/{LARGE}/, large);
	previewHTML=previewHTML.replace(/{CAPTION}/g, 'Spencer smiling');
  preview.innerHTML=previewHTML;
  pageStyle.value=selectedStyle.value;
}
function restoreStyle(current,template) {
  var defaultStyle = document.getElementById(current).value;
  var pageStyle = document.getElementById(template);
  pageStyle.value=defaultStyle;
}
changeStyle('audioTemplatePreview','AUDIOTEMPLATE', 'AUDIOTEMPLATESELECT',
'SELECTED_AUDIOTEMPLATE','funky.mp3');
changeStyle('imageTemplatePreview','IMAGETEMPLATE', 'IMAGETEMPLATESELECT',
'SELECTED_AUDIOTEMPLATE','smiling.jpg');
changeStyle('video1TemplatePreview','VIDEO1TEMPLATE', 'VIDEO1TEMPLATESELECT',
'SELECTED_VIDEO1TEMPLATE','hi.mp4');
changeStyle('video2TemplatePreview','VIDEO2TEMPLATE', 'VIDEO2TEMPLATESELECT',
'SELECTED_VIDEO2TEMPLATE','hi.flv');
</script>
