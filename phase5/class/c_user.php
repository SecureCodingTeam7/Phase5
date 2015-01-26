<?php
include_once(__DIR__.'/../include/conf.php');

class User {
	public $email = null;
	public $password = null;
	public $name = null;
	public $id = null;
	public $isEmployee = null;
	public $isActive = null;
	public $pin = null;
	public $useScs = null;
	public $securityQuestionNumber = null;
	public $securityQuestionAnswer = null;
	public $DEBUG = false;

	
	public function register( $data = array() ) {
		
		// DEBUG
		/*
			echo "<br />===================================================<br />";
			echo "Call: register() with POST DATA:<br />";
			echo "EMAIL: ".$data['email']."<br />";
			echo "Name: ".$data['username']."<br />";
			echo "Pass: ".$data['password']."<br />";
			echo "ConfirmPass: ".$data['confirm_password']."<br />";
			echo "Status: ".$data['status']."<br />";
		*/
		
		// validate input
		if( isset( $data['email'] ) ) {
			$this->email = stripslashes( strip_tags( $data['email'] ) ); 
		} else {
			throw new InvalidInputException("No email found. Please check the Email address.");
		}
		
		if (!isValidEmail( $this->email )) {
			throw new InvalidInputException("Email address invalid. Please check the Email address.");
		}
		
		if( checkUserExists( $this->email ) ){
			throw new InvalidInputException("There already exists a user with this email address.");
		}
		
		if ( isset( $data['username'] ) ) {
			$this->name = stripslashes( strip_tags( $data['username'] ) );
		} else {
			throw new InvalidInputException("No Name provided. Please check the Name.");
		}
		
		if ( preg_match('/[^a-z\s-]/i', $this->name ) ) {
			throw new InvalidInputException("Invalid Name. Please check the Name.");
		}
		
		if( isset( $data['password'] ) ) {
			$this->password = stripslashes( strip_tags( $data['password'] ) );
		} else {
			throw new InvalidInputException("Please check your password.");
		}
		
		if( isset( $data['confirm_password'] ) ) {
			$confirm_password = stripslashes( strip_tags( $data['confirm_password'] ) );
		} else {
			throw new InvalidInputException("Please check the confirmation password.");
		}
		
		if( isset( $data['status'] ) ) {
			$status = stripslashes( strip_tags( $data['status'] ) );
		} else {
			throw new InvalidInputException("Please select whether you are an Employee or Client.");
		}
		
		if( isset( $data['use_scs'] ) ) {
			$this->useScs = stripslashes( strip_tags( $data['use_scs'] ) );
			if ($this->useScs != "1" && $this->useScs != "0") {
				throw new InvalidInputException("SCS Value is invalid.");
			}
		} else {
			throw new InvalidInputException("Please select whether you use scs or not.");
		}
		
		if( isset( $data['sec_q_number'] ) ) {
			$this->securityQuestionNumber = stripslashes( strip_tags( $data['sec_q_number'] ) );
		} else {
			throw new InvalidInputException("Security Question not specified.");
		}

		if( isset( $data['sec_q_answer'] ) ) {
			$this->securityQuestionAnswer = stripslashes( strip_tags( $data['sec_q_answer'] ) );
		} else {
			throw new InvalidInputException("Security Question Answer not specified.");
		}


		// Input seems valid, proceed with registration
		if ($data['status'] == 1){
			$this->isEmployee = true;
		} else {
			$this->isEmployee = false;
		}
		
		if (! UserController::checkPassword( $this->password, $confirm_password ) ){
		 	throw new InvalidInputException("The two passwords do not match. Please check your password and confirmation password.");
		}
		
		
		try{
			// PW for PDF files or PIN for SCS
			$pin = randomDigits(6);
			
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	 
			$sql = "INSERT INTO users (email,name,passwd,is_employee,is_active,pin,use_scs,security_question_number,security_question_answer) VALUES (:email,:name,:password,:isEmployee,:isActive,:pin,:use_scs,:security_question_number,:security_question_answer)";
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "email", $this->email, PDO::PARAM_STR );
			$stmt->bindValue( "name", $this->name, PDO::PARAM_STR );
			$stmt->bindValue( "password", generateSaltedHash($this->password), PDO::PARAM_STR );
			$stmt->bindValue( "isEmployee", $this->isEmployee, PDO::PARAM_STR );
			$stmt->bindValue( "isActive", false, PDO::PARAM_STR );
			$stmt->bindValue( "pin", $pin, PDO::PARAM_STR );
			$stmt->bindValue( "use_scs", $this->useScs, PDO::PARAM_STR );
			$stmt->bindValue( "security_question_number", $this->securityQuestionNumber, PDO::PARAM_STR );
			$stmt->bindValue( "security_question_answer", $this->securityQuestionAnswer, PDO::PARAM_STR );
			$stmt->execute();
				
			$connection = null;
			
			if ( $stmt->rowCount() > 0 ) {
				
				if(!$this->isEmployee){
					$message = "we are excited to welcome you at myBank. Please note your Personal Identification Number (PIN): ".$pin."\n It is important that you keep your PIN confidential. MyBank Employees will never ask you for your PIN via Email.\n\n If you chose the PDF TAN option, the PIN is used to gain access to the PDF file, which includes your transaction numbers.\n\n Alternatively, if you chose the Smart Card option, the PIN is used by our Smart Card Tool, to generate your transaction numbers.\n To download the Smart Card Simulator, please login to your account on our website. The download link can be found on your Account page.";
					MailController::sendMail($this->email, $message, "Welcome to myBank, ".$this->name);
				}
				return true;
			} else {
				return false;
			}
			
		} catch ( PDOException $e ) {
			echo "<br />Connect Error (register): ". $e->getMessage();
			return false;
		}
	}
	
	public function addAccount( $accountNumber ) {
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

			$sql = "INSERT INTO accounts (user_id,account_number,next_tan) VALUES (:user_id,:account_number,:next_tan)";
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "user_id", $this->id, PDO::PARAM_STR );
			$stmt->bindValue( "account_number", $accountNumber, PDO::PARAM_STR );
			$stmt->bindValue( "next_tan", rand(0,99));
			$stmt->execute();
		
			$connection = null;
				
			if ( $stmt->rowCount() > 0 ) {
				if($this->useScs == "0") {	
					TanController::generateTANList( $accountNumber, $this );
				} 
				return true;
			} else {
				return false;
			}
				
		} catch ( PDOException $e ) {
			echo "<br />Connect Error (addAccount): ". $e->getMessage();
			return false;
		}
	}
	
	public function getAccounts () {
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT * FROM accounts WHERE user_id = :id";
		
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "id", $this->id, PDO::PARAM_STR );
			$stmt->execute();
		
			$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 2);
			// var_dump($result);
			$connection = null;
			return $result;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public function checkPwRecoveryId( $id ) {
		
		if(!is_numeric($id))  {
			return false;
		}
		
		return strcmp($this->pwRecoverId, $id) == 0;
	}
	
	public function doPwRecovery( $id, $postArray ) {
			
		if(!$this->checkPwRecoveryId( $id )) {
			return false;
		}
		
		if(strcmp($this->securityQuestionAnswer, $postArray['sec_q_answer']) != 0) {
			return false;
		}
		
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$sql = "update users set passwd = :password, pw_recover_id = NULL where id = :id";
			
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "password", generateSaltedHash($postArray['password']), PDO::PARAM_STR );
			$stmt->bindValue( "id", $this->id, PDO::PARAM_STR );
			$stmt->execute();
			
			return true;
			
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return false;
		}
	}
}
?>
