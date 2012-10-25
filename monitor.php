<?php
/**
* Server monitoring script
* Written by Andrew McCombe <euperia@gmail.com>
* checks the load average and reports if load average is higher 
* than the set threshold.
* sends an email with the following details:
*   - Output of Apache server status (if running)
*   - Output of netstat -pan
*   - Output of top
*   - Last 30 lines of /var/log/syslog
*   - Ouptut of ps auxwwwf
*/

define('MAIL_RECIPIENT', 'a@b.c');

$load_average_threshold = 5;         // <--- change this value for the load average threshold

// start with load checking
$average = file_get_contents('/proc/loadavg');
$load = explode(" ", $average);
$current = (int) $load[0];
$fivemin = (int) $load[1];
$fifteen = (int) $load[2];
$hostname = php_uname('n');

/*
 * Get the apache server-status page
*/
function server_status() {
	$status_file = '/tmp/server_status.txt';
	exec("w3m -dump -cols 300 http://127.0.0.1/server-status > " . $status_file);
	return file_get_contents($status_file);
}

function netstat() {
	exec('netstat -pan', $netstat);
	return implode("\n",$netstat);
}	

function top() {
	exec("top -b -n 1", $top);
	return implode("\n", $top);
}


if ($current >= $load_average_threshold) {

	// threshold met - send some stats
	$subject = $hostname . ' Load average of ' . implode(', ', array($load[0], $load[1], $load[2]));
	
	// get ps stats
	exec('ps auxwwwf', $ps);
	exec('tail -n 30 /var/log/syslog', $syslog);

	$body = 'Load average >= ' . $load_average_threshold. "\n".date('h:i:s d.m.Y')."\n\nps -auxwwwf\n------------\n" . implode("\n",$ps);
	$body .= "\n\n\nTAIL (tail -n 30 /var/log/syslog)\n---------------------------\n" . implode("\n", $syslog);
	$body .= "\n\nAPACHE STATUS\n\n" . server_status();
	$body .= "\n\nNETSTAT\n\n" . netstat();
	$body .= "\n\nTOP\n\n" . top();

	mail(MAIL_RECIPIENT, $subject,$body); 
	echo "\nMail Sent\n";

}
