<?php
include_once(__DIR__.'/../include/conf.php');

class DataAccess {
	
	
	public static function getTransactions( $user, $accountNumber ) {
		/* Make sure account number belongs to this user */
		$userAccounts = $user->getAccounts();
		if ( !in_array($accountNumber, $userAccounts ) ) {
			return array ();
		}
		
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				
			$sql = "SELECT *, BIN(`is_approved` + 0) AS `is_approved` FROM transactions WHERE source = :account_number OR destination = :account_number ORDER BY date_time";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue( "account_number", $accountNumber, PDO::PARAM_STR );
			$stmt->execute();
			$result = $stmt->fetchAll();
			
			$realResult = array();
			
			if ($stmt->rowCount() > 0) {
				foreach($result as $transaction) {
					$sourceName = getAccountOwner( $transaction['source'] );
					$destName = getAccountOwner ( $transaction['destination'] );
					$transaction['source_name'] = $sourceName;
					$transaction['destination_name'] = $destName;
					
					array_push($realResult, $transaction);
				}
				return $realResult;
			} else {
				return array();
			}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public static function getAccountsForId ($id) {
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT * FROM accounts WHERE user_id = :id";
		
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "id", $id, PDO::PARAM_STR );
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
	
	public static function getAllUsers () {
		
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT id, email,  BIN(`is_active` + 0) AS `is_active` FROM users WHERE is_employee = 0 ORDER BY email";
		
			$stmt = $connection->prepare( $sql );
			$stmt->execute();
		
			$result = $stmt->fetchAll();
			// var_dump($result);
			$connection = null;
			return $result;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public static function getInApprovedUsers () {
		
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT id, email,  BIN(`is_employee` + 0) AS `is_employee` FROM users WHERE is_active = 0";
		
			$stmt = $connection->prepare( $sql );
			$stmt->execute();
		
			$result = $stmt->fetchAll();
			// var_dump($result);
			$connection = null;
			return $result;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public static function getUserDataFromEmail( $email ) {
		$result = array ();
		
		if (!isValidEmail( $email )) {
			throw new InvalidInputException("Email address (".$email.") invalid. Please check the Email address.");
		}
		
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT id, name, use_scs, email, passwd, pin, BIN(`is_employee` + 0) AS `is_employee`, BIN(`is_active` + 0) AS `is_active`, pw_recover_id, security_question_number, security_question_answer FROM users WHERE email = :email LIMIT 1";
		
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "email", $email, PDO::PARAM_STR );
			$stmt->execute();
		
			$result = $stmt->fetch();
			
			$connection = null;
			return $result;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error (getUserDataFromEmail): ". $e->getMessage();
			return array();
		}
	}
	
	public static function getUserByEmail( $email ) {
		$result = array ();
		
		if ( !isValidEmail( $email ) ) {
			throw new InvalidInputException("Email address (".$email.") invalid. Please check the Email address.");
		}
		
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT id, name, use_scs, email, passwd, pin, BIN(`is_employee` + 0) AS `is_employee`, BIN(`is_active` + 0) AS `is_active`, pw_recover_id, security_question_number, security_question_answer FROM users WHERE email = :email LIMIT 1";
		
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "email", $email, PDO::PARAM_STR );
			$stmt->execute();
		
			$result = $stmt->fetch();
			
			$user = new User();
			$user->email = $result['email'];
			$user->name = $result['name'];
			$user->password = $result['passwd'];
			$user->isEmployee = $result['is_employee'];
			$user->isActive = $result['is_active'];
			$user->id = $result['id'];
			$user->pwRecoverId = $result['pw_recover_id'];
			$user->pin = $result['pin'];
			$user->useScs = $result['use_scs'];
			$user->securityQuestionNumber = $result['security_question_number'];
			$user->securityQuestionAnswer = $result['security_question_answer'];
			
			$connection = null;
			return $user;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error (getUserDataFromEmail): ". $e->getMessage();
			return array();
		}
	}
	
	public static function getUserDataFromID( $id ) {
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT id, name, email, passwd, use_scs, pin, BIN(`is_employee` + 0) AS `is_employee`, BIN(`is_active` + 0) AS `is_active`, pw_recover_id, security_question_number, security_question_answer FROM users WHERE id = :id LIMIT 1";
	
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "id", $id, PDO::PARAM_STR );
			$stmt->execute();
	
			$result = $stmt->fetch();
				
			$connection = null;
			return $result;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error (getUserDataFromEmail): ". $e->getMessage();
			return array();
		}
	}
	
	public static function getUserByID( $id ) {
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT id, name, email, passwd, use_scs, pin, BIN(`is_employee` + 0) AS `is_employee`, BIN(`is_active` + 0) AS `is_active`, pw_recover_id, security_question_number, security_question_answer FROM users WHERE id = :id LIMIT 1";
	
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "id", $id, PDO::PARAM_STR );
			$stmt->execute();
	
			$result = $stmt->fetch();
			
			$user = new User();
			$user->email = $result['email'];
			$user->name = $result['name'];
			$user->password = $result['passwd'];
			$user->isEmployee = $result['is_employee'];
			$user->isActive = $result['is_active'];
			$user->id = $result['id'];
			$user->pwRecoverId = $result['pw_recover_id'];
			$user->pin = $result['pin'];
			$user->useScs = $result['use_scs'];
			$user->securityQuestionNumber = $result['security_question_number'];
			$user->securityQuestionAnswer = $result['security_question_answer'];
			$connection = null;
			return $user;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error (getUserDataFromEmail): ". $e->getMessage();
			return array();
		}
	}
	
	public static function getAccountNumberID( $accountNumber ) {
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$sql = "SELECT id FROM accounts WHERE account_number = :account_number";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue( "account_number", $accountNumber, PDO::PARAM_STR );
			$stmt->execute();
			$result = $stmt->fetch();
			
			if ($stmt->rowCount() > 0) {
				return $result['id'];
			} else {
				return -1;
			}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return -1;
		}	
	}
}
?>