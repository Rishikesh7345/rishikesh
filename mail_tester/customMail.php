<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


	       include_once('hello/PHPMailer.php');
		   include_once('hello/SMTP.php');
	       include_once('hello/Exception.php');

			// To User 
// 			$mail = new PHPMailer\PHPMailer\PHPMailer(true);
//             $mail->isSMTP();
// 			$mail->Host = 'smtp.gmail.com'; 
// 			$mail->SMTPAuth = true;
// 			$mail->Username = 'husnpreet@proedworld.com';
// 			$mail->Password = 'Japna@1612';
			
// 			$mail->Host = 'smtp.gmail.com'; 
// 			$mail->SMTPAuth = true;
// 			$mail->Username = 'husnpreet@proedworld.com';
// 			$mail->Password = 'Japna@1612';
			
// 				$mail->Host = 'smtp.gmail.com'; 
// 			$mail->SMTPAuth = true;
// 			$mail->Username = 'husnpreet@proedworld.com';
// 			$mail->Password = 'czgtirkrvlbmxnyx';
			
// 			$mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
// 			$mail->Port = 587;
// 			$mail->EnableSsl = true;
// 			$mail->setFrom('husnpreet@proedworld.com', 'Assessment Test');
// 			$mail->addAddress('rishikesh@18thdigitech.net');
// 			$mail->addReplyTo('rishikesh@18thdigitech.net', 'Assessment Test');
// // 			//$mail->addAttachment($student_report_pdf);
// 			$mail->isHTML(true);
// 			$mail->Subject = 'Assessment Test Report of';
// 			$mail->Body = 'Please find your Assessment Test Report generated';
// 			$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
// 			$mail->send();

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
			$mail->Host = 'smtp.gmail.com'; 
			$mail->SMTPAuth = true;
			$mail->Username = 'husnpreet@proedworld.com';
			$mail->Password = 'czgtirkrvlbmxnyx';
			//$mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port = 587;
			$mail->SMTPDebug = 2; // debugging: 1 = errors and messages, 2 = messages only
$mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for Gmail

$mail->SMTPOptions = array(
'ssl' => array(
'verify_peer' => false,
'verify_peer_name' => false,
'allow_self_signed' => true
)
);
$mail->SMTPKeepAlive = true;

			$mail->setFrom('husnpreet@proedworld.com', 'Assessment Test');
			$mail->addAddress('rishikesh@18thdigitech.net');
			//$mail->addReplyTo('rishikesh@18thdigitech.net', 'Assessment Test');
// 			$mail->addAttachment($student_report_pdf);
			$mail->isHTML(true);
			$mail->Subject = 'Assessment Test Report of';
			$mail->Body = 'Please find your Assessment Test Report generated';
// 			$mail->Subject = 'Assessment Test Report of'. $user->display_name;
// 			$mail->Body = 'Please find your Assessment Test Report generated'. $user->display_name;
			$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
			$mail->send();
			
			