<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Last Flag Update</title>
<style type="text/css">
	span.helv18 {font-family: Helvetica, Arial, sans-serif; font-size: 18px; font-weight: bold; color: #444};
</style>
</head>

<body>
<p><span class="helv18">Updated 
<?php
	$date_handle = fopen("../backend/lastupdate.txt","r");
		$udate = strtotime(fgets($date_handle,1024));
		echo date('F j, Y',$udate) . " at " . date('g:ia',$udate);
	fclose($date_handle) ;
?>
</span></p>
</body>
</html>
