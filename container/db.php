<?
$db=new mysqli('localhost','root','','pict');
if ($db->connect_errno) die('Error: Unable to connect to database.');
$db->set_charset('utf8');
?>
