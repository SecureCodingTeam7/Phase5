<?php
include_once(__DIR__.'/../include/conf.php');

class TransactionController {


	public static function commitTransaction( $source, $destination, $amount, $code, $description, $user ) {
		$is_approved = true;
		if ( $amount >= 10000 ) {
			$is_approved = false;
		}
		
		if( $user->useScs == "0" ) {
			try {
				/* Using standard TAN method */
				$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
				$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
				$sql = "UPDATE trans_codes SET is_used = :is_used WHERE code = :code";
				$stmt = $connection->prepare( $sql );
				$stmt->bindValue( "code", $code, PDO::PARAM_STR );
				$stmt->bindValue( "is_used", true, PDO::PARAM_STR);
				$stmt->execute();
				
				if ( $stmt->rowCount() > 0 ) {
					if ( TanController::updateNextTan( $source ) ) {
						return TransactionController::insertTransaction($source, $destination, $amount, $description, $code, $is_approved);
					}
				} else { 
					throw new TransferException("TAN was already used.");
				}
			} catch ( PDOException $e ) {
				echo "<br />Connect Error: ". $e->getMessage();
				return false;
			}
		} else {
			/* Using SCS method */
			return TransactionController::insertTransaction($source, $destination, $amount, $description, $code, $is_approved);
		}	
	}
	
	public static function insertTransaction ($source, $destination, $amount, $description, $code, $is_approved) {
		
		$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
		$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		$sql = "INSERT INTO transactions (source, destination, amount, description, code, is_approved, date_time) VALUES (:source, :destination, :amount, :description, :code, :is_approved, NOW())";
		$stmt = $connection->prepare( $sql );
		$stmt->bindValue( "source", $source, PDO::PARAM_STR );
		$stmt->bindValue( "destination", $destination, PDO::PARAM_STR );
		$stmt->bindValue( "amount", $amount, PDO::PARAM_STR );
		$stmt->bindValue( "description", $description, PDO::PARAM_STR );
		$stmt->bindValue( "code", $code, PDO::PARAM_STR );
		$stmt->bindValue( "is_approved", $is_approved, PDO::PARAM_STR );
		$stmt->execute();
		
		if ( $stmt->rowCount() > 0) {
			
			if($is_approved) {
				if ( TransactionController::updateBalances( $source, $destination, $amount ) ) {
					if ( TransactionController::updateAvailableFunds ( $source, -$amount ) ) {
						return TransactionController::updateAvailableFunds ( $destination, $amount );
					}
				}
				
				throw new TransferException ("Failed to update balances or available funds.");
			} else {
				return TransactionController::updateAvailableFunds( $source, -$amount );
			}
		} else {
			throw new TransferException("Failed to insert transaction.");
		}
	}
	
	
	public static function transferCredits( $data = array(), $source, $user ) {

		if ( isset( $data['description'] ) ) {
			$description = stripslashes( strip_tags( $data['description'] ) );
			if (!preg_match('/^[a-z0-9 .:,\-]+$/i', $description)) { 
				throw new TransferException("The description may only contain letters, numbers,<br />and the following characters: .,:-"); 
			}
			
			if (strlen($description) > 200) {
				throw new TransferException("Please shorten your description to 200 characters or less.");
			}
		} else throw new TransferException("Description invalid.");
		
		if ( isset( $data['destination'] ) ) {
			$destination = stripslashes( strip_tags( $data['destination'] ) );
			if (!ctype_digit ( $destination )) {
				throw new TransferException("The Destination Account may only contain digits.");
			}
		} else throw new TransferException("Destination invalid.");
		
		if ( isset( $data['amount'] ) ) {
			$amount = stripslashes( strip_tags( $data['amount'] ) );
			
			if ( !is_numeric( $amount ) ) {
				throw new TransferException("Amount must be a number.");;
			}

			if ( $amount < 0.01 ) {
				throw new TransferException("Amount must be at least one cent.");;
			}
			
		} else throw new TransferException("Amount Invalid.");
		
		if ( isset( $data['tan'] ) ) {
			$tan = stripslashes( strip_tags( $data['tan'] ) );
			
			if (!ctype_digit ( $tan )) {
				throw new TransferException("The TAN may only contain digits.");
			}
		} else throw new TransferException("TAN invalid.");
		
		if ( $destination == $source ) {
			throw new TransferException("Destination account must be different from source account.");
		}
		
		/* Make sure source account has sufficient available funds */
		if ( TransactionController::getAvailableFundsForAccount ( $source ) < $amount ) {
			throw new TransferException("You have insufficient available funds.");
		}

		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			// Obtain user_id associated with given source account
			$sql = "SELECT user_id FROM accounts WHERE account_number = :account_number";
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "account_number", $source, PDO::PARAM_STR );
			$stmt->execute();
			
			$result = $stmt->fetch();
			$connection = null;
			
			// Was user_id found for given source account?
			if ( $stmt->rowCount() > 0 ) {
				// Make sure source account belongs to this user
				if ( $result['user_id'] != $user->id ) {
					throw new TransferException("User mismatch detected. Please Log out and Sign back in.");
				} else {
					// source account belongs to user
					// make sure destination account exists
					if (!checkAccountExists( $destination )) {
						throw new TransferException("The destination account doesn't exist.");
					} else {
						if( $user->useScs == "1" ) {
							if( TanController::verifyGeneratedTAN( $destination, $amount, $tan, $user ) ) {
								UserController::resetLockCounter( $user->email ); // Reset the lock out counter
								return TransactionController::commitTransaction( $source, $destination, $amount, $tan, $description, $user );
							}
						} else { // if TAN was wrong, call increment lock counter
							if( UserController::incrementLockCounter( $user->email ) ) {
								throw new TooManyInvalidTansException();
							}
						}	
						
						$currentTANNumber = TanController::getNextTan( $source );
						if ( $currentTANNumber < 0 )
							throw new TransferException("Unable to obtain TAN number.");
						
						if ( TanController::verifyTAN( $source, $tan, $currentTANNumber ) ) {
							UserController::resetLockCounter( $user->email ); // Reset lock counter on valid tan entry
							return TransactionController::commitTransaction( $source, $destination, $amount, $tan, $description, $user );
						} else { // invalid tan
							if( UserController::incrementLockCounter( $user->email ) ) { // increment lock counter
								throw new TooManyInvalidTansException();
							}
							
							throw new TransferException("Invalid TAN.");
						}
					}
				}
			} else {
				return false;
			}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return false;
		}
	}
	
	public static function getInApprovedTransactions() {
		
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT id, source, destination, amount, date_time FROM transactions WHERE is_approved = 0";
		
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
	
	public static function isApprovedTransaction( $transactionID ) {
		
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT is_approved FROM transactions WHERE id = :transaction_id";
				
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "transaction_id", $transactionID, PDO::PARAM_INT );
			$stmt->execute();
	
			$results = $stmt->fetch();
	
			$isApproved = $results['is_approved'];
			
			return $isApproved;
				
			$connection = null;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public static function getBalanceForAccount( $accountNumber ) {
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT balance FROM accounts WHERE account_number = :accountNumber";
		
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "accountNumber", $accountNumber, PDO::PARAM_INT );
			$stmt->execute();
		
			$result = $stmt->fetch();
			$connection = null;
			return $result['balance'];
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return -1;
		}
	}
	
	public static function getAvailableFundsForAccount( $accountNumber ) {
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT available_funds FROM accounts WHERE account_number = :accountNumber";
	
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "accountNumber", $accountNumber, PDO::PARAM_INT );
			$stmt->execute();
	
			$result = $stmt->fetch();
			$connection = null;
			return $result['available_funds'];
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return -1;
		}
	}
	
	static function updateBalances($srcAccount, $destAccount, $amount) {
		
		if( !checkAccountExists( $srcAccount ) ) {
			throw new InvalidInputException ("Unable to update balance. Source Account does not exist.");
		}
		
		if( !checkAccountExists( $destAccount ) ) {
			throw new InvalidInputException ("Unable to update balance. Destination Account does not exist.");
		}
		
		if( $amount <= 0 ) {
			throw new InvalidInputException ("Unable to update balance. Amount is invalid.");
		}
		
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			
			$srcBalance = TransactionController::getBalanceForAccount( $srcAccount );
				
			$sql = "UPDATE accounts set balance = :balance  WHERE account_number = :account_number";
			
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "balance", $srcBalance - $amount , PDO::PARAM_STR );
			$stmt->bindValue( "account_number", $srcAccount, PDO::PARAM_STR );
			$stmt->execute();
				
				
			$destBalance = TransactionController::getBalanceForAccount( $destAccount );
				
			$sql = "UPDATE accounts set balance = :balance  WHERE account_number = :account_number";
			
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "balance", $destBalance + $amount , PDO::PARAM_STR );
			$stmt->bindValue( "account_number", $destAccount, PDO::PARAM_STR );
			$stmt->execute();
			
			$connection = null;
			return true;
			
		} catch ( PDOException $e ) {
				echo "<br />Connect Error: ". $e->getMessage();
				return false;
		}
	}
	
	static function updateAvailableFunds( $account, $amount ) {
	
		if( !checkAccountExists( $account ) ) {
			throw new InvalidInputException ("Unable to update available funds. Account does not exist.");
		}
	
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
				
			$currentFundsAvailable = TransactionController::getAvailableFundsForAccount( $account );
			$newFundsAvailable = $currentFundsAvailable + $amount;
			
			$sql = "UPDATE accounts set available_funds = :available_funds  WHERE account_number = :account_number";
				
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "available_funds", $newFundsAvailable, PDO::PARAM_STR );
			$stmt->bindValue( "account_number", $account, PDO::PARAM_STR );
			$stmt->execute();
				
			$connection = null;
			return true;
				
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return false;
		}
	}


}
?>