<?php
// Repeated archiving code for U30s and other data sources - to mySQL database
// Version 3 :: Ben Wetherill :: 1/20/15 
//-------------------------------------------------------------------------------------------

date_default_timezone_set('America/New_York');

function archive_OnsetU30($name) {
		//Reads data file from Onset U30 and updates local database archive
		$dbkeys = read_key("dbuser");
		$dbuser = $dbkeys[0]; $dbpw = $dbkeys[1];
		
		global $specialmsg, $corrections ;
		$specialmsg = "" ;
		$corrections = 0 ;
		$site = "";
		$hobo_addr = "";
		$station_type = "" ;
		switch ($name) {
			case "CommunityBoating": 
				$site="CharlesCB" ;
				$hobo_addr="http://webservice.hobolink.com/rest/public/devices/10191005/data_files/latest/txt" ;
				$station_type = "riverstation" ;
				break ;
		}
		
		//query database to get last date
		$arc_lastdate = mktime(0, 0, 0, 0, 0, 0);
		$datatable = "crwa_notification.rawdata";
		$rawdb = new mysqli("notification.crwa.org", $dbuser, $dbpw, "crwa_notification");
		if ($rawdb->connect_error) {
    		$specialmsg = 'Connect Error: ' . $rawdb->connect_error;
			return FALSE;
		}
		if ($statement = $rawdb->query("SELECT datetime FROM $datatable WHERE site='$site' ORDER BY datetime DESC LIMIT 1")){
			$row = $statement->fetch_assoc();
			$arc_lastdate = strtotime($row['datetime']);
			$statement->free();
		}

		//open and read Onset data file
		$data_handle = fopen($hobo_addr,"r") ;
		if (!$data_handle) {
			fclose($archive_handle) ;
			$specialmsg = "Onset_" . $name . " not found" ;
			return FALSE;
		}
		$ons_indxcol = -1 ;
		$ons_datecol = -1 ;
		$ons_salicol = -1 ;
		$ons_cdomcol = -1 ;
		$ons_clorcol = -1 ;
		$ons_wtmpcol = -1 ;
		$ons_atmpcol = -1 ;
		$ons_windcol = -1 ;
		$ons_gustcol = -1 ;
		$ons_wdircol = -1 ;
		$ons_bdircol = -1 ;
		$ons_prescol = -1 ;
		$ons_rhumcol = -1 ;
		$ons_dewpcol = -1 ;
		$ons_pharcol = -1 ;
		$ons_raincol = -1 ;
		$ons_turbcol = -1 ;
		$ons_battcol = -1 ;
		//read Onset file
		while (!feof($data_handle)) {
			$line = fgets ($data_handle,1024);
			if (substr($line,0,12)=="------------") {
				//get column #'s
				$line = fgets ($data_handle,1024) ;
				$headers = explode("\",\"",$line) ;
				$count = 0 ;
				foreach ($headers as $value) {
					switch (substr(trim($value),0,6)) {
						case "\"#":$ons_indxcol=$count ; break ;
						case "Time, ":$ons_datecol=$count ; break ;
						case "Salini":$ons_salicol=$count ; break ;
						case "CDOM, ":$ons_cdomcol=$count ; break ;
						case "Chloro":$ons_clorcol=$count ; break ;
						case "Water ":$ons_wtmpcol=$count ; break ;
						case "Air Te":$ons_atmpcol=$count ; break ;
						case "Wind S":$ons_windcol=$count ; break ;
						case "Gust S":$ons_gustcol=$count ; break ;
						case "Wind D":$ons_wdircol=$count ; break ;
						case "Buoy h":$ons_bdircol=$count ; break ;
						case "Pressu":$ons_prescol=$count ; break ;
						case "RH, %":$ons_rhumcol=$count ; break ;
						case "DewPt,":$ons_dewpcol=$count ; break ;
						case "PAR, u":$ons_pharcol=$count ; break ;
						case "Rain, ":$ons_raincol=$count ; break ;
						case "Turbid":$ons_turbcol=$count ; break ;
						case "Batt, ":$ons_battcol=$count ; break ;
						case "Voltag":break ;
						case "Curren":break ;
						default: $specialmsg = " Unmatched field in (" . $name . " Onset file)" ;
					}
					$count = $count + 1 ;
				}
				if (min($ons_datecol,$ons_atmpcol,$ons_windcol) <= -1) {
					fclose($data_handle) ;
					$rawdb->close();
					$specialmsg = "No headers in Onset data file_" . $name  ;
					return FALSE ;
				}
				//read data
				$salifault = 0 ;
				$cdomfault = 0 ;
				$turbfault = 0 ;
				while (!feof($data_handle)) {
					$line = fgets ($data_handle,1024);
					$datavals = explode(",",$line) ;
					if (strlen($line) > 10) {
						$thistime = strtotime($datavals[$ons_datecol]) ;
						if (($thistime > $arc_lastdate) && ($datavals[$ons_indxcol] != "")) {
							//correct salinity-cdom sensor errors - replaces missing values with previous values
							$corrected = FALSE ;
							if (($station_type == "cdombuoy")) { 
								if (($datavals[$ons_salicol] < 0) && ($prevsali > 0) && ($salifault < 5)) {
									$datavals[$ons_salicol] = $prevsali ;
									$salifault = $salifault + 1 ;
									$corrected = TRUE ;
								} else {
									$salifault = 0 ;
								}
							}
							if ($corrected == TRUE) {$corrections = $corrections + 1;}
							//write to archive
							$thistimestring = strftime("%Y-%m-%d %H:%M:%S", $thistime);
							$rawdb->query("INSERT INTO $datatable (site,datetime,watertemp_F,pressure_inHg,par_uE,rain_in,airtemp_F,relhumidity_pct,dewpoint_F,windspeed_mph,gustspeed_mph,winddir_deg,battery_V) VALUES 	('$site',TIMESTAMP('$thistimestring'),trim($datavals[$ons_wtmpcol]),trim($datavals[$ons_prescol]),trim($datavals[$ons_pharcol]),trim($datavals[$ons_raincol]),trim($datavals[$ons_atmpcol]),trim($datavals[$ons_rhumcol]),trim($datavals[$ons_dewpcol]),trim($datavals[$ons_windcol]),trim($datavals[$ons_gustcol]),trim($datavals[$ons_wdircol]),trim($datavals[$ons_battcol]))"); //trim removes hidden \r\n
						}
					}	
				}	
			}
		}
		fclose($data_handle) ;
		$rawdb->close();
		return TRUE ;
}

function archive_USGSflow($name) {
		//Reads data file from USGS flow gauge and updates local database archive
		$dbkeys = read_key("dbuser");
		$dbuser = $dbkeys[0]; $dbpw = $dbkeys[1];
		
		global $specialmsg, $corrections ;
		$specialmsg = "" ;
		$corrections = 0 ;
		$site = "";
		$sitenum = "";
		switch ($name) {
			case "Waltham": 
				$site="CharlesWalthamUSGS" ;
				$sitenum = "01104500";
				break ;
		}
		
		//query database to get last date
		$arc_lastdate = mktime(0, 0, 0, 0, 0, 0);
		$datatable = "crwa_notification.rawdata";
		$rawdb = new mysqli("notification.crwa.org", $dbuser, $dbpw, "crwa_notification");
		if ($rawdb->connect_error) {
    		$specialmsg = 'Connect Error: ' . $rawdb->connect_error;
			return FALSE;
		}
		if ($statement = $rawdb->query("SELECT datetime FROM $datatable WHERE site='$site' ORDER BY datetime DESC LIMIT 1")){
			$row = $statement->fetch_assoc();
			$arc_lastdate = strtotime($row['datetime']);
			$statement->free();
		}

		//open and read USGS data
		$starttime = max(mktime(date("G",$arc_lastdate)+1, 0, 0, date("m",$arc_lastdate), date("d",$arc_lastdate), 
			date("Y",$arc_lastdate)), mktime(0, 0, 0, date("m"), date("d")-100, date("Y")));
		$endtime = mktime(date("G"), 0, 0, date("m"), date("d"), date("Y")) ; //hour required for starttime <= endtime logic
		if ($starttime <= $endtime) {
			$usg_handle = fopen("http://waterdata.usgs.gov/nwis/uv?cb_00060=on&format=rdb&period=&begin_date=" . 
				date('Y\-m\-d',$starttime) . "&end_date=" . date('Y\-m\-d',$endtime) . "&site_no=" . $sitenum,"r");
			if (!$usg_handle) {
				fclose($flowarch_handle) ;
				return FALSE;
			}
			while (!feof($usg_handle)) {
				$line = fgets ($usg_handle,1024);
				if ((substr($line,0,1)!="#") && (strlen($line) > 10)) {
					//get column numbers
					$headers = explode("\t",$line) ;
					$count = 0 ;
					$usg_flowcol = -1 ;
					$usg_datecol = -1 ;
					foreach ($headers as $value) {
						switch (trim($value)) {
							case "datetime": $usg_datecol = $count ; break ;
							case "01_00060": $usg_flowcol = $count ; break ;
						}
						$count = $count + 1 ;
					}
					if (min($usg_flowcol,$usg_datecol) <= -1) {
						fclose($usg_handle) ;
						fclose($flowarch_handle) ;
						$specialmsg = "Flowgauge columns not identified" ;
						return FALSE ;
					}
					//read data
					$trgthour = mktime(0,0,0,0,0,0) ;
					$flow = 0 ;
					$avgcount = 1 ;
					while (!feof($usg_handle)) {
						$line = fgets ($usg_handle,1024) ;
						if (substr($line,0,4) == "USGS") {
							$datavals = explode("\t",$line) ;
							$thistime = strtotime($datavals[$usg_datecol]) ;
							if (($thistime > $arc_lastdate) && ($datavals[$usg_flowcol] != "")) {
								$thistimestring = strftime("%Y-%m-%d %H:%M:%S", $thistime);
								$rawdb->query("INSERT INTO $datatable (site,datetime,flow_cfs) VALUES 	('$site',TIMESTAMP('$thistimestring'),trim($datavals[$usg_flowcol]))"); //trim removes hidden \r\n
							}
						}
					}
				}
			}
			fclose($usg_handle) ;
		}
		$rawdb->close();
		return TRUE ;
}

function runflaglogic($ModReach2,$ModReach3,$ModReach4,$ModReach5,$log_R2,$log_R3,$log_R4,$lin_LF,$log_LF,$cyano,$cso,$latest_cyano,$latest_cso) {
	//function runflaglogic($upper,$lin_NBBU,$log_NBBU,$lin_LF,$log_LF,$cyano,$cso,$latest_cyano,$latest_cso) {
		//identifies which flag to fly based on model output and events
		$flag = "blue"; 
		$cause = "";
		//if ($upper) {
			//if (($lin_NBBU >= 630) || ($log_NBBU >= 0.5)) {
			//$flag = "red" ; $cause = "model";
			//}
		//} else {
			//if (($lin_LF >= 630) || ($log_LF >= 0.5)) {
			//	$flag = "red" ; $cause = "model";
			//}	
		//}
			
			
		//update 2015 - Test 
		if ($ModReach2) {
			if ($log_R2 >= 0.5) {
				$flag = "red" ; $cause = "model";
			}
		} elseif ($ModReach3) {
			if($log_R3 >= 0.5) {
				$flag = "red" ; $cause = "model";
			}
		} elseif ($ModReach4) {
			if($log_R4 >= 0.5) {
				$flag = "red" ; $cause = "model";
			}
		} else {
			if (($lin_LF >= 630) || ($log_LF >= 0.5)) {
				$flag = "red" ; $cause = "model";
			}
		}
			
		
		if (($cyano == 1) && ($flag == "blue")) {
			$flag = "yellow" ; $cause = "cyano";
		}	
		if ($cso == 1) {
			$flag = "red" ; $cause = "cso";
		}
		//Overwrite flags with current algae or CSO event, if new event since model update
		if (($latest_cyano == 1) && ($flag == "blue")) {
			$flagimg = "yellow" ;  $cause = "cyano";
		}	
		if ($latest_cso == 1) {
			$flagimg = "red" ; $cause = "cso";
		}
		
		$flaginfo = array($flag,$cause);
		return $flaginfo;
}

function read_key($keytype){
		//reads keys from keys.txt file
		switch ($keytype) {
			case "dbuser": 
				$dbuser; $dbpw;
				$key_handle = fopen("../scripts/keys.txt","r");
				while (!feof($key_handle)) {
					$line = fgets ($key_handle,1024) ;
					$parts = explode(",",$line) ;
					if ($parts[0] == $keytype){
						$dbuser = decipher_key(trim($parts[1]));
						$dbpw = decipher_key(trim($parts[2]));
					}
				}
				fclose($key_handle) ;
				$keys = array($dbuser,$dbpw);
				return $keys;
				break;
			case "adminkey":
				$adminkey;
				$key_handle = fopen("../scripts/keys.txt","r");
				while (!feof($key_handle)) {
					$line = fgets ($key_handle,1024) ;
					$parts = explode(",",$line) ;
					if ($parts[0] == $keytype){
						$adminkey = decipher_key(trim($parts[1]));
					}
				}			
				fclose($key_handle) ;
				return $adminkey;
				break;
		}
}

function decipher_key($key) {
		$oldltrs = str_split($key);
		$newkey;
		$newltrs = array(); $count = 0;
		if ($oldltrs[0] == "!"){
			foreach($oldltrs as $ltr) {
				if ($count > 0) {
					$newltrs[$count-1] = chr(ord($ltr)-2);
				}
				$count++;
			}
			$newkey = implode($newltrs);
		} else {
			$newkey = $key;
		}
		return $newkey;
}

function check_keys() {
		//checks keys.txt file to confirm that keys are encrypted
		$key_handle = fopen("../scripts/keys.txt","r");
		$lines = array(); $linecount = 0;
		$changed = FALSE;
		while (!feof($key_handle)) {
			$lines[$linecount] = fgets($key_handle,1024) ;
			$parts = explode(",",$lines[$linecount]) ;
			$count = 0 ;
			foreach ($parts as $prt) {
				if ($count > 0) {
					if (substr(trim($prt),0,1) != "!") {
						$parts[$count] = encode_key(trim($prt));
						$changed = TRUE;
					}
				}
				$count++;
			}
			$lines[$linecount] = implode(",",$parts);
			$linecount++;
		}
		fclose($key_handle) ;
		if ($changed == TRUE) {
			$key_handle = fopen("../scripts/keys.txt","w");
			foreach($lines as $ln) {
				if (trim($ln) != "") {
					fwrite($key_handle,trim($ln) . "\r\n") ;				
				}
			}
			fclose($key_handle);
		}
}

function encode_key($key) {
		$oldltrs = str_split($key);
		$newltrs = array(); $count = 0;
		foreach($oldltrs as $ltr) {
			$newltrs[$count] = chr(ord($ltr)+2);
			$count++;
		}
		$newkey = "!" . implode($newltrs);
		return $newkey;
}

?>