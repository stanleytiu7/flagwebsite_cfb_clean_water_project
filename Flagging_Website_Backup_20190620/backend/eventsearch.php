<?php require_once('../scripts/archive_repeatingcode.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>CRWA Event Search</title>
<style type="text/css">
	span.error {color: red;}
	span.title {font-weight: bold; text-decoration: underline;}
</style>
</head>

<body>

	<p><span class="title">Event Search Form</span></p>

	<?php
		//check form entries
		$sdateErr = $edateErr = $keyErr = $userErr = "" ;
		$eventtype = $location = $startdate = $enddate = $start = $end = $user = $key = "";
		$validsearch ;
		$adminkey = read_key("adminkey");

		if (($_SERVER["REQUEST_METHOD"] == "POST") && !empty($_POST['search'])) {
			$validsearch = TRUE;
			$eventtype = test_input($_POST["eventtype"]);
			if ($eventtype == "CSO"){
				$location = test_input($_POST["csolocation"]);
			} else {
				$location = test_input($_POST["cyanolocation"]);
			}
			if (empty($_POST["startdate"])) {
					$sdateErr = "Start date required";
					$validsearch = FALSE;
			} else {
				$startdate = test_input($_POST["startdate"]);
				if (!strtotime($startdate)){
					$sdateErr = "Invalid date";
					$validsearch = FALSE;
					$start = $startdate;
				} else {
					$startdate = strtotime($startdate);
					$start = strftime("%m/%d/%Y",$startdate);
				}
			}
			if (!empty($_POST["enddate"])) {
				$enddate = test_input($_POST["enddate"]);
				if (!strtotime($enddate)){
					$edateErr = "Invalid date";
					$validsearch = FALSE;
					$end = $enddate;
				} else {
					if (strtotime($enddate) < $startdate) {
						$enddate = $startdate+86309;
						$edateErr = "Can't be before start date.";
					} else {
						$enddate = strtotime($enddate)+86309;
					}
					$end = strftime("%m/%d/%Y",$enddate);
				}
			}
			$user = test_input($_POST["user"]);
			$key = test_input($_POST["key"]);
			if ($key != $adminkey) {
				$keyErr = "Incorrect key";
				$validsearch = FALSE;
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
			<option value="all" selected="selected">All</option>
			<option value="CottagePark">Cottage Park</option>
		</select></p>
		<p><input type="radio" name="eventtype" value="Cyanobacteria" <?php if($eventtype=="Cyanobacteria"){echo "checked=\'yes\'";}?> /> Cyanobacteria event &nbsp;
		Location: <select name="cyanolocation">
			<option value="all" selected="selected">All</option>
			<option value="NewtonYC" >Newton YC</option>
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
		<p>Start Date: <input type="text" name="startdate" size="10" value="<?php echo $start;?>"/> 
		&nbsp;to &nbsp;<input type="text" name="enddate" size="10" value="<?php echo $end;?>"/> MM/DD/YY <span class="error"> <?php echo $sdateErr . " " . $edateErr;?></span></p>
		<p>User: <input type="text" name="user", size="20" value="<?php echo $user;?>"/><span class="error"> <?php echo $userErr;?></span></p>
		<p>Admin key: <input type="text" name="key" size="15" value="<?php echo $key;?>"/><span class="error"> <?php echo $keyErr;?></span></p>
		<p><input type="submit" value="Find Events" name="search"/></p>
	</form>

	<?php
		//execute search
		$dbkeys = read_key("dbuser");
		$dbuser = $dbkeys[0]; $dbpw = $dbkeys[1];

		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			if ($validsearch) {
				$datatable = "crwa_notification.eventdata";
				$wqdb = new mysqli("notification.crwa.org", $dbuser, $dbpw, "crwa_notification");
				if ($wqdb->connect_error) {
					echo "DB Connect Error: " . $wqdb->connect_error . "<br/>";
					exit();
				}
				$locsearch ; $usersearch;
				if ($location == "all"){$locsearch="%";}else{$locsearch=$location;}
				$starttime = strftime("%Y-%m-%d %H:%M:%S",$startdate);
				if ($enddate == ""){
					$endtime=strftime("%Y-%m-%d %H:%M:%S",$startdate+86309);
				}else{
					$endtime = strftime("%Y-%m-%d %H:%M:%S",$enddate);
				}
				if ($user == ""){$usersearch="%";}else{$usersearch=$user;}
				
				$statement = $wqdb->query("SELECT indx,event,location,startdate,enddate,user,lastupdate FROM $datatable WHERE event='$eventtype' AND location LIKE '$locsearch' AND startdate>=TIMESTAMP('$starttime') AND startdate<=TIMESTAMP('$endtime') AND user LIKE '$usersearch' ORDER BY startdate,indx");
				if ($statement->num_rows > 0){
					echo "Index, Event, Location, Startdate, Enddate, User, LastUpdate<br/>";
					while($row = $statement->fetch_assoc()){
						echo $row['indx'] . ", " . $row['event'] . ", " . $row['location'] . ", " . $row['startdate'] . ", " . $row['enddate'] . ", " . $row['user'] . ", " . $row['lastupdate'] . "<br/>";
					}
				} else {
					echo "Search returned no events.";
					exit();
				}
				$wqdb->close();
			} else {
				echo "Incorrect entries.<br/>" ;
			}
		}
	?>

</body>

</html>
