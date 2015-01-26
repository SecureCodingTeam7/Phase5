<?php
include_once(__DIR__.'/../include/conf.php');

class UserController {

	
	public static function checkCredentials( $data = array() ) {
		$email = "";
		$password = "";
		
		if( isset( $data['email'] ) ) $email = stripslashes( strip_tags( $data['email'] ) );
		else return false;
		if( isset( $data['password'] ) ) $password = stripslashes( strip_tags( $data['password'] ) );
		else return false;
		
		$success = false;
		
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT passwd, lock_counter, BIN(`is_active` + 0) AS `is_active` FROM users WHERE email = :email LIMIT 1";
				
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "email", $email, PDO::PARAM_STR );
			$stmt->execute();
				
			$result = $stmt->fetch();
			if( $result ) {				
				 if( crypt($password,$result['passwd']) === $result['passwd']){
					if($result['is_active'] == 0){
						$connection = null;
						throw new IsActiveException();
					}
					
					/* On successful login, reset the lock counter */
					UserController::resetLockCounter( $email );
					
					$success = true;
				} 
				
				else { // Password incorrect, increase lock out counter, if counter >= 3 deactivate account.
					if(UserController::incrementLockCounter( $email )) {
						throw new Exception("You entered invalid information multiple times, hence your account was disabled for your own security. Please contact our customer service for help.");
					}
				}
				
			}
				
			$connection = null;
			return $success;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return $success;
		}
	}
	
	public static function checkPassword($passwd,$confirm_passwd){

		$uppercase = preg_match('@[A-Z]@', $passwd);
		$lowercase = preg_match('@[a-z]@', $passwd);
		$number    = preg_match('@[0-9]@', $passwd);

		
		//TODO display rules for password
		 if(!$uppercase || !$lowercase || !$number || strlen($passwd) < 8) {
		//	echo " password not secure ";
			return false;
		}
		#compare passwords
		else if($passwd!=$confirm_passwd){
		//	echo "You entered different passwords";
			return false;
		}
		
		return true;
	}
	
	static function incrementLockCounter( $email ) {

		$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
		$sql = "SELECT lock_counter FROM users WHERE email = :email LIMIT 1";

		$stmt = $connection->prepare( $sql );
		$stmt->bindValue( "email", $email, PDO::PARAM_STR );
		$stmt->execute();

		$result = $stmt->fetch();

		if ($result['lock_counter'] < 5) {
			$sql = "update users set lock_counter = lock_counter + 1 where email = :email";
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "email", $email, PDO::PARAM_STR );
			$stmt->execute();
			return false;
		} else {
			$sql = "update users set is_active = 0, lock_counter = 0 where email = :email";
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "email", $email, PDO::PARAM_STR );
			$stmt->execute();
			return true;
		}
		
		$connection = null;
	}

	static function resetLockCounter( $email ) {
		$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
		$sql = "update users set lock_counter = 0 where email = :email";
		$stmt = $connection->prepare( $sql );
		$stmt->bindValue( "email", $email, PDO::PARAM_STR );
		$stmt->execute();
		$connection = null;
	}
}
?>