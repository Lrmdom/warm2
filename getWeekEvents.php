<?php
/**
 * Created by PhpStorm.
 * User: led
 * Date: 27-10-2015
 * Time: 17:13
 */
require_once 'settings/global.php';
require_once 'settings/config_api.php';

set_include_path('src/' . PATH_SEPARATOR . get_include_path());
require_once 'src/Google/Client.php';
require_once 'src/Google/Service/Calendar.php';
require_once 'api/backend/functions.php';

global $client;

//TODO get bookings in db and if not exist eventId return google Event to be possible to add to db

$client = new Google_Client();
$client->setApplicationName("warmrental_google_sync");
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope("https://www.googleapis.com/auth/calendar");
$client->setAccessType('offline');
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $tokens = html_entity_decode($_SESSION['access_token']);
    $tokens = json_decode($tokens, true);
    $tokens["refresh_token"] = $_SESSION['refresh_token'];
    $tokens = json_encode($tokens, true);
    $client->setAccessToken($tokens);

}
$service = new Google_Service_Calendar($client);


$calendarId = $_POST['calendar'];

$datetime= date("c", strtotime(date('Y-m-d H:i:s')));
$dtWeek = date("c", strtotime("-7 day", strtotime($datetime)));

$params=array(
    //'orderBy' => 'startTime',
    'timeMin' => $datetime,
    'updatedMin'=>$dtWeek
);

$events = $service->events->listEvents($calendarId , $params);
$evs=array();
$i=0;
while (true) {
    foreach ($events->getItems() as $event) {
        /*  $evs[$i]['summary'] =  $event->getSummary();
            $evs[$i]['id'] = $event->getId();
            $evs[$i]['start'] = $event->getStart();
            $evs[$i]['end'] = $event->getEnd();*/

        $evs[$i]['summary'] =  $event->summary;
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
$items['data']= $evs;

?>
<table id="dtable20">
    <thead>
    <tr>
        <th>id</th>
        <th>summary</th>
        <th>Start</th>
        <th>End</th>

    </tr>
    </thead>

    <tfoot>
    <tr>
        <th>id</th>
        <th>summary</th>
        <th>Start</th>
        <th>End</th>

    </tr>
    </tfoot>
</table>
<script>
    $(document).ready(function () {
        var events = <?php echo json_encode($items['data'])?> ;
        if (Object.keys(events).length > 0) {
            var table = $('#dtable20').DataTable({

                "pageLength": 50,
                data: events,
                columns: [
                    {data: 'id'},
                    {data: 'summary'},
                    {data: 'start'},
                    {data: 'end'},
                ],
            });
        } else {
            $("#dialog").html("No events on this calendar!!")
        }
    });
</script>