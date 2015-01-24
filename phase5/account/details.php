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
	$user = new User();
	$selectedAccount = "none";
	$user->getUserDataFromEmail( $_SESSION['user_email'] );
	
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
	<title>Account Details | myBank</title>
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
			
			$transactions = $user->getTransactions( $selectedAccount );
			$odd = true;
			$count = 0;
			
			?>
			<table class="pure-table">
			    <thead>
			        <tr>
						<th><div class="table-header-image"><IMG SRC="../images/32_account.png" ALT="Your Account Number" WIDTH=32 HEIGHT=32>Account Number</div></th>
			            <th><div class="table-header-image"><IMG SRC="../images/32_ID.png" ALT="Your Account Number" WIDTH=32 HEIGHT=32>Full Name</div></th>
						<th><div class="table-header-image"><IMG SRC="../images/32_Email.png" ALT="Your Account Number" WIDTH=32 HEIGHT=31>Email</div></th>
						<th><div class="table-header-image"><IMG SRC="../images/32_MoneyB.png" ALT="Your Account Number" WIDTH=32 HEIGHT=32>Balance</div></th>
						<th><div class="table-header-image"><IMG SRC="../images/32_MoneyA.png" ALT="Your Account Number" WIDTH=32 HEIGHT=32>Available Funds</div></th>
						<th><div class="table-header-image"><IMG SRC="../images/32_SCS.png" ALT="Your Account Number" WIDTH=32 HEIGHT=32>SCS User</div></th>
			        </tr>
			    </thead>
			
			    <tbody>
					<tr>
						<td><?php echo $selectedAccount; ?></td>
						<td><?php echo $user->name; ?></td>
						<td><?php echo $user->email; ?></td>
						<td><?php echo $user->getBalanceForAccount ( $selectedAccount ); ?></td>
						<td><?php echo $user->getAvailableFundsForAccount ( $selectedAccount );; ?></td>
						<td><?php if ($user->useScs == 1) { echo "yes"; } else { echo "no"; } ?></td>
					</tr>
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
