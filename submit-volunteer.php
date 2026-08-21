<?php

/*
|--------------------------------------------------------------------------
| Volunteer Form - Direct SMTP Relay Test
|--------------------------------------------------------------------------
|
| Sends volunteer submissions directly through:
|
| relay-hosting.secureserver.net
| Port 25
| No authentication
| No SSL/TLS
|
*/


ini_set('display_errors', '0');

header('Content-Type: application/json; charset=UTF-8');



// ============================================================
// RESPONSE HELPER
// ============================================================

function respond($success, $message, $status = 200)
{
    http_response_code($status);

    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);

    exit;
}



// ============================================================
// ONLY ACCEPT POST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    respond(
        false,
        'Invalid request.',
        405
    );
}



// ============================================================
// HONEYPOT SPAM CHECK
// ============================================================

$website = trim($_POST['website'] ?? '');

if ($website !== '') {

    /*
      Pretend submission worked so
      automated bots do not learn that
      they were blocked.
    */

    respond(
        true,
        'Thank you for volunteering!'
    );
}



// ============================================================
// COLLECT FORM DATA
// ============================================================

$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');



// ============================================================
// REQUIRED FIELD VALIDATION
// ============================================================

if (
    $firstName === '' ||
    $lastName === '' ||
    $email === ''
) {

    respond(
        false,
        'Please complete your name and email address.',
        400
    );
}



// ============================================================
// LENGTH VALIDATION
// ============================================================

if (
    strlen($firstName) > 80 ||
    strlen($lastName) > 80 ||
    strlen($email) > 150 ||
    strlen($phone) > 40
) {

    respond(
        false,
        'One or more fields are too long.',
        400
    );
}



// ============================================================
// EMAIL VALIDATION
// ============================================================

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    respond(
        false,
        'Please enter a valid email address.',
        400
    );
}



// ============================================================
// REMOVE CR/LF TO PREVENT HEADER INJECTION
// ============================================================

$firstName = str_replace(
    ["\r", "\n"],
    '',
    $firstName
);

$lastName = str_replace(
    ["\r", "\n"],
    '',
    $lastName
);

$email = str_replace(
    ["\r", "\n"],
    '',
    $email
);

$phone = str_replace(
    ["\r", "\n"],
    '',
    $phone
);



// ============================================================
// EMAIL SETTINGS
// ============================================================

$smtpHost = 'relay-hosting.secureserver.net';

$smtpPort = 25;

$fromEmail = 'info@vote4arslan.ca';

$toEmail = 'info@vote4arslan.ca';



// ============================================================
// SUBJECT
// ============================================================

$subject =
    'New Volunteer Signup - ' .
    $firstName .
    ' ' .
    $lastName;



// ============================================================
// MESSAGE BODY
// ============================================================

$body = '';

$body .= "NEW VOLUNTEER SIGNUP\r\n";

$body .= "============================\r\n\r\n";

$body .=
    "A new volunteer has signed up through vote4arslan.ca.\r\n\r\n";


$body .= "VOLUNTEER INFORMATION\r\n";

$body .= "----------------------------\r\n";


$body .=
    "First Name: " .
    $firstName .
    "\r\n";


$body .=
    "Last Name: " .
    $lastName .
    "\r\n";


$body .=
    "Email: " .
    $email .
    "\r\n";


$body .=
    "Phone: " .
    (
        $phone !== ''
        ? $phone
        : 'Not provided'
    ) .
    "\r\n\r\n";


$body .=
    "----------------------------\r\n";


$body .=
    "Submitted through the volunteer form at vote4arslan.ca.\r\n";



// ============================================================
// SMTP HELPER: READ SERVER RESPONSE
// ============================================================

function smtpRead($socket)
{
    $response = '';

    while (($line = fgets($socket, 515)) !== false) {

        $response .= $line;

        /*
          SMTP multiline responses use:

          250-First line
          250-Second line
          250 Final line

          When character 4 is a space,
          the response is complete.
        */

        if (
            strlen($line) >= 4 &&
            $line[3] === ' '
        ) {
            break;
        }
    }

    return $response;
}



// ============================================================
// SMTP HELPER: SEND COMMAND
// ============================================================

function smtpCommand(
    $socket,
    $command,
    $expectedCodes
) {

    fwrite(
        $socket,
        $command . "\r\n"
    );


    $response = smtpRead($socket);


    $code = intval(
        substr(
            trim($response),
            0,
            3
        )
    );


    if (!in_array($code, $expectedCodes, true)) {

        throw new Exception(
            'SMTP error: ' .
            trim($response)
        );
    }


    return $response;
}



// ============================================================
// CONNECT TO SMTP RELAY
// ============================================================

$errno = 0;

$errstr = '';


$socket = @fsockopen(
    $smtpHost,
    $smtpPort,
    $errno,
    $errstr,
    15
);


if (!$socket) {

    respond(
        false,
        'Unable to connect to the email server. Please try again later.',
        500
    );
}



// ============================================================
// SET CONNECTION TIMEOUT
// ============================================================

stream_set_timeout(
    $socket,
    15
);



// ============================================================
// SMTP CONVERSATION
// ============================================================

try {


    // --------------------------------------------------------
    // SERVER GREETING
    // --------------------------------------------------------

    $greeting = smtpRead($socket);

    $greetingCode = intval(
        substr(
            trim($greeting),
            0,
            3
        )
    );


    if ($greetingCode !== 220) {

        throw new Exception(
            'SMTP connection rejected: ' .
            trim($greeting)
        );
    }



    // --------------------------------------------------------
    // EHLO
    // --------------------------------------------------------

    smtpCommand(
        $socket,
        'EHLO vote4arslan.ca',
        [250]
    );



    // --------------------------------------------------------
    // ENVELOPE SENDER
    // --------------------------------------------------------

    smtpCommand(
        $socket,
        'MAIL FROM:<' . $fromEmail . '>',
        [250]
    );



    // --------------------------------------------------------
    // RECIPIENT
    // --------------------------------------------------------

    smtpCommand(
        $socket,
        'RCPT TO:<' . $toEmail . '>',
        [250, 251]
    );



    // --------------------------------------------------------
    // BEGIN EMAIL DATA
    // --------------------------------------------------------

    smtpCommand(
        $socket,
        'DATA',
        [354]
    );



    // ========================================================
    // EMAIL HEADERS
    // ========================================================

    $headers = '';

    $headers .=
        'From: Vote4Arslan Website <' .
        $fromEmail .
        ">\r\n";


    $headers .=
        'To: ' .
        $toEmail .
        "\r\n";


    $headers .=
        'Reply-To: ' .
        $firstName .
        ' ' .
        $lastName .
        ' <' .
        $email .
        ">\r\n";


    $headers .=
        'Subject: ' .
        $subject .
        "\r\n";


    $headers .=
        'Date: ' .
        date(DATE_RFC2822) .
        "\r\n";


    $headers .=
        'Message-ID: <' .
        uniqid('', true) .
        '@vote4arslan.ca>' .
        "\r\n";


    $headers .=
        "MIME-Version: 1.0\r\n";


    $headers .=
        "Content-Type: text/plain; charset=UTF-8\r\n";


    $headers .=
        "Content-Transfer-Encoding: 8bit\r\n";


    /*
      Blank line separates email headers
      from the body.
    */

    $message =
        $headers .
        "\r\n" .
        $body;



    // ========================================================
    // SMTP DOT-STUFFING
    // ========================================================

    /*
      SMTP considers a line containing only
      "." to be the end of the email.

      Dot-stuffing protects body lines that
      begin with a period.
    */

    $message = preg_replace(
        '/^\./m',
        '..',
        $message
    );



    // ========================================================
    // SEND MESSAGE
    // ========================================================

    fwrite(
        $socket,
        $message .
        "\r\n.\r\n"
    );


    $sendResponse =
        smtpRead($socket);


    $sendCode = intval(
        substr(
            trim($sendResponse),
            0,
            3
        )
    );


    if (
        $sendCode !== 250 &&
        $sendCode !== 251
    ) {

        throw new Exception(
            'SMTP delivery failed: ' .
            trim($sendResponse)
        );
    }



    // --------------------------------------------------------
    // QUIT
    // --------------------------------------------------------

    fwrite(
        $socket,
        "QUIT\r\n"
    );


    fclose($socket);



    // ========================================================
    // SUCCESS
    // ========================================================

    respond(
        true,
        'Thank you for volunteering! We’ll be in touch soon.'
    );


} catch (Exception $exception) {


    if (is_resource($socket)) {

        fwrite(
            $socket,
            "QUIT\r\n"
        );

        fclose($socket);
    }


    /*
      We deliberately don't send the raw
      SMTP error to the visitor.

      Server details should not be exposed
      publicly.
    */

    error_log(
        'Volunteer SMTP error: ' .
        $exception->getMessage()
    );


    respond(
        false,
        'We could not send your submission. Please try again or email info@vote4arslan.ca.',
        500
    );

}
?>