<?php

require '../mimedecode.php';

class postiefunctions2Test extends PHPUnit_Framework_TestCase {

    function standardConfig() {
        return array(
            'prefer_text_type' => 'plain',
            'allow_html_in_body' => false,
            'banned_files_list' => array(),
            'imagetemplate' => '<a href="{FILELINK}">{FILENAME}</a>',
            'drop_signature' => true,
            'message_encoding' => 'UTF-8',
            'message_dequote' => true,
            'allow_html_in_subject' => true,
            'message_start' => ':start',
            'message_end' => ':end',
            'sig_pattern_list' => array('--', '- --'),
            'custom_image_field' => false,
            'start_image_count_at_zero' => false,
            'images_append' => false,
            'filternewlines' => true,
            'convertnewline' => false,
            'auto_gallery' => false,
            'image_placeholder' => '#img%#'
        );
    }

    function testInlineImage() {

        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );

        $message = file_get_contents("data/inline.var");
        $email = unserialize($message);
        $decoded = DecodeMIMEMail($email);

        $partcnt = count($decoded->parts);
        $this->assertEquals(2, $partcnt);

        FilterTextParts($decoded, "plain");

        $attachments = array(
            "html" => array(), //holds the html for each image
            "cids" => array(), //holds the cids for HTML email
            "image_files" => array() //holds the files for each image
        );

        $config = $this->standardConfig();
        $content = GetContent($decoded, $attachments, 1, "wayne", $config);
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

        $config = $this->standardConfig();
        $content = GetContent($decoded, $attachments, 1, "wayne", $config);
    }

    function testSig() {

        $message = file_get_contents("data/signature.var");
        $email = unserialize($message);
        $decoded = DecodeMIMEMail($email);

        $partcnt = count($decoded->parts);
        $this->assertEquals(2, $partcnt);

        FilterTextParts($decoded, "plain");

        $attachments = array(
            "html" => array(), //holds the html for each image
            "cids" => array(), //holds the cids for HTML email
            "image_files" => array() //holds the files for each image
        );

        $config = $this->standardConfig();
        $filternewlines = $config['filternewlines'];
        $convertnewline = $config['convertnewline'];

        $content = GetContent($decoded, $attachments, 1, "wayne", $config);

        $subject = GetSubject($decoded, $content, $config);
        $this->assertEquals('signature', $subject);

        $customImages = SpecialMessageParsing($content, $attachments, $config);
        $this->assertEquals(null, $customImages);
        $this->assertEquals("test content\n\n", $content);

        $post_excerpt = GetPostExcerpt($content, $filternewlines, $convertnewline);

        $postAuthorDetails = getPostAuthorDetails($subject, $content, $decoded);
    }

    function testGreek() {

        $message = file_get_contents("data/greek.var");
        $email = unserialize($message);
        $decoded = DecodeMIMEMail($email);
    }

    public function testReplaceImagePlaceHolders() {
        $c = "";
        $config = $this->standardConfig();
        $attachements = array("image.jpg" => 'template with {CAPTION}');

         ReplaceImagePlaceHolders(&$c, array(), $config);
        $this->assertEquals("", $c);
        
        ReplaceImagePlaceHolders(&$c, $attachements, $config);
        $this->assertEquals("template with ", $c);

        $c = "#img1#";
        ReplaceImagePlaceHolders(&$c, $attachements, $config);
        $this->assertEquals("template with ", $c);
        
        $c = "test #img1# test";
        ReplaceImagePlaceHolders(&$c, $attachements, $config);
        $this->assertEquals("test template with  test", $c);
        
        $c = "test #img1 caption='1'# test";
        ReplaceImagePlaceHolders(&$c, $attachements, $config);
        $this->assertEquals("test template with 1 test", $c);
        
         $c = "test #img1 caption='1'# test #img2 caption='2'#";
        ReplaceImagePlaceHolders(&$c, $attachements, $config);
        $this->assertEquals("test template with 1 test #img2 caption='2'#", $c);
        
        $attachements = array("image1.jpg" => 'template with {CAPTION}', "image2.jpg" => 'template with {CAPTION}');
        $c = "test #img1 caption='1'# test #img2 caption='2'#";
        ReplaceImagePlaceHolders(&$c, $attachements, $config);
        $this->assertEquals("test template with 1 test template with 2", $c);
        
        $config['auto_gallery']=true;
        $c = "test";
        ReplaceImagePlaceHolders(&$c, $attachements, $config);
        $this->assertEquals("[gallery]\ntest", $c);
        
        $config['images_append']=true;
        $c = "test";
        ReplaceImagePlaceHolders(&$c, $attachements, $config);
        $this->assertEquals("test[gallery]", $c);
        
        $c = "test";
        ReplaceImagePlaceHolders(&$c, array(), $config);
        $this->assertEquals("test", $c);
    }

}

?>
