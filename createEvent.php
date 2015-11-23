<?php
/**
 * Created by PhpStorm.
 * User: led
 * Date: 27-10-2015
 * Time: 14:33
 */
require_once 'settings/global.php';
require_once 'settings/config_api.php';

set_include_path('src/' . PATH_SEPARATOR . get_include_path());
require_once 'src/Google/Client.php';
require_once 'src/Google/Service/Calendar.php';
require_once 'api/backend/functions.php';
require_once 'conflictsFunctions.php';

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
$service = new Google_Service_Calendar($client);

$calendarId = $_POST['cal'];

$datetime= date("c", strtotime(date('Y-m-d H:i:s')));
$params=array(
    //'orderBy' => 'startTime',
    'timeMin' => $datetime,
);

$events = $service->events->listEvents($calendarId, $params);
$evs = array();
$i = 0;
while (true) {
    foreach ($events->getItems() as $event) {
        /*  $evs[$i]['summary'] =  $event->getSummary();
            $evs[$i]['id'] = $event->getId();
            $evs[$i]['start'] = $event->getStart();
            $evs[$i]['end'] = $event->getEnd();*/

        $evs[$i]['summary'] = $event->summary;
        $evs[$i]['id'] = $event->id;
        $evs[$i]['start'] = $event['modelData']['start']['date'];
        $evs[$i]['end'] = $event['modelData']['end']['date'];
        $i++;
    }
    $pageToken = $events->getNextPageToken();
    if ($pageToken) {
        $optParams = array('pageToken' => $pageToken);
        $events = $service->events->listEvents($calendarId, $optParams);
    } else {
        break;
    }

}




$start = str_replace(" 00:00:00", "", $_POST['start']);
$final = str_replace(" 00:00:00", "", $_POST['end']);

$end = $_POST['end'];
$date = str_replace('-', '/', $end);
$tomorrow = date('Y-m-d', strtotime($date . "+1 days"));


$create=verifyConflicts($evs, $start, $end);
if ($create===1) {
    $event = new Google_Service_Calendar_Event(array(
        'summary' => str_replace(" 00:00:00", "", $_POST['summary']),
        'end' => array(
            'date' => $tomorrow,
        ),
        'start' => array(
            'date' => $start,
        ),
    ));

    $event = $service->events->insert($calendarId, $event);
    echo json_encode($event);
} else {
    $event = array('id' => '<span class="error">error!!!</span>', 'summary' => '<span class="error">Potencial overbooking!!!</span>');
    echo json_encode($event);
}
