<?php
ini_set( 'session.cookie_httponly', 1 );
include_once(__DIR__."/include/db_connect.php"); 
include_once(__DIR__."/class/c_user.php");
include_once(__DIR__."/class/c_DataAccess.php");
include_once(__DIR__."/class/c_UserController.php");
include_once(__DIR__."/include/helper.php");
include_once(__DIR__."/header.php");
session_start();

/* Generate Form Token (unless this is already a submit) */
if (!isset($_POST['checkLogin'])) {
	$_SESSION['CSRFToken'] = generateFormToken();
}

/* DEBUG 
echo "<br />SESSION TOKEN:<br />";
echo $_SESSION['CSRFToken'];
echo "<br />SESSION TOKEN END<br />";
*/

if ( !isset($_SESSION['user_email']) || !isset($_SESSION['user_level']) || !isset($_SESSION['user_login']) ) {
    // thats okay, the user can login
} else if ( $_SESSION['user_email'] == "" || $_SESSION['user_level'] == "" || $_SESSION['user_login'] == "") {
	// thats okay, the user can login
} else {
	// user already logged in
	if($_SESSION['user_level']) {
		header("Location: employee/approve.php");
	} else {
		header("Location: account/index.php");
	}
	die();
}

if( !(isset( $_POST['checkLogin'] ) ) ) { ?>
<!doctype html>
<html>
	<head>
		<title>Account Dashboard | myBank</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<script src="include/jquery-2.1.3.min.js"></script>
		<link href="style/bootstrap.css" rel="stylesheet" />
		<script src="include/bootstrap.min.js"></script>
		<link href="style/pure.css" type="text/css" rel="stylesheet" />
		<link href="style/bootstrap.css" rel="stylesheet">
		<link href="style/style.css" type="text/css" rel="stylesheet" />
	</head>
	
	<body>
		<div id="content">
		
			<?php render_guest_header("Login"); ?>
			
			<div id="main">
				<p>No Account yet? <a href="register.php"><em>Click here</em></a> to register with us.<br /><br /></p>		
					<form method="post" action="" class="pure-form pure-form-aligned">
						<fieldset>
							<input type="hidden" name="CSRFToken" value="<?php echo $_SESSION['CSRFToken']; ?>" />
							<div class="pure-control-group">
								<label for="email">Email</label>
								<input name="email" id="email" type="email" placeholder="YourAccount@bank.de" required>
							</div>
					
							<div class="pure-control-group">
								<label for="password">Password</label>
								<input name="password" id="password" type="password" placeholder="***********" required>
							</div>
					
							<div class="pure-controls">
								<button id="SignInButton" type="submit" name="checkLogin" class="pure-button pure-button-primary">Sign In</button>
							</div>
						</fieldset>
					</form>
				<a href="pw_recovery.php">Forgot Password?</a>
			</div> <!-- main -->
		</div> <!-- content -->
	</body>
</html>

<?php 
/* Not rendering page, Form submission.. */
} else {
	/* Check CSRFToken */
	if (validateFormToken($_POST['CSRFToken'])) {
		
		$user = DataAccess::getUserByEmail( $_POST['email'] );

		//~ echo "<br />[DEBUG]Email: ".  $_POST['email'];
		//~ echo "<br />[DEBUG]Password: " .  $_POST['password'];
		
		try { 
		
			if( UserController::checkCredentials( $_POST ) ) {
				/* Set Session */
				session_start();
				session_regenerate_id();
				$_SESSION['user_email'] = $user->email;
				$_SESSION['user_level'] = $user->isEmployee;
				$_SESSION['user_login'] = 1;
				
				if($user->isEmployee) {
					header("Location: employee/approve.php");
				} else {
					header("Location: account/index.php");
				}
				die();
				
				//~ echo "<br />Successful Login. <a href='account/index.php'>Click here</a> to continue.";
				//~ echo "<br />[DEBUG] Session Data: user_email: " . $_SESSION['user_email'] .
															  //~ ", user_level: " . $_SESSION['user_level'] .
															  //~ ", user_login: " . $_SESSION['user_login'];	
			} else {
				/* Completeley destroy Session */
				//$_SESSION = array();
				//session_destroy();
				
				echo "<br />Incorrect Email/Password. Please <a href='login.php'>Try again</a>.";	
			}
		} catch(IsActiveException $e) {
			$_SESSION = array();
			session_destroy();
			echo "<br />Your account was not approved yet, please wait until someone does!";
		} catch(Exception $e) {
			echo "<br />".$e->getMessage();
		}
	} else {
		echo "Invalid Token. Please <a href='login.php'>Try again</a>.";
	}
}
?>
