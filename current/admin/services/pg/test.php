<?php
ini_set('display_errors', 'On');
printf("%-30s %s\n", "CUBRID PHP Version:", cubrid_version());
$conn = cubrid_connect ("localhost", 8080, "root", "suemans@123");

$lobs = cubrid_lob_get($conn, "SELECT image FROM pgImageTable WHERE pgId = '56' and imageDes LIKE 'pgImage1%'");

cubrid_lob_send($conn, $lobs[0]);
cubrid_lob_close($lobs);
cubrid_disconnect($conn);
?>