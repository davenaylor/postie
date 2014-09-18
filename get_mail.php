<?php
http_response_code(403);
?>
<html>
    <head>
        <title>Postie - Error</title>
    </head>
    <body>
        This URL is no longer supported for forcing an email check please update your cron job to 
        access http://&lt;mysite&gt;/?postie=get-mail
    </body>
</html>