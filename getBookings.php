<?php
/**
 * Created by PhpStorm.
 * User: led
 * Date: 18-10-2015
 * Time: 19:31
 */

require_once 'api/backend/functions.php';
//$sql1="SELECT * FROM vrs6_bookings where listing_id=".$_POST['listing'];
$sql1 = "SELECT * FROM vrs6_bookings where event_id IS NULL AND (start_date >= CURDATE() AND listing_id=" . $_POST['listing'] . ")";

$rs1 = $db_conn->query($sql1);
$bookings = array();
while ($row = $rs1->fetch_assoc()) {
    array_push($bookings, $row);
}
$json = json_encode($bookings);

?>
<button class="bulkCreateEvents btn btn-warning btn-lg">Bulk create Events</button>

<table id="dtable5">
    <thead>
    <tr>
        <th></th>
        <th>id</th>
        <th>Blank BK</th>
        <th>Book Status</th>
        <th>Admin Status</th>
        <th>Star</th>
        <th>End</th>
        <th>Full Name</th>
        <th>EventID</th>
        <th>Event Name</th>
    </tr>
    </thead>

    <tfoot>
    <tr>
        <th></th>
        <th>id</th>
        <th>Blank BK</th>
        <th>Book Status</th>
        <th>Admin Status</th>
        <th>Star</th>
        <th>End</th>
        <th>Full Name</th>
        <th>EventID</th>
        <th>Event Name</th>
    </tr>
    </tfoot>
</table>
<script>
    $(document).ready(function () {
        var jsonArray =<?php echo json_encode($bookings) ?>;
        if (Object.keys(jsonArray).length > 0) {
            var table = $('#dtable5').DataTable({

                "pageLength": 50,
                data: jsonArray,
                columns: [
                    {
                        "className": 'isSynced',
                        "orderable": false,
                        data: null,
                        "defaultContent": "</span><span class='editBooking glyphicon glyphicon-pencil'></span>"
                    },
                    {data: 'booking_id'},
                    {data: 'blank_booking'},
                    {data: 'booking_status'},
                    {data: 'admin_status'},
                    {data: 'start_date'},
                    {data: 'end_date'},
                    {data: 'full_name'},
                    {
                        data: 'event_id',
                        "defaultContent": "<button class='createEvent2 btn btn-sm btn-default'>Send to Cal  <span class=' glyphicon glyphicon-circle-arrow-right'></span></button><img class='loader '  style='display:none' src='images/ajax-loader-small.gif'/>"
                    },
                    {data: 'event_name'},

                ],
            });
        } else {
            $("#dialog").html("No bookings or all bookings uploaded to calendar.")
        }
    });

    var calend = '<?php echo $_POST['cal']?>';
    var listing = '<?php echo $_POST['listing']?>';
    $(document).on('click', '.createEvent2', function (event) {
        event.stopImmediatePropagation();
        var tr = $(this).closest('tr');
        $(tr).find('td:eq(8) > img').show();
        var start = tr.find('td:eq(5)').text();
        var end = tr.find('td:eq(6)').text();
        var id = tr.find('td:eq(1)').text();
        var summary = " booking: " + id + " Listing: " + listing + " from:" + start + "end:" + end;

        $.post('createEvent.php', {cal: calend, start: start, end: end, summary: summary}, function (results) {
            result = JSON.parse(results);
            if (result.id === 1) {
                tr.find('td:eq(8)').html(result.id);
                tr.find('td:eq(9)').html(result.summary);
                $.post('saveEventToDb.php',{result:result,id:id},function(){
                    tr.append("<td class='alert alert-success'>Uploaded and saved to db</td>");
                });
            } else {
                //tr.find('td:eq(8)').html('Check your connection! Event not created!');
                tr.find('td:eq(8)').html(result.id);
                tr.find('td:eq(9)').html(result.summary);
            }
        });
    });


    /* $(document).on('click', '.syncBookings', function () {
     event.stopImmediatePropagation();
     var tr = $(this).closest('tr');
     var cell = tr.find('td:eq(3)').text();
     //var row = table.row(tr);

     $('#dialog').load('syncBooking.php', {listing: cell}).dialog("open");
     console.debug(cell);

     });
     $(document).on('click', '.editBooking', function () {
     event.stopImmediatePropagation();

     var tr = $(this).closest('tr');
     var cell = tr.find('td:eq(1)').text();
     //var row = table.row(tr);

     $('#dialog').load('getBook.php', {calendar: cell}).dialog("open");
     console.debug(cell);

     });*/

</script>