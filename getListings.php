<?php
/**
 * Created by PhpStorm.
 * User: led
 * Date: 22-10-2015
 * Time: 22:40
 */

require_once 'api/backend/functions.php';
$req = "SELECT vrs6_listings.listing_id, vrs6_listings.title_1, vrs6_calendars.calendar_id, vrs6_calendars.calendar_title FROM  vrs6_listings LEFT JOIN vrs6_calendars ON vrs6_listings.listing_id = vrs6_calendars.listing_id where vrs6_calendars.listing_id IS NULL ORDER BY vrs6_listings.listing_id";
//var_dump($req);die();
$rs1 = $db_conn->query($req);
$listing = array();
$listings = array();

while ($row = $rs1->fetch_assoc()) {
    $listing['listing_id'] = $row['listing_id'];
    $listing['title_1'] = $row['title_1'];
    $listing['calendar_id'] = $row['calendar_id'];
    $listing['calendar_title'] = $row['calendar_title'];
    $listings[] = $listing;
}

$jsonList = json_encode($listings);
$nNoCals = count($listings);
?>
<button class="bulkCreateCal btn btn-warning btn-lg">Bulk create Calendars on this page</button>
<br>
<br>
<table id="dtable3">
    <thead>
    <tr>
        <th>Listing Id</th>
        <th>Listing title</th>
        <th>Calendar Id</th>
        <th>Calendar Title</th>

    </tr>
    </thead>

    <tfoot>
    <tr>
        <th>Listing Id</th>
        <th>Listing title</th>
        <th>Calendar Id</th>
        <th>Calendar Title</th>

    </tr>
    </tfoot>
</table>

<script>

    $(document).ready(function () {
        $(".noCalendar").html(<?php echo $nNoCals?>);
        var dt =<?php echo $jsonList?>;
        var gCals =<?php echo json_encode($_POST['gCals'])?>;
        $('#dtable3').DataTable({

            "pageLength": 10,
            data: dt,
            columns: [
                {data: 'listing_id'},
                {data: 'title_1'},
                {
                    data: 'calendar_id',
                    "defaultContent": "<button class='createCalendar btn btn-sm btn-default'>Create  <span class=' glyphicon glyphicon-calendar'></span></button><img class='loader '  style='display:none' src='images/ajax-loader-small.gif'/> "
                },
                {data: 'calendar_title'},
            ]
        });

        $(document).on('click', '.bulkCreateCal', function (event) {
            event.stopImmediatePropagation();
            $(".createCalendar").each(function () {
                $(this).trigger('click');
            });

        });


        $(document).on('click', '.createCalendar', function (event) {
            event.stopImmediatePropagation();
            var tr = $(this).closest('tr');
            $(tr).find('td:eq(2) > img').show();
            var calTitle = "#" + tr.find('td:eq(0)').text() + " " + tr.find('td:eq(1)').text();
            $.post('createCalendar.php', {calTitle: calTitle, gCals: gCals}, function (results) {
                result = JSON.parse(results);
                if (result.id) {
                    tr.find('td:eq(2)').html(result.id);
                    tr.find('td:eq(3)').html(result.summary);
                    $.post('saveCalendarToDb.php',{result:result,listing:tr.find('td:eq(0)').text()})
                } else {
                    tr.find('td:eq(3)').html('Check your connection! Cal not created!');
                }
            });
        });
    });
</script>