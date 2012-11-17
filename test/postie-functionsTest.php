<?php

require 'wpstub.php';
require'../postie-functions.php';

class postiefunctionsTest extends PHPUnit_Framework_TestCase {

    public function testAllowCommentsOnPost() {
        $original_content = "test content, no comment control";
        $modified_content = "test content, no comment control";
        $allow = AllowCommentsOnPost($modified_content);
        $this->assertEquals("open", $allow);
        $this->assertEquals($original_content, $modified_content);

        $original_content = "test content, comment control closed ";
        $modified_content = "test content, comment control closed comments:0";
        $allow = AllowCommentsOnPost($modified_content);
        $this->assertEquals("closed", $allow);
        $this->assertEquals($original_content, $modified_content);

        $original_content = "test content, comment control open ";
        $modified_content = "test content, comment control open comments:1";
        $allow = AllowCommentsOnPost($modified_content);
        $this->assertEquals("open", $allow);
        $this->assertEquals($original_content, $modified_content);

        $original_content = "test content, comment control registered only ";
        $modified_content = "test content, comment control registered only comments:2";
        $allow = AllowCommentsOnPost($modified_content);
        $this->assertEquals("registered_only", $allow);
        $this->assertEquals($original_content, $modified_content);
    }

    public function testBannedFileName() {
        $this->assertFalse(BannedFileName("", null));
        $this->assertFalse(BannedFileName("", ""));
        $this->assertFalse(BannedFileName("", array()));
        $this->assertFalse(BannedFileName("test", array()));
        $this->assertTrue(BannedFileName("test", array("test")));
        $this->assertFalse(BannedFileName("test", array("test1")));
        $this->assertTrue(BannedFileName("test.exe", array("*.exe")));
        $this->assertFalse(BannedFileName("test.pdf", array("*.exe")));
        $this->assertFalse(BannedFileName("test.pdf", array("*.exe", "*.js", "*.cmd")));
        $this->assertFalse(BannedFileName("test.cmd.pdf", array("*.exe", "*.js", "*.cmd")));
        $this->assertTrue(BannedFileName("test test.exe", array("*.exe")));
    }

    public function testCheckEmailAddress() {
        $this->assertFalse(CheckEmailAddress(null, null));
        $this->assertFalse(CheckEmailAddress(null, array()));
        $this->assertFalse(CheckEmailAddress("", array()));
        $this->assertFalse(CheckEmailAddress("", array("")));
        $this->assertFalse(CheckEmailAddress("bob", array("jane")));
        $this->assertTrue(CheckEmailAddress("bob", array("bob")));
        $this->assertTrue(CheckEmailAddress("bob", array("BoB")));
    }

    public function testConvertUTF8ToISO_8859_1() {
        $this->assertEquals("test", ConvertUTF8ToISO_8859_1("random", "stuff", "test"));
        $this->assertEquals("Phasa Thai", ConvertUTF8ToISO_8859_1('quoted-printable', 'iso-8859-1', "Phasa Thai"));
        $this->assertEquals("??????? Phasa Thai", ConvertUTF8ToISO_8859_1('quoted-printable', 'utf-8', "ภาษาไทย Phasa Thai"));
        $this->assertEquals("??????? Phasa Thai", ConvertUTF8ToISO_8859_1('base64', 'utf-8', "ภาษาไทย Phasa Thai"));
        $this->assertEquals("ภาษาไทย Phasa Thai", ConvertUTF8ToISO_8859_1('something', 'utf-8', "ภาษาไทย Phasa Thai"));
        $this->assertEquals("ภาษาไทย Phasa Thai", ConvertUTF8ToISO_8859_1('base64', 'iso-8859-1', "ภาษาไทย Phasa Thai"));
    }

    public function testConvertToUTF_8() {
        $this->assertEquals("に投稿できる", ConvertToUTF_8('iso-2022-jp', iconv("UTF-8", "ISO-2022-JP", "に投稿できる")));
        $this->assertEquals("Код Обмена Информацией, 8 бит", ConvertToUTF_8('koi8-r', iconv("UTF-8", "koi8-r","Код Обмена Информацией, 8 бит")));
    }
    
    public function testDeterminePostDate(){
        $content="test";
        $r=DeterminePostDate($content);
        $this->assertEquals(0, $r[3]);
    }
}

?>
