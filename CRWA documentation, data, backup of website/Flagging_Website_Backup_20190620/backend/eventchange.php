<?php require_once('../scripts/archive_repeatingcode.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>CRWA Event Change</title>
<style type="text/css">
	span.error {color: red;}
	span.title {font-weight: bold; text-decoration: underline;}
</style>
</head>

<body>

	<p><span class="title">Event Change Form</span> (use search form to find index number first)</p>
	<p>To update or delete multiple entries, enter index numbers one at a time and click Update or Delete after each.</p>

	<?php
		//check form entries
		$indxErr = $enddtErr = $kyErr = $usrErr = "" ;
		$indx = $enddate = $enddt = $endtm = $usr = $ky = "";
		$validentry ;
		$adminkey = read_key("adminkey");

		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			$validentry = TRUE;
			if (empty($_POST["indx"])) {
				$indxErr = "Index is required";
				$validentry = FALSE;
			} else {
				$indx = test_input($_POST["indx"]);
			}
			if (!empty($_POST['change'])) {
				$enddt = test_input($_POST["enddate"]);
				$endtm = test_input($_POST["endtime"]);
				if (empty($_POST["enddate"]) || empty($_POST["endtime"])) {
					$enddtErr = "End date and time required.";
					$validentry = FALSE;
				} else {
					$enddate = $enddt . " " . $endtm;
					if (!strtotime($enddate)){
						$enddtErr = "Invalid date";
						$validentry = FALSE;
					} else {
						$enddate = strtotime($enddate);
						$enddt = strftime("%m/%d/%Y",$enddate);
						$endtm = strftime("%H:%M",$enddate);
					}
				}
				if (empty($_POST["user"])) {
					$usrErr = "User is required";
					$validentry = FALSE;
				} else {
					$usr = test_input($_POST["user"]);
				}
			}
			$ky = test_input($_POST["key"]);
			if ($ky != $adminkey) {
				$kyErr = "Incorrect key";
				$validentry = FALSE;
			}
		}
		
		function test_input($data) {
			$data = trim($data);
			$data = stripslashes($data);
			$data = htmlspecialchars($data);
			return $data;
		}
	?>		

	<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
		<p>Index: <input type="text" name="indx", size="10" value="<?php echo $indx;?>"/> <span class="error"> <?php echo $indxErr;?></span></p>
		<p>End Date: <input type="text" name="enddate" size="10" value="<?php echo $enddt;?>"/> MM/DD/YY <input type="text" name="endtime" size="5" value="<?php echo $endtm;?>"/> HH:MM (update only)<span class="error"> <?php echo $enddtErr;?></span></p>
		<p>User: <input type="text" name="user", size="20" value="<?php echo $usr;?>"/> (update only)<span class="error"> <?php echo $usrErr;?></span></p>
		<p>Admin key: <input type="text" name="key" size="15" value="<?php echo $ky;?>"/><span class="error"> <?php echo $kyErr;?></span></p>
		<p><input type="submit" value="Update" name="change"/> or <input type="submit" value="Delete" name="delete"/></p>
	</form>

	<?php
		//execute change
		$dbkeys = read_key("dbuser");
		$dbuser = $dbkeys[0]; $dbpw = $dbkeys[1];
		
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			if ($validentry) {
				$datatable = "crwa_notification.eventdata";
				$wqdb = new mysqli("notification.crwa.org", $dbuser, $dbpw, "crwa_notification");
				if ($wqdb->connect_error) {
					echo "DB Connect Error: " . $wqdb->connect_error . "<br/>";
					exit();
				}
				$ev = $lo = $st = $en = $us = $la = "";
				if ($statement=$wqdb->query("SELECT event,location,startdate,enddate,user,lastupdate FROM $datatable WHERE indx='$indx'")) {
					$row = $statement->fetch_assoc();
					$ev = $row['event'];
					$lo = $row['location'];
					$st = $row['startdate'];
					$en = $row['enddate'];
					$us = $row['user'];
					$la = $row['lastupdate'];
					$statement->free();
				} else {
					echo "Invalid index<br/>";
					$wqdb->close();
					exit();
				}
				if ($ev == "") {
					echo "Invalid index<br/>";
					$wqdb->close();
					exit();
				}
				if (!empty($_POST['change'])) {
					if ($enddate >= strtotime($st)) {
						$currenttime = strftime("%Y-%m-%d %H:%M:%S",time());
						$endtime = strftime("%Y-%m-%d %H:%M:%S",$enddate);
						$wqdb->query("UPDATE $datatable SET enddate=TIMESTAMP('$endtime'),user='$usr',lastupdate=TIMESTAMP('$currenttime') WHERE indx=$indx"); 
						echo "Updated:<br/>From " . $ev . ", " . $lo . ", " . $st . ", " . $en . "<br/>To " . $ev . ", " . $lo . ", " . $st . ", " . $endtime . "<br/>";
					} else {
						echo "Incorrect entries.<br/>End Date must be after start date of event (" . $st . ").<br/>" ;
					}
				} else {
					$wqdb->query("DELETE FROM $datatable WHERE indx=$indx");
					echo "Deleted " . $indx . ", " . $ev . ", " . $lo . ", " . $st . ", " . $en . "<br/>";
				}
				$wqdb->close();
			} else {
				echo "Incorrect entries.<br/>" ;
			}
		}
	?>

</body>

</html>
