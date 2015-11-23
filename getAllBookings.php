<?php
/**
 * Created by PhpStorm.
 * User: led
 * Date: 18-10-2015
 * Time: 19:31
 */

require_once 'api/backend/functions.php';
$sql1 = "SELECT *,vrs6_calendars.calendar_id as calendar FROM vrs6_bookings LEFT JOIN vrs6_calendars ON vrs6_calendars.listing_id = vrs6_bookings.listing_id where event_id IS NULL AND start_date >= CURDATE()";

$rs1 = $db_conn->query($sql1);
$bookings = array();
while ($row = $rs1->fetch_assoc()) {
    array_push($bookings, $row);
}
$json = json_encode($bookings);
?>
<table id="dtable4">
    <thead>
    <tr>
        <th></th>
        <th>listing</th>
        <th>id</th>
        <th>Blank BK</th>
        <th>Book Status</th>
        <th>Admin Status</th>
        <th>Star</th>
        <th>End</th>
        <th>Full Name</th>
        <th>EventID</th>
        <th>Event Name</th>
        <th>Calendar</th>

    </tr>
    </thead>

    <tfoot>
    <tr>
        <th></th>
        <th>listing</th>
        <th>id</th>
        <th>Blank BK</th>
        <th>Book Status</th>
        <th>Admin Status</th>
        <th>Star</th>
        <th>End</th>
        <th>Full Name</th>
        <th>EventID</th>
        <th>Event Name</th>
        <th>Calendar</th>

    </tr>
    </tfoot>
</table>
<script>

    function checkCalendar(data) {
      return data;
    }

    $(document).ready(function () {

       $(".allBookings").html(<?php echo count($bookings)?>);

        var table = $('#dtable4').DataTable({
            "pageLength": 50,
            data:<?php echo json_encode($bookings) ?>,
            // data:dat,
            columns: [
                {
                    "className": 'isSynced',
                    "orderable": false,
                    data: null,
                    "defaultContent": "<button class='showCalendarLastWeekEvents btn btn-sm btn-default'>this week added events <span class=' glyphicon glyphicon-dashboard'></span></button><button class='showCalendarEvents btn btn-sm btn-default'>Show cal events <span class=' glyphicon glyphicon-list'></span></button><img class='loader '  style='display:none' src='images/ajax-loader-small.gif'/> "


                },
                {data: 'listing_id'},
                {data: 'booking_id'},
                {data: 'blank_booking'},
                {data: 'booking_status'},
                {data: 'admin_status'},
                {data: 'start_date'},
                {data: 'end_date'},
                {data: 'full_name'},
                {
                    data: 'event_id',
                    defaultContent: "<button class='createEvent btn btn-sm btn-default'>Send to Cal  <span class=' glyphicon glyphicon-circle-arrow-right'></span></button><img class='loader '  style='display:none' src='images/ajax-loader-small.gif'/>",
                    /* "render": function (data, type, full, meta) {
                     if (data === undefined || data === null) {
                     return 'No cal! ';
                     } else {
                     return "<button class='createEvent btn btn-sm btn-default'>Send to Cal  <span class=' glyphicon glyphicon-circle-arrow-right'></span></button><img class='loader '  style='display:none' src='images/ajax-loader-small.gif'/>",

                     }
                     }*/
                },
                {data: 'event_name'},
                {data: 'calendar'},
            ],
        });


    });



    $(document).on('click', '.showCalendarEvents', function (event) {
        event.stopImmediatePropagation();

        var tr = $(this).closest('tr');
        $(tr).find('td:eq(0) > img').show();

        var cell = tr.find('td:eq(11)').text();
        $.post('getEvents.php', {calendar: cell},function(res){
            $('#dialog').html(res).dialog("open");
            $(tr).find('td:eq(0) > img').hide();

        });

    });

    $(document).on('click', '.showCalendarLastWeekEvents', function (event) {
        event.stopImmediatePropagation();

        var tr = $(this).closest('tr');
        $(tr).find('td:eq(0) > img').show();

        var cell = tr.find('td:eq(11)').text();
        $.post('getWeekEvents.php', {calendar: cell},function(res){
            $('#dialog').html(res).dialog("open");
            $(tr).find('td:eq(0) > img').hide();

        });

    });

    $(document).on('click', '.createEvent', function (event) {


        event.stopImmediatePropagation();
        var tr = $(this).closest('tr');
        $(tr).find('td:eq(9) > img').show();
        var start = tr.find('td:eq(6)').text();
        var end = tr.find('td:eq(7)').text();
        var id = tr.find('td:eq(2)').text();
        var listing = tr.find('td:eq(1)').text();
        var calend = tr.find('td:eq(11)').text();

        var summary = " booking: " + id + " Listing: " + listing + " from:" + start + "end:" + end;
        if (checkCalendar(calend)) {
            $.post('createEvent.php', {cal: calend, start: start, end: end, summary: summary}, function (results) {
                var result = JSON.parse(results);
                if (result.id === 1) {

                    tr.find('td:eq(9)').html(result.id);
                    tr.find('td:eq(10)').html(result.summary);
                    $.post('saveEventToDb.php', {result: result, id: id}, function () {
                        tr.append("<td class='alert alert-success'>Uploaded and saved to db</td>");
                    });
                } else {
                   // tr.find('td:eq(9)').html('Check your connection! Event not created!');
                    tr.find('td:eq(9)').html(result.id);
                    tr.find('td:eq(10)').html(result.summary);
                }
            });
        }else{
            alert('Calendar non existent!');
            $(tr).find('td:eq(9) > img').hide();
        }
    });

</script>