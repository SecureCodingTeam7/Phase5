<?php
include_once(__DIR__.'/../include/conf.php');

class TanController {
	
	public function verifyTAN( $accountNumber, $tan, $tanNumber ) {
		
		$accountID = DataAccess::getAccountNumberID( $accountNumber );
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
			$sql = "SELECT code FROM trans_codes WHERE account_id = :account_id AND code_number = :code_number";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue( "account_id", $accountID, PDO::PARAM_STR );
			$stmt->bindValue( "code_number", $tanNumber, PDO::PARAM_STR );
			$stmt->execute();
			
			$result = $stmt->fetch();
			
			if ( $tan == $result['code'] ) {
				return true;
			} else {
				return false;
			}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return false;
		}
	}
	
	public function verifyGeneratedTAN( $accountNumber, $amount, $tan, $user) {
		
		try {
			$timeStamp = getUTCTime();
		}catch( TimeServerException $ex) {
			echo $ex->getMessage();
			return false;
		}
		
		
		$pin = $user->pin;
		$actual_seed = $timeStamp - $timeStamp % (1 * 60);
		$former_seed = $timeStamp  - $timeStamp % (1*60) - 60;
	
		$tan_one = TanController::generateTanWithSeed($actual_seed,$pin,$accountNumber,$amount);
		$tan_two = TanController::generateTanWithSeed($former_seed,$pin,$accountNumber,$amount);
		
		if ((strcmp($tan,$tan_one) == 0) || (strcmp($tan,$tan_two) == 0)) {
			return true;
		}
		else{
			return false;
		}
	}
	
	function generateTanWithSeed($seed,$pin,$destination,$amount){
		
		$plaintext = $seed.$pin.$destination.$amount.$seed;

		$hash = hash('sha256',$plaintext);
		$hash_array = array();
		for ($i=0;$i<16;$i += 2) {
			$tmp = substr($hash, $i,1).substr($hash, $i+1,1);
			array_push($hash_array, hexdec($tmp));
		}
		$hash_string = "";
		for ($j=0;$j<16;$j++) {
			$tmp = $hash_array[$j];
			if ($tmp > 127) {
				$tmp -= 256;
			}
		 	$hash_string .= strval(abs($tmp));
		}
		return substr($hash_string,0,15);
	}
	
	public static function generateTANList( $accountNumber, $user ) {
		
		/* Generate 100 random, unique transaction Codes of length 15 digits for this user */
		$maxNumTries = 100; // maximum number of rerolls in case a code is not unique
		
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			// Obtain ID of given accountNumber
			$accountID = DataAccess::getAccountNumberID ( $accountNumber );
			
			if ($accountID < 0) {
				return false;
			}
			
			for ( $codeNumber = 0; $codeNumber < 100; $codeNumber++) {
				$tries = 0;
				$unique = false;
				while ( !$unique && ( $tries <= $maxNumTries ) ) {
					$tries++;
					
					// Generate random code of length 15
					$code = randomDigits(15);
					
					// Make sure this code is unique
					$sql = "SELECT code FROM trans_codes WHERE code = :code";
					$stmt = $connection->prepare ( $sql );
					$stmt->bindValue ( "code", $code, PDO::PARAM_STR );
					$stmt->execute();
					
					// If code was not found set unique to true
					if ( $stmt->rowCount() == 0 ) {
						$unique = true;
					}
				}
				
				if ($tries >= $maxNumTries) {
					echo "Failed to Generate TAN List, too many tries!";
					return false;
				}
				
				$tans[$codeNumber] = $code; 
				// Code is unique, insert it into db
				$sql = "INSERT INTO trans_codes (account_id, code_number, code, is_used) VALUES (:account_id, :code_number, :code, :is_used)";
				$stmt = $connection->prepare ( $sql );
				$stmt->bindValue ( "account_id", $accountID, PDO::PARAM_STR );
				$stmt->bindValue ( "code_number", $codeNumber, PDO::PARAM_STR );
				$stmt->bindValue ( "code", $code, PDO::PARAM_STR );
				$stmt->bindValue ( "is_used", false, PDO::PARAM_STR );
				
				$stmt->execute();
			}
			
			$temp_file = tempnam(sys_get_temp_dir(), 'test');
			$tmp_pdf = $temp_file.".pdf";
			
			createPDF($tmp_pdf,$tans,$user->pin,$accountNumber);
			
			$message= "your transaction codes for the account ".$accountNumber." are located in the attached PDF file.\n Please note, that you will need to enter your Personal Identification Number, which you should have received in a different email.";

			try{
				MailController::sendMailWithAttachment($user->email, $message, "TAN Codes", $tmp_pdf);
				unlink($tmp_pdf);
			}
			catch (SendEmailException $e){
				echo "<br/>".$e->errorMessage();
				unlink($tmp_pdf);	
				return false;
			}
			return true;
				
				
			//~ } else {
				//~ return false;
			//~ }
		} catch ( PDOException $e ) {
			echo "<br />Connect Error (generateTANList): ". $e->getMessage();
			return false;
		}
	}
	
	public function selectRandomTAN( $accountNumber ) {
		$accountID = DataAccess::getAccountNumberID ( $accountNumber );
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				
			$sql = "SELECT code_number, code FROM trans_codes WHERE account_id = :account_id AND is_used = false";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue( "account_id", $accountID, PDO::PARAM_STR );
			$stmt->execute();
			$result = $stmt->fetchAll();
				
			$connection = null;
			
			if ($stmt->rowCount() > 0) {
				$index = rand ( 0, ($stmt->rowCount() - 1) );
				$tanNumber = $result[$index]['code_number'];
				$tan = $result[$index]['code'];
				//echo "<br />TAN NUMBER: " .$tanNumber;
				//echo "<br />TAN: " .$tan;
				return $tanNumber;
			} else {
				return -1;
			}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return -1;
		}
	}
	
	public function getNextTan( $accountNumber ) {
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$sql = "SELECT next_tan FROM accounts WHERE account_number = :account_number";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue ( "account_number", $accountNumber, PDO::PARAM_STR );
			$stmt->execute();
			$result = $stmt->fetch();
			
			return $result['next_tan'];
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return -1;
		}
	}
	
	public function updateNextTan( $accountNumber ) {
		$randomTANNumber = TanController::selectRandomTAN( $accountNumber );

		if ( $randomTANNumber < 0 )
			throw new TransferException("Failed to generate new TAN number (All TANs exhausted?).");
			
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$sql = "UPDATE accounts SET next_tan = :next_tan WHERE account_number = :account_number";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue ( "next_tan", $randomTANNumber, PDO::PARAM_STR );
			$stmt->bindValue ( "account_number", $accountNumber, PDO::PARAM_STR );
			$stmt->execute();
			
			if ($stmt->rowCount() > 0) {
				return true;
			} else {
				throw new TransferException("Failed to update TAN number.");
			}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return false;
		}
	}
}
?>