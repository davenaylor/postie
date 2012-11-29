<?php

require '../mimedecode.php';

class postiefunctions2Test extends PHPUnit_Framework_TestCase {

    function testInlineImage() {

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

        $config = array(
            'prefer_text_type' => 'plain',
            'allow_html_in_body' => false,
            'banned_files_list' => array(),
            'imagetemplate' => '<a href="{FILELINK}">{FILENAME}</a>'
        );
        $content = GetContent($decoded, $attachments, 1, "wayne", $config);
    }

}

?>
