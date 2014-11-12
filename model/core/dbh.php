<?php
$dbh = new PDO('mysql:host=localhost;dbname=client_compte', 'root', '', [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"]);
//$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);