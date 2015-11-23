<?php
/**
 * Created by PhpStorm.
 * User: led
 * Date: 27-10-2015
 * Time: 16:09
 */
require_once 'api/backend/functions.php';

$sql1 = "UPDATE vrs6_bookings set event_id= '".$_POST['result']['id']."' , event_name='" .$_POST['result']['summary'] ."' where booking_id =".$_POST['id'];
$db_conn->query($sql1);