<?php
/**
 * Created by PhpStorm.
 * User: led
 * Date: 27-10-2015
 * Time: 16:09
 */
require_once 'api/backend/functions.php';
$sql1 = "insert into  vrs6_calendars set calendar_id= '".$_POST['result']['id']."' , calendar_title='" .$_POST['result']['summary'] ."', listing_id='". $_POST['listing']."'";
$db_conn->query($sql1);