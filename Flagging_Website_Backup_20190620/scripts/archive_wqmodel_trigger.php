<?php
// Trigger for automatically running model update and archiving
// Crontab should call this .php file 
//-------------------------------------------------------------------------------------------

require_once('archive_wqmodel.php');
archive_wqmodel(FALSE) ;

?>