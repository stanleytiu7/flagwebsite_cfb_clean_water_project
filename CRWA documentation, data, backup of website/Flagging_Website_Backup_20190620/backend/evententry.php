<?php require_once('../scripts/archive_repeatingcode.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>CRWA Event Entry</title>
<style type="text/css">
	span.error {color: red;}
	span.title {font-weight: bold; text-decoration: underline;}
</style>
</head>

<body>

	<p><span class="title">Event Entry Form</span></p>

	<?php
		//check form entries
		$sdateErr = $edateErr = $keyErr = $userErr = "" ;
		$eventtype = $location = $start = $finish = $startdate = $starttime = $enddate = $endtime = $user = $key = "";
		$validentry ;
		$adminkey = read_key("adminkey");
		$CSO_hold = 3;
		$Cyano_hold = 90;

		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			$validentry = TRUE;
			$hold = 7;
			$eventtype = test_input($_POST["eventtype"]);
			if ($eventtype == "CSO"){
				$location = test_input($_POST["csolocation"]);
				$hold = $CSO_hold; //default days to end date if end date blank
			} else {
				$count = 0;
				foreach ($_POST['cyanolocation'] as $selectedloc){
					$location[$count] = test_input($selectedloc);
					$count++;
				}
				$hold = $Cyano_hold; //default days to end date if end date blank
			}
			$startdate = test_input($_POST["startdate"]);
			$starttime = test_input($_POST["starttime"]);
			if (empty($_POST["startdate"]) || empty($_POST["starttime"])) {
				$sdateErr = "Start date is required";
				$validentry = FALSE;
			} else {
				$start = test_input($_POST["startdate"]) . " " . test_input($_POST["starttime"]);
				if (!strtotime($start)){
					$sdateErr = "Invalid date";
					$validentry = FALSE;
				} else {
					$start = strftime("%Y-%m-%d %H:%M:%S",strtotime($start));
				}
			}
			$enddate = test_input($_POST["enddate"]);
			$endtime = test_input($_POST["endtime"]);
			if (empty($_POST["enddate"]) || empty($_POST["endtime"])) {
				$finish = strftime("%Y-%m-%d %H:%M:%S", mktime(0,0,0,date("m"), date("d")+$hold, date("Y")));
				$edateErr = "Set to " . $hold . " days in the future by default.";
			} else {
				$finish = test_input($_POST["enddate"]) . " " . test_input($_POST["endtime"]);
				if (!strtotime($finish)){
					$edateErr = "Invalid date";
					$validentry = FALSE;
				} else {
					if (strtotime($finish) < strtotime($start)) {
						$edateErr = "Can't be before start date.";
						$validentry = FALSE;
					} else {
						$finish = strftime("%Y-%m-%d %H:%M:%S",strtotime($finish));
					}
				}
			}
			if (empty($_POST["user"])) {
				$userErr = "User is required";
				$validentry = FALSE;
			} else {
				$user = test_input($_POST["user"]);
			}
			$key = test_input($_POST["key"]);
			if ($key != $adminkey) {
				$keyErr = "Incorrect key";
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
		<p><input type="radio" name="eventtype" value="CSO" checked/> CSO event &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		Location: <select name="csolocation">
			<option value="CottagePark" selected="selected">Cottage Park</option>
		</select></p>
		<p><input type="radio" name="eventtype" value="Cyanobacteria" <?php if($eventtype=="Cyanobacteria"){echo "checked=\'yes\'";}?> /> Cyanobacteria event &nbsp;
		Location: <select name="cyanolocation[]" multiple="yes" size="5">
			<option value="NewtonYC" selected="selected">Newton YC</option>
			<option value="WatertownYC">Watertown YC</option>
			<option value="CommRowing">Community Rowing</option>
			<option value="CRCK">CR Canoe & Kayak</option>
			<option value="HarvardWeld">Harvard Weld</option>
			<option value="RiversideBC">Riverside BC</option>
			<option value="CRYC">CR Yacht Club</option>
			<option value="UnionBC">Union BC</option>
			<option value="CommBoating">Community Boating</option>
			<option value="CRCKKendall">CR Canoe & Kayak Kdl</option>
		</select></p>
		<p>Start Date: <input type="text" name="startdate" size="10" value="<?php echo $startdate;?>"/> MM/DD/YY <input type="text" name="starttime" size="5" value="<?php echo $starttime;?>"/> HH:MM <span class="error"> <?php echo $sdateErr;?></span></p>
		<p>End Date: <input type="text" name="enddate" size="10" value="<?php echo $enddate;?>"/> MM/DD/YY <input type="text" name="endtime" size="5" value="<?php echo $endtime;?>"/> HH:MM (If blank, defaults to <?php echo $CSO_hold;?> days from today for CSO and <?php echo $Cyano_hold;?> days for Cyanobacteria.) <span class="error"> <?php echo $edateErr;?></span></p>
		<p>User: <input type="text" name="user", size="20" value="<?php echo $user;?>"/><span class="error"> <?php echo $userErr;?></span></p>
		<p>Admin key: <input type="text" name="key" size="15" value="<?php echo $key;?>"/><span class="error"> <?php echo $keyErr;?></span></p>
		<p><input type="submit" value="Submit Event"/></p>
	</form>

	<?php
		//execute entry
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
				$indx = 1;
				if ($statement = $wqdb->query("SELECT indx FROM $datatable WHERE 1 ORDER BY indx DESC LIMIT 1")){
					$row = $statement->fetch_assoc();
					$indx = $row['indx'] + 1;
					$statement->free();
				}
				$currenttime = strftime("%Y-%m-%d %H:%M:%S",time());
				if ($eventtype == "CSO") {
					$wqdb->query("INSERT INTO $datatable (indx,event,location,startdate,enddate,user,lastupdate) VALUES 	('$indx','$eventtype','$location',TIMESTAMP('$start'),TIMESTAMP('$finish'),'$user',TIMESTAMP('$currenttime'))");
					echo "Entered:  " . $eventtype . " for " . $location . " from " . $start . " to " . $finish . " index " . $indx . "<br/>";
				} else {
					foreach ($location as $loc) {
						$wqdb->query("INSERT INTO $datatable (indx,event,location,startdate,enddate,user,lastupdate) VALUES 	('$indx','$eventtype','$loc',TIMESTAMP('$start'),TIMESTAMP('$finish'),'$user',TIMESTAMP('$currenttime'))");
						echo "Entered:  " . $eventtype . " for " . $loc . " from " . $start . " to " . $finish . " index " . $indx . "<br/>";
						$indx++;
					}
				}
				$wqdb->close();
			} else {
				echo "Incorrect entries.<br/>" ;
			}
		}
	?>

</body>

</html>
