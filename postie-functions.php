<?php
/*
$Id$
*/

/*TODO 
 * html purify
 * windows 1252 encoding
 * upload real attachments
 */
#global $config,$debug;
#$debug=true;
#$config=GetConfig();

include_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . "wp-config.php");
//define("POSTIE_ROOT",dirname(__FILE__));
define("POSTIE_TABLE",$GLOBALS["table_prefix"]. "postie_config");

/* this function is necessary for wildcard matching on non-posix systems */
if (!function_exists('fnmatch')) {
  function fnmatch($pattern, $string) {
    $pattern = strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' =>
        '.', '\[' => '[', '\]' => ']'));
    return @preg_match(
        '/^' . strtr(addcslashes($pattern, '/\\.+^$(){}=!<>|'),
        array('*' => '.*', '?' => '.?')) . '$/i', $string
    );
  }
}
/**
  * This is the main handler for all of the processing
  */
function PostEmail($poster,$mimeDecodedEmail) {
  $config = GetConfig();
  $GLOBALS["POSTIE_IMAGE_ROTATION"] = 0;
  $attachments = array(
          "html" => array(), //holds the html for each image
          "cids" => array(), //holds the cids for HTML email
          "image_files" => array() //holds the files for each image
          );
  print("<p>Message Id is :" . $mimeDecodedEmail->headers["message-id"] . "</p><br/>\n");
  print("<p>Email has following attachments:</p>");
  foreach($mimeDecodedEmail->parts as $parts) {
    print("<p>".$parts->ctype_primary ." ".$parts->ctype_secondary) ."</p>\n";
  }
  FilterTextParts($mimeDecodedEmail);
  $tmpPost=array('post_title'=> 'tmptitle',
                 'post_content'=>'tmoPost');
  /* in order to do attachments correctly, we need to associate the
  attachments with a post. So we add the post here, then update it 
  */
  $post_id = wp_insert_post($tmpPost);
  $content = GetContent($mimeDecodedEmail,$attachments,$post_id);
  echo "content is: $content";
  $subject = GetSubject($mimeDecodedEmail,$content);
  if ($debug) {
    echo "the subject is $subject, right after calling GetSubject\n";
  }
  $customImages = SpecialMessageParsing($content,$attachments);
  $post_excerpt = GetPostExcerpt($content);
  $postAuthorDetails=getPostAuthorDetails($subject,$content,
      $mimeDecodedEmail);
  $message_date = NULL;
  if (array_key_exists("date",$mimeDecodedEmail->headers)
        && !empty($mimeDecodedEmail->headers["date"])) {
    HandleMessageEncoding(
        $mimeDecodedEmail->headers["content-transfer-encoding"],
        $mimeDecodedEmail->ctype_parameters["charset"],
        $mimeDecodedEmail->headers["date"]);
    $message_date = $mimeDecodedEmail->headers['date'];
  }
  list($post_date,$post_date_gmt) = DeterminePostDate($content, $message_date);

  ubb2HTML($content);	

  if ($config['CONVERTURLS']) 
    $content=clickableLink($content);
  
  if ($config['FILTERNEWLINES']) 
    $content = FilterNewLines($content);
  //$content = FixEmailQuotes($content);
  
  $id=checkReply($subject); 
  $post_categories = GetPostCategories($subject);
  $post_tags = GetPostTags($content);
  $comment_status = AllowCommentsOnPost($content);
  
  if ((empty($id) || is_null($id))) {
    $id=$post_id;
    $isReply=false;
    if ($config['ADD_META']=='yes') {
      if ($config['WRAP_PRE']=='yes') {
        $content = $postAuthorDetails['content'] . "<pre>\n" . $content . "</pre>\n";
        $content = "<pre>\n" . $content . "</pre>\n";
      } else {
        $content = $postAuthorDetails['content'] . $content;
        $content = $content;
      }
    } else {
      if ($config['WRAP_PRE']=='yes') {
        $content = "<pre>\n" . $content . "</pre>\n";
      }
    }
  } else {
    $isReply=true;
    wp_delete_post($post_id);
  }


  $post_status=$config['POST_STATUS'];
  $details = array(
      'post_author'  => $poster,
      'comment_author'  => $postAuthorDetails['author'],
      'email_author'  => $postAuthorDetails['email'],
      'post_date'   => $post_date,
      'post_date_gmt'  => $post_date_gmt,
      'post_content'  => $content,
      'post_title'  =>  $subject,
      'post_modified'  => $post_date,
      'post_modified_gmt' => $post_date_gmt,
      'ping_status' => get_option('default_ping_status'),
      'post_category' => $post_categories,
      'tags_input' => $post_tags,
      'comment_status' => $comment_status,
      'post_name' => sanitize_title($subject),
      'post_excerpt' => $post_excerpt,
      'ID' => $id,
      'customImages' => $customImages,
      'post_status' => $post_status
  );
  DisplayEmailPost($details);
  PostToDB($details,$isReply); 
}
/** FUNCTIONS **/


function clickableLink($text) {
  # this functions deserves credit to the fine folks at phpbb.com

  $text = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1:",
  $text);

  // pad it with a space so we can match things at the start of the 1st line.
  $ret = ' ' . $text;
  // try to embed youtube videos
  $ret = preg_replace("#(^|[\n ]|<p[^<]*>)[\w]+?://(www\.)?youtube\.com/watch\?v=([_a-zA-Z0-9]+).*?([ \n]|$|</p>)#is", "\\1<embed width='425' height='344' allowfullscreen='true' allowscriptaccess='always' type='application/x-shockwave-flash' src=\"http://www.youtube.com/v/\\3&hl=en&fs=1\" />", $ret);

  echo "ret=$ret\n";
  // matches an "xxxx://yyyy" URL at the start of a line, or after a space.
  // xxxx can only be alpha characters.
  // yyyy is anything up to the first space, newline, comma, double quote or <
  $ret = preg_replace("#(^|[\n ])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is",
  "\\1<a href=\"\\2\" >\\2</a>", $ret);

  // matches a "www|ftp.xxxx.yyyy[/zzzz]" kinda lazy URL thing
  // Must contain at least 2 dots. xxxx contains either alphanum, or "-"
  // zzzz is optional.. will contain everything up to the first space, newline,
  // comma, double quote or <.
  $ret = preg_replace("#(^|[\n ])((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*)#is",
  "\\1<a href=\"http://\\2\" >\\2</a>", $ret);

  // matches an email@domain type address at the start of a line, or after a space.
  // Note: Only the followed chars are valid; alphanums, "-", "_" and or ".".
  $ret = preg_replace("#(^|[\n
  ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a
  href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret);

  // Remove our padding..
  $ret = substr($ret, 1);
  return $ret;
} 

function getPostAuthorDetails(&$subject,&$content,&$mimeDecodedEmail) {
  /* we check whether or not the e-mail is a forwards or a redirect. If it is
  * a fwd, then we glean the author details from the body of the post.
  * Otherwise we get them from the headers    
  */
  global $wpdb;
  // see if subject starts with Fwd:
  if (preg_match("/(^Fwd:) (.*)/", $subject, $matches)) {
      $subject=trim($matches[2]);
    if (preg_match("/\nfrom:(.*?)\n/i",$content,$matches)) {
      $theAuthor=GetNameFromEmail($matches[1]);
      $mimeDecodedEmail->headers['from']=$theAuthor;
    }
    if (preg_match("/\ndate:(.*?)\n/i",$content,$matches)) {
      $theDate=$matches[1];
      $mimeDecodedEmail->headers['date']=$theDate;
    }
  } else {
    $theDate=$mimeDecodedEmail->headers['date'];
    $theAuthor=GetNameFromEmail($mimeDecodedEmail->headers['from']);
    $theEmail = RemoveExtraCharactersInEmailAddress(trim(
        $mimeDecodedEmail->headers["from"]));
  }
  // now get rid of forwarding info in the content
  $lines=preg_split("/\r\n/",$content);
  $newContents='';
  foreach ($lines as $line) {
    if (preg_match("/^(from|subject|to|date):.*?/i",$line,$matches)==0 && 
    //    preg_match("/^$/i",$line,$matches)==0 &&
        preg_match("/^-+\s*forwarded\s*message\s*-+/i",$line,$matches)==0) {
      $newContents.=preg_replace("/\r/","",$line) . "\n" ;
    }
  }
  $content=$newContents;
  $theDetails=array(
  'content' =>"<div class='postmetadata alt'>On $theDate, $theAuthor" . 
               " posted:</div>",
  'emaildate' => $theDate,
  'author' => $theAuthor,
  'email' => $theEmail
  );
  return($theDetails);
}

function checkReply(&$subject) {
  /* we check whether or not the e-mail is a reply to a previously
   * published post. First we check whether it starts with Re:, and then
   * we see if the remainder matches an already existing post. If so,
   * then we add that post id to the details array, which will cause the
   * existing post to be overwritten, instead of a new one being
   * generated
  */
  
  global $wpdb;
  // see if subject starts with Re:
  if (preg_match("/(^Re:) (.*)/", $subject, $matches)) {
    $subject=trim($matches[2]);
    // strip out category info into temporary variable
    $tmpSubject=$subject;
    if ( preg_match('/(.+): (.*)/', $tmpSubject, $matches))  {
      $tmpSubject = trim($matches[2]);
      $matches[1] = array($matches[1]);
    }
    else if (preg_match_all('/\[(.[^\[]*)\]/', $tmpSubject, $matches)) {
      preg_match("/](.[^\[]*)$/",$tmpSubject,$tmpSubject_matches);
      $tmpSubject = trim($tmpSubject_matches[1]);
    }
    else if ( preg_match_all('/-(.[^-]*)-/', $tmpSubject, $matches) ) {
      preg_match("/-(.[^-]*)$/",$tmpSubject,$tmpSubject_matches);
      $tmpSubject = trim($tmpSubject_matches[1]);
    }
    $checkExistingPostQuery= "SELECT ID FROM $wpdb->posts WHERE 
        '$tmpSubject' = post_title";
    if ($id=$wpdb->get_var($checkExistingPostQuery)) {
      if (is_array($id)) {
        $id=$id[count($id)-1];
      }
    } else {
      $id=NULL;
    }
  }
  return($id);
}

function postie_read_me() {
  include(POSTIE_ROOT . DIRECTORY_SEPARATOR. "postie_read_me.php");
}
/**
*  This sets up the configuration menu
*/
function PostieMenu() {
  if( function_exists('add_options_page') ) {
    if (current_user_can('manage_options')) {
      add_options_page("Postie", "Postie" , 
          0, POSTIE_ROOT .  "/postie.php", "ConfigurePostie");
    }
  }
}
/**
  * This handles actually showing the form
  */
function ConfigurePostie() {
  include(POSTIE_ROOT . DIRECTORY_SEPARATOR. "config_form.php");
}

/**
  * This function handles determining the protocol and fetching the mail
  * @return array
  */ 
function FetchMail($server=NULL, $port=NULL, $email=NULL, $password=NULL,
    $protocol=NULL, $offset=NULL, $test=NULL) {
  //$config = GetConfig();
  $emails = array();
  if (!$server || !$port || !$email) {
    die("Missing Configuration For Mail Server\n");
  }
  if ($server == "pop.gmail.com") {
    print("\nMAKE SURE POP IS TURNED ON IN SETTING AT Gmail\n");
  }
  switch ( strtolower($protocol) ) {
    case 'smtp': //direct 
      $fd = fopen("php://stdin", "r");
      $input = "";
      while (!feof($fd)) {
        $input .= fread($fd, 1024);
      }
      fclose($fd);
      $emails[0] = $input;
      break;
    case 'imap':
    case 'imap-ssl':
    case 'pop3-ssl':
      HasIMAPSupport(false);
      if ($test) {
        $emails = TestIMAPMessageFetch();
      } else {
        $emails = IMAPMessageFetch($server, $port, $email, 
            $password, $protocol, $offset, $test); 
      }
      break;
    case 'pop3':
    default: 
      if ($test) {
        $emails = TestPOP3MessageFetch();
      } else {
        $emails =POP3MessageFetch ($server, $port, $email, 
            $password, $protocol, $offset, $test);
      }
  }
  if (!$emails)
    die("\nThere does not seem to be any new mail.\n");
  return($emails);
}
/**
  *Handles fetching messages from an imap server
  */
function TestIMAPMessageFetch ( ) {			
  print("**************RUNING IN TESTING MODE************\n");
  $config = GetConfig();
  $email = $config["TEST_EMAIL_ACCOUNT"];
  $password = $config["TEST_EMAIL_PASSWORD"];
  return(IMAPMessageFetch($config['MAIL_SERVER'], $config['MAIL_SERVER_PORT'],
      $email, $password, $config['INPUT_PROTOCOL'],
      $config['TIME_OFFSET'], $config['TEST_EMAIL']));

}
/**
  *Handles fetching messages from an imap server
  */
function IMAPMessageFetch ($server=NULL, $port=NULL, $email=NULL, 
    $password=NULL, $protocol=NULL, $offset=NULL, $test=NULL) {
    if (!$config) {
        $config = GetConfig();
    }
    require_once("postieIMAP.php");

    $mail_server = &PostieIMAP::Factory($protocol);
    print("\nConnecting to $server:$port ($protocol) \n");
    if (!$mail_server->connect($server, $port,$email,$password)) {
        print("Mail Connection Time Out\n
                Common Reasons: \n
                Server Down \n
                Network Issue \n
                Port/Protocol MisMatch \n
                ");
        die("The Server said:".$mail_server->error()."\n");
    }
    $msg_count = $mail_server->getNumberOfMessages();
    $emails = array();
	// loop through messages 
	for ($i=1; $i <= $msg_count; $i++) {
		$emails[$i] = $mail_server->fetchEmail($i);
        if ( $config["DELETE_MAIL_AFTER_PROCESSING"]) {
			$mail_server->deleteMessage($i);
		}
        else {
            print("Not deleting messages!\n");
        }
	}
    if ( $config["DELETE_MAIL_AFTER_PROCESSING"]) {
        $mail_server->expungeMessages();
    }
	//clean up
	$mail_server->disconnect();	
	return $emails;
}
function TestPOP3MessageFetch ( ) {			
  print("**************RUNING IN TESTING MODE************\n");
  $config = GetConfig();
  $email = $config["TEST_EMAIL_ACCOUNT"];
  $password = $config["TEST_EMAIL_PASSWORD"];
  return(POP3MessageFetch($config['MAIL_SERVER'], $config['MAIL_SERVER_PORT'],
      $email, $password, $config['INPUT_PROTOCOL'],
      $config['TIME_OFFSET'], $config['TEST_EMAIL']));
}
/**
  *Retrieves email via POP3
  */
function POP3MessageFetch ($server=NULL, $port=NULL, $email=NULL, 
    $password=NULL, $protocol=NULL, $offset=NULL, $test=NULL) {
    if (!$config) {
        $config = GetConfig();
    }
	require_once(ABSPATH.WPINC.DIRECTORY_SEPARATOR.'class-pop3.php');
	$pop3 = &new POP3();
    print("\nConnecting to $server:$port ($protocol))  \n");
    if (!$pop3->connect($server, $port)) {
        if (strpos($pop3->ERROR,"POP3: premature NOOP OK, NOT an RFC 1939 Compliant server") === false) {
            print("Mail Connection Time Out\n
                    Common Reasons: \n
                    Server Down \n
                    Network Issue \n
                    Port/Protocol MisMatch \n
                    ");
            die("The Server Said $pop3->ERROR \n");
        }
    }

	//Check to see if there is any mail, if not die
	$msg_count = $pop3->login($email, $password);
	if (!$msg_count) {
		$pop3->quit();
        return(array());
	}

	// loop through messages 
	for ($i=1; $i <= $msg_count; $i++) {
		$emails[$i] = implode ('',$pop3->get($i));
        if ( $config["DELETE_MAIL_AFTER_PROCESSING"]) {
			if( !$pop3->delete($i) ) {
				echo 'Oops '.$pop3->ERROR.'\n';
				$pop3->reset();
				exit;
			} else {
				echo "Mission complete, message $i deleted.\n";
			}
		}
        else {
            print("Not deleting messages!\n");
        }
	}
	//clean up
	$pop3->quit();	
	return $emails;
}
/**
  * Determines if it is a writable directory
  */
function IsWritableDirectory($directory) {
    if (!is_dir($directory)) {
        die ("Sorry but ".$directory." is not a valid directory.");
    }
    if (!is_writable($directory)) {
        die("The web server cannot write to ".$directory." please correct the permissions");
    }

}
/**
  * This function handles putting the actual entry into the database
  * @param array - categories to be posted to
  * @param array - details of the post
  */
function PostToDB($details) {
  $config = GetConfig();
  if ($config["POST_TO_DB"]) {
    //generate sql for insertion	    
    $_POST['publish'] = true; //Added to make subscribe2 work - it will only handle it if the global varilable _POST is set
    if (!$isReply) {
      $post_ID = wp_update_post($details);
    } else {
      // strip out quoted content
      $lines=preg_split("/[\r\n]/",$details['post_content']);
      $newContents='';
      foreach ($lines as $line) {
        //$match=preg_match("/^>.*/i",$line);
        //echo "line=$line,  match=$match";
        if (preg_match("/^>.*/i",$line)==0 &&
           preg_match("/^(from|subject|to|date):.*?/i",$line)==0 && 
           preg_match("/^-+.*?(from|subject|to|date).*?/i",$line)==0 && 
           preg_match("/^on.*?wrote:$/i",$line)==0 && 
           preg_match("/^-+\s*forwarded\s*message\s*-+/i",$line)==0) {
          $newContents.="$line\n";
        }
      }
      $comment = array(
      'comment_author'=>$details['comment_author'],
      'comment_post_ID' =>$details['ID'], 
      'comment_author_email' => $details['email_author'],
      'comment_date' =>$details['post_date'],
      'comment_date_gmt' =>$details['post_date_gmt'], 
      'comment_content' =>$newContents,
      'comment_author_url' =>'',
      'comment_author_IP' =>'',
      'comment_approved' =>1,
      'comment_agent' =>'', 
      'comment_type' =>'', 
      'comment_parent' => 0
      );

      echo "the comment is:\n";
      $post_ID = wp_insert_comment($comment);
    }
    if ($config["CUSTOM_IMAGE_FIELD"]) {
      if (count($details['customImages'])>1) {
      $imageField=1;
        foreach ($details['customImages'] as $image) {
          add_post_meta($post_ID, 'image'. $imageField, $image);
          $imageField++;
        }
      } else {
        add_post_meta($post_ID, 'image', $image);
      }
    }
  }
}

/**
  * This function determines if the mime attachment is on the BANNED_FILE_LIST
  * @param string
  * @return boolean
  */
function BannedFileName($filename) {
  $config = GetConfig();
  foreach ($config["BANNED_FILES_LIST"] as $bannedFile) {
    if (fnmatch($bannedFile, $filename)) {
      print("<p>Ignoreing $filename - it is on the banned files list.");
      return(true);
    }
  }
  return(false);
}

//tear apart the meta part for useful information
function GetContent ($part,&$attachments, $post_id) {
  $config = GetConfig();
  $meta_return = NULL;	

  DecodeBase64Part($part);
  if (BannedFileName($part->ctype_parameters['name'])
      || BannedFileName($part->ctype_parameters['name'])) {
    return(NULL);
  }
  
  if ($part->ctype_primary == "application"
      && $part->ctype_secondary == "octet-stream") {
    if ($part->disposition == "attachment") {
      $image_endings = array("jpg","png","gif","jpeg","pjpeg");
      foreach ($image_endings as $type) {
        if (eregi(".$type\$",$part->d_parameters["filename"])) {
            $part->ctype_primary = "image";
            $part->ctype_secondary = $type;
            break;
        }
      }
    } else {
      $mimeDecodedEmail = DecodeMIMEMail($part->body);
      FilterTextParts($mimeDecodedEmail);
      foreach($mimeDecodedEmail->parts as $section) {
        $meta_return .= GetContent($section,$attachments,$post_id);
      }
    }
  }
  if ($part->ctype_primary == "multipart"
      && $part->ctype_secondary == "appledouble") {
    $mimeDecodedEmail = DecodeMIMEMail("Content-Type: multipart/mixed; boundary=".$part->ctype_parameters["boundary"]."\n".$part->body);
    FilterTextParts($mimeDecodedEmail);
    FilterAppleFile($mimeDecodedEmail);
    foreach($mimeDecodedEmail->parts as $section) {
      $meta_return .= GetContent($section,$attachments,$post_id);
    }
  } else { 
    switch ( strtolower($part->ctype_primary) ) {
      case 'multipart':
        FilterTextParts($part);
        foreach ($part->parts as $section) {
          $meta_return .= GetContent($section,$attachments,$post_id);
        }
        break;
      case 'text':
          HandleMessageEncoding($part->headers["content-transfer-encoding"],
                                $part->ctype_parameters["charset"],
                                $part->body);

          //go through each sub-section
          if ($part->ctype_secondary=='enriched') {
            //convert enriched text to HTML
            $meta_return .= etf2HTML($part->body ) . "\n";
          } elseif ($part->ctype_secondary=='html') {
            echo "\nhtml\n";
            //strip excess HTML
            $meta_return .= HTML2HTML($part->body ) . "\n";
            //$meta_return .= $part->body  . "\n";
          } else {
            echo "\ntext\n";
            //regular text, so just strip the pgp signature
            if (ALLOW_HTML_IN_BODY) {
              $meta_return .= $part->body  . "\n";
            } else {
              $meta_return .= htmlentities( $part->body ) . "\n";
            }
            $meta_return = StripPGP($meta_return);
          }
          break;

      case 'image':
        $overrides = array('test_form'=>false, 'test_size'=>false,
                           'test_type'=>false);
        $tmpFile=tempnam('/tmp', 'postie');
        $fp = fopen($tmpFile, 'w');
        fwrite($fp, $part->body);
        fclose($fp);
        $the_file = array('name' => $part->ctype_parameters['name'],
                          'type' => $part->ctype_secondary,
                          'tmp_name' => $tmpFile,
                          'size' => filesize($tmpFile),
                          'error' => ''
                          );
        $file_id = postie_media_handle_upload($the_file, $post_id);
        unlink($tmpFile);
        $file = wp_get_attachment_url($file_id);

        $cid = trim($part->headers["content-id"],"<>");; //cids are in <cid>
        $marimex=$marime[0]+20;
        $marimey=$marime[1]+20;
        $onclick='';
        if ($config['IMAGE_NEW_WINDOW']) {
          $onclick='" onclick="window.open(' . "'"
              . $config["URLPHOTOSDIR"] . $fullImage . "','"
                . "full_size_image" . "','"
                . "toolbar=0,scrollbars=0,location=0,status=0,menubar=0,resizable=1,height=" . $marimey . ",width=" . $marimex . "');" . "return false;";
        }
        $the_post=get_post($file_id);
        /* TODO make these options */
        $url=wp_get_attachment_link($file_id);
        $align='left';
        $size='medium';
        if ($config['USEIMAGETEMPLATE']) {
          add_filter('image_send_to_editor', 'parseImageTemplate', 1,7);
        }
        $attachments["html"][] .= get_image_send_to_editor($file_id,
              $the_post->post_excerpt, $the_post->post_title, $align, 
              $url, '', $size);
        if ($cid) {
          $attachments["cids"][$cid] = array($file,count($attachments["html"]) - 1);
        }
        break;
      default:
        if (in_array(strtolower($part->ctype_primary),
            $config["SUPPORTED_FILE_TYPES"])) {
          //pgp signature - then forget it
          if ( $part->ctype_secondary == 'pgp-signature' )
            break;
          //postie_upload_attachment($part);
          $overrides = array('test_form'=>false, 'test_size'=>false,
                             'test_type'=>false);
          $tmpFile=tempnam('/tmp', 'postie');
          $fp = fopen($tmpFile, 'w');
          fwrite($fp, $part->body);
          fclose($fp);
          $the_file = array('name' => $part->ctype_parameters['name'],
                            'type' => $part->ctype_secondary,
                            'tmp_name' => $tmpFile,
                            'size' => filesize($tmpFile),
                            'error' => ''
                            );
          $file_id = postie_media_handle_upload($the_file, $post_id);
          unlink($tmpFile);
          $file = wp_get_attachment_url($file_id);
          $cid = trim($part->headers["content-id"],"<>");; //cids are in <cid>

          if ($part->ctype_secondary == "3gpp"
             || $part->ctype_secondary == "octet-stream"
             || $part->ctype_secondary == "3g2"
             || $part->ctype_secondary == "3gp"
             || $part->ctype_secondary == "mp4"
             || $part->ctype_secondary == "quicktime"
             ||  $part->ctype_secondary == "3gpp2") {
            if ($config["3GP_QT"]) {
               //Shamelessly borrowed from http://www.postneo.com/2003/12/19/embedding-3gpp-in-html
              $autoplay='false';
              if ($config['AUTOPLAY']) {
                $autoplay='true';
              }
              $attachments["html"][] = '<!--Mime Type of File is '.$part->ctype_primary."/".$part->ctype_secondary.' -->' .
                  '<object '.
                      'classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" '.
                      'codebase="http://www.apple.com/qtactivex/qtplugin.cab" '.
                      'width="' . $config['VIDEO_WIDTH'] . '" '.
                      'height="' . $config['VIDEO_HEIGHT'] . '"> '.
                      '<param name="src" value="'. $file. '" /> '.
                      "<param name=\"autoplay\" value=\"$autoplay\" /> ".
                        '<param name="controller" value="true" /> '.
                       '<embed '.
                       'src="'. $file. '" '.
                       'width="' . $config['VIDEO_WIDTH'] . '" '.
                       'height="' . $config['VIDEO_HEIGHT'] . '"'.
                       "autoplay=\"$autoplay\" ".
                       'controller="true" '.
                       'type="video/quicktime" '.
                       'pluginspage="http://www.apple.com/quicktime/download/" '.
                       'width="' . $config['PLAYER_WIDTH'] . '" '.
                       'height="' . $config['PLAYER_HEIGHT'] . '">'.
                       '</embed> '.
                       '</object>';
            } else {
              if (file_exists($config["3GP_FFMPEG"])) {
                //options from http://www.getid3.org/phpBB2/viewtopic.php?p=1290&
                $scaledFile = 'thumb-' . $file;

                @exec (escapeshellcmd($config["3GP_FFMPEG"]) . 
                    " -i " .  escapeshellarg($file) .
                    " -y -ss 00:00:01 -vframes 1 -an -sameq -f gif " . 
                    escapeshellarg($scaledFile) );
                @exec ('chmod 755 ' . escapeshellarg($scaledFile));

                $attachments["html"][] .= '<!--Mime Type of File is '.$part->ctype_primary."/".$part->ctype_secondary.' --><div class="' . $config["3GPDIV"].'"><a href="' . $config["URLPHOTOSDIR"] . $fileName. '"><img src="' . $config["URLPHOTOSDIR"] . $scaledFileName . '" alt="' . $part->ctype_parameters['name'] . '" style="'.$config["IMAGESTYLE"].'" class="'.$config["IMAGECLASS"].'" /></a></div>' . "\n";
              } else {
                $attachments["html"][] = '<div class="' .
                $config["ATTACHMENTDIV"].'"><a href="' . $file. '" class="' . $config["3GPCLASS"].'">' . $part->ctype_parameters['name'] . '</a></div>' . "\n";
              }
            }
          } elseif ($part->ctype_secondary == "x-shockwave-flash") {
            $attachments["html"][] = '<!--Mime Type of File is '.$part->ctype_primary."/".$part->ctype_secondary.' -->'.
                '<object '.
                 'classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"  '.
                 'codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" '.
                 'width=""  '.  'height=""> '.
                 '<param name="movie" value="'. $config["URLFILESDIR"] . 
                 $filename .'"> '.
                 '<param name="quality" value="high"> '.
                 '<embed  '.  'src="'. $config["URLFILESDIR"] . $filename .'"  '.
                 'width=""  '.  'height=""  '.  'quality="high"  '.
                 'pluginspage="http://www.macromedia.com/go/getflashplayer"  '.
                 'type="application/x-shockwave-flash"  '.
                 'width=""  '.  'height=""></embed> '.  '</object>'; 
          } else {
            $attachments["html"][] = '<a href="' . $file . '">' . 
                $part->ctype_parameters['name'] . '</a>' . "\n";
          }
          if ($cid) {
            $attachments["cids"][$cid] = array($file,
                count($attachments["html"]) - 1);
          }
        }
        break;
    }		
  }
  return($meta_return);
}

function ubb2HTML(&$text) {
  // Array of tags with opening and closing
  $tagArray['img'] = array('open'=>'<img src="','close'=>'">');
  $tagArray['b'] = array('open'=>'<b>','close'=>'</b>');
  $tagArray['i'] = array('open'=>'<i>','close'=>'</i>');
  $tagArray['u'] = array('open'=>'<u>','close'=>'</u>');
  $tagArray['url'] = array('open'=>'<a href="','close'=>'">\\1</a>');
  $tagArray['email'] = array('open'=>'<a href="mailto:','close'=>'">\\1</a>');
  $tagArray['url=(.*)'] = array('open'=>'<a href="','close'=>'">\\2</a>');
  $tagArray['email=(.*)'] = array('open'=>'<a href="mailto:','close'=>'">\\2</a>');
  $tagArray['color=(.*)'] = array('open'=>'<font color="','close'=>'">\\2</font>');
  $tagArray['size=(.*)'] = array('open'=>'<font size="','close'=>'">\\2</font>');
  $tagArray['font=(.*)'] = array('open'=>'<font face="','close'=>'">\\2</font>');
  // Array of tags with only one part
  $sTagArray['br'] = array('tag'=>'<br>');
  $sTagArray['hr'] = array('tag'=>'<hr>');

  foreach($tagArray as $tagName=>$replace) {
    $tagEnd = preg_replace('/\W/Ui','',$tagName);
    $text = preg_replace("|\[$tagName\](.*)\[/$tagEnd\]|Ui","$replace[open]\\1$replace[close]",$text);
  }
  foreach($sTagArray as $tagName=>$replace) {
    $text = preg_replace("|\[$tagName\]|Ui","$replace[tag]",$text);
  }
  return $text;
}


// This function turns Enriched Text into something similar to HTML
// Very basic at the moment, only supports some functionality and dumps the rest
// FIXME: fix colours: <color><param>FFFF,C2FE,0374</param>some text </color>
function etf2HTML ( $content ) {

$search = array(
  '/<bold>/',
  '/<\/bold>/',
  '/<underline>/',
  '/<\/underline>/',
  '/<italic>/',
  '/<\/italic>/',
  '/<fontfamily><param>.*<\/param>/',
  '/<\/fontfamily>/',
  '/<x-tad-bigger>/',
  '/<\/x-tad-bigger>/',
  '/<bigger>/',
  '</bigger>/',
  '/<color>/',
  '/<\/color>/',	
  '/<param>.+<\/param>/'
);

$replace = array (
  '<b>',
  '</b>',
  '<u>',
  '</u>',
  '<i>',
  '</i>',
  '',
  '',
  '',
  '',
  '',
  '',
  '',
  '',
  ''
);
  // strip extra line breaks
  $content = preg_replace($search,$replace,$content);
  return trim($content);
}


// This function cleans up HTML in the e-mail
function HTML2HTML ( $content ) {
$search = array(
  '/<html>/',
  '/<\/html>/',
  '/<title>/',
  '/<\/title>/',
  '/<body[^<]*>/',
  '/<\/body>/',
  '/<head>/',
  '/<\/head>/',
  '/<meta[^<]*>/',
  '/<style[^<]*>[^<]*<\/style>/',
  '/<!DOCTYPE[^<]*>/',
  '/<img src=[\'"][^<]*>/'
//		'/<img src="cid:(.*)" .*>/'
);

$replace = array (
  '',
  '',
  '',
  '',
  '',
  '',
  '',
  '',
  '',
  '',
  '',
  ''
);
  // strip extra line breaks
  $content = preg_replace($search,$replace,trim($content));
  return ($content);
}



/**
* Determines if the sender is a valid user.
* @return integer|NULL
*/
function ValidatePoster( &$mimeDecodedEmail ) {
  global $wpdb;
  $config = GetConfig();
  $poster = NULL;
  $from = RemoveExtraCharactersInEmailAddress(trim($mimeDecodedEmail->headers["from"]));
  $resentFrom = RemoveExtraCharactersInEmailAddress(trim($mimeDecodedEmail->headers["resent-from"]));

if ( empty($from) ) { 
      echo 'Invalid Sender - Emtpy! ';
      return;
  }

  //See if the email address is one of the special authorized ones
  print("Confirming Access For $from \n");
  $sql = 'SELECT id FROM '. $wpdb->users.' WHERE user_email=\'' . addslashes($from) . "' LIMIT 1;";
  $user_ID= $wpdb->get_var($sql);
  $user = new WP_User($user_ID);
  if ($config["TURN_AUTHORIZATION_OFF"] || CheckEmailAddress($from) || CheckEmailAddress($resentFrom)) {
    if (empty($user_ID)){
        print("$from is authorized to post as the administrator\n");
        $from = get_option("admin_email");
        $adminUser=$config['ADMIN_USERNAME'];
        echo "adminUser='$adminUser'";
        $poster = $wpdb->get_var("SELECT ID FROM $wpdb->users WHERE
        user_login  = '$adminUser'");
    }
    else {
      $poster = $user_ID;
    }
  }
  else if ($user->has_cap("post_via_postie")) {
          $poster = $user_ID;
  }
  if (!$poster) {
      echo 'Invalid sender: ' . htmlentities($from) . "! Not adding email!\n";
      if ($config["FORWARD_REJECTED_MAIL"]) {
          if (ForwardRejectedMailToAdmin($mimeDecodedEmail)) { 
              echo "A copy of the message has been forwarded to the administrator.\n"; 
          } else {
              echo "The message was unable to be forwarded to the adminstrator.\n";
          }
      }
      return;
  } 
  return $poster;
}

/**
* Looks at the content for the start of the message and removes everything before that
* If the pattern is not found everything is returned
* @param string
* @param string
*/
function StartFilter(&$content,$start) {
  $pos = strpos($content,$start);
  if ( $pos === false) {
      return($content);
  }
  $content = substr($content, $pos + strlen($start), strlen($content));
}

/**
* Looks at the content for the start of the signature and removes all text
* after that point
* @param string
* @param array - a list of patterns to determine if it is a sig block
*/
function RemoveSignature( &$content,$filterList = array('--','- --' )) {
$arrcontent = explode("\n", $content);
$i = 0;
for ($i = 0; $i<=count($arrcontent); $i++) {
  $line = $arrcontent[$i];
  $nextline = $arrcontent[$i+1];
      foreach ($filterList as $pattern) {
          if (preg_match("/^$pattern/",trim($line))) {
              //print("<p>Found in $line");
              break 2;
          }
      } 
  $strcontent .= $line ."\n";
}
  $content = $strcontent;
}
/**
* Looks at the content for the given tag and removes all text
* after that point
* @param string
* @param filter
*/
function EndFilter( &$content,$filter) {
$arrcontent = explode("\n", $content);
$i = 0;
for ($i = 0; $i<=count($arrcontent); $i++) {
  $line = $arrcontent[$i];
  $nextline = $arrcontent[$i+1];
      if (preg_match("/^$filter/",trim($line))) {
          //print("<p>Found in $line");
          break;
      }
  $strcontent .= $line ."\n";
}
  $content = $strcontent;
}

//filter content for new lines
function FilterNewLines ( $content ) {
  $config=GetConfig();
  $search = array (
    "/\r\n/",
    "/\r/",
    "/\n\n/",
    "/\n/"
  );
  $replace = array (
          "\n",
          "\n",
          'ACTUAL_NEW_LINE',
    'LINEBREAK'
  );
  // strip extra line breaks, and replace double line breaks with paragraph
  // tags
  $result = preg_replace($search,$replace,$content);
  //$newContent='<p>' . preg_replace('/ACTUAL_NEW_LINE/',"</p>\n<p>",$result);
  $newContent=preg_replace('/ACTUAL_NEW_LINE/',"\n\n",$result);
  //$newContent=preg_replace('/<p>LINEBREAK$/', '', $newContent);
  if ($config['CONVERTNEWLINE']) {
    $newContent= preg_replace('/LINEBREAK/',"<br />\n",$newContent);
    if ($debug) {
      echo "converting newlines\n";
    }
  } else {
    $newContent= preg_replace('/LINEBREAK/'," ",$newContent);
    if ($debug) {
      echo "not converting newlines\n";
    }
  }
  return($newContent);
}
function FixEmailQuotes ( $content ) {
  # place e-mails quotes (indicated with >) in blockquote and pre tags
  $search = array (
    "/^>/"
  );
  $replace = array (
          '<br />&gt;'
  );
  // strip extra line breaks, and replace double line breaks with paragraph
  // tags
  $result = preg_replace($search,$replace,$content);
  //return('<p>' . preg_replace('/ACTUAL_NEW_LINE/',"<\/p>\n<p>",$result)
      //. '</p>');
  return($result);
}

//strip pgp stuff
function StripPGP ( $content ) {
  $search = array (
    '/-----BEGIN PGP SIGNED MESSAGE-----/',
    '/Hash: SHA1/'
  );
  $replace = array (
    ' ',
    ''
  );
  // strip extra line breaks
  $return = preg_replace($search,$replace,$content);
  return $return;
}

function ConvertToISO_8859_1($encoding,$charset, &$body ) {
  $config = GetConfig();
  $charset = strtolower($charset);
  $encoding = strtolower($encoding);
  if( (strtolower($config["MESSAGE_ENCODING"]) == "iso-8859-1") && (strtolower($charset) != 'iso-8859-1')) {
    if( $encoding == 'base64' || $encoding == 'quoted-printable' ) {
      $body = utf8_decode($body);
    }
  }
}
function IsISO88591Blog() {
  $config = GetConfig();
  if( (strtolower($config["MESSAGE_ENCODING"]) == "iso-8859-1")) {
      return(true);
  }
  return(false);
}
function IsUTF8Blog() {
  $config = GetConfig();
  if( (strtolower($config["MESSAGE_ENCODING"]) == "utf-8")) {
      return(true);
  }
  return(false);
}
function HandleMessageEncoding($encoding, $charset,&$body) {
    $charset = strtolower($charset);
    $encoding = strtolower($encoding);
    /*
    if ($encoding == '') {
      $encoding = '7bit';
    }
    */
    HandleQuotedPrintable($encoding, $body);
    if (isISO88591Blog()) {
        ConvertToISO_8859_1($encoding,$charset,$body);
    }
    else {
        ConvertToUTF_8($encoding,$charset,$body);
    }
}
function ConvertToUTF_8($encoding,$charset,&$body) {
    $charset = strtolower($charset);
    $encoding = strtolower($encoding);
    switch($charset) {
        case "iso-8859-1":
            $body = utf8_encode($body);
            break;
        case "iso-2022-jp":
            $body = iconv("ISO-2022-JP//TRANSLIT","UTF-8",$body);
            break;
        case "Windows-1252":
            $body = iconv("Windows-1252//TRANSLIT","UTF-8",$body);
            break;
    }
}

/**
  * This function handles decoding base64 if needed
  */
function DecodeBase64Part( &$part ) {
    if ( strtolower($part->headers['content-transfer-encoding']) == 'base64' ) {
        $part->body = base64_decode($part->body);
    }
}

function HandleQuotedPrintable($encoding, &$body ) {
    $config = GetConfig();
    if ( $config["MESSAGE_DEQUOTE"] && strtolower($encoding) == 'quoted-printable' ) {
			$body = quoted_printable_decode($body);
    }
}


function GenerateImageFileName($dir,$type) {
    static $ctr;
    $config = GetConfig();
    $ctr++;
    $type = strtolower($type);
    if ($type == "jpeg"
            || $type = "pjpeg") {
        $type = "jpg";
    }
    if ($config["TEST_EMAIL"]) {
        return($dir . "TEST-" . date("Ymd-His-",time()) . $ctr . "." . $type);
    }
    else {
        return($dir . date("Ymd-His-",time()) . $ctr . "." . $type);
    }
}
function ConfirmTrailingDirectorySeperator($string) {
    if (substr($string,strlen($string) - 1,1) == DIRECTORY_SEPARATOR) {
        return(true);
    }
    return(false);
}
/**
  * Checks for the comments tag
  * @return boolean
  */
function AllowCommentsOnPost(&$content) {
    $comments_allowed = get_option('default_comment_status');
    if (eregi("comments:([0|1|2])",$content,$matches)) {
        $content = ereg_replace("comments:$matches[1]","",$content);
        if ($matches[1] == "1") {
            $comments_allowed = "open";
        }
        else if ($matches[1] == "2") {
            $comments_allowed = "registered_only";
        }
        else {
            $comments_allowed = "closed";
        }
    }
    return($comments_allowed);
}
/**
  * Needed to be able to modify the content to remove the usage of the delay tag
  */
function DeterminePostDate(&$content, $message_date = NULL) {
    $config = GetConfig();
    $delay = 0;
    if ($debug) {
      echo "inside Determine Post Date, message_date = $message_date\n";
    }
    if (eregi("delay:(-?[0-9dhm]+)",$content,$matches)
        && trim($matches[1])) {
        if (eregi("(-?[0-9]+)d",$matches[1],$dayMatches)) {
            $days = $dayMatches[1];
        }
        if (eregi("(-?[0-9]+)h",$matches[1],$hourMatches)) {
            $hours = $hourMatches[1];
        }
        if (eregi("(-?[0-9]+)m",$matches[1],$minuteMatches)) {
            $minutes = $minuteMatches[1];
        }
        $delay = (($days * 24 + $hours) * 60 + $minutes) * 60;
        $content = ereg_replace("delay:$matches[1]","",$content);
    }
    if (!empty($message_date) && $delay==0) {
        $dateInSeconds = strtotime($message_date);
    }
    else {
        $dateInSeconds = time() + $delay;
    }
    $post_date = gmdate('Y-m-d H:i:s',$dateInSeconds + ($config["TIME_OFFSET"] * 3600));
    $post_date_gmt = gmdate('Y-m-d H:i:s',$dateInSeconds);

    //echo "--------------------DELAY------------\n";
    //echo "delay=$delay, dateInSeconds = $dateInSeconds\n";
    //echo "post_date=$post_date\n";
    //echo "--------------------DELAY------------\n";
    return(array($post_date,$post_date_gmt));
}
/**
  * This function takes the content of the message - looks for a subject at the begining surrounded by # and then removes that from the content
  */
function ParseInMessageSubject($content) {
    $config = GetConfig();
    if (substr($content,0,1) != "#") {
        //print("<p>Didn't start with # '".substr(ltrim($content),0,10)."'");
        return(array($config["DEFAULT_TITLE"],$content));
    }
    $subjectEndIndex = strpos($content,"#",1);
    if (!$subjectEndIndex > 0) {
        return(array($config["DEFAULT_TITLE"],$content));
    }
    $subject = substr($content,1,$subjectEndIndex - 1);
    $content = substr($content,$subjectEndIndex + 1,strlen($content));
    return(array($subject,$content));
}
/**
  * This method sorts thru the mime parts of the message. It is looking for files labeled - "applefile" - current
  * this part of the file attachment is not supported
  *@param object
  */
function FilterAppleFile(&$mimeDecodedEmail) {
    $newParts = array();
    $found = false;
    for ($i = 0; $i < count($mimeDecodedEmail->parts); $i++)  {
        if ($mimeDecodedEmail->parts[$i]->ctype_secondary == "applefile") {
            $found = true;
        }
        else {
            $newParts[] = &$mimeDecodedEmail->parts[$i];
        }
    }
    if ($found && $newParts) {
        $mimeDecodedEmail->parts = $newParts; //This is now the filtered list of just the preferred type.
    }
}
function postie_media_handle_upload($the_file, $post_id, $post_data = array()) {
  $overrides = array('test_form'=>false);

  $time = current_time('mysql');
  if ( $post = get_post($post_id) ) {
    if ( substr( $post->post_date, 0, 4 ) > 0 )
      $time = $post->post_date;
  }

  $file = postie_handle_upload($the_file, $overrides, $time);

  if ( isset($file['error']) )
    return new WP_Error( 'upload_error', $file['error'] );

  $url = $file['url'];
  $type = $file['type'];
  $file = $file['file'];
  $title = preg_replace('/\.[^.]+$/', '', basename($file));
  $content = '';

  // use image exif/iptc data for title and caption defaults if possible
  if ( $image_meta = @wp_read_image_metadata($file) ) {
    if ( trim($image_meta['title']) )
      $title = $image_meta['title'];
    if ( trim($image_meta['caption']) )
      $content = $image_meta['caption'];
  }

  // Construct the attachment array
  $attachment = array_merge( array(
    'post_mime_type' => $type,
    'guid' => $url,
    'post_parent' => $post_id,
    'post_title' => $title,
    'post_excerpt' => $content,
    'post_content' => $content,
  ), $post_data );

  // Save the data
  $id = wp_insert_attachment($attachment, $file, $post_id);
  echo "id=$id\n";
  if ( !is_wp_error($id) ) {
    wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
  }

  return $id;

}

function postie_handle_upload( &$file, $overrides = false, $time = null ) {
	// The default error handler.
	if (! function_exists( 'wp_handle_upload_error' ) ) {
		function wp_handle_upload_error( &$file, $message ) {
			return array( 'error'=>$message );
		}
	}

	// You may define your own function and pass the name in $overrides['upload_error_handler']
	$upload_error_handler = 'wp_handle_upload_error';

	// You may define your own function and pass the name in $overrides['unique_filename_callback']
	$unique_filename_callback = null;

	// $_POST['action'] must be set and its value must equal $overrides['action'] or this:
	$action = 'wp_handle_upload';

	// Courtesy of php.net, the strings that describe the error indicated in $_FILES[{form field}]['error'].
	$upload_error_strings = array( false,
		__( "The uploaded file exceeds the <code>upload_max_filesize</code> directive in <code>php.ini</code>." ),
		__( "The uploaded file exceeds the <em>MAX_FILE_SIZE</em> directive that was specified in the HTML form." ),
		__( "The uploaded file was only partially uploaded." ),
		__( "No file was uploaded." ),
		'',
		__( "Missing a temporary folder." ),
		__( "Failed to write file to disk." ));

	// All tests are on by default. Most can be turned off by $override[{test_name}] = false;
	$test_form = true;
	$test_size = true;

	// If you override this, you must provide $ext and $type!!!!
	$test_type = true;
	$mimes = false;

	// Install user overrides. Did we mention that this voids your warranty?
	if ( is_array( $overrides ) )
		extract( $overrides, EXTR_OVERWRITE );

	// A correct form post will pass this test.
	if ( $test_form && (!isset( $_POST['action'] ) || ($_POST['action'] != $action ) ) )
		return $upload_error_handler( $file, __( 'Invalid form submission.' ));

	// A successful upload will pass this test. It makes no sense to override this one.
	if ( $file['error'] > 0 )
		return $upload_error_handler( $file, $upload_error_strings[$file['error']] );

	// A non-empty file will pass this test.
	if ( $test_size && !($file['size'] > 0 ) )
		return $upload_error_handler( $file, __( 'File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini.' ));

	// A properly uploaded file will pass this test. There should be no reason to override this one.
  /*
	if (!file_exists( $file['tmp_name'] ) )
    print_r($file);
		return $upload_error_handler( $file, __( 'Specified file failed upload test.'));
*/
	// A correct MIME type will pass this test. Override $mimes or use the upload_mimes filter.
	if ( $test_type ) {
		$wp_filetype = wp_check_filetype( $file['name'], $mimes );

		extract( $wp_filetype );

		if ( ( !$type || !$ext ) && !current_user_can( 'unfiltered_upload' ) )
			return $upload_error_handler( $file, __( 'File type does not meet security guidelines. Try another.' ));

		if ( !$ext )
			$ext = ltrim(strrchr($file['name'], '.'), '.');

		if ( !$type )
			$type = $file['type'];
	}

	// A writable uploads dir will pass this test. Again, there's no point overriding this one.
	if ( ! ( ( $uploads = wp_upload_dir($time) ) && false === $uploads['error'] ) )
		return $upload_error_handler( $file, $uploads['error'] );

	$filename = wp_unique_filename( $uploads['path'], $file['name'], $unique_filename_callback );

	// Move the file to the uploads dir
	$new_file = $uploads['path'] . "/$filename";
	if ( false === @ rename( $file['tmp_name'], $new_file ) ) {
		return $upload_error_handler( $file, sprintf( __('The uploaded file could not be moved to %s.' ), $uploads['path'] ) );
	}

	// Set correct file permissions
	$stat = stat( dirname( $new_file ));
	$perms = $stat['mode'] & 0000666;
	@ chmod( $new_file, $perms );

	// Compute the URL
	$url = $uploads['url'] . "/$filename";

	$return = apply_filters( 'wp_handle_upload', array( 'file' => $new_file, 'url' => $url, 'type' => $type ) );

	return $return;
}/**
  * Searches for the existance of a certain MIME TYPE in the tree of mime attachments
  * @param primary mime
  * @param secondary mime
  * @return boolean
  */
function SearchForMIMEType($part,$primary,$secondary) {
    if ($part->ctype_primary == $primary && $part->ctype_secondary == $secondary) {
            return true;
    }
    if ($part->ctype_primary == "multipart") {
        for ($i = 0; $i < count($part->parts); $i++) {
            if (SearchForMIMEType($part->parts[$i], $primary,$secondary)) {
                return true;
            }
        }
    }
    return false;
}
/**
  * This method sorts thru the mime parts of the message. It is looking for a certain type of text attachment. If 
  * that type is present it filters out all other text types. If it is not - then nothing is done
  *@param object
  */
function FilterTextParts(&$mimeDecodedEmail) {
    $config = GetConfig();
    $newParts = array();
    $found = false;
    for ($i = 0; $i < count($mimeDecodedEmail->parts); $i++)  {
        if (in_array($mimeDecodedEmail->parts[$i]->ctype_primary,array("text","multipart"))) {
            if (SearchForMIMEType($mimeDecodedEmail->parts[$i],"text",$config["PREFER_TEXT_TYPE"])) {
            $newParts[] = &$mimeDecodedEmail->parts[$i];
            $found = true;
            }
        }
        else {
            $newParts[] = &$mimeDecodedEmail->parts[$i];
        }
    }
    if ($found && $newParts) {
        $mimeDecodedEmail->parts = $newParts; //This is now the filtered list of just the preferred type.
    }
}
/**
  *This forwards on the mail to the admin for review
  *It execpts an object containing the entire message
  */
function ForwardRejectedMailToAdmin( &$mail_content) {
    $config = GetConfig();
    if ($config["TEST_EMAIL"]) {
        return;
    }
	$user = get_userdata('1');
	$myname = $user->user_nicename;
	$myemailadd = get_option("admin_email");
	$blogname = get_option("blogname");
	$recipients = $myemailadd;
	if (count($recipients) == 0) {
		return false;
	}

    $from = trim($mail_content->headers["from"]);
    $subject = $mail_content->headers['subject'];
    
	// Set email subject
	$alert_subject = $blogname . ": Unauthorized Post Attempt";

	// Set sender details
	$headers = "From: " .$from ."\r\n";
    if (isset($mail_content->headers["mime-version"])) {
        $headers .= "Mime-Version: ". $mail_content->headers["mime-version"] . "\r\n";
    }
    if (isset($mail_content->headers["content-type"])) {
        $headers .= "Content-Type: ". $mail_content->headers["content-type"] . "\r\n";
    }

	// SDM 20041123
	foreach ($recipients as $recipient) {
		$recipient = trim($recipient);
		if (! empty($recipient)) {
			$headers .= "Bcc: " . $recipient . "\r\n";
		}
	}

	// construct mail message
	$message = "An unauthorized message has been sent to " . $blogname . " from " . $from. ". The subject of this message was: '" . $subject . "'.";
	$message .= "\n\nIf you wish to allow posts from this address, please add " . $from. " to the registered users list and manually add the content of the e-mail found below.";
	$message .= "\n\nOtherwise, the e-mail has already been deleted from the server and you can ignore this message.";
	$message .= "\n\nIf you would like to prevent posstie from forwarding mail
  in the future, please change the FORWARD_REJECTED_MAIL setting in the Postie
  settings panel"; 
	$message .= "\n\nThe original content of the e-mail has been attached.\n\n";
    $boundary = "--".$mail_content->ctype_parameters["boundary"] ."\n";

    $mailtext = $boundary;
    $mailtext .= "Content-Type: text/plain;format=flowed;charset=\"iso-8859-1\";reply-type=original\n";
    $mailtext .= "Content-Transfer-Encoding: 7bit\n";
    $mailtext .= "\n";
    $mailtext .= $message;
    foreach ($mail_content->parts as $part) {
        $mailtext .= $boundary;
        $mailtext .= "Content-Type: ".$part->headers["content-type"] . "\n";
        $mailtext .= "Content-Transfer-Encoding: ".$part->headers["content-transfer-encoding"] . "\n";
        if (isset($part->headers["content-disposition"])) {
            $mailtext .= "Content-Disposition: ".$part->headers["content-disposition"] . "\n";
        }
        $mailtext .= "\n";
        $mailtext .= $part->body;
    }
	
	// Send message
	mail($myemailadd, $alert_subject, $mailtext, $headers);

	return true;
}
/**
  * This function handles the basic mime decoding
  * @param string
  * @return array
  */
function DecodeMIMEMail($email) {
    $params = array();
    $params['include_bodies'] = true;
    $params['decode_bodies'] = false;
    $params['decode_headers'] = true;
    $params['input'] = $email;
    return(Mail_mimeDecode::decode($params));
}
    
/**
  * This is used for debugging the mimeDecodedEmail of the mail
  */
function DisplayMIMEPartTypes($mimeDecodedEmail) {
    foreach($mimeDecodedEmail->parts as $part) {
        print("<p>".$part->ctype_primary . " / ".$part->ctype_secondary . "/ ".$part->headers['content-transfer-encoding'] ."\n");
    }
}

/**
  * This compares the current address to the list of authorized addresses
  * @param string - email address
  * @return boolean
  */
function CheckEmailAddress($address) {
    $config = GetConfig();
    $address = strtolower($address);
    if (!is_array($config["AUTHORIZED_ADDRESSES"])
            || !count($config["AUTHORIZED_ADDRESSES"])) {
        return false;
    }
    return(in_array($address,$config["AUTHORIZED_ADDRESSES"]));
}
/**
  *This method works around a problemw with email address with extra <> in the email address
  * @param string
  * @return string
  */
function RemoveExtraCharactersInEmailAddress($address) {
    $matches = array();
    if (preg_match('/^[^<>]+<([^<> ()]+)>$/',$address,$matches)) {
        $address = $matches[1];
    }
    else if (preg_match('/<([^<> ()]+)>/',$address,$matches)) {
        $address = $matches[1];
    }

    return($address);
}

/** 
  * This function gleans the name from the 'from:' header if available. If not
  * it just returns the username (everything before @)
  */
function GetNameFromEmail($address) {
    $matches = array();
    if (preg_match('/^([^<>]+)<([^<> ()]+)>$/',$address,$matches)) {
        $name = $matches[1];
    }
    else if (preg_match('/<([^<>@ ()]+)>/',$address,$matches)) {
        $name = $matches[1];
    }

    return($name);
}

function parseImageTemplate($html, $id, $alt, $title, $align, $url,
    $size='medium') {
  $config=GetConfig();

  $htmlalt = ( empty($alt) ) ? $title : $alt;

  $html = get_image_tag($id, $htmlalt, $title, $align, $size);

  $rel = $rel ? ' rel="attachment wp-att-'.attr($id).'"' : '';

  if ( $url )
    $html = '<a href="' . clean_url($url) . "\"$rel>$html</a>";


/* we check template for thumb, thumbnail, large, full and use that as
size. If not found, we default to medium */
  $imageTemplate=$config['IMAGETEMPLATE'];
  if (strpos($imageTemplate, '{THUMBNAIL}')!==false  ||
      strpos($imageTemplate, '{THUMB}')!==false) {
    $size='thumbnail';
  } else if (strpos($imageTemplate, '{LARGE}')!==false) {
    $size='large';
  } else if (strpos($imageTemplate, '{FULL}')!==false) {
    $size='full';
  }
  list( $img_src, $width, $height ) = image_downsize($id, $size);
  $hwstring = image_hwstring($width, $height);
  $uploadDir=wp_upload_dir();
  $absFileName=$uploadDir['path'] .'/'. basename($img_src);
  $relFileName=str_replace(ABSPATH,'', $absFileName);
  $fileLink=wp_get_attachment_url($id);
  $pageLink=get_attachment_link($id);

  $imageTemplate=str_replace('{THUMBNAIL}', $img_src, $imageTemplate);
  $imageTemplate=str_replace('{THUMB}', $img_src, $imageTemplate);
  $imageTemplate=str_replace('{MEDIUM}', $img_src, $imageTemplate);
  $imageTemplate=str_replace('{LARGE}', $img_src, $imageTemplate);
  $imageTemplate=str_replace('{FULL}', $img_src, $imageTemplate);
  $imageTemplate=str_replace('{FILELINK}', $fileLink, $imageTemplate);
  $imageTemplate=str_replace('{PAGELINK}', $pageLink, $imageTemplate);
  $imageTemplate=str_replace('{WIDTH}', $width . 'px', $imageTemplate);
  $imageTemplate=str_replace('{HEIGHT}', $height . 'px', $imageTemplate);
  $imageTemplate=str_replace('{FILENAME}', $absFileName, $imageTemplate);
  $imageTemplate=str_replace('{RELFILENAME}', $relFileName, $imageTemplate);
  if ($alt!='') {
    $imageTemplate=str_replace('{CAPTION}', $alt, $imageTemplate);
  }
  return($imageTemplate);
} 
/**
  * When sending in HTML email the html refers to the content-id(CID) of the image - this replaces
  * the cid place holder with the actual url of the image sent in
  * @param string - text of post
  * @param array - array of HTML for images for post
  */
function ReplaceImageCIDs(&$content,&$attachments) {
    $used = array();
    foreach ($attachments["cids"] as $key => $info) {
        $pattern = "/cid:$key/";
        if(preg_match($pattern,$content)) {
            $content = preg_replace($pattern,$info[0],$content);
            $used[] = $info[1]; //Index of html to ignore
        }
    }
    $html = array();
    for ($i = 0; $i < count($attachments["html"]); $i++) {
        if (!in_array($i,$used)) {
            $html[] = $attachments["html"][$i];
        }
    }
    $attachments["html"] = $html;

}
/**
  * This function handles replacing image place holder #img1# with the HTML for that image
  * @param string - text of post
  * @param array - array of HTML for images for post
  */
function ReplaceImagePlaceHolders(&$content,$attachments) {
  $config = GetConfig();
  ($config["START_IMAGE_COUNT_AT_ZERO"] ? $startIndex = 0 :$startIndex = 1);
  foreach ( $attachments as $i => $value ) {
    // looks for ' #img1# ' etc... and replaces with image
    $img_placeholder_temp = str_replace("%", intval($startIndex + $i), $config["IMAGE_PLACEHOLDER"]);
    $eimg_placeholder_temp = str_replace("%", intval($startIndex + $i),
    "#eimg%#");
    $img_placeholder_temp=rtrim($img_placeholder_temp,'#');
    $eimg_placeholder_temp=rtrim($eimg_placeholder_temp,'#');
    if ( stristr($content, $img_placeholder_temp)  ||
         stristr($content, $eimg_placeholder_temp) ) {
      // look for caption
      $caption='';
      if ( preg_match("/$img_placeholder_temp caption=['\"](.*)['\"]/", $content, $matches))  {
        $caption =$matches[1];
        $img_placeholder_temp.=' ' . $matches[0];
        $eimg_placeholder_temp.=' ' . $matches[0];
      }
      $value = str_replace('{CAPTION}', $caption, $value);
      $img_placeholder_temp.='#';
      $eimg_placeholder_temp.='#';
      $content = str_replace($img_placeholder_temp, $value, $content);
      $content = str_replace($eimg_placeholder_temp, $value, $content);
      print(htmlspecialchars("value=$value\n",ENT_QUOTES));
      print(htmlspecialchars("content=\n***\n$content\n***\n",ENT_QUOTES));
    } else {
      $value = str_replace('{CAPTION}', '', $value);
      /* if using the gallery shortcode, don't add pictures at all */
      if (!preg_match("/\[gallery[^\[]*\]/", $content, $matches))  {
        if ($config["IMAGES_APPEND"]) {
          $content .= $value;
        } else {
          $content = $value . $content;
        }
      }
    }
  }
}
/**
  *This function handles finding and setting the correct subject
  * @return array - (subject,content)
  */
function GetSubject(&$mimeDecodedEmail,&$content) {
    $config = GetConfig();
    //assign the default title/subject
    if ( $mimeDecodedEmail->headers['subject'] == NULL ) {
        if ($config["ALLOW_SUBJECT_IN_MAIL"]) {
            list($subject,$content) = ParseInMessageSubject($content);
        }
        else {
            $subject = $config["DEFAULT_TITLE"];
        }
        $mimeDecodedEmail->headers['subject'] = $subject;
    } else {	
        $subject = $mimeDecodedEmail->headers['subject'];
        HandleMessageEncoding($mimeDecodedEmail->headers["content-transfer-encoding"],
                              $mimeDecodedEmail->ctype_parameters["charset"],
                              $subject);
        if (!$config["ALLOW_HTML_IN_SUBJECT"]) {
            $subject = htmlentities($subject);
        }
    }
    //This is for ISO-2022-JP - Can anyone confirm that this is still neeeded?
     // escape sequence is 'ESC $ B' == 1b 24 42 hex.
    if (strpos($subject, "\x1b\x24\x42") !== false) {
        // found iso-2022-jp escape sequence in subject... convert!
        $subject = iconv("ISO-2022-JP//TRANSLIT", "UTF-8", $subject);
    }
    return($subject);
}
/** 
  * this function determines tags for the post
  *
  */
function GetPostTags(&$content) {
  $config = GetConfig();
  global $wpdb;
  $post_tags = array();
  //try and determine tags
  if ( preg_match('/tags: ?(.*)\n/', $content, $matches))  {
    $content = str_replace($matches[0], "", $content);
    $post_tags = preg_split("/,\s*/", $matches[1]);
  }
  if (!count($post_tags)) {
      echo "using default tags " . $config["DEFAULT_POST_TAGS"]. "\n";
      $post_tags =  $config["DEFAULT_POST_TAGS"];
  }
  return($post_tags);
}
/** 
  * this function determines excerpt for the post
  *
  */
function GetPostExcerpt(&$content) {
  $config = GetConfig();
  global $wpdb;
  $post_excerpt = '';
  //try and determine excerpt
    //echo "CONTENT ------
          //$content 
           //-------";
  if ( preg_match('/:excerptstart ?(.*):excerptend/s', $content, $matches))  {
    $content = str_replace($matches[0], "", $content);
    $post_excerpt = $matches[1];
    //print_r($matches);
  }
  return($post_excerpt);
}
/**
  * This function determines categories for the post
  * @return array
  */
function GetPostCategories(&$subject) {
    $config = GetConfig();
    global $wpdb;
    $post_categories = array();
    $matches = array();
    //try and determine category
    if ( preg_match('/(.+): (.*)/', $subject, $matches))  {
        $subject = trim($matches[2]);
        $matches[1] = array($matches[1]);
    }
    else if (preg_match_all('/\[(.[^\[]*)\]/', $subject, $matches)) {
        preg_match("/](.[^\[]*)$/",$subject,$subject_matches);
        $subject = trim($subject_matches[1]);
    }
    else if ( preg_match_all('/-(.[^-]*)-/', $subject, $matches) ) {
        preg_match("/-(.[^-]*)$/",$subject,$subject_matches);
        $subject = trim($subject_matches[1]);
    }
    if (count($matches)) {
        foreach($matches[1] as $match) {
            $match = trim($match);
            $category = NULL;
            print("Working on $match\n"); 

            $sql_name = 'SELECT term_id 
                         FROM ' . $wpdb->terms. ' 
                         WHERE name=\'' . addslashes($match) . '\'';
            $sql_id = 'SELECT term_id 
                       FROM ' . $wpdb->terms. ' 
                       WHERE term_id=\'' . addslashes($match) . '\'';
            $sql_sub_name = 'SELECT term_id 
                             FROM ' . $wpdb->terms. ' 
                             WHERE name LIKE \'' . addslashes($match) . '%\' limit 1';
                
            if ( $category = $wpdb->get_var($sql_name) ) {
                //then category is a named and found 
            } elseif ( $category = $wpdb->get_var($sql_id) ) {
                //then cateogry was an ID and found 
            } elseif ( $category = $wpdb->get_var($sql_sub_name) ) {
                //then cateogry is a start of a name and found
            }  
            if ($category) {
                $post_categories[] = $category;
            }
        }
    }
    if (!count($post_categories)) {
        $post_categories[] =  $config["DEFAULT_POST_CATEGORY"];
    }
    return($post_categories);
}
/**
  *This function just outputs a simple html report about what is being posted in
  */
function DisplayEmailPost($details) {
  $config = GetConfig();
  if ($debug) {
    print_r($config);
    print_r($details);
  }
  $theFinalContent=$details['post_content'];
  // Report
  print '</pre><p><b>Post Author</b>: ' . $details["post_author"]. '<br />' . "\n";
  print '<b>Date</b>: ' . $details["post_date"] . '<br />' . "\n";
  print '<b>Date GMT</b>: ' . $details["post_date_gmt"] . '<br />' . "\n";
  foreach($details["post_category"] as $category) {
      print '<b>Category</b>: ' . $category . '<br />' . "\n";
  }
  print '<b>Ping Status</b>: ' . $details["ping_status"] . '<br />' . "\n";
  print '<b>Comment Status</b>: ' . $details["comment_status"] . '<br />' . "\n";
  print '<b>Subject</b>: ' . $details["post_title"]. '<br />' . "\n";
  print '<b>Postname</b>: ' . $details["post_name"] . '<br />' . "\n";
  print '<b>Posted content:</b></p><hr />' . $details["post_content"] . '<hr /><pre>';
}
/**
  * Takes a value and builds a simple simple yes/no select box
  * @param string
  * @param string
  * @param string
  * @param string
  */
function BuildBooleanSelect($label,$id,$current_value,$recommendation = NULL) {
   $string="<tr>
	<th scope=\"row\">". __($label, 'postie').":</th>
	<td><select name=\"$id\" id=\"$id\">
    <option value=\"1\">".__("Yes", 'postie')."</option>
    <option value=\"0\" ". (!$current_value ? "SELECTED" : NULL) .
    ">".__("No", 'postie').'</option>
	</select>';
    if ($recommendation!=NULL) {
      $string.='<span class="recommendation">'.__($recommendation,
      'postie').'</span>';
    }
    $string.="</td>\n</tr>";
    return($string);
}
/**
  * This takes an array and display a text box for editing
  *@param string
  *@param string
  *@param array
  *@param string
  */
function BuildTextArea($label,$id,$current_value,$recommendation = NULL) {
  $string = "<tr> <th scope=\"row\">".__($label, 'postie').":";
  if ($recommendation) {
    $string.="<br /><span class='recommendation'>".__($recommendation,
    'postie')."</span>";
  }
  $string.="</th>";

   $string .="<td><textarea cols=40 rows=5 name=\"$id\" id=\"$id\">";
        if (is_array($current_value)) {
            foreach($current_value as $item) {
                $string .= "$item\n";
            }
        }
    $string .= "</textarea></td>
	</tr>";
    return($string);
}
/**
  *Handles the creation of the table needed to store all the data
  */
function SetupConfiguration() {
    if (! function_exists('maybe_create_table')) {
        require_once(ABSPATH . DIRECTORY_SEPARATOR. 'wp-admin'.DIRECTORY_SEPARATOR.'upgrade-functions.php');
    }
    $create_table_sql = "CREATE TABLE ".POSTIE_TABLE ." (
         label text NOT NULL,
         value text not NULL
             );";

    maybe_create_table(POSTIE_TABLE,$create_table_sql);
}
/**
  *This function resets all the configuration options to the default
  */
function ResetPostieConfig() {
	global $wpdb;
  //Get rid of the old table
  //$postie_table=$GLOBALS["table_prefix"]. "postie_config";
  $query ="UPDATE ". POSTIE_TABLE . " set value='' WHERE label NOT IN ('MAIL_PASSWORD', 'MAIL_SERVER', 'MAIL_SERVER_PORT', 'MAIL_USERID', 'INPUT_PROTOCOL');";
  echo "<script type='text/javascript'>alert('query=$query');</script>\n";
  $results=$wpdb->query($wpdb->prepare($query));
  $config = GetConfig();
  $key_arrays = GetListOfArrayConfig();
  foreach($key_arrays as $key) {
      $config[$key] = join("\n",$config[$key]);
  }
  UpdatePostieConfig($config);
  return('foo');
}
/**
  * This function handles updating the configuration 
  *@return boolean
  */
function UpdatePostieConfig($data) {
    SetupConfiguration();
    $key_arrays = GetListOfArrayConfig();
    $config = GetDBConfig();
    foreach($config as $key => $value) {
        if (isset($data[$key])) {
            if (in_array($key,$key_arrays)) { //This is stored as an array
                $config[$key] = array();
                $values = explode("\n",$data[$key]);
                foreach($values as $item) {
                    if (trim($item)) {
                        $config[$key][] = trim($item);
                    }
                }
            }
            else {
                $config[$key] = $data[$key];
            }
        }
    }
    WriteConfig($config);
    UpdatePostiePermissions($data["ROLE_ACCESS"]);
    return(1);
}
/**
  * This handles actually writing out the changes
  *@param array
  */
function WriteConfig($config) {
    global $wpdb;
    foreach($config as $key=>$value) {
        $label = apply_filters('content_save_pre', $key);
        $q = $wpdb->query("DELETE FROM ". POSTIE_TABLE . " WHERE label = '$label';");
        if (!is_array($value)) {
            $q = $wpdb->query("INSERT INTO ". POSTIE_TABLE . " (label,value) VALUES ('$label','".apply_filters('content_save_pre', $value)."');");
        }
        else {
            foreach($value as $item) {
                $q = $wpdb->query("INSERT INTO ". POSTIE_TABLE . " (label,value) VALUES ('$label','".apply_filters('content_save_pre', $item)."');");
            }
        }
    }
}
/**
  *This handles actually reading the config from the database
  * @return array
  */
function ReadDBConfig() {
    SetupConfiguration();
    $config = array();
    global $wpdb;
    $data = $wpdb->get_results("SELECT label,value FROM ". POSTIE_TABLE .";");
    if (is_array($data)) {
        foreach($data as $row) {
            if (in_array($row->label,GetListOfArrayConfig())) {
                if (!is_array($config[$row->label])) {
                    $config[$row->label] = array();
                }
                $config[$row->label][] = $row->value;
            }
            else {
                $config[$row->label] = $row->value;
            }
        }
    }

    return($config);
}
/**
  * This handles the configs that are stored in the data base
  * You should never call this outside of the library
  * @return array
  * @access private
  */
function GetDBConfig() {
    $config = ReadDBConfig();
    if (!isset($config["ADMIN_USERNAME"])) { $config["ADMIN_USERNAME"] =
    'admin'; }
    if (!isset($config["PREFER_TEXT_TYPE"])) { $config["PREFER_TEXT_TYPE"] = "plain";}
    if (!isset($config["DEFAULT_TITLE"])) { $config["DEFAULT_TITLE"] = "Live From The Field";}
    if (!isset($config["INPUT_PROTOCOL"])) { $config["INPUT_PROTOCOL"] = "pop3";}
    if (!isset($config["IMAGE_PLACEHOLDER"])) { $config["IMAGE_PLACEHOLDER"] = "#img%#";}
    if (!isset($config["IMAGES_APPEND"])) { $config["IMAGES_APPEND"] = true;}
    if (!isset($config["3GPDIV"])) { $config["3GPDIV"] = "postie-3gp-div";}
    if (!isset($config["ATTACHMENTDIV"])) { $config["ATTACHMENTDIV"] = "postie-attachment-div";}
    if (!isset($config["3GPCLASS"])) { $config["3GPCLASS"] = "postie-video";}
    if (!isset($config["VIDEO_WIDTH"])) { $config["VIDEO_WIDTH"] = 128;}
    if (!isset($config["PLAYER_WIDTH"])) { $config["PLAYER_WIDTH"] = 128;}
    if (!isset($config["VIDEO_HEIGHT"])) { $config["VIDEO_HEIGHT"] = 112;}
    if (!isset($config["PLAYER_HEIGHT"])) { $config["PLAYER_HEIGHT"] = 150;}
    if (!isset($config["VIDEO_AUTOPLAY"])) { $config["VIDEO_AUTOPLAY"] = false;}
    if (!isset($config["JPEGQUALITY"])) { $config["JPEGQUALITY"] = 80;}
    if (!isset($config["AUTO_SMART_SHARP"])) { $config["AUTO_SMART_SHARP"] = false;}
    if (!isset($config["ALLOW_SUBJECT_IN_MAIL"])) { $config["ALLOW_SUBJECT_IN_MAIL"] = true;}
    if (!isset($config["DROP_SIGNATURE"])) { $config["DROP_SIGNATURE"] = true;}
    if (!isset($config["MESSAGE_START"])) { $config["MESSAGE_START"] = ":start";}
    if (!isset($config["MESSAGE_END"])) { $config["MESSAGE_END"] = ":end";}
    if (!isset($config["FORWARD_REJECTED_MAIL"])) { $config["FORWARD_REJECTED_MAIL"] = true;}
    if (!isset($config["ALLOW_HTML_IN_SUBJECT"])) { $config["ALLOW_HTML_IN_SUBJECT"] = true;}
    if (!isset($config["ALLOW_HTML_IN_BODY"])) { $config["ALLOW_HTML_IN_BODY"] = true;}
    if (!isset($config["START_IMAGE_COUNT_AT_ZERO"])) { $config["START_IMAGE_COUNT_AT_ZERO"] = false;}
    if (!isset($config["MESSAGE_ENCODING"])) { $config["MESSAGE_ENCODING"] = "UTF-8"; }
    if (!isset($config["MESSAGE_DEQUOTE"])) { $config["MESSAGE_DEQUOTE"] = true; }
    if (!isset($config["TURN_AUTHORIZATION_OFF"])) { $config["TURN_AUTHORIZATION_OFF"] = false;}
    if (!isset($config["CUSTOM_IMAGE_FIELD"])) { $config["CUSTOM_IMAGE_FIELD"] = false;}
    if (!isset($config["CONVERTNEWLINE"])) { $config["CONVERTNEWLINE"] = false;}


    if (!isset($config["SIG_PATTERN_LIST"])) { $config["SIG_PATTERN_LIST"] = array('--','- --',"\?--");}
    if (!isset($config["BANNED_FILES_LIST"])) { $config["BANNED_FILES_LIST"] = array();}
    if (!isset($config["SUPPORTED_FILE_TYPES"])) { $config["SUPPORTED_FILE_TYPES"] = array("video","application");}
    if (!isset($config["AUTHORIZED_ADDRESSES"])) { $config["AUTHORIZED_ADDRESSES"] = array();}
    if (!isset($config["MAIL_SERVER"])) { $config["MAIL_SERVER"] = NULL; }
    if (!isset($config["MAIL_SERVER_PORT"])) { $config["MAIL_SERVER_PORT"] =  NULL; }
    if (!isset($config["MAIL_USERID"])) { $config["MAIL_USERID"] =  NULL; }
    if (!isset($config["MAIL_PASSWORD"])) { $config["MAIL_PASSWORD"] =  NULL; }
    if (!isset($config["DEFAULT_POST_CATEGORY"])) { $config["DEFAULT_POST_CATEGORY"] =  NULL; }
    if (!isset($config["DEFAULT_POST_TAGS"])) { $config["DEFAULT_POST_TAGS"] =  NULL; }
    if (!isset($config["TIME_OFFSET"])) { $config["TIME_OFFSET"] =  get_option('gmt_offset'); }
    if (!isset($config["3GP_QT"])) { $config["3GP_QT"] =  true; }
    if (!isset($config["3GP_FFMPEG"])) { $config["3GP_FFMPEG"] = "/usr/bin/ffmpeg";}
    if (!isset($config["WRAP_PRE"])) { $config["WRAP_PRE"] =  'no'; }
    if (!isset($config["CONVERTURLS"])) { $config["CONVERTURLS"] =  true; }
    if (!isset($config["ADD_META"])) { $config["ADD_META"] =  'no'; }
    if (!isset($config["USEIMAGETEMPLATE"]))  
      $config["USEIMAGETEMPLATE"] = false; 
    if (!isset($config["USEVIDEOTEMPLATE"])) 
      $config["USEVIDEOTEMPLATE"] = false; 
    if (!isset($config["POST_STATUS"])) 
      $config["POST_STATUS"] = 'publish'; 
    if (!isset($config["IMAGE_NEW_WINDOW"])) 
      $config["IMAGE_NEW_WINDOW"] = false; 
    if (!isset($config["FILTERNEWLINES"]))  
      $config["FILTERNEWLINES"] = true; 
    if (!isset($config["IMAGETEMPLATE"])) { $config["IMAGETEMPLATE"] =
      "<div class='imageframe alignleft'><a href='{IMAGE}'><img src='{THUMBNAIL}' alt='{CAPTION}' title='{CAPTION}' class='attachment' /></a><div class='imagecaption'>{CAPTION}</div></div>";
    }
    if (!isset($config["VIDEOTEMPLATE"])) { $config["VIDEOTEMPLATE"] =
        '<object '.
            'classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" '.
            'codebase="http://www.apple.com/qtactivex/qtplugin.cab" '.
            'width="' . $config['VIDEO_WIDTH'] . '" '.
            'height="' . $config['VIDEO_HEIGHT'] . '"> '.
            '<param name="src" value="'. 
            $config["URLFILESDIR"] . $filename .'" /> '.
            '<param name="autoplay" value="no" /> '.
              '<param name="controller" value="true" /> '.
             '<embed '.
             'src="'. $config["URLFILESDIR"] . $filename .'" '.
             'width="' . $config['VIDEO_WIDTH'] . '" '.
             'height="' . $config['VIDEO_HEIGHT'] . '"'.
             'autoplay="no" '.
             'controller="true" '.
             'type="video/quicktime" '.
             'pluginspage="http://www.apple.com/quicktime/download/" '.
             'width="' . $config['PLAYER_WIDTH'] . '" '.
             'height="' . $config['PLAYER_HEIGHT'] . '">'.
             '</embed> '.
             '</object>';
    }
    return($config);
}
/**
  * This function handles building up the configuration array for the program
  * @return array
  */
function GetConfig() {
  $config = GetDBConfig();
  //These should only be modified if you are testing
  $config["DELETE_MAIL_AFTER_PROCESSING"] = true;
  $config["POST_TO_DB"] = true;
  $config["TEST_EMAIL"] = false;
  $config["TEST_EMAIL_ACCOUNT"] = "blogtest";
  $config["TEST_EMAIL_PASSWORD"] = "yourpassword";
  if (file_exists(POSTIE_ROOT . '/postie_test_variables.php')) { 
    include(POSTIE_ROOT . '/postie_test_variables.php');
  }
  //include(POSTIE_ROOT . "/../postie-test.php");
  // These are computed
  $config["TIME_OFFSET"] = get_option('gmt_offset');
  $config["POSTIE_ROOT"] = POSTIE_ROOT;
  for ($i = 0; $i < count($config["AUTHORIZED_ADDRESSES"]); $i++) {
    $config["AUTHORIZED_ADDRESSES"][$i] = strtolower($config["AUTHORIZED_ADDRESSES"][$i]);
  }
  return $config;
}
/**
  * Converts from one directory structure to url
  * @return string
  */
function ConvertFilePathToUrl($path) {
    return(str_replace(DIRECTORY_SEPARATOR , "/", $path));
}
/**
  * Returns a list of config keys that should be arrays
  *@return array
  */
function GetListOfArrayConfig() {
    return(array("SUPPORTED_FILE_TYPES","AUTHORIZED_ADDRESSES","SIG_PATTERN_LIST","BANNED_FILES_LIST"));
}
/**
  * Detects if they can do IMAP
  * @return boolean
  */
function HasIMAPSupport($display = true) {
  $function_list = array("imap_open",
                         "imap_delete",
                         "imap_expunge",
                         "imap_body",
                         "imap_fetchheader");
  return(HasFunctions($function_list,$display));
}
function HasIconvInstalled($display = true) {
  $function_list = array("iconv");
  return(HasFunctions($function_list,$display));
}
/**
  * Handles verifing that a list of functions exists
  * @return boolean
  * @param array
  */
function HasFunctions($function_list,$display = true) {
  foreach ($function_list as $function) {
    if (!function_exists($function)) {
      if ($display) {
        print("<p>Missing $function");
      }
      return(false);
    }
  }
  return(true);
}
/**
  * This filter makes it easy to change the html from showing the thumbnail to the actual picture
  */
function filter_postie_thumbnail_with_full($content) {
   $content = str_replace("thumb.","",$content);
   return($content);
}
/**
  * This function tests to see if postie is its own directory
  */
function TestPostieDirectory() {
  $dir_parts = explode(DIRECTORY_SEPARATOR,dirname(__FILE__)); 
  $last_dir = array_pop($dir_parts);
  if ($last_dir != "postie") {
    return false;
  }
  return true;
}
/**
  *This function looks for markdown which causes problems with postie
  */
function TestForMarkdown() {
    if (in_array("markdown.php",get_option("active_plugins"))) {
        return(true);
    }
    return(false);

}
/**
  * This function handles setting up the basic permissions
  */
function PostieAdminPermissions() {
    global $wp_roles;
    $admin = $wp_roles->get_role("administrator");
    $admin->add_cap("config_postie");
    $admin->add_cap("post_via_postie");

}
function UpdatePostiePermissions($role_access) {
    global $wp_roles;
    PostieAdminPermissions();
    if (!is_array($role_access)) {
        $role_access = array();
    }
    foreach($wp_roles->role_names as $roleId => $name) {
        $role = &$wp_roles->get_role($roleId);
        if ($roleId != "administrator") {
            if ($role_access[$roleId]) {
                $role->add_cap("post_via_postie");
            }
            else {
                $role->remove_cap("post_via_postie");
            }
        }
    }
}
function DebugEmailOutput(&$email,&$mimeDecodedEmail) {
    $config = GetConfig();
    if ($config["TEST_EMAIL"]) {
        $file = fopen("test_emails/" . $mimeDecodedEmail->headers["message-id"].".txt","w");
        fwrite($file, $email);
        fclose($file);
        $file = fopen("test_emails/" . $mimeDecodedEmail->headers["message-id"]."-mime.txt","w");
        fwrite($file, print_r($mimeDecodedEmail,true));
        fclose($file);
    }
}
/** 
  * This function provides a hook to be able to write special parses for provider emails that are difficult to work with 
  * If you want to extend this functionality - write a new function and call it from here
  */
function SpecialMessageParsing(&$content, &$attachments){
  $config = GetConfig();
  if ( preg_match('/You have been sent a message from Vodafone mobile/',$content)) {
    VodafoneHandler($content, $attachments); //Everything for this type of message is handled below
      return;
  }
  if ( $config["MESSAGE_START"] ) {
    StartFilter($content,$config["MESSAGE_START"]);
  }
  if ( $config["MESSAGE_END"] ) {
    EndFilter($content,$config["MESSAGE_END"]);
  }
  if ( $config["DROP_SIGNATURE"] ) { 
    RemoveSignature($content,$config["SIG_PATTERN_LIST"]);
  }
  if  ($config["PREFER_TEXT_TYPE"] == "html"
          && count($attachments["cids"])) {
      ReplaceImageCIDs($content,$attachments);
  }
  if (!$config['CUSTOM_IMAGE_FIELD']) {
    ReplaceImagePlaceHolders($content,$attachments["html"]);
  } else {
    $customImages=array();
    foreach ($attachments["html"] as $value) {
      preg_match('/<img src="([^"]*)"/', $value, $matches);
      array_push($customImages,$matches[1]);
    }

    return($customImages);
  }
  return(NULL);
}
/**
  * Special Vodafone handler - their messages are mostly vendor trash - this strips them down.
  */
function VodafoneHandler(&$content, &$attachments){
    $index = strpos($content,"TEXT:");
    if (strpos !== false) {
        $alt_content = substr($content,$index,strlen($content));
        if (preg_match("/<font face=\"verdana,helvetica,arial\" class=\"standard\" color=\"#999999\"><b>(.*)<\/b>/",$alt_content,$matches)) {
            //The content is now just the text of the message
            $content = $matches[1];
            //Now to clean up the attachments
            $vodafone_images = array("live.gif","smiley.gif","border_left_txt.gif","border_top.gif","border_bot.gif","border_right.gif","banner1.gif","i_text.gif","i_picture.gif",);
            while(list($key,$value) = each($attachments['cids'])) {
                if (!in_array($key, $vodafone_images)) {
                    $content .=  "<br/>".$attachments['html'][$attachments['cids'][$key][1]] ;
                }
            }
        }
    }

}
?>
