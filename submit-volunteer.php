<?php

/*
|--------------------------------------------------------------------------
| Volunteer Form Handler
|--------------------------------------------------------------------------
|
| Receives volunteer information from contact.html
| and sends it to info@vote4arslan.ca.
|
*/


// Do not expose PHP errors to visitors.

ini_set('display_errors', '0');


// Always return JSON.

header(
    'Content-Type: application/json; charset=UTF-8'
);



// ============================================================
// ONLY ACCEPT POST REQUESTS
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);

    exit;
}



// ============================================================
// SPAM PROTECTION
// ============================================================

/*
|--------------------------------------------------------------------------
| Honeypot
|--------------------------------------------------------------------------
|
| Normal visitors cannot see this field.
| Many bots automatically fill every field.
|
*/

$website =
    trim($_POST['website'] ?? '');


if ($website !== '') {

    /*
      Pretend the form worked.

      This prevents bots from learning
      that they triggered the filter.
    */

    echo json_encode([
        'success' => true
    ]);

    exit;
}



// ============================================================
// COLLECT FORM DATA
// ============================================================

$firstName =
    trim($_POST['firstName'] ?? '');

$lastName =
    trim($_POST['lastName'] ?? '');

$email =
    trim($_POST['email'] ?? '');

$phone =
    trim($_POST['phone'] ?? '');



// ============================================================
// REQUIRED FIELDS
// ============================================================

if (
    $firstName === '' ||
    $lastName === '' ||
    $email === ''
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' =>
            'Please complete your name and email address.'
    ]);

    exit;
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

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' =>
            'One or more fields are too long.'
    ]);

    exit;
}



// ============================================================
// EMAIL VALIDATION
// ============================================================

if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' =>
            'Please enter a valid email address.'
    ]);

    exit;
}



// ============================================================
// PREVENT EMAIL HEADER INJECTION
// ============================================================

$firstName =
    str_replace(
        ["\r", "\n"],
        '',
        $firstName
    );


$lastName =
    str_replace(
        ["\r", "\n"],
        '',
        $lastName
    );


$email =
    str_replace(
        ["\r", "\n"],
        '',
        $email
    );


$phone =
    str_replace(
        ["\r", "\n"],
        '',
        $phone
    );



// ============================================================
// DESTINATION EMAIL
// ============================================================

$to =
    'info@vote4arslan.ca';



// ============================================================
// SUBJECT
// ============================================================

$subject =
    'New Volunteer Signup - ' .
    $firstName .
    ' ' .
    $lastName;



// ============================================================
// EMAIL MESSAGE
// ============================================================

$body =
    "NEW VOLUNTEER SIGNUP\n";

$body .=
    "============================\n\n";


$body .=
    "A new volunteer has signed up through vote4arslan.ca.\n\n";


$body .=
    "VOLUNTEER INFORMATION\n";

$body .=
    "----------------------------\n";


$body .=
    "First Name: " .
    $firstName .
    "\n";


$body .=
    "Last Name: " .
    $lastName .
    "\n";


$body .=
    "Email: " .
    $email .
    "\n";


$body .=
    "Phone: " .
    (
        $phone !== ''
        ? $phone
        : 'Not provided'
    ) .
    "\n\n";


$body .=
    "----------------------------\n";

$body .=
    "Submitted through the volunteer form at vote4arslan.ca.\n";



// ============================================================
// EMAIL HEADERS
// ============================================================

/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
|
| The From address uses vote4arslan.ca.
|
| The volunteer's email is placed in Reply-To.
|
| That means when the campaign clicks Reply,
| the response goes directly to the volunteer.
|
*/

$headers = [];


$headers[] =
    'From: Vote4Arslan Website <info@vote4arslan.ca>';


$headers[] =
    'Reply-To: ' .
    $firstName .
    ' ' .
    $lastName .
    ' <' .
    $email .
    '>';


$headers[] =
    'MIME-Version: 1.0';


$headers[] =
    'Content-Type: text/plain; charset=UTF-8';



// ============================================================
// SEND EMAIL
// ============================================================

$sent =
    mail(
        $to,
        $subject,
        $body,
        implode(
            "\r\n",
            $headers
        )
    );



// ============================================================
// RESPONSE
// ============================================================

if ($sent) {

    echo json_encode([
        'success' => true,
        'message' =>
            'Thank you for volunteering!'
    ]);

    exit;

}



// ============================================================
// EMAIL FAILED
// ============================================================

http_response_code(500);


echo json_encode([
    'success' => false,
    'message' =>
        'We could not send your submission. Please try again or email info@vote4arslan.ca.'
]);


exit;
?>