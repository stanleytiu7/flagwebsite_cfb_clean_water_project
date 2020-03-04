<?php require_once('../scripts/archive_repeatingcode.php'); ?>
<?php require_once('../scripts/archive_wqmodel.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Charles River Water Quality Projections</title>
<style type="text/css">
	span.bold_und {font-weight: bold; text-decoration: underline;}
	span.ital {font-style: italic;}
	span.bold {font-weight: bold;}
	span.und {text-decoration: underline;}
	table.flags {table-layout:fixed; padding:0; margin-left:10;}
	table.flags td{overflow: hidden;text-align:center;}
	table.flags td.label{text-align:left;}
	table.flags td.pic{text-align:center;}
</style>
</head>

<body>
<table width="800" cellpadding="0" cellspacing="0" class="content_table">
<tr height="25">
	<td width="111"></td>
	<td width="689">
		<div class="header2">
		<img src="../images/CRWA_banner_main.jpg" width="600"/><br/>
		<span class="bold">Water Quality Flagging Program ( 
			<script language="javascript">
				thisdate= new Date()
				document.write(thisdate.getMonth()+1+"/"+thisdate.getDate()+"/"+thisdate.getFullYear())
			</script>
			)
		</span><br/><br/><br/>
		</div>		
</tr>
<tr height="640">
	<td colspan="2" valign="top" align="left"> 
	<?php
		//Hourly data
		//get hourly data
		$days = 8 ;
		$arrayset = read_boatingmodel($days) ;
		//$startdate = $arrayset[0] ;
		//$enddate = $arrayset[1] ;
		$timeset = $arrayset[2] ;
		$wtmpset = $arrayset[3] ;
		$rainset = $arrayset[4] ;
		$daysset = $arrayset[5] ;
		$windset = $arrayset[6] ;
		$flowset = $arrayset[7] ;
		$parset = $arrayset[8] ;	
		$Lin_NBBU_set = $arrayset[9] ;
		$Lin_LF_set = $arrayset[10] ;
		$Log_NBBU_set = $arrayset[11] ;
		$Log_LF_set = $arrayset[12] ;
		$cso_CP_set = $arrayset[13] ;
		$cyano_NYC_set = $arrayset[14] ;
		$cyano_WYC_set = $arrayset[15] ;
		$cyano_CR_set = $arrayset[16] ;
		$cyano_CRCK_set = $arrayset[17] ;
		$cyano_HW_set = $arrayset[18] ;
		$cyano_RBC_set = $arrayset[19] ;
		$cyano_CRYC_set = $arrayset[20] ;
		$cyano_UBC_set = $arrayset[21] ;
		$cyano_CB_set = $arrayset[22] ;
		$cyano_CRCKK_set = $arrayset[23] ;
		$last = 0 ; $lastday = 0 ; $key = 0 ;
		foreach ($timeset as $i) {
			if (strtotime($i)==TRUE) {
				$last = $key;
				if (date("G",strtotime($i)) == 23) {
					$lastday = $key ;
				}
			}
			$key++ ;
		}
		$boathouse = array("Newton Yacht Club","Watertown Yacht Club","Community Rowing, Inc.","CRCK at Herter Park","Harvard's Weld Boathouse","Riverside Boat Club","Charles River Yacht Club","Union Boat Club","Community Boating","CRCK at Kendall Square") ;
		$boathouse_set = array($cyano_NYC_set,$cyano_WYC_set,$cyano_CR_set,$cyano_CRCK_set,$cyano_HW_set,$cyano_RBC_set,$cyano_CRYC_set,$cyano_UBC_set,$cyano_CB_set,$cyano_CRCKK_set) ;
		$upper = 6; //First # of locations in boathouse array that are in upper Charles

		//Assign flags
		$flag = array(); $img = array(); $comment = array();
		$linear = array(); $logistic = array();
		for ($x=0;$x<count($boathouse);$x++) {
			$flag[$x] = "Blue" ; $img[$x] = "../images/blue_flag.jpg" ; $comment[$x] = "(safe for boating)" ;
			$uprCh = TRUE ;
			if ($x<$upper) {$uprCh = TRUE;} else {$uprCh = FALSE;}
			if ($uprCh) {
				$linear[$x] = round($Lin_NBBU_set[$last]);
				$logistic[$x] = round($Log_NBBU_set[$last]*100);
			} else {
				$linear[$x] = round($Lin_LF_set[$last]);
				$logistic[$x] = round($Log_LF_set[$last]*100);
			}
			$flaginfo = runflaglogic($uprCh,$Lin_NBBU_set[$last],$Log_NBBU_set[$last],$Lin_LF_set[$last],$Log_LF_set[$last],$boathouse_set[$x][$last],$cso_CP_set[$last],0,0);
			switch ($flaginfo[0]) {
				case "blue": $flag[$x] = "Blue" ; $img[$x] = "../images/blue_flag.jpg" ; break ;
				case "yellow": 
					$flag[$x] = "Yellow" ; $img[$x] = "../images/yellow_flag.jpg" ; 
					if ($flaginfo[1] = "cyano") { $comment[$x] = "(cyanobacteria)" ;}
					break ;
				case "red": 
					$flag[$x] = "Red" ; $img[$x] = "../images/red_flag.jpg" ; 
					switch ($flaginfo[1]) {
						case "model": $comment[$x] = "(health risk)" ; break ;
						case "cso": $comment[$x] = "(CSO)" ; break ;
					}
					$linear[$x] = "-"; $logistic[$x] = "-";
					break ;
			}
		}

		//Print Flags
		echo "<table class=\"flags\" style=\"padding-left: 30px\">" ;
		echo "<tr valign=\"bottom\">
			<td width=\"190\"><span class=\"bold\">As of: " . strftime("%m/%d/%y %H:%M",strtotime($timeset[$last])+59*60) . "</span></td>
			<td width=\"100\"></td>
			<td width=\"120\"><span class=\"und\">Modeled Concentration</span></td>
			<td width=\"120\"><span class=\"und\">Probability of Exceedance</span></td>
			<td width=\"180\"><span class=\"und\">Flag</span></td></tr>" ;
		for ($x=0;$x<count($boathouse);$x++) {
			echo "<tr height=\"100\" valign=\"bottom\"><td class=\"label\">&nbsp&nbsp&nbsp" . $boathouse[$x] . "</td><td class=\"pic\"><img src=\"" . $img[$x] . "\" 		
				width=\"18\"/></td>
				<td>" . $linear[$x] . "&nbsp;cfu</td><td>" . $logistic[$x] . "%</td>
				<td>" . $flag[$x] . "&nbsp;" . $comment[$x] . "</td></tr>" ;
		}
		echo "</table><br/><br/>" ;
	?>
	</td>
</tr>
</table>
</body>
</html>
