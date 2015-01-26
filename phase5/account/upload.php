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
		/* Generate Form Token (valid for this session) */
		if (!isset($_SESSION['CSRFToken'])) {
			$_SESSION['CSRFToken'] = generateFormToken();
		}
		
        /* Session Valid */
        $user = DataAccess::getUserByEmail ( $_SESSION['user_email'] );
        $selectedAccount = "none";
        $requiredTAN = -1;
        $uploadMessage = "";
 
        
        if ( isset( $_SESSION['selectedAccount'] ) ) {
            $selectedAccount = $_SESSION['selectedAccount'];
            
            
            if(isset($_POST['uploadFile'])){
               
            	if (isset( $_POST['CSRFToken']) && validateFormToken($_POST['CSRFToken'])) {
	                //~ $name       = "transactionFile";
	                //~ $temp_name  = $_FILES['myfile']['tmp_name'];
	                
	               $uploadStatus = $_FILES['myfile']['error'];
	
					switch($uploadStatus){
						case UPLOAD_ERR_OK:
									
								$tan_number = "";
								if($user->useScs == "1") {
									$tan_number = "-1";
	                			} else {
									$tan_number = TanController::getNextTan( $selectedAccount );
								}
									
	                            $command = "transfer_parser ".$user->id." ".$selectedAccount." ".$tan_number." ".$_FILES['myfile']['tmp_name']." 2>&1";
	                            $result="";
	                            exec($command,$result,$return);
	
	                            if($return == 0 ){
	                                $uploadMessage="Transaction committed";
									if($user->useScs == "0") {
	                               	 TanController::updateNextTan( $selectedAccount);
	}
	                                
	                            }
	      
	                            else {
									
	                                $uploadMessage = "Error: ".$result[0];
	                            }
	                            break;
	                            
	                     case UPLOAD_ERR_INI_SIZE: 
									$uploadMessage = "Error: uploaded file is too big";
									break;
	                     default:
								    $uploadMessage = "Error: please upload your file again"; 
								    break;
					}
	            } else {
	            	$_SESSION['error'] =  "CSRF Token invalid.";
	            }
            }
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
		<title>Transfer Upload | myBank</title>
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

			<?php render_user_header($selectedAccount, $user, "Transfer"); ?>
			
			<div id="main">
				<div id="description">
					Example file:<br /><br />
					code:299347049962292<br /><br />
					destination:2510053093<br />
					amount:123<br />
					description:my description<br /><br />
					destination:123450976<br />
					amount:100.80<br />
					description:my description2<br /><br />
					...<br />
				</div>
			<form method="post" action="" class="pure-form pure-form-aligned" enctype='multipart/form-data'>

				<fieldset>
					<input type="hidden" name="CSRFToken" value="<?php echo $_SESSION['CSRFToken']; ?>" />
					<div class = "pure-controls" > Source : #<?php echo $selectedAccount ;?>
					</div>
				
					<?php if($user->useScs == "0") { ?>
					<div class = "pure-controls" > Required Tan : #<?php echo TanController::getNextTan( $selectedAccount );?>
					</div>
					<?php
					}
					?>

					<div class="pure-controls">
						<input type="file" name="myfile"><br>
					</div>
					
					<div class="pure-controls">
						<button type="submit" name = "uploadFile" class="pure-button pure-button-primary" > Submit</button>
					</div>
					
					<div class="pure-controls">
						<?php echo $uploadMessage; ?>
					</div>
				</fieldset>	
			</form>
				

			</div> <!-- main -->
		</div> <!-- content -->
	</body>
</html>
