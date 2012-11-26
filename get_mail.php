<?php

include_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . "wp-config.php");
require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mimedecode.php');
require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'postie-functions.php');
require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'simple_html_dom.php');

if (!ini_get('safe_mode')) {
    $original_mem_limit = ini_get('memory_limit');
    ini_set('memory_limit', -1);
    ini_set('max_execution_time', 300);
}

EchoInfo("Starting mail fetch");
EchoInfo("Time: " . date('Y-m-d H:i:s', time()) . " GMT");
include('Revision');

$test_email = null;
$config = get_option('postie-settings');
extract($config);
$emails = FetchMail($mail_server, $mail_server_port, $mail_userid, $mail_password, $input_protocol, $time_offset, $test_email, $delete_mail_after_processing);
$message = 'Done.';

EchoInfo(sprintf(__("There are %d messages to process", "postie"), count($emails)));

//loop through messages
foreach ($emails as $email) {
    if (function_exists('memory_get_usage'))
        EchoInfo(__("memory at start of e-mail processing:") . memory_get_usage());

    //sanity check to see if there is any info in the message
    if ($email == NULL) {
        $message = __('Dang, message is empty!', 'postie');
        continue;
    } else if ($email == 'already read') {
        $message = __("There does not seem to be any new mail.", 'postie');
        continue;
    }
    // check for XSS attacks - we disallow any javascript, meta, onload, or base64
    if (preg_match("@((%3C|<)/?script|<meta|document\.|\.cookie|\.createElement|onload\s*=|(eval|base64)\()@is", $email)) {
        EchoInfo("possible XSS attack - ignoring email");
        continue;
    }

    $mimeDecodedEmail = DecodeMIMEMail($email, true);

    //Check poster to see if a valid person
    $poster = ValidatePoster($mimeDecodedEmail, $config);
    if (!empty($poster)) {
        if (IsDebugMode()) {
            DebugEmailOutput($email, $mimeDecodedEmail);
        }
        PostEmail($poster, $mimeDecodedEmail, $config);
    } else {
        EchoInfo("Ignoring email - not authorized.");
    }
    if (function_exists('memory_get_usage'))
        EchoInfo("memory at end of e-mail processing:" . memory_get_usage());
} // end looping over messages
EchoInfo($message);

if (!ini_get('safe_mode')) {
    ini_set('memory_limit', $original_mem_limit);
}
?>
