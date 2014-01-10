<?php
/*
* Contact Form Class
*/

require_once 'google/appengine/api/mail/Message.php';

use google\appengine\api\mail\Message;

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');

$admin_email = 'hello@iodie.com'; // Your Email
$message_min_length = 5; // Min Message Length


class Contact_Form{
	function __construct($details, $email_admin, $message_min_length){
		$this->details = $details;
		$this->name = stripslashes($details['name']);
		$this->email = trim($details['email']);
		$this->subject = 'Contact from Your Website'; // Subject 
		$this->message = stripslashes($details['message']);
	
		$this->email_admin = $email_admin;
		$this->message_min_length = $message_min_length;
		
		$this->response_status = 1;
		$this->response_html = '';
	}


	private function validateEmail(){
		$regex = '/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i';
	
		if($this->email == '') { 
			return false;
		} else {
			$string = preg_replace($regex, '', $this->email);
		}
	
		return empty($string) ? true : false;
	}


	private function validateFields(){
		// Check name
		if(!$this->name)
		{
			$this->response_html .= '<p>Please enter your name</p>';
			$this->response_status = 0;
		}

		// Check email
		if(!$this->email)
		{
			$this->response_html .= '<p>Please enter an e-mail address</p>';
			$this->response_status = 0;
		}
		
		// Check valid email
		if($this->email && !$this->validateEmail())
		{
			$this->response_html .= '<p>Please enter a valid e-mail address</p>';
			$this->response_status = 0;
		}
		
		// Check message length
		if(!$this->message || strlen($this->message) < $this->message_min_length)
		{
			$this->response_html .= '<p>Please enter your message. It should have at least '.$this->message_min_length.' characters</p>';
			$this->response_status = 0;
		}
	}


	private function sendEmail(){
		$mail_options = [
		    "sender" => $this->email_admin,
		    "to" => $this->email_admin,
		    "subject" => $this->subject . " - " . $this->name . " <" . $this->email . ">",
		    "textBody" => $this->message
		];

		try {
		    $message = new Message($mail_options);
			syslog(LOG_INFO, "Attempting Send " . json_encode($this->details));
		    $message->send();
			
			// Set reply all OK.
			$this->response_status = 1;
			$this->response_html = '<p>Thank You!</p>';
		} catch (Exception $e) {
			try { 
				$mail_options = [
				    "sender" => $this->email_admin,
				    "to" => $this->email_admin,
				    "subject" => "Good news everyone. First Human-Customer contact has FAILED.",
				    "textBody" => "See appengine logs for details\n" . json_encode($_POST) . "\n\n" . json_encode($_SERVER)
				];
				$message = new Message($mail_options);
				$message->send();
			} catch(Exception $e) { };
			
			$this->response_status = 0;
			$this->response_html = '<p>Quirks in the system. Team has been notified. Please use other contact methods until we fix our bugs.</p>';

		}
	}


	function sendRequest(){
		$this->validateFields();
		if($this->response_status)
		{
			$this->sendEmail();
		}

		$response = array();
		$response['status'] = $this->response_status;	
		$response['html'] = $this->response_html;
		
		echo json_encode($response);
	}
}

syslog(LOG_INFO, "Post: " . json_encode($_POST) . "\n\nSERVER: " . json_encode($_SERVER));
$contact_form = new Contact_Form($_POST, $admin_email, $message_min_length);
$contact_form->sendRequest();

?>