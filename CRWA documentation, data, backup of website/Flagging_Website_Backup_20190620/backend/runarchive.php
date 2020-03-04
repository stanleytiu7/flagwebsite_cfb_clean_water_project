<?php require_once('../scripts/archive_repeatingcode.php'); ?>
<?php require_once('../scripts/archive_wqmodel.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>CR Run Archive</title>
<style type="text/css">
	span.error {color: red;}
	span.title {font-weight: bold; text-decoration: underline;}
</style>
</head>

<body>

	<?php
		//check form entries
		$keyErr = "" ;
		$key = "";
		$from_date = "";
		$from = "no repost";
		$checked = "";
		$fromErr = "";
		$validentry ;
		$adminkey = read_key("adminkey");

		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			$validentry = TRUE;
			$key = test_input($_POST["key"]);
			if ($key != $adminkey) {
				$keyErr = "Incorrect key";
				$validentry = FALSE;
			}
			if (!empty($_POST["from_date"])) {
				$from_date = test_input($_POST["from_date"]);
				if (($from_date != "no repost") && ($from_date != "")){
					if (!strtotime($from_date)){
						$fromErr = "Invalid date";
						$validentry = FALSE;
					} else {
						$from_date = strtotime($from_date);
						$from = strftime("%m/%d/%Y %H:%M",$from_date);
						if ($from_date >= time()) {
							$fromErr = "Can't be in future.";
							$validentry = FALSE;
						}
						if (($from_date < mktime(0,0,0,date("m"),date("d")-5,date("Y"))) && (!isset($_POST["confirm_repost"]))){
							$fromErr = "Confirm long repost by checking confirmation box.";
							$validentry = FALSE;							
						}
						if (($from_date >= mktime(0,0,0,date("m"),date("d")-5,date("Y"))) && (isset($_POST["confirm_repost"]))){
							$fromErr = "Confirmation checkbox should not be checked for short reposts.";
							$checked = "checked=\'checked\'";
							$validentry = FALSE;							
						}
					}
				}
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
		<p>Repost data from: <input type="text" name="from_date" size="20" value="<?php echo $from;?>" /> MM/DD/YY HH:MM (24-hour time format) <span class="error"><?php echo $fromErr;?></span></p>
		<p>The above field should only be used if manually reposting data.  Leave blank if not reposting.</p>
		<p>To repost, first load a Hobolink "Manual Export" file into the directory "/commboating_files" with data spanning all time periods from before the repost point to the current time.  The export file must be in the "HOBOware CSV" format.  The file name can end in ".csv" or ".txt".  Be careful about the format of the label on the index column.  It should have quotation marks around the # symbol.  Then run this job specifying the start-date for reposting.  The most recent file in the directory will be used.</p>
		<p> If reposting more than 5 days of data, check this box to confirm <input type="checkbox" name="confirm_repost" <?php echo $checked;?>" /></p>
		<br/><br/>
		<p>Admin key: <input type="text" name="key" size="15" value="<?php echo $key;?>"/><span class="error"> <?php echo $keyErr;?></span></p>
		<p><input type="submit" value="Run Archive"/></p>
	</form>
	
	<?php
		//execute run
		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			if ($validentry) {
				if (($from_date != "no repost") && ($from_date != "")){
					$dbkeys = read_key("dbuser");
					$dbuser = $dbkeys[0]; $dbpw = $dbkeys[1];
					$datatable = "crwa_notification.rawdata";
					$rawdb = new mysqli("notification.crwa.org", $dbuser, $dbpw, "crwa_notification");
					if ($rawdb->connect_error) {
						echo "DB Connect Error: " . $rawdb->connect_error . "<br/>";
						exit();
					}
					$from_datestring = strftime("%Y-%m-%d %H:%M:%S", $from_date);
					$rawdb->query("DELETE FROM $datatable WHERE datetime>=TIMESTAMP('$from_datestring')");
					$rawdb->close();
					archive_wqmodel(TRUE);
				} else {
					archive_wqmodel(FALSE);
				}
			} else {
				echo "Incorrect entries.<br/>" ;
			}
		}
	?>
	
</body>
</html>
