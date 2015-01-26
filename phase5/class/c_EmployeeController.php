<?php
include_once(__DIR__.'/../include/conf.php');

class EmployeeController {


	public function approveUsers( $data = array() ) {
		
		/* Make sure POST Data contains array of userIDs */
		if ( !isset($data['users']) || count($data['users']) <= 0 ) {
			throw new InvalidInputException("Submission data invalid. No users found.");
		}
		
		/* Obtain array of user IDs from POST Data */
		$userIDs = $data['users'];
		
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			
			foreach( $userIDs as $userID ) {
				
				/* Make sure userID is numeric */
				if ( !is_numeric( $userID ) ) {
					throw new InvalidInputException("User ID invalid.");
				}
				
				/* Make sure balance is set in POST Data */
				if ( !isset( $data['balance'.$userID] ) ) {
					throw new InvalidInputException("Submission data invalid. Balance for user ".$userID." not found.");
				}
				
				$newBalance = $data['balance'.$userID];
				
				/* Make sure balance is numeric */
				if ( !is_numeric( $newBalance ) || ( $newBalance < 0 ) ) {
					throw new InvalidInputException("Balance must be a positive number.");
				}
				
				/* Make sure user exists & is not an active user */
				if ( isActiveUser( $userID ) ) {
					throw new InvalidInputException("This user is already active.");
				}
				

				$user = DataAccess::getUserByID( $userID );

				if( !$user->isEmployee ) {
					MailController::sendMail($user->email, "we are pleased to inform you, that your account was enabled by one of our employees.","Your Account has been approved");
					$user->addAccount( generateNewAccountNumber() );
				}

				
				
				/* Mark User as Active */
				$sql = "UPDATE users set is_active = 1 WHERE id = :id";
				$stmt = $connection->prepare( $sql );
				$stmt->bindValue( "id", $userID, PDO::PARAM_INT );
				$stmt->execute();
				
				/* Set Balance for User */
				$sql = "UPDATE accounts set balance = :balance, available_funds = :available_funds WHERE user_id = :id";
				$stmt = $connection->prepare( $sql );
				$stmt->bindValue( "id", $userID, PDO::PARAM_INT );
				$stmt->bindValue( "balance", $newBalance, PDO::PARAM_INT );
				$stmt->bindValue( "available_funds", $newBalance, PDO::PARAM_INT );
				$stmt->execute();
				
				//$count++;
			}
			
			$connection = null;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public function approveTransactions($transactionIds) {

		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
		
			foreach( $transactionIds as $transactionId ) {
				
				if ( !TransactionController::isApprovedTransaction ( $transactionId ) ) {
					$sql = "UPDATE transactions set is_approved = 1 WHERE id = :id";
				
					$stmt = $connection->prepare( $sql );
					$stmt->bindValue( "id", $transactionId, PDO::PARAM_INT );
					$stmt->execute();
					
					$sql = "SELECT source, destination, amount FROM transactions  WHERE id = :id";
				
					$stmt = $connection->prepare( $sql );
					$stmt->bindValue( "id", $transactionId, PDO::PARAM_INT );
					$stmt->execute();
					
					$results = $stmt->fetch();
					
					$src = $results['source'];
					$dest = $results['destination'];
					$amount = $results['amount'];
					TransactionController::updateBalances( $src, $dest, $amount );
					TransactionController::updateAvailableFunds( $dest, $amount );
				} else {
					throw new InvalidInputException ("Transaction with ID ".$transactionId." is already approved.");
				}
			}
			
			$connection = null;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
}
?>