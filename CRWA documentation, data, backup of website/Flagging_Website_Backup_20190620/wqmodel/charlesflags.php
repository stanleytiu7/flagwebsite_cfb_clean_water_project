<?php require_once('../scripts/archive_repeatingcode.php'); ?>
<?php require_once('../scripts/archive_wqmodel.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Charles River Water Quality Projections</title>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
  google.load("visualization", "1", {packages:["corechart"]});
  google.setOnLoadCallback(drawChart);
	  function drawChart() {
		//Charts
		var hconcdata = new google.visualization.DataTable();
		var hflowdata = new google.visualization.DataTable();
		var hwinddata = new google.visualization.DataTable();
		// Add columns
		hconcdata.addColumn('string', 'Time');
		hconcdata.addColumn('number', 'Threshold');
		hconcdata.addColumn('number', 'Reach 2');
		hconcdata.addColumn('number', 'Reach 3');
		hconcdata.addColumn('number', 'Reach 4');
		hconcdata.addColumn('number', 'Reach 5');
		hflowdata.addColumn('string', 'Time');
		hflowdata.addColumn('number', 'Riverflow');
		hflowdata.addColumn('number', 'Rain');
		hwinddata.addColumn('string', 'Time');
		hwinddata.addColumn('number', 'Water Temp');
		hwinddata.addColumn('number', 'PAR');
		// Add rows
		<?php 
			//create master arrays
			$days = 14 ;
			$arrayset = read_boatingmodel($days) ;
			//$startdate = $arrayset[0] ;
			//$enddate = $arrayset[1] ;
			$timeset = $arrayset[2] ;
			$wtmpset = $arrayset[3] ;
			$rainset = $arrayset[5] ;
			$daysset = $arrayset[6] ;
			$windset = $arrayset[7] ;
			$flowset = $arrayset[8] ;
			$parset = $arrayset[9] ;
			$Log_R2_set = $arrayset[10] ;//update 2015
			$Log_R3_set = $arrayset[11] ;//update 2015
			$Log_R4_set = $arrayset[12] ;//update 2015
			//$Lin_NBBU_set = $arrayset[10] ;
			//$Log_NBBU_set = $arrayset[12] ;
			//$Lin_LF_set = $arrayset[13] ;
			$Log_LF_set = $arrayset[14] ;	
			$vals = 0 ; $key = 0 ;
			foreach ($timeset as $i) {
				if (strtotime($i)==TRUE) {
					$vals = $key+1;
				}
				$key++ ;
			}
			//Populate data arrays
			echo "hconcdata.addRows(" . $vals . ");" ;
			for ($row=0;$row<$vals;$row++) {
				echo "hconcdata.setCell(" . $row . ", 0, '" . strftime("%b-%d %H:%M",strtotime($timeset[$row])) . "');" ;
				echo "hconcdata.setCell(" . $row . ", 1, 0.5);" ;
				echo "hconcdata.setCell(" . $row . ", 2, " . $Log_R2_set[$row] . ");" ;
				echo "hconcdata.setCell(" . $row . ", 3, " . $Log_R3_set[$row] . ");" ;
				echo "hconcdata.setCell(" . $row . ", 4, " . $Log_R4_set[$row] . ");" ;
				echo "hconcdata.setCell(" . $row . ", 5, " . $Log_LF_set[$row] . ");" ;
			}	
			echo "hflowdata.addRows(" . $vals . ");" ;
			for ($row=0;$row<$vals;$row++) {
				echo "hflowdata.setCell(" . $row . ", 0, '" . strftime("%b-%d %H:%M",strtotime($timeset[$row])) . "');" ;
				echo "hflowdata.setCell(" . $row . ", 1, " . $flowset[$row] . ");" ;
				echo "hflowdata.setCell(" . $row . ", 2, " . $rainset[$row] . ");" ;
			}	
			echo "hwinddata.addRows(" . $vals . ");" ;
			for ($row=0;$row<$vals;$row++) {
				echo "hwinddata.setCell(" . $row . ", 0, '" . strftime("%b-%d %H:%M",strtotime($timeset[$row])) . "');" ;
				echo "hwinddata.setCell(" . $row . ", 1, " . $wtmpset[$row] . ");" ;
				echo "hwinddata.setCell(" . $row . ", 2, " . $parset[$row] . ");" ;
			}
		?>
		var hconcoptions = {
		  title: 'Modeled E.coli bacteria - last 14 days',
		  vAxis: {title: 'Probability of exceedance', textStyle: {fontSize: 8}, gridlines: {count:4}, 
		  		viewWindowMode: "explicit", viewWindow: {min: 0, max: 1}},
		  hAxis: {textStyle: {fontSize: 8}, showTextEvery:48},
		  series: {0:{lineWidth: 1, color: 'black', visibleInLegend: false}, 1:{lineWidth: 4}, 2:{lineWidth: 4}, 3:{lineWidth: 4}, 4:{lineWidth: 4}},
		  chartArea: {left:"10%", top:"15%", width:"80%",height:"75%"},
		  legend: {position: 'top'}
		};
		var hflowoptions = {
		  title: 'River Flow / Rain - last 14 days',
		  vAxis: {title: 'Flow (cfs)', textStyle: {fontSize: 8}, gridlines: {count:4}},
		  hAxis: {textStyle: {fontSize: 8}, showTextEvery:48},
		  series: {0:{lineWidth: 2}, 1:{lineWidth: 2, color: 'gray', targetAxisIndex: 1}},
		  vAxes: {1:{title: 'Rain (in/hour)', textStyle: {fontSize: 8}, gridlines: {count:4}, 
		  		viewWindowMode: "explicit", viewWindow: {min: 0}}},
		  chartArea: {left:"10%", top:"15%", width:"80%",height:"75%"},
		  legend: {position: 'top'}
		};
		var hwindoptions = {
		  title: 'Water Temp / PAR - last 14 days',
		  vAxis: {title: 'Water temp (deg C)', textStyle: {fontSize: 8}, gridlines: {count:4}},
		  hAxis: {textStyle: {fontSize: 8}, showTextEvery:48},
		  series: {0:{lineWidth: 2}, 1:{lineWidth: 2, targetAxisIndex: 1}},
		  vAxes: {1:{title: 'PAR (uE)', textStyle: {fontSize: 8}, gridlines: {count:4}}},
		  chartArea: {left:"10%", top:"15%", width:"80%",height:"75%"},
		  legend: {position: 'top'}
		};
		var hconcchart = new google.visualization.LineChart(document.getElementById('hconcchart_div'));
		hconcchart.draw(hconcdata, hconcoptions);
		var hflowchart = new google.visualization.LineChart(document.getElementById('hflowchart_div'));
		hflowchart.draw(hflowdata, hflowoptions);
		var hwindchart = new google.visualization.LineChart(document.getElementById('hwindchart_div'));
		hwindchart.draw(hwinddata, hwindoptions);
	}
</script>
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
			<!--<span class="red">off season - frozen snapshot of 8/15/13</span>-->
			)
		</span><br/><br/><br/>
		</div>		
</tr>
<tr height="640">
	<td colspan="2" valign="top" align="left"> 
	<div class="sub_header2">The following predicted concentrations of Fecal Indicator Bacteria (FIB) are based on modeled correlations between FIB and weather or water conditions.  Flagging is based on safety thresholds for boating.  The weather and water data is sourced from the <a href="charlesWS.html" style="color:#3366CC">CESN weather station at Community Boating</a> and the <a href="http://waterdata.usgs.gov/nwis/dv?referred_module=sw&site_no=01104500" style="color:#3366CC">USGS Waltham river flow gauge</a>.  Currently the predictions are only based on one year of historical statistics.  An ideal model would include 2-3 years of history.  New models will be developed in future years as more data is collected.  <a href="../projects/charles.php" style="color:#3366CC">Click here for a full description of this bacteria forecasting project.</a><br/><br/></div>
	<?php
		//Hourly data
		//get hourly data
		$days = 8 ;
		$arrayset = read_boatingmodel($days) ;
		//$startdate = $arrayset[0] ;
		//$enddate = $arrayset[1] ;
		$timeset = $arrayset[2] ;
		$wtmpset = $arrayset[3] ;
		$rainset = $arrayset[5] ;
		$daysset = $arrayset[6] ;
		$windset = $arrayset[7] ;
		$flowset = $arrayset[8] ;
		$parset = $arrayset[9] ;
		$Log_R2_set = $arrayset[10] ;//update 2015
		$Log_R3_set = $arrayset[11] ;//update 2015
		$Log_R4_set = $arrayset[12] ;//update 2015
		$Lin_LF_set = $arrayset[13] ;
		$Log_LF_set = $arrayset[14] ;
		$cso_CP_set = $arrayset[15] ;
		$cyano_NYC_set = $arrayset[16] ;
		$cyano_WYC_set = $arrayset[17] ;
		$cyano_CR_set = $arrayset[18] ;
		$cyano_CRCK_set = $arrayset[19] ;
		$cyano_HW_set = $arrayset[20] ;
		$cyano_RBC_set = $arrayset[21] ;
		$cyano_CRYC_set = $arrayset[22] ;
		$cyano_UBC_set = $arrayset[23] ;
		$cyano_CB_set = $arrayset[24] ;
		$cyano_CRCKK_set = $arrayset[25] ;
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
		//$upper = 6; //First # of locations in boathouse array that are in upper Charles
		$ModReach2 = 3 ; // update 2015 - First # of locations in boathouse array that are in Model Reach 2
		$ModReach3 = 5; // update 2015 - Extent of Model Reach 3
		$ModReach4 = 6; // update 2015 - Extent of Model Reach 4

		//Assign flags
		$flag = array(); $img = array(); $comment = array();
		$linear = array(); $logistic = array();
		for ($x=0;$x<count($boathouse);$x++) {
			$flag[$x] = "Blue" ; $img[$x] = "../images/blue_flag.jpg" ; $comment[$x] = "(safe for boating)" ;
			//$uprCh = TRUE ;
			$ModReach = 2 ; //update 2015
			//if ($x<$upper) {$uprCh = TRUE;} else {$uprCh = FALSE;}
			if ($x<$ModReach2) {
				$ModReach = 2;
				$linear[$x] = "-";
				$logistic[$x] = round($Log_R2_set[$last]*100);
			} elseif ($x<$ModReach3) {
				$ModReach = 3;
				$linear[$x] = "-";
				$logistic[$x] = round($Log_R3_set[$last]*100);
			} elseif ($x<$ModReach4) {
				$ModReach = 4;
				$linear[$x] = "-";
				$logistic[$x] = round($Log_R4_set[$last]*100);
			} else {
				$ModReach = 5;
				$linear[$x] = round($Lin_LF_set[$last]);
				$logistic[$x] = round($Log_LF_set[$last]*100);
			}
			$flaginfo = runflaglogic($ModReach,$Log_R2_set[$last],$Log_R3_set[$last],$Log_R4_set[$last],$Lin_LF_set[$last],$Log_LF_set[$last],$boathouse_set[$x][$last],$cso_CP_set[$last],0,0);
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
					//if red flag then not reporting model output because could conflict with flag
					$linear[$x] = "-"; $logistic[$x] = "-"; 
					break ;
			}
		}

		//Print Flags
		echo "<table class=\"flags\" style=\"padding-left: 30px\">" ;
		echo "<tr><th nowrap colspan=\"5\" height=\"25\" valign=\"bottom\">
				<span class=\"bold_und\">Water quality flags, with forecasted <span class=\"ital\">E.coli</span> concentrations and probabilities 
				(updated hourly):</span>
			</th></tr>" ;
		echo "<tr valign=\"bottom\">
			<td width=\"190\"><span class=\"bold\">As of: " . strftime("%m/%d/%y %H:%M",strtotime($timeset[$last])+59*60) . "</span></td>
			<td width=\"100\"></td>
			<td width=\"120\"><span class=\"und\">Modeled Concentration</span></td>
			<td width=\"120\"><span class=\"und\">Probability of Exceedance</span></td>
			<td width=\"180\"><span class=\"und\">Flag</span></td></tr>" ;
		echo "<tr><td class=\"label\"><span class=\"und\">Model Reach 2</span></td></tr>" ;
		for ($x=0;$x<$ModReach2;$x++) {
			echo "<tr><td class=\"label\">&nbsp&nbsp&nbsp" . $boathouse[$x] . "</td><td class=\"pic\"><img src=\"" . $img[$x] . "\" 		
				width=\"18\"/></td>
				<td>" . $linear[$x] . "&nbsp;cfu</td><td>" . $logistic[$x] . "%</td>
				<td>" . $flag[$x] . "&nbsp;" . $comment[$x] . "</td></tr>" ;
		}
		echo "<tr><td class=\"label\"><span class=\"und\">Model Reach 3</span></td></tr>" ;
		for ($x=$ModReach2;$x<$ModReach3;$x++) {
			echo "<tr><td class=\"label\">&nbsp&nbsp&nbsp" . $boathouse[$x] . "</td><td class=\"pic\"><img src=\"" . $img[$x] . "\" 		
				width=\"18\"/></td>
				<td>" . $linear[$x] . "&nbsp;cfu</td><td>" . $logistic[$x] . "%</td>
				<td>" . $flag[$x] . "&nbsp;" . $comment[$x] . "</td></tr>" ;
		}
		echo "<tr><td class=\"label\"><span class=\"und\">Model Reach 4</span></td></tr>" ;
		for ($x=$ModReach3;$x<$ModReach4;$x++) {
			echo "<tr><td class=\"label\">&nbsp&nbsp&nbsp" . $boathouse[$x] . "</td><td class=\"pic\"><img src=\"" . $img[$x] . "\" 		
				width=\"18\"/></td>
				<td>" . $linear[$x] . "&nbsp;cfu</td><td>" . $logistic[$x] . "%</td>
				<td>" . $flag[$x] . "&nbsp;" . $comment[$x] . "</td></tr>" ;
		}
		echo "<tr><td class=\"label\"><span class=\"und\">Model Reach 5</span></td></tr>" ;
		for ($x=$ModReach4;$x<count($boathouse);$x++) {
			echo "<tr><td class=\"label\">&nbsp&nbsp&nbsp" . $boathouse[$x] . "</td><td class=\"pic\"><img src=\"" . $img[$x] . "\" 
				width=\"18\"/></td><td>" . $linear[$x] . "&nbsp;cfu</td><td>" . $logistic[$x] . "%</td>
				<td>" . $flag[$x] . "&nbsp;" . $comment[$x] . "</td></tr>" ;
		}
		echo "</table><br/><br/>" ;
			
		echo "<div style=\"width: 800px; padding-left: 20px\">
				<div id=\"hconcchart_div\" style=\"width: 750px; height: 200px\"></div><br/>
				<div id=\"hflowchart_div\" style=\"width: 750px; height: 200px\"></div><br/>
				<div id=\"hwindchart_div\" style=\"width: 750px; height: 200px\"></div><br/>
				</div>" ;
		echo "<br/>" ;

	?>
	<div class="sub_header2" style="font-size: 16px"><span class="und">Model formulas:</span><br/><br/>
	<div style="font-weight:normal; font-size:14px"><p>Reach 2 logistic model</p>
	<p style="color:blue">ln(p/(1-p)) = 0.26 + 0.044*WtmpD1 - 0.036*AtmpD1 + 0.0014*Hours - 0.23*log(Hours+0.0001)</p>
	<p><span class="bold">p</span> = probability of exceeding safety threshold; </p>
	<p><span class="bold">WtmpD1</span> = Previous 24hr average water temp (F); <span class="bold">AtmpD1</span> = previous 24hr average air temp (F);  <span class="bold">Hours</span> = Hours since last rain</p><br/>
	<p>Reach 3 logistic model</p>
	<p style="color:blue">ln(p/(1-p)) = 1.41 + 0.026*WtmpD1 - 0.0007*PARD2 + 0.0009*Hours - 0.302*log(Hours+0.0001) + 0.0015*FlowD2 - log(FlowD2)</p>
	<p><span class="bold">p</span> = probability of exceeding safety threshold; </p>
	<p><span class="bold">WtmpD1</span> = Previous 24hr average water temp (F); <span class="bold">PARD2</span> = previous 48hr average PAR;  <span class="bold">Hours</span> = Hours since last rain;  <span class="bold">FlowD2</span> = previous 48hr average flow</p><br/>
	<p>Reach 4 logistic model</p>
	<p style="color:blue">ln(p/(1-p)) = 3.65 + 0.025*WtmpD1 - 0.664*PARD2 + 0.0014*Hours - 0.343*log(Hours+0.0001)</p>
	<p><span class="bold">p</span> = probability of exceeding safety threshold; </p>
	<p><span class="bold">WtmpD1</span> = Previous 24hr average water temp (F); <span class="bold">PARD2</span> = previous 48hr average PAR;  <span class="bold">Hours</span> = Hours since last rain</p><br/>
	<p>Reach 5 logistic model</p>
	<p style="color:blue">ln(p/(1-p)) = -3.18 + 3.94*RainD2 - 1.62*RainD7 + 1.28*log(FlowD1) - 0.34*WindD1 - 0.21*WtmpD1</p>
	<p><span class="bold">p</span> = probability of exceeding safety threshold; </p>
	<p><span class="bold">RainD2</span> = Previous 48hr total rainfall; <span class="bold">RainD7</span> = previous 7-day total rainfall</p>
	<p><span class="bold">FlowD1</span> = Previous 24hr average riverflow; <span class="bold">WindD1</span> = previous 24hr average windspeed</p>
	<p><span class="bold">WtmpD1</span> = previous 24hr average water temp</p>
	</div>
	</td>
</tr>
<tr>
	<td colspan="2">
	</td>
</tr>
</table>
</body>
</html>
