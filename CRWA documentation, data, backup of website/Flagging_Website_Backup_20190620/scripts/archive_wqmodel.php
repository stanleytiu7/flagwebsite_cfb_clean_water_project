<?php
// Water quality model archiving and calculations for Charles River - using MySQL database.
// Version 2 :: Ben Wetherill :: 1/20/15 
// Updated by Hong Minh on May 2015 with new models and new reaches.
// Updated to correct issue in read_boatingmodel by Ben Wetherill 6/29/15
//-------------------------------------------------------------------------------------------
// 2015 model update
// info on data time units: 
// data from CESN are provided on CESN website for every 10 minutes
// flow data are provided from USGS Waltham river flow gauge with a daily mean discharge average in cfs

chdir(dirname(__FILE__));
require_once('archive_repeatingcode.php');//2015 update
date_default_timezone_set('America/New_York');
$specialmsg = "" ;
$corrections = 0 ;

function archive_wqmodel($repost) {
		//runs each of the archiving and model calc jobs and updates archiving log
		set_time_limit(250);
		check_keys();
		$log_handle = fopen("../backend/archivelog.txt","a+");
			fwrite($log_handle,"\r\n" . date("Y-m-d H:i:s") . "," . "WQModel" . ",") ;
		echo "Start " . date("Y-m-d H:i:s") . "<br/>";
		global $specialmsg, $corrections ;
		$return_val = TRUE ;
		
			if (archive_OnsetRX3000("CommunityBoating",$repost) == FALSE) {
				$return_val = FALSE ;
				if ($specialmsg == "") {$specialmsg = " Error in (archive_OnsetRX3000(Community Boating).";}
			}
			fwrite($log_handle,"C." . $corrections . "." . $specialmsg . ",") ;
			
			if (archive_USGSflow("Waltham") == FALSE) {
				$return_val = FALSE ;
				if ($specialmsg == "") {$specialmsg = " Error in (archive_USGSflow(Waltham))." ;}
			}
			fwrite($log_handle,"W." . $corrections . "." . $specialmsg . ",") ;
		
			if (archive_boatingmodel() == FALSE) {
				$return_val = FALSE ;
				if ($specialmsg == "") {$specialmsg = " Error in (archive_boatingmodel)." ;}
			}
			fwrite($log_handle,"BM." . $specialmsg . ",") ;
		
		if ($return_val==TRUE) {
			echo "Archiving job finished successfully. " . date("H:i:s") ;
			echo $specialmsg ;
			fwrite($log_handle,"Archiving job finished successfully. " . date("H:i:s")) ;
		} else {
			echo "Archiving job did not complete." ;
			echo $specialmsg ;
			fwrite($log_handle,"Archiving job did not complete. " . $specialmsg . "," . date("H:i:s")) ;
		}
		fclose($log_handle) ;
		return $return_val ;
}

function archive_boatingmodel() {
		//prepares data for CRWA's flags - boating limit, and stores in datatable "modeldata"
		$dbkeys = read_key("dbuser");
		$dbuser = $dbkeys[0]; $dbpw = $dbkeys[1];
		
		global $specialmsg ;
		$specialmsg = "" ;
		$datatable = "crwa_notification.rawdata";
		$eventtable = "crwa_notification.eventdata";
		$modeltable = "crwa_notification.modeldata";
		$wqdb = new mysqli("notification.crwa.org", $dbuser, $dbpw, "crwa_notification");
		if ($wqdb->connect_error) {
    		$specialmsg = 'Connect Error: ' . $wqdb->connect_error;
			return FALSE;
		}
		
		//query database to get previous model calculations and date of last calculation
		$loc = "Charles";
		$mdl_lastdate = mktime(0, 0, 0, 0, 0, 0);
		$mdl_recalc = 15 ; //number of days back that will always be recalculated
		$timeset = array() ;
		$wtmpset = array() ;
		$atmpset = array(); //2015 update = air temperature
		$rainset = array() ;
		$daysset = array() ; // 2015 update : will refer to number of days since last rain greater than 0.2” instead of 0.1""
		$windset = array() ;
		$flowset = array() ;
		$parset = array() ;
		$cso_CP_set = array() ;
		$cyano_NYC_set = array() ;
		$cyano_WYC_set = array() ;
		$cyano_CR_set = array() ;
		$cyano_CRCK_set = array() ;
		$cyano_HW_set = array() ;
		$cyano_RBC_set = array() ;
		$cyano_CRYC_set = array() ;
		$cyano_UBC_set = array() ;
		$cyano_CB_set = array() ;
		$cyano_CRCKK_set = array() ;
		//Following required in case there is an empty value in index 0, otherwise 4699 will be entered
		$wtmpset[0] = NULL ; 
		$atmpset[0]= NULL; //2015 update
		$rainset[0] = NULL ;  
		$daysset[0] = NULL ; 
		$windset[0] = NULL ;  
		$flowset[0] = NULL ;  
		$parset[0] = NULL ;  
		$cso_CP_set[0] = NULL ;  
		$cyano_NYC_set[0] = NULL ;  
		$cyano_WYC_set[0] = NULL ;  
		$cyano_CR_set[0] = NULL ;  
		$cyano_CRCK_set[0] = NULL ;  
		$cyano_HW_set[0] = NULL ;  
		$cyano_RBC_set[0] = NULL ;  
		$cyano_CRYC_set[0] = NULL ;  
		$cyano_UBC_set[0] = NULL ;  
		$cyano_CB_set[0] = NULL ;  
		$cyano_CRCKK_set[0] = NULL ;  
		$arraycount = 0 ;
		$stopdate = strftime("%Y-%m-%d %H:%M:%S",mktime(0, 0, 0, date("m"), date("d")-$mdl_recalc, date("Y")));
		//update 2015 
		$statement = $wqdb->query("SELECT datetime,watertemp_C,airtemp_C,rain_in,raindays,windspeed_mph,flow_cfs,par_uE,cso_CP,cyano_NewtonYC,cyano_WatertownYC,cyano_CommRowing,cyano_CRCK,cyano_HarvardWeld,cyano_RiversideBC,cyano_CRYC,cyano_UnionBC,cyano_CommBoating,cyano_CRCKKendall FROM $modeltable WHERE location='$loc' AND datetime<TIMESTAMP('$stopdate') ORDER BY datetime");
		//$statement = $wqdb->query("SELECT datetime,watertemp_C,rain_in,raindays,windspeed_mph,flow_cfs,par_uE,cso_CP,cyano_NewtonYC,cyano_WatertownYC,cyano_CommRowing,cyano_CRCK,cyano_HarvardWeld,cyano_RiversideBC,cyano_CRYC,cyano_UnionBC,cyano_CommBoating,cyano_CRCKKendall FROM $modeltable WHERE location='$loc' AND datetime<TIMESTAMP('$stopdate') ORDER BY datetime");
		while($row = $statement->fetch_assoc()){
			$timeset[$arraycount] = strtotime($row['datetime']) ;
			$wtmpset[$arraycount] = $row['watertemp_C'] ; //already in Celsius
			$atmpset[$arraycount] = $row['airtemp_C'] ; //already in Celsius ? 2015 update
			$rainset[$arraycount] = $row['rain_in'] ;
			$daysset[$arraycount] = $row['raindays'] ;
			$windset[$arraycount] = $row['windspeed_mph'] ;
			$flowset[$arraycount] = $row['flow_cfs'] ;
			$parset[$arraycount] = $row['par_uE'] ;
			$cso_CP_set[$arraycount] = $row['cso_CP'] ;
			$cyano_NYC_set[$arraycount] = $row['cyano_NewtonYC'] ;
			$cyano_WYC_set[$arraycount] = $row['cyano_WatertownYC'] ;
			$cyano_CR_set[$arraycount] = $row['cyano_CommRowing'] ;
			$cyano_CRCK_set[$arraycount] = $row['cyano_CRCK'] ;
			$cyano_HW_set[$arraycount] = $row['cyano_HarvardWeld'] ;
			$cyano_RBC_set[$arraycount] = $row['cyano_RiversideBC'] ;
			$cyano_CRYC_set[$arraycount] = $row['cyano_CRYC'] ;
			$cyano_UBC_set[$arraycount] = $row['cyano_UnionBC'] ;
			$cyano_CB_set[$arraycount] = $row['cyano_CommBoating'] ;
			$cyano_CRCKK_set[$arraycount] = $row['cyano_CRCKKendall'] ;
			$arraycount++ ;
			$mdl_lastdate = strtotime($row['datetime']) ;
		}
		$statement->free();

		//define variables for start and end time of new calculations and populate dates in array
		$starttime = max(mktime(date("G",$mdl_lastdate)+1, 0, 0, date("m",$mdl_lastdate), date("d",$mdl_lastdate), 
			date("Y",$mdl_lastdate)), 
			mktime(0, 0, 0, date("m"), date("d")-$mdl_recalc-100, date("Y")));
		$endtime = mktime(date("G"), 0, 0, date("m"), date("d"), date("Y")) ;
		$startcount = $arraycount ;
		for ($i=$startcount;$i<=$startcount + round(($endtime - $starttime) / (60*60));$i++) { 
			$timeset[$i] = $starttime + ($i-$startcount) * (60*60) ; //creates array row for every 1 hour
			//following required to make sure arrays are all of same offset for array_slice()
			$wtmpset[$i] = NULL ;
			$atmpset[$i] = NULL ; //update 2015
			$rainset[$i] = NULL ;
			$daysset[$i] = NULL ;
			$windset[$i] = NULL ;
			$flowset[$i] = NULL ;
			$parset[$i] = NULL ;
			$cso_CP_set[$i] = 0 ;
			$cyano_NYC_set[$i] = 0 ;
			$cyano_WYC_set[$i] = 0 ;
			$cyano_CR_set[$i] = 0 ;
			$cyano_CRCK_set[$i] = 0 ;
			$cyano_HW_set[$i] = 0 ;
			$cyano_RBC_set[$i] = 0 ;
			$cyano_CRYC_set[$i] = 0 ;
			$cyano_UBC_set[$i] = 0 ;
			$cyano_CB_set[$i] = 0 ;
			$cyano_CRCKK_set[$i] = 0 ;
			$arraycount++ ;
		}	
		
		//query database to get new weather station data for model arrays
		$wtmp = 0 ;
		$atmp = 0 ; // 2015 update
		$wind = 0 ;
		$rain = 0 ;
		$par = 0 ;
		$days = 0 ;
		$raincum = 0 ; //test update 2015
		if ($startcount==0) {
			$days = 0 ;
		} else {
			$days = $daysset[$startcount-1] ;
		}	
		//test update 2015
		//if ($startcount==0) {
			//$raincum = 0 ;
		//} else {
			//$raincum = $daysset[$startcount-1] ;
		//}	
		
		$avgcount = 1 ;
		$trgthour = mktime(0,0,0,0,0,0) ;
		$site = "CharlesCB";
		$starttimestring = strftime("%Y-%m-%d %H:%M:%S", $starttime);
		// 2015 update
		$statement = $wqdb->query("SELECT datetime,watertemp_F,airtemp_F, rain_in,windspeed_mph,par_uE FROM $datatable WHERE site='$site' AND datetime>=TIMESTAMP('$starttimestring') ORDER BY datetime");
		// $statement = $wqdb->query("SELECT datetime,watertemp_F,rain_in,windspeed_mph,par_uE FROM $datatable WHERE site='$site' AND datetime>=TIMESTAMP('$starttimestring') ORDER BY datetime");
		while($row = $statement->fetch_assoc()){
			$thistime = strtotime($row['datetime']) ;	
			$thishour = mktime(date("G",$thistime),0,0,date("m",$thistime),date("d",$thistime),date("Y",$thistime)) ;
			if (($thishour >= $starttime) && ($thishour <= $endtime)) {
				if ($thishour == $trgthour) {
					$wtmp = $wtmp + $row['watertemp_F'] ;
					$atmp = $atmp + $row['airtemp_F'] ; // 2015 update
					$wind = $wind + $row['windspeed_mph'] ;
					$rain = $rain + $row['rain_in'] ;
					$par = $par + $row['par_uE'] ;
					$avgcount = $avgcount + 1 ;
				} else {
					if ($trgthour > mktime(0,0,0,0,0,0)) {
						$indx = array_search($trgthour,$timeset) ;
						if ($indx !== FALSE) {
							$wtmpset[$indx] = round((($wtmp/$avgcount)-32)*5/9,3) ; //converted to Celsius
							$atmpset[$indx] = round((($atmp/$avgcount)-32)*5/9,3) ; // 2015 update - converted to Celsius
							$windset[$indx] = round($wind/$avgcount,2) ;
							$rainset[$indx] = $rain ;	
							$parset[$indx] = round($par/$avgcount,0) ;
							
							//if (array_sum(array_slice($rainset,$indx-23,24))>=0.1) {
							//	$days = 0 ;
							//} else {
							//	$days = $days + 1/24 ;
							//}
							//$daysset[$indx] = $days ;
							
							//update 2015 - test $raincum to define earlier
							if($rainset[$indx] > 0){
							$raincum = $raincum + $rainset[$indx];
							}
								else{
									$raincum = 0 ;	
								}
								
							if($raincum > 0.2){
								$days = 0;
							} else {$days = $days + 1/24 ;
							}
							$daysset[$indx] = $days ;
						}							
					}
					$trgthour = $thishour ;
					$wtmp = $row['watertemp_F'] ;
					$atmp = $row['airtemp_F'] ; //2015 update
					$wind = $row['windspeed_mph'] ;
					$rain = $row['rain_in'] ;
					$par = $row['par_uE'] ;
					$avgcount = 1 ;
				}
			}
		}
		$statement->free();
		
		//query database to get new flow gauge data for model arrays
		$site = "CharlesWalthamUSGS";
		$flow = 0 ;
		$avgcount = 1 ;
		$trgthour = mktime(0,0,0,0,0,0) ;
		$statement = $wqdb->query("SELECT datetime,flow_cfs FROM $datatable WHERE site='$site' AND datetime>=TIMESTAMP('$starttimestring') ORDER BY datetime");
		while($row = $statement->fetch_assoc()){
			$thistime = strtotime($row['datetime']) ;	
			$thishour = mktime(date("G",$thistime),0,0,date("m",$thistime),date("d",$thistime),date("Y",$thistime)) ;
			if (($thishour >= $starttime) && ($thishour <= $endtime)) {
				if ($thishour == $trgthour) {
					$flow = $flow + $row['flow_cfs'] ;
					$avgcount = $avgcount + 1 ;
				} else {
					if ($trgthour > mktime(0,0,0,0,0,0)) {
						$indx = array_search($trgthour,$timeset) ;
						if ($indx !== FALSE) {
							$flowset[$indx] = round($flow/$avgcount,1) ;
						}
					}
					$trgthour = $thishour ;
					$flow = $row['flow_cfs'] ;
					$avgcount = 1 ;
				}
			}
		}
		$statement->free();
		
		//query database to get new CSO events
		$cso_hold = 48; //hours to retain CSO flag after CSO finished
		$csostartstring = strftime("%Y-%m-%d %H:%M:%S", $starttime-$cso_hold*60*60);
		$statement = $wqdb->query("SELECT location,startdate,enddate FROM $eventtable WHERE event='CSO' AND enddate>=TIMESTAMP('$csostartstring') ORDER BY startdate");
		while($row = $statement->fetch_assoc()){
			$firsttime = max(strtotime($row['startdate']),$starttime) ;
			$firsttime = mktime(date("G",$firsttime),ceil(intval(date("i",$firsttime))/60)*60,0,date("m",$firsttime),date("d",$firsttime),date("Y",$firsttime));
			$lasttime = min(strtotime($row['enddate']),$endtime+1) ;
			for($t=$firsttime;$t<$lasttime+$cso_hold*60*60;$t=$t+60*60){
				$indx = array_search($t,$timeset) ;
				if ($indx !== FALSE) {
					switch ($row['location']) {
						case "CottagePark": $cso_CP_set[$indx] = 1; break;
					}	
				}
			}
		}
		$statement->free();
	
		//query database to get new cyanobacteria events
		$cyano_hold = 0; //hours to retain cyanobacteria flag after bloom finished
		$cyanostartstring = strftime("%Y-%m-%d %H:%M:%S", $starttime-$cyano_hold*60*60);
		$statement = $wqdb->query("SELECT location,startdate,enddate FROM $eventtable WHERE event='Cyanobacteria' AND enddate>=TIMESTAMP('$cyanostartstring') ORDER BY startdate");
		while($row = $statement->fetch_assoc()){
			$firsttime = max(strtotime($row['startdate']),$starttime) ;
			$firsttime = mktime(date("G",$firsttime),ceil(intval(date("i",$firsttime))/60)*60,0,date("m",$firsttime),date("d",$firsttime),date("Y",$firsttime));
			$lasttime = min(strtotime($row['enddate']),$endtime+1) ;
			for($t=$firsttime;$t<$lasttime+$cyano_hold*60*60;$t=$t+60*60){
				$indx = array_search($t,$timeset) ;
				if ($indx !== FALSE) {
					switch ($row['location']) {
						case "NewtonYC": $cyano_NYC_set[$indx] = 1; break;
						case "WatertownYC": $cyano_WYC_set[$indx] = 1; break;
						case "CommRowing": $cyano_CR_set[$indx] = 1; break;
						case "CRCK": $cyano_CRCK_set[$indx] = 1; break;
						case "HarvardWeld": $cyano_HW_set[$indx] = 1; break;
						case "RiversideBC": $cyano_RBC_set[$indx] = 1; break;
						case "CRYC": $cyano_CRYC_set[$indx] = 1; break;
						case "UnionBC": $cyano_UBC_set[$indx] = 1; break;
						case "CommBoating": $cyano_CB_set[$indx] = 1; break;
						case "CRCKKendall": $cyano_CRCKK_set[$indx] = 1; break;
					}	
				}
			}
		}
		$statement->free();

		//calculate model for full history and update model data in database
		if ($starttime <= $endtime) {
			$wqdb->query("DELETE FROM $modeltable WHERE 1");
			
			$lasttime = $timeset[0] - 60*60 ;
			$finalx ; $final_lg_R2 ; $final_lg_R3 ; $final_lg_R4; $final_li_LF; $final_lg_LF; $lastx ;
			//$finalx ; $final_li_NBBU ; $final_li_LF ; $final_lg_NBBU ; $final_lg_LF ; $lastx ;
			$rowcount = 0 ;
			for($x=0;$x<$arraycount;$x++) {
				if (is_null($rainset[$x])==FALSE && is_null($windset[$x])==FALSE && is_null($wtmpset[$x])==FALSE && is_null($atmpset[$x])==FALSE &&
					is_null($flowset[$x])==FALSE && is_null($daysset[$x])==FALSE && is_null($parset[$x])==FALSE) {
					$rowcount++ ;
					if (($timeset[$x] != $lasttime + 60*60)&&($timeset[$x] > $starttime)&&($rowcount > 1)) {
						$specialmsg = "hour missing in CR model data" . strftime("%Y-%m-%d %H:%M:%S", $timeset[$x]) ;
					}
					//$li_conc_NBBU = 0; 
					//$lg_prb_NBBU = 0; 
					$lg_prb_R2 = 0; $lg_prb_R3 = 0;$lg_prb_R4 = 0;
					$li_conc_LF = 0;$lg_prb_LF = 0;
					if ($rowcount >= 168) {  //need enough rows for all calculations 168=7 days
						//calculate model explanatory variables
						$rainD1 = array_sum(array_slice($rainset,$x-23,24)) ;
						$rainD2 = array_sum(array_slice($rainset,$x-47,48)) ;
						$rainD3 = array_sum(array_slice($rainset,$x-71,72)) ;
						$rainD7 = array_sum(array_slice($rainset,$x-167,168)) ;
						$flowD1 = array_sum(array_slice($flowset,$x-23,24))/24 ;
						$flowD2 = array_sum(array_slice($flowset,$x-47,48))/48 ; // 2015 updated - corrected
						$flowD4 = array_sum(array_slice($flowset,$x-95,96))/96 ; //2015 corrected
						$windD1 = array_sum(array_slice($windset,$x-23,24))/24 ;
						$wtmpD1 = array_sum(array_slice($wtmpset,$x-23,24))/24 ;  //already Celsius - converted above
						$atmpD1 = array_sum(array_slice($atmpset,$x-23,24))/24 ; // 2015 update - already Celsius - converted above
						$parD1 = array_sum(array_slice($parset,$x-23,24))/24 ;
						$parD2 = array_sum(array_slice($parset,$x-47,48))/48 ; // 2015 update
						
						//calculate model response values
						//$li_conc_NBBU = exp(2.34 - 0.068*$daysset[$x] + 0.12*$wtmpD1 + 0.29*log($flowD1) - 0.0021*$parD1) ;
						$li_conc_LF = exp(2.7144 + 0.65*log($flowD1) + 1.68*$rainD2 - 0.071*($wtmpD1) - 0.29*$rainD7 - 0.09*$windD1) ;
						
						//$lg_NBBU = 1.72 + 4.53*log($rainD2+0.0001) + 0.127*$wtmpD1 ;
						$lg_LF = -3.184 + 3.936*$rainD2 - 1.62*$rainD7 + 1.2798*log($flowD1) - 0.3397*$windD1 - 0.2112*($wtmpD1)						;
			
						//$lg_prb_NBBU = exp($lg_NBBU) / (1 + exp($lg_NBBU)) ;
						$lg_prb_LF = exp($lg_LF) / (1 + exp($lg_LF)) ;
						
						//calculate E.coli based Red Flag model response values for updated model 2015
						$lg_R2 =  0.2629+0.0444*($wtmpD1*9/5+32)-0.0364*($atmpD1*9/5+32)+0.0014*24*$daysset[$x]-0.226*log($daysset[$x]*24+0.0001) ;
						$lg_R3 = 1.4144+0.0255*($wtmpD1*9/5+32)-0.0007*$parD2+0.0009*24*$daysset[$x]-0.3022*log($daysset[$x]*24+0.0001)+0.0015*$flowD2-0.3957*log($flowD2) ;
						$lg_R4 = 3.6513+0.0254*($wtmpD1*9/5+32)-0.6636*log($parD2)-0.0014*24*$daysset[$x]-0.3428*log($daysset[$x]*24+0.0001) ;

						$lg_prb_R2 = exp($lg_R2) / (1 + exp($lg_R2)) ;
						$lg_prb_R3 = exp($lg_R3) / (1 + exp($lg_R3)) ;
						$lg_prb_R4 = exp($lg_R4) / (1 + exp($lg_R4)) ;
	
					}
		
					//write to database
					$thistimestring = strftime("%Y-%m-%d %H:%M:%S", $timeset[$x]);
					//2015 update
					$wqdb->query("INSERT INTO $modeltable (indx,location,datetime,watertemp_C,airtemp_C,rain_in,raindays,windspeed_mph,flow_cfs,par_uE,lg_prb_R2_pct,lg_prb_R3_pct,lg_prb_R4_pct,li_conc_LF_cfu, lg_prb_LF_pct,cso_CP,cyano_NewtonYC,cyano_WatertownYC,cyano_CommRowing,cyano_CRCK,cyano_HarvardWeld,cyano_RiversideBC,cyano_CRYC,cyano_UnionBC,cyano_CommBoating,cyano_CRCKKendall) 
					VALUES ('$rowcount','$loc',TIMESTAMP('$thistimestring'),round($wtmpset[$x],3),round($atmpset[$x],3),'$rainset[$x]',round($daysset[$x],2),'$windset[$x]','$flowset[$x]',round($parset[$x],0),round($lg_prb_R2,2),round($lg_prb_R3,2),round($lg_prb_R4,2),round($li_conc_LF),round($lg_prb_LF,2),'$cso_CP_set[$x]','$cyano_NYC_set[$x]','$cyano_WYC_set[$x]','$cyano_CR_set[$x]','$cyano_CRCK_set[$x]','$cyano_HW_set[$x]','$cyano_RBC_set[$x]','$cyano_CRYC_set[$x]','$cyano_UBC_set[$x]','$cyano_CB_set[$x]','$cyano_CRCKK_set[$x]')");
					
					//$wqdb->query("INSERT INTO $modeltable (indx,location,datetime,watertemp_C,rain_in,raindays,windspeed_mph,flow_cfs,par_uE,li_conc_NBBU_cfu,li_conc_LF_cfu,lg_prb_NBBU_pct,lg_prb_LF_pct,cso_CP,cyano_NewtonYC,cyano_WatertownYC,cyano_CommRowing,cyano_CRCK,cyano_HarvardWeld,cyano_RiversideBC,cyano_CRYC,cyano_UnionBC,cyano_CommBoating,cyano_CRCKKendall) ;
					//VALUES ('$rowcount','$loc',TIMESTAMP('$thistimestring'),round($wtmpset[$x],3),'$rainset[$x]',round($daysset[$x],2),'$windset[$x]','$flowset[$x]',round($parset[$x],0),round($li_conc_NBBU),round($li_conc_LF),round($lg_prb_NBBU,2),round($lg_prb_LF,2),'$cso_CP_set[$x]','$cyano_NYC_set[$x]','$cyano_WYC_set[$x]','$cyano_CR_set[$x]','$cyano_CRCK_set[$x]','$cyano_HW_set[$x]','$cyano_RBC_set[$x]','$cyano_CRYC_set[$x]','$cyano_UBC_set[$x]','$cyano_CB_set[$x]','$cyano_CRCKK_set[$x]')");

					$finalx = $x ;
					//$final_li_NBBU = $li_conc_NBBU ;
					//$final_lg_NBBU = $lg_prb_NBBU ; 
					
					$final_lg_R2 = $lg_prb_R2 ; // 2015 update
					$final_lg_R3 = $lg_prb_R3 ;// 2015 update
					$final_lg_R4 = $lg_prb_R4 ;// 2015 update
					$final_li_LF = $li_conc_LF ;
					$final_lg_LF = $lg_prb_LF ; 
					$lasttime = $timeset[$x] ; 
				}
				$lastx = $x;
			}
			$lastupdatex = $finalx;  //added to handle times when model data outdated
			
			//Update flag images with correct colors
			$boathouse = array("NewtonYC","WatertownYC","CommRowing","CRCK","HarvardWeld","RiversideBC","CRYC","UnionBC","CommBoating","CRCKKendall") ;
			$boathouse_set = array($cyano_NYC_set,$cyano_WYC_set,$cyano_CR_set,$cyano_CRCK_set,$cyano_HW_set,$cyano_RBC_set,$cyano_CRYC_set,$cyano_UBC_set,$cyano_CB_set,$cyano_CRCKK_set) ;
			//$upper = 6; //First # of locations in boathouse array that are in upper Charles
			$ModReach2 = 4 ; // update 2015 - First # of locations in boathouse array that are in Model Reach 2
			$ModReach3 = 5; // update 2015 - Extent of Model Reach 3
			$ModReach4 = 6; // update 2015 - Extent of Model Reach 4
			
			for ($x=0;$x<count($boathouse);$x++) {
				$flagimg = "../images/blue_flag.jpg" ;
				//$uprCh = TRUE ;
				$ModReach = 2 ; //update 2015
				//if ($x<$upper) {$uprCh = TRUE;} else {$uprCh = FALSE;}
				if ($x<$ModReach2) {
					$ModReach = 2;
				} elseif ($x<$ModReach3) {
					$ModReach = 3;
				} elseif ($x<$ModReach4) {
					$ModReach = 4;
				} else {
					$ModReach = 5;
				}
				$flaginfo = runflaglogic($ModReach,$final_lg_R2,$final_lg_R3,$final_lg_R4,$final_li_LF,$final_lg_LF,$boathouse_set[$x][$finalx],$cso_CP_set[$finalx],$boathouse_set[$x][$lastx],$cso_CP_set[$lastx]);
				//$flaginfo = runflaglogic($uprCh,$final_li_NBBU,$final_lg_NBBU,$final_li_LF,$final_lg_LF,$boathouse_set[$x][$finalx],$cso_CP_set[$finalx],$boathouse_set[$x][$lastx],$cso_CP_set[$lastx]);
				switch ($flaginfo[0]) {
					case "blue": $flagimg = "../images/blue_flag.jpg" ; break ;
					case "red": $flagimg = "../images/red_flag.jpg" ; break ;
					case "red": $flagimg = "../images/red_flag.jpg" ; break ;
				}
				copy($flagimg,"../images/".$boathouse[$x]."_flag.jpg");
				if(($boathouse_set[$x][$lastx]==1) || ($cso_CP_set[$lastx]==1)){ 
					$lastupdatex = $lastx;
				}
			}
			//Write to file last update date of flag images
			$date_handle = fopen("../backend/lastupdate.txt","w");
				fwrite($date_handle,strftime("%Y-%m-%d %H:%M:%S", $timeset[$lastupdatex])) ;
			fclose($date_handle) ;
		}
		$wqdb->close();
		return TRUE ;
}

function read_boatingmodel($days) {  
		//collects data array from modeldata table for Charles River boating model web pages
		$dbkeys = read_key("dbuser");
		$dbuser = $dbkeys[0]; $dbpw = $dbkeys[1];
		
		$startdate = mktime(0, 0, 0, date("m")  , date("d")-$days, date("Y"));
		$enddate = mktime(date("G"), 0, 0, date("m")  , date("d"), date("Y")) ;
		//$startdate = mktime(0, 0, 0, 8  , 15-$days, 2013); //use if frozen date
		//$enddate = mktime(23, 0, 0, 8  , 15, 2013) ; //use if frozen date
		$timeset = array() ;
		$wtmpset = array() ;
		$atmpset = array() ;//2015 update
		$rainset = array() ;
		$daysset = array() ;
		$windset = array() ;
		$flowset = array() ;
		$parset = array() ;
		//$Lin_NBBU_set = array() ;
		//$Log_NBBU_set = array() ;
		$Log_R2_set = array() ; //2015 update
		$Log_R3_set = array() ; //2015 update
		$Log_R4_set = array() ; //2015 update
		$Lin_LF_set = array() ;
		$Log_LF_set = array() ;
		$cso_CP_set = array() ;
		$cyano_NYC_set = array() ;
		$cyano_WYC_set = array() ;
		$cyano_CR_set = array() ;
		$cyano_CRCK_set = array() ;
		$cyano_HW_set = array() ;
		$cyano_RBC_set = array() ;
		$cyano_CRYC_set = array() ;
		$cyano_UBC_set = array() ;
		$cyano_CB_set = array() ;
		$cyano_CRCKK_set = array() ;

		//read data from database
		$modeltable = "crwa_notification.modeldata";
		$wqdb = new mysqli("notification.crwa.org", $dbuser, $dbpw, "crwa_notification");
		if ($wqdb->connect_error) {
    		echo "Not able to connect to database <br/>";
			exit;
		}
		$lasttime = mktime(0,0,0,0,0,0) ;
		$loc = "Charles";
		$startdatestring = strftime("%Y-%m-%d %H:%M:%S",$startdate);
		$enddatestring = strftime("%Y-%m-%d %H:%M:%S",$enddate);
		$statement = $wqdb->query("SELECT datetime,watertemp_C,airtemp_C,rain_in,raindays,windspeed_mph,flow_cfs,par_uE,lg_prb_R2_pct,lg_prb_R3_pct,lg_prb_R4_pct,li_conc_LF_cfu,lg_prb_LF_pct,cso_CP,cyano_NewtonYC,cyano_WatertownYC,cyano_CommRowing,cyano_CRCK,cyano_HarvardWeld,cyano_RiversideBC,cyano_CRYC,cyano_UnionBC,cyano_CommBoating,cyano_CRCKKendall FROM $modeltable WHERE location='$loc' AND datetime>=TIMESTAMP('$startdatestring') AND datetime<=TIMESTAMP('$enddatestring') ORDER BY datetime");
		//$statement = $wqdb->query("SELECT datetime,watertemp_C,rain_in,raindays,windspeed_mph,flow_cfs,par_uE,li_conc_NBBU_cfu,li_conc_LF_cfu,lg_prb_NBBU_pct,lg_prb_LF_pct,cso_CP,cyano_NewtonYC,cyano_WatertownYC,cyano_CommRowing,cyano_CRCK,cyano_HarvardWeld,cyano_RiversideBC,cyano_CRYC,cyano_UnionBC,cyano_CommBoating,cyano_CRCKKendall FROM $modeltable WHERE location='$loc' AND datetime>=TIMESTAMP('$startdatestring') AND datetime<=TIMESTAMP('$enddatestring') ORDER BY datetime");
		if ($wqdb->error) {
			echo "Corrupted database.<br/>" ;
			exit ;
		}
		while($row = $statement->fetch_assoc()){
			$thistime = strtotime($row['datetime']) ;
			$indx = round(($thistime - $startdate) / (60*60)) ;
			$timeset[$indx] = strftime("%Y-%m-%d %H:%M:%S",$thistime) ;
			$wtmpset[$indx] = $row['watertemp_C'] ;
			$atmpset[$indx] = $row['airtemp_C'] ; //2015 update
			$rainset[$indx] = $row['rain_in'] ;
			$daysset[$indx] = $row['raindays'] ;
			$windset[$indx] = $row['windspeed_mph'] ;
			$flowset[$indx] = $row['flow_cfs'] ;									
			$parset[$indx] = $row['par_uE'] ;
			//$Lin_NBBU_set[$indx] = $row['li_conc_NBBU_cfu'] ;
			//$Log_NBBU_set[$indx] = $row['lg_prb_NBBU_pct'] ;
			$Log_R2_set[$indx] = $row['lg_prb_R2_pct'] ; //2015 update
			$Log_R3_set[$indx] = $row['lg_prb_R3_pct'] ; //2015 update
			$Log_R4_set[$indx] = $row['lg_prb_R4_pct'] ; //2015 update
			$Lin_LF_set[$indx] = $row['li_conc_LF_cfu'] ;
			$Log_LF_set[$indx] = $row['lg_prb_LF_pct'] ;
			$cso_CP_set[$indx] = $row['cso_CP'] ;
			$cyano_NYC_set[$indx] = $row['cyano_NewtonYC'] ;
			$cyano_WYC_set[$indx] = $row['cyano_WatertownYC'] ;
			$cyano_CR_set[$indx] = $row['cyano_CommRowing'] ;
			$cyano_CRCK_set[$indx] = $row['cyano_CRCK'] ;
			$cyano_HW_set[$indx] = $row['cyano_HarvardWeld'] ;
			$cyano_RBC_set[$indx] = $row['cyano_RiversideBC'] ;
			$cyano_CRYC_set[$indx] = $row['cyano_CRYC'] ;
			$cyano_UBC_set[$indx] = $row['cyano_UnionBC'] ;
			$cyano_CB_set[$indx] = $row['cyano_CommBoating'] ;
			$cyano_CRCKK_set[$indx] = $row['cyano_CRCKKendall'] ;
			if (($thistime != $lasttime + 60*60)&&($indx > 0)) {
				echo "Hours missing in model database.<br/>" ;
				exit;
			}
			$lasttime = $thistime ;
		}
		$statement->free();
		$wqdb->close();

		//return data as single response array
		$response = array() ;
		$response[0] = $startdatestring ;
		$response[1] = $enddatestring ;
		$response[2] = $timeset ;
		$response[3] = $wtmpset ;
		$response[4] = $atmpset ; //update 2015
		$response[5] = $rainset ;
		$response[6] = $daysset ;
		$response[7] = $windset ;
		$response[8] = $flowset ;
		$response[9] = $parset ;
		$response[10] = $Log_R2_set ;//update 2015
		$response[11] = $Log_R3_set ;//update 2015
		$response[12] = $Log_R4_set ;//update 2015
		//$response[9] = $Lin_NBBU_set ;
		//$response[11] = $Log_NBBU_set ;
		$response[13] = $Lin_LF_set ;
		$response[14] = $Log_LF_set ;
		$response[15] = $cso_CP_set ;
		$response[16] = $cyano_NYC_set ;
		$response[17] = $cyano_WYC_set ;
		$response[18] = $cyano_CR_set ;
		$response[19] = $cyano_CRCK_set ;
		$response[20] = $cyano_HW_set ;
		$response[21] = $cyano_RBC_set ;
		$response[22] = $cyano_CRYC_set ;
		$response[23] = $cyano_UBC_set ;
		$response[24] = $cyano_CB_set ;
		$response[25] = $cyano_CRCKK_set ;
		return $response ;
}

?>