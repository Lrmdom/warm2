<?php
ini_set('max_execution_time', 0);
require_once 'settings/global.php';
require_once 'settings/config_api.php';

set_include_path('src/' . PATH_SEPARATOR . get_include_path());
require_once 'src/Google/Client.php';
require_once 'src/Google/Service/Calendar.php';
require_once 'api/backend/functions.php';
require_once 'conflictsFunctions.php';

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

$datetime = date("c", strtotime(date('Y-m-d H:i:s')));
$params = array(
    //'orderBy' => 'startTime',
    'timeMin' => $datetime,
);


$dbcals = json_decode($_POST['dbCals'], true);
$gcals = json_decode($_POST['gCals'], true);
$conflicts = array();

foreach ($gcals as $gcal) {
    try {
        $evs = array();
        $i = 0;
        $events = $service->events->listEvents($gcal['calendar_id'], $params);
        while (true) {
            foreach ($events->getItems() as $event) {

                $evs[$i]['id'] = $event->id;
                $evs[$i]['created'] = $event->created;
                $evs[$i]['updated'] = $event->updated;
                $evs[$i]['summary'] = $event->summary;

                $evs[$i]['start'] = date('Y/m/j', strtotime($event['modelData']['start']['date']));
                $evs[$i]['end'] = date('Y/m/j', strtotime("-1 day", strtotime($event['modelData']['end']['date'])));
                $i++;
            }
            $pageToken = $events->getNextPageToken();
            if ($pageToken) {
                $optParams = array('pageToken' => $pageToken);
                $events = $service->events->listEvents($gcal['calendar_id'], $optParams);
            } else {
                break;
            }

        }
        $calEvents = $evs;
        $sql1 = "SELECT *,vrs6_calendars.calendar_id as calendar FROM vrs6_bookings LEFT JOIN vrs6_calendars ON vrs6_calendars.listing_id = vrs6_bookings.listing_id where  end_date >= CURDATE() AND vrs6_calendars.calendar_id='" . $gcal['calendar_id'] . "'";

        $rs1 = $db_conn->query($sql1);
        $bookings = array();
        $row = array();
        if ($rs1->num_rows > 0) {
            while ($row = $rs1->fetch_assoc()) {

                //delete event if admin_status = 0

                if($row['event_id']!=='DELETED FROM CALENDAR' && $row['admin_status']==0){
                    $event = new Google_Service_Calendar_Event(array(
                        'id' => $row['event_id']
                    ));
                    $service->events->delete($gcal['calendar_id'], $row['event_id']);

                    $sql1 = "UPDATE  vrs6_bookings set  event_id ='DELETED FROM CALENDAR' where booking_id=" . $row['booking_id'];
                    $db_conn->query($sql1);
                    $rowStatus='deleted';
                }

                //get overbookings

                $response = verifyConflicts($evs, $row['start_date'], $row['end_date']);
                if ($response !== 1) {
                    if ($row['event_id'] !== $response['eventId']) {
                        $conflict = array('calendar' => $gcal['calendar_id'], 'listing' => $row['listing_id'], 'booking' => $row['booking_id'], 'conflict' => "<span class='red'>overbooking :</span>" . $response['date']);
                        if ($row['event_id'] === NULL || $row['event_id'] === '') {
                            array_push($conflicts, $conflict);
                            $row = array();
                        }
                    }
                }
                //get mismatch dates

                if (!empty($row)) {
                    $res = verifyMismatchDates($gcal['calendar_id'], $evs, $row);

                    if ($res !== 0 && array_key_exists('conflict', $res)) {
                        array_push($conflicts, $res);
                        $row = array();
                    } elseif ($res !== 0 && array_key_exists('row', $res) && !isset($rowStatus)) {
                        $sql1 = "UPDATE  vrs6_bookings set event_name='" . $res['row']['summary'] . "', event_id ='" . $res['event']['id'] . "' where booking_id=" . $res['row']['booking_id'];
                        $db_conn->query($sql1);
                    }
                }

                //update booking status if removed from calendar

                $evsToUpdate = array();


                if ($calEvents) {
                    foreach ($calEvents as $key => $event) {
                        /*if (isset($row['event_id']) && $row['event_id'] !== null) {
                            $check=1;
                            if(in_array($row['event_id'], $event)){
                                $check=1;

                            }else{
                                $check=0;
                                array_push($evsToUpdate, $event);
                                //unset($calEvents[$key]);
                            }
                        }*/
                        //remove event from list (events to insert in db) if already exist in db
                        if (in_array($event['id'], $row)) {
                            unset($calEvents[$key]);
                        }
                    }

                }

               if (sizeof($evsToUpdate) > 0) {
                    foreach ($evsToUpdate as $evt) {
                        $sql1 = "UPDATE  vrs6_bookings set admin_status=0,event_id='deleted from cal' where booking_id=" . $evt['id'];
                        $db_conn->query($sql1);
                    }
                }
                //upload booking if not overbooking and if not uploaded yet

                if (!empty($row) && $row['event_id'] === null && $row['admin_status'] == 1) {
                    $start = str_replace(" 00:00:00", "", $row['start_date']);
                    $final = str_replace(" 00:00:00", "", $row['end_date']);

                    $end = $row['end_date'];
                    $date = str_replace('-', '/', $end);
                    $tomorrow = date('Y-m-d', strtotime($date . "+1 days"));
                    $summary = " booking: " . $row['booking_id'] . " Listing: " . $row['listing_id'] . " from: " . $start . " end:" . $final;
                    $row['summary'] = $summary;
                    $event = new Google_Service_Calendar_Event(array(
                        'summary' => $summary,
                        'end' => array(
                            'date' => $tomorrow,
                        ),
                        'start' => array(
                            'date' => $start,
                        ),
                    ));

                    $r = verifyConflicts($evs, $start, $final);
                    if ($r === 1) {
                        $returnedEvent = $service->events->insert($gcal['calendar_id'], $event);

                        $e['summary'] = $returnedEvent->summary;
                        $e['id'] = $returnedEvent->id;
                        $e['start'] = $returnedEvent['modelData']['start']['date'];
                        $e['end'] = $returnedEvent['modelData']['end']['date'];

                        $sql1 = "UPDATE  vrs6_bookings set event_name='" . $e['summary'] . "', event_id ='" . $e['id'] . "' where booking_id=" . $row['booking_id'];
                        $db_conn->query($sql1);
                        //array_push($calEvents, $e);
                        array_push($evs, $e);
                    } else {
                        $conflict = array('calendar' => $gcal['calendar_id'], 'listing' => $row['listing_id'], 'booking' => $row['booking_id'], 'conflict' => "<span class='red'>overbooking :</span>" . $response['date']);

                        array_push($conflicts, $conflict);
                    }
                    //delete event from calendar , update db
                } /*elseif (isset($row['admin_status']) && $row['admin_status'] == 0 && $row['event_id'] !== null ) {
                    $event = new Google_Service_Calendar_Event(array(
                        'id' => $row['event_id']
                    ));
                    $service->events->delete($gcal['calendar_id'], $event);
                    $sql1 = "UPDATE  vrs6_bookings set event_name=NULL, event_id =NULL where booking_id=" . $res['row']['booking_id'];
                    $db_conn->query($sql1);
                }*/

            }
        }

        //insert into db events that not exists with event_id null or blank
        if ($calEvents) {
            foreach ($calEvents as $event) {
                $start = str_replace(" 00:00:00", "", $event['start']);
                $final = str_replace(" 00:00:00", "", $event['end']);

                $r = verifyConflictsCal($evs, $event['start'], $event['end'], $event['id']);
                if ($r === 1) {
                    $values = "'" . $gcal['listing_id'] . "','" . $event['id'] . "','" . $event['summary'] . "','" . $start . "','" . $final . "',true";
                    $sql1 = "insert into vrs6_bookings (listing_id,event_id,event_name,start_date,end_date,admin_status) values(" . $values . ")";
                    $db_conn->query($sql1);
                } else {
                    $conflict = array('calendar' => $gcal['calendar_id'], 'listing' => $gcal['listing_id'], 'booking' => $event['id'], 'conflict' => "<span class='red'>overbooking :</span>" . $r['date']);
                    array_push($conflicts, $conflict);
                }
            }
        }
        $evs = array();
        $i = 0;
        //update table calendars with last synced date
        $sql1 = "UPDATE  vrs6_calendars set syncdate = now() where calendar_id='" . $gcal['calendar_id'] . "' order by syncdate asc";
        $db_conn->query($sql1);
    } catch (Exception $e) {
        var_dump($e);

    }
}
echo json_encode($conflicts);



