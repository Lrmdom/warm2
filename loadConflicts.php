
<table id="dtableConflicts">
    <thead>
    <tr>
        <th>calendar</th>
        <th>listing</th>
        <th>booking</th>
        <th>conflict</th>
    </tr>
    </thead>

    <tfoot>
    <tr>
        <th>calendar</th>
        <th>listing</th>
        <th>booking</th>
        <th>conflict</th>
    </tr>
    </tfoot>
</table>
<script>

    var table = $('#dtableConflicts').DataTable({

        "pageLength": 50,
        data: <?php echo $_POST['conflicts'] ?>,
        columns: [
            {data: 'calendar'},
            {data: 'listing'},
            {data: 'booking'},
            {data: 'conflict'},

        ],
    });

    //$('#allConflicts').html(<?php echo count($_POST['conflicts']) ?>)
</script>