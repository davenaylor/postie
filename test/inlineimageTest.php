<?php

require '../mimedecode.php';

class postiefunctions2Test extends PHPUnit_Framework_TestCase {

    function testBase64Subject() {
        $message = file_get_contents("data/b-encoded-subject.var");
        $email = unserialize($message);
        $decoded = DecodeMIMEMail($email, true);
        $this->assertEquals("テストですよ", $decoded->headers['subject']);
    }

    function testQuotedPrintableSubject() {
        $message = file_get_contents("data/q-encoded-subject.var");
        $email = unserialize($message);
        $decoded = DecodeMIMEMail($email, true);
        $this->assertEquals("Pár minut před desátou a jsem v práci první", $decoded->headers['subject']);
    }

    function testInlineImage() {

        $message = file_get_contents("data/inline.var");
        $email = unserialize($message);
        $mimeDecodedEmail = DecodeMIMEMail($email);

        $partcnt = count($mimeDecodedEmail->parts);
        $this->assertEquals(2, $partcnt);

        $config = config_GetDefaults();
        $config['prefer_text_type'] = 'html';
        extract($config);

        //-- start test
        $attachments = array(
            "html" => array(), //holds the html for each image
            "cids" => array(), //holds the cids for HTML email
            "image_files" => array() //holds the files for each image
        );

        filter_PreferedText($mimeDecodedEmail, "plain");
        $content = GetContent($mimeDecodedEmail, $attachments, 1, "wayne", $config);
        $subject = GetSubject($mimeDecodedEmail, $content, $config);
        $customImages = SpecialMessageParsing($content, $attachments, $config);
        $post_excerpt = tag_Excerpt($content, $filternewlines, $convertnewline);
        $postAuthorDetails = getPostAuthorDetails($subject, $content, $mimeDecodedEmail);
        $message_date = NULL;
        $message_date = tag_Date($content, $message_date);
        list($post_date, $post_date_gmt, $delay) = DeterminePostDate($content, $message_date, $time_offset);
        filter_ubb2HTML($content);
        if ($converturls) {
            $content = filter_Videos($content, $shortcode); //videos first so linkify doesn't mess with them
            $content = filter_linkify($content);
        }
        $post_categories = tag_categories($subject, $default_post_category);
        $post_tags = tag_Tags($content, $default_post_tags);
        $comment_status = tag_AllowCommentsOnPost($content);
        if ($filternewlines) {
            $content = filter_newlines($content, $convertnewline);
        }
        $post_type = tag_PostType($subject);

        $this->assertEquals('test<div><br></div><div><img src="http://example.net/wp-content/uploads/filename" alt="Inline image 1"><br></div><div><br></div><div>test</div>     ', $content);
    }

    function testMultipleImagesWithSig() {

        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );


        $message = file_get_contents("data/multiple images with signature.var");
        $email = unserialize($message);
        $decoded = DecodeMIMEMail($email);

        $partcnt = count($decoded->parts);
        $this->assertEquals(3, $partcnt);

        FilterTextParts($decoded, "plain");

        $attachments = array(
            "html" => array(), //holds the html for each image
            "cids" => array(), //holds the cids for HTML email
            "image_files" => array() //holds the files for each image
        );

        $config = config_GetDefaults();
        $content = GetContent($decoded, $attachments, 1, "wayne", $config);
    }

    function testSig() {

        $message = file_get_contents("data/signature.var");
        $email = unserialize($message);
        $decoded = DecodeMIMEMail($email);

        $partcnt = count($decoded->parts);
        $this->assertEquals(2, $partcnt);

        filter_PreferedText($decoded, "plain");

        $attachments = array(
            "html" => array(), //holds the html for each image
            "cids" => array(), //holds the cids for HTML email
            "image_files" => array() //holds the files for each image
        );

        $config = config_GetDefaults();
        $filternewlines = $config['filternewlines'];
        $convertnewline = $config['convertnewline'];

        $content = GetContent($decoded, $attachments, 1, "wayne", $config);

        $subject = GetSubject($decoded, $content, $config);
        $this->assertEquals('signature', $subject);

        $customImages = SpecialMessageParsing($content, $attachments, $config);
        $this->assertEquals(null, $customImages);
        $this->assertEquals("test content\n\n", $content);

        $post_excerpt = tag_Excerpt($content, $filternewlines, $convertnewline);

        $postAuthorDetails = getPostAuthorDetails($subject, $content, $decoded);
    }

    function testQuotedPrintable() {
        $str = quoted_printable_decode("ABC=C3=C4=CEABC=");
        $str = iconv('ISO-8859-7', 'UTF-8', $str);
        $this->assertEquals("ABCΓΔΞABC", $str);

        $str = quoted_printable_decode('<span style=3D"font-family:arial,sans-serif;font-size:13px">ABC=C3=C4=CEABC=</span><br>');
        $str = iconv('ISO-8859-7', 'UTF-8', $str);
        $this->assertEquals('<span style="font-family:arial,sans-serif;font-size:13px">ABCΓΔΞABC=</span><br>', $str);
    }

    function testBase64() {
        $str = base64_decode("QUJDw8TOQUJDCg==");
        $str = iconv('ISO-8859-7', 'UTF-8', $str);
        $this->assertEquals("ABCΓΔΞABC\n", $str);
    }

    function testHandleMessageEncoding() {
        $e = HandleMessageEncoding('quoted-printable', 'iso-8859-7', '<span style=3D"font-family:arial,sans-serif;font-size:13px">ABC=C3=C4=CEABC=</span><br>');
        $this->assertEquals('<span style="font-family:arial,sans-serif;font-size:13px">ABCΓΔΞABC=</span><br>', $e);
    }

    function testGreek() {
        $config = config_GetDefaults();
        $message = file_get_contents("data/greek.var");
        $email = unserialize($message);

        $decoded = DecodeMIMEMail($email);
        print_r($decoded);

        filter_PreferedText($decoded, 'html');
        $attachments = array(
            "html" => array(), //holds the html for each image
            "cids" => array(), //holds the cids for HTML email
            "image_files" => array() //holds the files for each image
        );
        $content = GetContent($decoded, $attachments, 1, 'wayne@devzing.com', $config);
        print_r($content);
    }

    public function testReplaceImagePlaceHolders() {
        $c = "";
        $config = config_GetDefaults();
        $config['allow_html_in_body']=true;
        
        $attachements = array("image.jpg" => '<img title="{CAPTION}" />');

        filter_ReplaceImagePlaceHolders($c, array(), $config);
        $this->assertEquals("", $c);

        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals('<img title="" />', $c);

        $c = "#img1#";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals('<img title="" />', $c);

        $c = "test #img1# test";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals('test <img title="" /> test', $c);

        $c = "test #img1 caption='1'# test";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals('test <img title="1" /> test', $c);

        $c = "test #img1 caption=# test";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals('test <img title="" /> test', $c);
        
        $c = "test #img1 caption=1# test";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals('test <img title="1" /> test', $c);
        
        $c = "test #img1 caption='! @ % ^ & * ( ) ~ \"Test\"'# test";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals('test <img title="! @ % ^ &amp; * ( ) ~ &quot;Test&quot;" /> test', $c);

        $c = "test <div>#img1 caption=&#39;! @ % ^ &amp; * ( ) ~ &quot;Test&quot;&#39;#</div> test";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals('test <div><img title="&amp;" />39;! @ % ^ &amp; * ( ) ~ &quot;Test&quot;&#39;#</div> test', $c);

        $c = "test #img1 caption=\"I'd like some cheese.\"# test";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals('test <img title="I&#039;d like some cheese." /> test', $c);

        $c = "test #img1 caption=\"Eiskernbrecher mögens laut\"# test";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals('test <img title="Eiskernbrecher mögens laut" /> test', $c);

        $c = "test #img1 caption='[image-caption]'# test";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals('test <img title="[image-caption]" /> test', $c);

        $c = "test #img1 caption='1'# test #img2 caption='2'#";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals('test <img title="1" /> test #img2 caption=\'2\'#', $c);

        $attachements = array("image1.jpg" => 'template with {CAPTION}', "image2.jpg" => 'template with {CAPTION}');
        $c = "test #img1 caption='1'# test #img2 caption='2'#";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals("test template with 1 test template with 2", $c);

        $config['auto_gallery'] = true;
        $config['images_append'] = false;
        $c = "test";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals("[gallery]\ntest", $c);

        $config['images_append'] = true;
        $c = "test";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals("test[gallery]", $c);

        $config['images_append'] = true;
        $c = "test";
        filter_ReplaceImagePlaceHolders($c, $attachements, $config);
        $this->assertEquals("test[gallery]", $c);

        $c = "test";
        filter_ReplaceImagePlaceHolders($c, array(), $config);
        $this->assertEquals("test", $c);
    }

}

?>
