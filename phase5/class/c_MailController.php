<?php
include_once(__DIR__.'/../include/conf.php');

class MailController {
	
	
	static function sendMail($email,$message,$subject) {
		MailController::sendMailWithAttachment($email,$message,$subject,"");
	}
	
	static function sendMailWithAttachment($email,$message,$subject,$attachment){
			
		$mail = new PHPMailer();
		$mail->IsSMTP(); // enable SMTP
		$mail->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
		$mail->SMTPAuth = true;  // authentication enabled
		$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for GMail
		$mail->Host = 'smtp.gmail.com';
		$mail->Port = 465;
		$mail->Username = "scteam07";
		$mail->Password = "#team7#beste";
	
		$mail->From     = "noreply@mybank.com";
		$mail->FromName = 'mybank Customer Service';
		$mail->AddAddress($email);
		
		if($attachment != "") {
			$mail->AddAttachment($attachment,"transaction_codes");
		}
	
		$userData = DataAccess::getUserDataFromEmail( $email );
		$name = $userData['name'];
		$mail->Subject  = $subject;
		$mail->Body     = "Dear ".$name.",\n ".$message."\n\n with best regards,\n   your myBank Customer Service";
		$mail->WordWrap = 200;
	
		if(!$mail->Send()) {
			
			throw new SendEmailException($mail->ErrorInfo);
		} 
	}
	
	public static function sendPwRecoveryMail( $user ) {
		
		$pwRecoverId = randomDigits(15);
		
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "UPDATE users set pw_recover_id = :pw_recover_id WHERE id = :id";
		
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "pw_recover_id", $pwRecoverId, PDO::PARAM_STR );
			$stmt->bindValue( "id", $user->id, PDO::PARAM_STR );
			$stmt->execute();
		
			$connection = null;
			
			// Send the mail
			
			$message= "We have received a password reset request for your account. Please click on this link, if you wish to receive your new password via email: <ip>/pw_recovery.php?email=$user->email&id=$pwRecoverId";
				
			
			MailController::sendMail($user->email, $message, "Your Password Recovery Request");
			
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
		}
	}
}
?>