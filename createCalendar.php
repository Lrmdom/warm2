<?php
/**
 * Created by PhpStorm.
 * User: led
 * Date: 23-10-2015
 * Time: 12:09
 */


require_once 'settings/global.php';
require_once 'settings/config_api.php';

set_include_path('src/' . PATH_SEPARATOR . get_include_path());
require_once 'src/Google/Client.php';
require_once 'src/Google/Service/Calendar.php';
require_once 'api/backend/functions.php';

global $client;



$client = new Google_Client();
$client->setApplicationName("warmrental_google_sync");
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope("https://www.googleapis.com/auth/calendar");
$client->setAccessType('offline');
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    //Autenticar o cliente para de seguida iniciar servico Google


    $tokens = html_entity_decode($_SESSION['access_token']);
    $tokens = json_decode($tokens, true);
    $tokens["refresh_token"] = $_SESSION['refresh_token'];
    $tokens = json_encode($tokens, true);

    $client->setAccessToken($tokens);

    //$client->setAccessToken($_SESSION['access_token']);


}
//Google Calendar Service start
//global $service;
$service = new Google_Service_Calendar($client);

$calendar = new Google_Service_Calendar_Calendar($client);
$calendar->setSummary($_POST['calTitle']);
$calendar->setTimeZone('Europe/Lisbon');

if(in_array($_POST['calTitle'],$_POST['gCals'])){
    echo "Couldn't create Calendar. Calendar with title ".$_POST['calTitle']." allready exists!";
}else{

    $createdCalendar = $service->calendars->insert($calendar);

    echo json_encode($createdCalendar);
}
