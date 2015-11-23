<?php
/**
 * Created by PhpStorm.
 * User: led
 * Date: 18-10-2015
 * Time: 19:24
 */
require_once 'api/backend/functions.php';
$sql1 = "SELECT * FROM vrs6_calendars where calendar_id='".$_POST['calendar']."'";

$rs1 = $db_conn->query($sql1);
$calendar= $rs1->fetch_assoc();

?>
<form id="updCal" action="updateCalendar">

    <input type="text" name="titleId" width="150" value="<?php echo $calendar['calendar_title']?>">
    <input type="text" name="listingId" value="<?php echo $calendar['listing_id']?>" id="listId">
    <input type="text" disabled=true name="calendarId" width="200" value="<?php echo $calendar['calendar_id']?>"><br>

    <input type="submit" value="Save">
</form>
<script>
    $("#updCal").submit(function() {

        var url = "path/to/your/script.php"; // the script where you handle the form input.

        $.ajax({
            type: "POST",
            url: url,
            data: $(this).serialize(), // serializes the form's elements.
            success: function(data)
            {
                alert(data); // show response from the php script.
            }
        });

        return false; // avoid to execute the actual submit of the form.
    });
    var ac_config = {
        source: "listingsAutoComplete.php",
        select: function (event, ui) {
            $("#autocomplete").val(ui.item.calendar_title);
            $("#listing_id").val(ui.item.listing_id);
            $("#calendar_id").val(ui.item.calendar_id);
        },
        minLength: 1
    };
    $("#listId").autocomplete(ac_config);


</script>