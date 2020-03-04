<?php require_once('../scripts/archive_repeatingcode.php'); ?>
<?php require_once('../scripts/archive_wqmodel.php'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Charles Boathouse WQ Flags</title>
<script src="https://maps.googleapis.com/maps/api/js"></script>
<script>
function initialize() {
  var mapProp = {
    center:new google.maps.LatLng(42.362, -71.124),
    zoom:13,
    mapTypeId:google.maps.MapTypeId.ROADMAP
  };
  var map=new google.maps.Map(document.getElementById("googleMap"),mapProp);
  var redmark = new google.maps.MarkerImage("https://chart.apis.google.com/chart?cht=d&chdp=mapsapi&chl=pin%27i%5c%27%5b%27-2%27f%5chv%27a%5c%5dh%5c%5do%5cFC0011%27fC%5c000000%27tC%5c000000%27eC%5cLauto%27f%5c&ext=.png");
  var yellowmark = new google.maps.MarkerImage("https://chart.apis.google.com/chart?cht=d&chdp=mapsapi&chl=pin%27i%5c%27%5b%27-2%27f%5chv%27a%5c%5dh%5c%5do%5cFCF400%27fC%5c000000%27tC%5c000000%27eC%5cLauto%27f%5c&ext=.png");
  var bluemark = new google.maps.MarkerImage("https://chart.apis.google.com/chart?cht=d&chdp=mapsapi&chl=pin%27i%5c%27%5b%27-2%27f%5chv%27a%5c%5dh%5c%5do%5c0800FC%27fC%5c000000%27tC%5c000000%27eC%5cLauto%27f%5c&ext=.png");
	<?php
		$days = 2 ;
		$arrayset = read_boatingmodel($days) ;
		$timeset = $arrayset[2] ;
		$Log_R2_set = $arrayset[10] ;
		$Log_R3_set = $arrayset[11] ;
		$Log_R4_set = $arrayset[12] ;
		//$Lin_NBBU_set = $arrayset[9] ;
		//$Log_NBBU_set = $arrayset[11] ;
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
		$last = 0 ; $key = 0 ;
		foreach ($timeset as $i) {
			if (strtotime($i)==TRUE) {
				$last = $key;
			}
			$key++ ;
		}
		$boathouse = array("Newton Yacht Club","Watertown Yacht Club","Community Rowing, Inc.","CRCK at Herter Park","Harvard's Weld Boathouse","Riverside Boat Club","Charles River Yacht Club","Union Boat Club","Community Boating","CRCK at Kendall Square") ;
		$boathouse_loc = array("42.358728, -71.173897","42.360567, -71.166001","42.358918, -71.166086","42.369382, -71.131325","42.369446, -71.122313","42.358094, -71.116047","42.359869, -71.083861","42.358094, -71.073561","42.359552, -71.073389","42.362660, -71.081801") ;
		$boathouse_set = array($cyano_NYC_set,$cyano_WYC_set,$cyano_CR_set,$cyano_CRCK_set,$cyano_HW_set,$cyano_RBC_set,$cyano_CRYC_set,$cyano_UBC_set,$cyano_CB_set,$cyano_CRCKK_set) ;
		//Assign Regions
		//$upper = 6; //First # of locations in boathouse array that are in upper Charles
		$ModReach2 = 4 ; // update 2015 - First # of locations in boathouse array that are in Model Reach 2
		$ModReach3 = 5; // update 2015 - Extent of Model Reach 3
		$ModReach4 = 6; // update 2015 - Extent of Model Reach 4

		//Assign flags
		for ($x=0;$x<count($boathouse);$x++) {
			$flagimg = "bluemark" ;
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
			$flaginfo = runflaglogic($ModReach, $Log_R2_set[$last], $Log_R3_set[$last], $Log_R4_set[$last], $Lin_LF_set[$last], $Log_LF_set[$last], $boathouse_set[$x][$last],$cso_CP_set[$last],0,0);
			switch ($flaginfo[0]) {
				case "blue": $flagimg = "bluemark" ; break ;
				case "yellow": $flagimg = "yellowmark" ; break ;
				case "red": $flagimg = "redmark" ; break ;
			}
			echo "var marker=new google.maps.Marker({";
			echo "position: new google.maps.LatLng(".$boathouse_loc[$x]."),";
			echo "map: map,";
			echo "title: \"".$boathouse[$x]."\",";
			echo "icon: ".$flagimg.",";
			echo "});";
		}
	?>
}
google.maps.event.addDomListener(window, 'load', initialize);
</script></head>

<body>
<div id="googleMap" style="width:650px;height:500px;"></div>
</body>
</html>
