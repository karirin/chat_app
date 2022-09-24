<?php
function db_connect()
{
  if ($_SERVER["HTTP_HOST"] == "localhost") {
    $dsn = 'mysql:dbname=test;host=localhost;charset=utf8';
    $user = 'root';
    $password = '';
    $dbh = new PDO($dsn, $user, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
  } else {
    $dsn = 'mysql:dbname=heroku_2bd48d574bbec10;host=us-cdbr-east-06.cleardb.net;charset=utf8';
    $user = 'b059a375e1ac4d';
    $password = 'e0d648a1';
    $dbh = new PDO($dsn, $user, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $dbh;
  }
}
