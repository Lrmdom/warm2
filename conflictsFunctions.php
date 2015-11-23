<?php
/**
 * Created by PhpStorm.
 * User: led
 * Date: 02-11-2015
 * Time: 11:11
 */

function verifyConflicts($events, $start, $end)
{
    foreach ($events as $event) {
        $dtLoop = date('Y/m/j', strtotime("+1 day", strtotime($start)));
        $dtEnd = date(' Y/m/j ', strtotime("-1 day", strtotime($end)));

        While (strtotime($dtLoop) <= strtotime($dtEnd)) {
            $inic = date('Y/m/j', strtotime($event['start']));
            $fim = date('Y/m/j', strtotime($event['end']));
            While (strtotime($inic) <= strtotime($fim)) {
                if ($inic == $dtLoop) {
                    $res = array('date' => $dtLoop, 'eventId' => $event['id'], 'event' => $event);
                    return $res;
                }
                $inic = date('Y/m/j', strtotime("+1 day", strtotime($inic)));
            }
            $dtLoop = date(' Y/m/j ', strtotime("+1 day", strtotime($dtLoop)));
        }
    }
    return 1;
}

function verifyMismatchDates($cal, $events, $row)
{

    foreach ($events as $event) {
        if ($event['id'] == $row['event_id']) {

            if (date('Y/m/j', strtotime($row['start_date'])) != date('Y/m/j', strtotime($event['start'])) || date('Y/m/j', strtotime($row['end_date'])) != date('Y/m/j', strtotime($event['end']))) {
                $conflict = array('calendar' => $cal, 'listing' => $row['listing_id'], 'booking' => $row['booking_id'], 'conflict' => "<span class='red'>start date or end date mismatch on eventID :</span>" . $event['id']);

                return $conflict;
            }
        }
        if (date('Y/m/j', strtotime($row['start_date'])) == date('Y/m/j', strtotime($event['start'])) && date('Y/m/j', strtotime($row['end_date'])) == date('Y/m/j', strtotime($event['end']))) {
            $summary = " booking: " . $row['booking_id'] . " Listing: " . $row['listing_id'] . " from: " . $event['start'] . " end:" . $event['end'];
            $row['summary'] = $summary;
            $arr = array("row" => $row, "event" => $event);
            return $arr;
        }
        //return $event
    }
    return 0;
}

function saveEventToDb($row, $event)
{
    if (in_array($event['id'], $row)) {
        return 0;
    } else {
        return $event;
    }


}


