<?php
session_start();
ini_set( 'session.cookie_httponly', 1 );
include_once(__DIR__."/../class/c_user.php");
include_once(__DIR__."/../class/c_DataAccess.php");
include_once(__DIR__."/../class/c_TanController.php");
include_once(__DIR__."/../class/c_TransactionController.php");
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

/* DEBUG 
echo "<br />SESSION TOKEN: ";
echo $_SESSION['CSRFToken'];
if (isset($_POST['CSRFToken'])) {
	echo "<br />POST TOKEN: ";
	echo $_POST['CSRFToken'];
}*/

if ($session_valid) {
	/* Generate Form Token (valid for this session) */
	if (!isset($_SESSION['CSRFToken'])) {
		$_SESSION['CSRFToken'] = generateFormToken();
	}
	
	/* Session Valid */
	$user = DataAccess::getUserByEmail ($_SESSION['user_email']);
	$selectedAccount = "none";
	$transferSuccess = 0;
	$transferMessage = "";
	$requiredTAN = "-1";
	
	if ( isset( $_SESSION['selectedAccount'] ) ) {
		/* Make sure account belongs to user */
		$accounts = $user->getAccounts();
		
		if( in_array( $_SESSION['selectedAccount'], $accounts ) ) {
			
			$selectedAccount = $_SESSION['selectedAccount'];
			$requiredTAN = TanController::getNextTAN( $selectedAccount );
			
			if ( isset( $_POST['creditTransfer'] ) ) {
				//echo $_POST['amount'];
				//echo $_POST['destination'];
				//echo $_POST['description'];
				//echo $_POST['tan'];
				if (isset( $_POST['CSRFToken']) && validateFormToken($_POST['CSRFToken'])) {
					try {
						if( TransactionController::transferCredits( $_POST, $selectedAccount, $user ) ) {
							$transferSuccess = 1;
							$transferMessage = "Successfully transferred " .$_POST['amount']. " Euro to " .$_POST['destination'];
						} else {
							$transferSuccess = -1;
							$transferMessage = "Transfer Failed.";
						}
					} catch (TooManyInvalidTansException $e) {
						$_SESSION['error'] = $e->errorMessage();
					} catch (Exception $e) {
						$transferMessage = $e->errorMessage();
					}
				} else {
					$_SESSION['error'] =  "CSRF Token invalid.";
				}
			}
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
	<title>Credit Transfer | myBank</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<script src="../include/jquery-2.1.3.min.js"></script>
	<link href="../style/bootstrap.css" rel="stylesheet" />
	<script src="../include/bootstrap.min.js"></script>
	<link href="../style/pure.css" type="text/css" rel="stylesheet" />
	<link href="../style/bootstrap.css" rel="stylesheet">
	<link href="../style/style.css" type="text/css" rel="stylesheet" />
	
	<script language="javascript" type="text/javascript">
		function limitArea(limitField, limitCount, limitNum) {
			if (limitField.value.length > limitNum) {
				limitField.value = limitField.value.substring(0, limitNum);
			} else {
				limitCount.value = limitNum - limitField.value.length;
			}
		}
	</script>
</head>
<body>
	<div id="content">
	
		<?php render_user_header( $selectedAccount, $user, "Transfer" ); ?>
		
		<div id="main">

			<?php 
			if (!isset( $_SESSION['selectedAccount'] )) {
				echo "No account is active at the moment.<br />";
				echo "You can set the active account on the <a href=\"index.php\">Overview page</a>.";
			} else {
				echo "Credit Transfer for Account <em>#".$selectedAccount."</em>";
				echo "<p>   ".$transferMessage."</p>";
			?>
			
			<form method="post" action="" class="pure-form pure-form-aligned" enctype='multipart/form-data'>
			
				<fieldset>
					<input type="hidden" name="CSRFToken" value="<?php echo $_SESSION['CSRFToken']; ?>" />
					<div class="pure-control-group">
						<label for="destination">Destination</label>
						<input id="destination" name="destination" type="text" placeholder="Account Number" 
						value="<?php if (isset($_POST['destination'])) echo $_POST['destination']; ?>" required>
					</div>
			
					<div class="pure-control-group">
						<label for="amount">Amount</label>
						<input id="amount" name="amount" type="text" placeholder="Amount in Eur" 
						 value="<?php if (isset($_POST['amount'])) echo $_POST['amount']; ?>" required>
					</div>
					
					<div class="pure-control-group">
						<label for="description">Description</label>
						<textarea id="desc2" name="description" type="text" style="width:190px; height:200px;" 
						onKeyDown="limitArea(this.form.description,this.form.countdown,200);"
						onKeyUp="limitArea(this.form.description,this.form.countdown,200);"
						required><?php if (isset($_POST['description'])) echo $_POST['description']; ?></textarea>
					</div>
		        

					<div class="pure-control-group">
					<?php if($user->useScs) {
						echo "<label for=\"amount\"><em>SCS TAN</em></label>";
					} else {
						echo "<label for=\"amount\">TAN <em>#".TanController::getNextTAN( $selectedAccount )."</em></label>";
					}?>
						<input id="tan" name="tan" type="text" placeholder="TAN"
						value="<?php if (isset($_POST['tan'])) echo $_POST['tan']; ?>"
						required>
					</div>
			
					<div class="pure-controls">
						<button type="submit" name="creditTransfer" class="pure-button pure-button-primary">Transfer</button>
					</div>
					
					<div class = "pure-controls" > OR </div>
				</fieldset>
			</form>
		
			<form method="POST" action="upload.php" class="pure-form pure-form-aligned">
				<div class="pure-controls">
					<button type ="submit" name="upload" class="pure-button pure-button-primary">Upload File</button>
				</div>
			</form>

		<?php
		}
		?>
		</div> <!-- main -->
	</div> <!-- content -->
	<script>
		function disableFunction() {
		    document.getElementById("createNewAccountButton").disabled = 'true';
		}
	</script>
	
	<script>
		YUI({
			classNamePrefix: 'pure'
		}).use('gallery-sm-menu', function (Y) {

			var horizontalMenu = new Y.Menu({
				container         : '#demo-horizontal-menu',
				sourceNode        : '#std-menu-items',
				orientation       : 'horizontal',
				hideOnOutsideClick: false,
				hideOnClick       : false
			});

			horizontalMenu.render();
			horizontalMenu.show();

		});
	</script>
</body>
</html>

<?php
}
?>
