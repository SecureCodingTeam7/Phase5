<?php
session_start();
ini_set( 'session.cookie_httponly', 1 );
include_once(__DIR__."/../class/c_user.php");
include_once(__DIR__."/../include/helper.php");
include_once(__DIR__."/../include/InvalidSessionException.php");
include_once(__DIR__."/../header.php");

$loginPage = "../login.php";
$loginRedirectHeader = "Location: ".$loginPage;

try {
	validateUserSession(false);
	$session_valid = true;
} catch (InvalidSessionException $ex) { 
	/* DEBUG */
	// echo $ex->getMessage();
	$session_valid = false;
	header("Location:../logout.php");
	die();
}

if ($session_valid) {
	/* Session Valid */
	$user = DataAccess::getUserByEmail ( $_SESSION['user_email'] );
	$selectedAccount = "none";
	
	if ( isset( $_SESSION['selectedAccount'] ) ) {
		
		/* Make sure account belongs to user */
		$accounts = $user->getAccounts();
		if(in_array($_SESSION['selectedAccount'], $accounts)) {
			$selectedAccount = $_SESSION['selectedAccount'];
		} else {
			/* Possible malicious activity: Account does not belong to user
			 * Raise Session Error and close the session
			 */
			$_SESSION['error'] =  "Account mismatch detected.";
		}
	}
	
	/* If error or possible malicious activity was detected close the session */
	if ( ( isset( $_SESSION['error'] ) ) ) {
		header("Location:../logout.php");
		die();
	}
?>
<!doctype html>
<html>
<head>
	<title>Account History | myBank</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<script src="../include/jquery-2.1.3.min.js"></script>
	<link href="../style/bootstrap.css" rel="stylesheet" />
	<script src="../include/bootstrap.min.js"></script>
	<link href="../style/pure.css" type="text/css" rel="stylesheet" />
	<link href="../style/bootstrap.css" rel="stylesheet">
	<link href="../style/style.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div id="content">
	
		<?php render_user_header($selectedAccount, $user, "Account"); ?>
		
		<div id="main">
		<?php 
			if (!isset( $_SESSION['selectedAccount'] )) {
				echo "No account is active at the moment.<br />";
				echo "You can set the active account on the <a href=\"index.php\">Overview page</a>.";
			} else {
				echo "Transfer History for Account #".$selectedAccount;
			}
			
			$transactions = DataAccess::getTransactions( $user, $selectedAccount );
			$odd = true;
			$count = 0;
			
			?>
			<table class="pure-table">
			    <thead>
			        <tr>
						<th>#</th>
			            <th>Source</th>
						<th>Src-Name</th>
						<th>Destination</th>
						<th>Dst-Name</th>
						<th>Amount</th>
						<th>Valid</th>
						<th>Description</th>
						<th>Time</th>
			        </tr>
			    </thead>
			
			    <tbody>
			<?php 
			foreach ($transactions as $transaction) { 
					if ($odd) {
						echo "<tr class=\"pure-table-odd\">";
						$odd = false;
					} else { 
						echo "<tr>";
						$odd = true; 
					}?>
			            <td><?php echo ++$count; ?></td>
						<td><?php if ($transaction['source'] == $selectedAccount) {
							 echo "<p class=\"selectedAccount\">".$transaction['source']."</p>";
						} else { echo $transaction['source']; } ?></td>
						<td><?php echo $transaction['source_name'] ?></td>
						<td><?php if ($transaction['destination'] == $selectedAccount) {
							 echo "<p class=\"selectedAccount\">".$transaction['destination']."</p>";
						} else { echo $transaction['destination']; } ?></td>
					  	<td><?php echo $transaction['destination_name'] ?></td>
						<td><p class=<?php if($transaction['destination'] == $selectedAccount) echo "\"income\">"; else echo "\"expense\">"; echo $transaction['amount']."</p>"; ?></td>
						<td><?php if ($transaction['is_approved'] > 0) echo "yes"; else echo "no"; ?></td>
						<td><?php echo $transaction['description'] ?></td>
						<td><?php echo $transaction['date_time']; ?></td>
			        </tr>
			<?php
			}
			?>

			    </tbody>
			</table>

		</div>
		</div>
	</div>
</body>
</html>

<?php
}
?>
