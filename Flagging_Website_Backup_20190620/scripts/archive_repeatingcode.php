<?php
// Repeated archiving code for U30s and other data sources - to mySQL database
// Version 3 :: Ben Wetherill :: 1/20/15
// Updated by Hong Minh on May 2015 with new models and new reaches.
// Updated by Ben Wetherill with logger transition 6/29/15  removed battery field 7/11/15
// Updated by Ben Wetherill with permanent Onset RX3000 ReST solution 2/25/17
// Updated by Ben Wetherill with new label for air temperature field 9/25/18
//-------------------------------------------------------------------------------------------

date_default_timezone_set('America/New_York');

function archive_OnsetRX3000($name,$repost) {
		//Reads data file from Onset RX3000 and updates local database archive
		$dbkeys = read_key("dbuser");
		$dbuser = $dbkeys[0]; $dbpw = $dbkeys[1];

		global $specialmsg, $corrections ;
		$specialmsg = "" ;
		$corrections = 0 ;
		$site = "";
		$hobo_addr = ""; $hobouser; $hobopw; $token; $url; $query;
		$paramlist;
		switch ($name) {
			case "CommunityBoating":
				$site="CharlesCB" ;
				$paramlist = "watertemp_F,pressure_inHg,par_uE,rain_in,airtemp_F,relhumidity_pct,
					dewpoint_F,windspeed_mph,gustspeed_mph,winddir_deg";
				if ($repost == FALSE){
					//use standard ReST url
					$hobokeys = read_key("hobouser");
					$hobouser = $hobokeys[0]; $hobopw = $hobokeys[1];
					$token = read_key("hobotoken");
					$url = "http://webservice.hobolink.com/restv2/data/custom/file";
					$query = "ReST_Query" ;
				} else {
					//use newest file in commboating_files directory - must be in "HOBOware CSV" format
					date_default_timezone_set('America/New_York');
					$path = "../commboating_files";
					$d = dir($path);
					$filelist = array();
					$indx = 0;
					while (false != ($entry = $d->read())) {
						$filepath = $path . "/" . $entry;
						if (is_file($filepath)) {
							$filelist[$indx] = array(filemtime($filepath),$filepath);
							$indx++ ;
						}
					}
					rsort($filelist);
					$hobo_addr=$filelist[0][1];
				}
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
		$ons_indxcol = -1 ;
		$ons_datecol = -1 ;
		$ons_wtmpcol = -1 ;
		$ons_atmpcol = -1 ;
		$ons_windcol = -1 ;
		$ons_gustcol = -1 ;
		$ons_wdircol = -1 ;
		$ons_prescol = -1 ;
		$ons_rhumcol = -1 ;
		$ons_dewpcol = -1 ;
		$ons_pharcol = -1 ;
		$ons_raincol = -1 ;
		$ons_battcol = -1 ;  //no battery column with RX3000
		if ($repost == FALSE){
			//use standard ReST url
			$curl_post_data = "{\"query\":\"".$query."\",\"authentication\":{\"password\":\"".$hobopw.
								"\",\"user\":\"".$hobouser."\",\"token\":\"".$token."\"}}";
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
			curl_setopt($curl, CURLOPT_HTTPHEADER,
				array('Content-type: application/json','Content-length: '.strlen($curl_post_data)));
			$curl_response = curl_exec($curl);
			if (($curl_response === false)||($curl_response=="")) {
				curl_close($curl);
				$specialmsg = "Onset_" . $name . " not found" ;
				return FALSE;
			}
			curl_close($curl);
			$finishedMeta = FALSE;
			$gotHeader = FALSE;
			foreach(preg_split("/((\r?\n)|(\r\n?))/",$curl_response,-1,PREG_SPLIT_NO_EMPTY) as $line){
				if ($finishedMeta) {
					if (!$gotHeader){
						//get column #'s
						$headers = explode("\",\"",$line) ;
						$count = 0 ;
						foreach ($headers as $value) {
							if ($count > 11) { //needed because Onset is creating some duplicate empty fields
								break;
							}
							switch (substr(trim($value),0,6)) {
								case "\"#":$ons_indxcol=$count ; break ;
								case "Time, ":$ons_datecol=$count ; break ;
								case "Water ":$ons_wtmpcol=$count ; break ;
								case "Temp, ":$ons_atmpcol=$count ; break ;
								case "Wind S":$ons_windcol=$count ; break ;
								case "Gust S":$ons_gustcol=$count ; break ;
								case "Wind D":$ons_wdircol=$count ; break ;
								case "Pressu":$ons_prescol=$count ; break ;
								case "RH, %,":$ons_rhumcol=$count ; break ;
								case "DewPt,":$ons_dewpcol=$count ; break ;
								case "PAR, u":$ons_pharcol=$count ; break ;
								case "Rain, ":$ons_raincol=$count ; break ;
								case "Batt, ":$ons_battcol=$count ; break ;
								case "Voltag":break ;
								case "Curren":break ;
								default: $specialmsg = " Unmatched field in (" . $name . " Onset file)" ;
							}
							$count = $count + 1 ;
						}
						if (min($ons_datecol,$ons_atmpcol,$ons_windcol) <= -1) {
							$sensordb->close();
							$specialmsg = "No headers in Onset data file_" . $name  ;
							return FALSE ;
						}
						$gotHeader = TRUE;
					}
					//read data
					read_ToDB($rawdb,$paramlist,$name,$line,$arc_lastdate,$datatable,$site,$ons_indxcol,$ons_datecol,$ons_wtmpcol,$ons_atmpcol,$ons_windcol,$ons_gustcol,$ons_wdircol,$ons_prescol,$ons_rhumcol,$ons_dewpcol,$ons_pharcol,$ons_raincol,$ons_battcol);
				}
				if (substr($line,0,12)=="------------") {
					$finishedMeta = TRUE;
				}
			}
		} else {
			//use newest file in commboating_files directory - must be in "HOBOware CSV" format
			//need to manually manipulate index col label so that it has quotation marks around it
			$data_handle = fopen($hobo_addr,"r") ;
			if (!$data_handle) {
				fclose($archive_handle) ;
				$specialmsg = "Onset_" . $name . " not found" ;
				return FALSE;
			}
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
							case "Water ":$ons_wtmpcol=$count ; break ;
							case "Temp, ":$ons_atmpcol=$count ; break ;
							case "Wind S":$ons_windcol=$count ; break ;
							case "Gust S":$ons_gustcol=$count ; break ;
							case "Wind D":$ons_wdircol=$count ; break ;
							case "Pressu":$ons_prescol=$count ; break ;
							case "RH, %,":$ons_rhumcol=$count ; break ;
							case "DewPt,":$ons_dewpcol=$count ; break ;
							case "PAR, u":$ons_pharcol=$count ; break ;
							case "Rain, ":$ons_raincol=$count ; break ;
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
						$specialmsg = "No headers in uploaded data file_" . $name  ;
						return FALSE ;
					}
					//read data
					while (!feof($data_handle)) {
						$line = fgets ($data_handle,1024);
						read_ToDB($rawdb,$paramlist,$name,$line,$arc_lastdate,$datatable,$site,$ons_indxcol,$ons_datecol,$ons_wtmpcol,$ons_atmpcol,$ons_windcol,$ons_gustcol,$ons_wdircol,$ons_prescol,$ons_rhumcol,$ons_dewpcol,$ons_pharcol,$ons_raincol,$ons_battcol);
					}
				}
			}
			fclose($data_handle) ;
		}
		$rawdb->close();
		return TRUE ;
}

function read_ToDB($rawdb,$paramlist,$name,$line,$arc_lastdate,$datatable,$site,$ons_indxcol,$ons_datecol,$ons_wtmpcol,$ons_atmpcol,$ons_windcol,$ons_gustcol,$ons_wdircol,$ons_prescol,$ons_rhumcol,$ons_dewpcol,$ons_pharcol,$ons_raincol,$ons_battcol) {
		$datavals = explode(",",$line) ;
		if (strlen($line) > 10) {
			$thistime = strtotime($datavals[$ons_datecol]) ;
			if (($thistime > $arc_lastdate) && ($datavals[$ons_indxcol] != "")) {
				$thistimestring = strftime("%Y-%m-%d %H:%M:%S", $thistime);
				$vallist;
				switch ($name) {
					case "CommunityBoating":
						$vallist = trim($datavals[$ons_wtmpcol]).",".trim($datavals[$ons_prescol]).",".trim($datavals[$ons_pharcol]).",".trim($datavals[$ons_raincol]).",".trim($datavals[$ons_atmpcol]).",".trim($datavals[$ons_rhumcol]).",".trim($datavals[$ons_dewpcol]).",".trim($datavals[$ons_windcol]).",".trim($datavals[$ons_gustcol]).",".trim($datavals[$ons_wdircol]);
						break;
				}
				return $rawdb->query("INSERT INTO $datatable (site,datetime,". $paramlist .") VALUES
					('$site',TIMESTAMP('$thistimestring'),". $vallist .")");
			}
		}
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
				$rawdb->close();
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
							case "66190_00060": $usg_flowcol = $count ; break ;
						}
						$count = $count + 1 ;
					}
					if (min($usg_flowcol,$usg_datecol) <= -1) {
						fclose($usg_handle) ;
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

function runflaglogic($ModReach,$log_R2,$log_R3,$log_R4,$lin_LF,$log_LF,$cyano,$cso,$latest_cyano,$latest_cso) {
		//identifies which flag to fly based on model output and events
		$flag = "blue";
		$cause = "";

		//update 2015
		switch ($ModReach) {
			case 2:
				if ($log_R2 >= 0.65) {
					$flag = "red" ; $cause = "model";
				}
				break;
			case 3:
				if($log_R3 >= 0.65) {
					$flag = "red" ; $cause = "model";
				}
				break;
			case 4:
				if($log_R4 >= 0.65) {
					$flag = "red" ; $cause = "model";
				}
				break;
			case 5:
				if (($lin_LF >= 630) || ($log_LF >= 0.5)) {
					$flag = "red" ; $cause = "model";
				}
				break;
		}

		if (($cyano == 1) && ($flag == "blue")) {
			$flag = "red" ; $cause = "cyano";
		}
		if ($cso == 1) {
			$flag = "red" ; $cause = "cso";
		}
		//Overwrite flags with current algae or CSO event, if new event since model update
		if (($latest_cyano == 1) && ($flag == "blue")) {
			$flagimg = "red" ;  $cause = "cyano";
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
			case "hobouser":
				$user; $pw;
				$key_handle = fopen("../scripts/keys.txt","r");
				while (!feof($key_handle)) {
					$line = fgets ($key_handle,1024) ;
					$parts = explode(",",$line) ;
					if ($parts[0] == $keytype){
						$user = decipher_key(trim($parts[1]));
						$pw = decipher_key(trim($parts[2]));
					}
				}
				fclose($key_handle) ;
				$keys = array($user,$pw);
				return $keys;
				break;
			case "adminkey":
			case "hobotoken":
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
