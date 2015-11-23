<?PHP
/*
  # Warmrental Google Sync
  # Powered by comPonto.com
  # Configurações globais - Conecções e variáveis globais
 */


//error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
session_name("warmrental_google_sync_session");
session_start();

//Dataware House
require_once 'settings/global.php';
require_once 'api/dwh.php';

if (isset($false_access_token)) {
    die("<br>Acesso inválido, por favor faça o login <a href='index.php'>aqui</a>");
} else {

}

//Script start
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="comPonto.com">
    <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="http://www.warmrental.com/favicon.ico">

    <title>Warmrental - Google Calendar Sync</title>
    <style>
        /* cyrillic */
        @font-face {
            font-family: 'Lobster';
            font-style: normal;
            font-weight: 400;
            src: local('Lobster'), local('Lobster-Regular'), url(http://fonts.gstatic.com/s/lobster/v11/c28rH3kclCLEuIsGhOg7evY6323mHUZFJMgTvxaG2iE.woff2) format('woff2');
            unicode-range: U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
        }

        /* latin-ext */
        @font-face {
            font-family: 'Lobster';
            font-style: normal;
            font-weight: 400;
            src: local('Lobster'), local('Lobster-Regular'), url(http://fonts.gstatic.com/s/lobster/v11/9NqNYV_LP7zlAF8jHr7f1vY6323mHUZFJMgTvxaG2iE.woff2) format('woff2');
            unicode-range: U+0100-024F, U+1E00-1EFF, U+20A0-20AB, U+20AD-20CF, U+2C60-2C7F, U+A720-A7FF;
        }

        /* latin */
        @font-face {
            font-family: 'Lobster';
            font-style: normal;
            font-weight: 400;
            src: local('Lobster'), local('Lobster-Regular'), url(http://fonts.gstatic.com/s/lobster/v11/hhO8-q4hv9jbU4UQyl-u4vY6323mHUZFJMgTvxaG2iE.woff2) format('woff2');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2212, U+2215, U+E0FF, U+EFFD, U+F000;
        }

        #loading {
            display: none;
        }

        .red {
            color: darkred;
        }

        #loading img
    </style>
    <link rel="stylesheet" type="text/css" href="src/jquery-ui-1.11.0.custom/jquery-ui.css">

    <!-- Bootstrap core CSS -->
    <link href="src/bootstrap-3.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/slick_lf.css" media="screen"/>

    <!-- Custom styles for this template -->
    <link href="css/calendars.css" rel="stylesheet">

    <script src="js/jquery-2.1.1.min.js"></script>
    <script src="src/jquery-ui-1.11.0.custom/jquery-ui.js"></script>
</head>

<body>


<!-- Main jumbotron for a primary marketing message or call to action -->
<div class="jumbotron">
    <div class="container">
        <button name="logout" class="btn btn-danger logoutBut" id="logoutbut"><span
                class="glyphicon glyphicon-log-out"></span> Logout
        </button>
        <div class="login_page">
            Warmrental Google Sync
            <h1>Warmrental.com</h1>
        </div>
        <p class="lato"><span style="color: red;">Bem vindo <strong><?PHP echo $_SESSION['username']; ?></strong></span>,
            existem neste momento <span style="color: red;"> <?PHP echo $_SESSION['num_props']; ?> </span>propriedades
            registadas com calendários atribuidos. Clique no botão seguinte para entrar na central de sincronização.</p>

        <div class="row">
            <div class="col-md-2">
            </div>
            <div class="col-md-2">
            </div>
            <div class="col-md-2">
                <p><a id="calendar_redirect" style="width: 100%" class="btn btn-success btn-lg" role="button"><span
                            class="glyphicon glyphicon-refresh"></span> Sincronizar</a></p>
            </div>
            <div class="col-md-2">
                <p><a id="manutencao" style="color: #A22628; width: 100%" class="btn btn-warning btn-lg"
                      role="button"><span class="glyphicon glyphicon-refresh"></span> Manutenção </a></p>
            </div>
            <div class="col-md-2">
            </div>
            <div class="col-md-2">
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.9/css/jquery.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://nightly.datatables.net/js/jquery.dataTables.min.js"></script>

<div class="container">

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">

        <li><a href="#calendarsList" role="tab" data-toggle="tab">
                <i class="fa fa-user"></i> <strong>Calendars</strong> With listings
                <span class="badge calList"><?php echo count($db_Calendars) ?></span>

            </a>
        </li>
        <li>
            <a href="getListings.php" data-target="#listingsList" data-toggle="tabajax" role="tab"
               data-toggle="tab">
                <icon class="fa fa-home"></icon>
                <strong>Listings</strong> Without calendar
                <span class="badge noCalendar"></span>
            </a>

        </li>
        <li>
            <a href="getAllBookings.php" role="tab" data-target="#listingsList" data-toggle="tabajax2">
                <i class="fa fa-envelope"></i><strong>Bookings</strong> Not Synced
                <span class="badge allBookings"></span>
            </a>
        </li>
        <li>
            <a href="conflictManager.php" role="tab" data-target="#conflictManager" data-toggle="tabajax3">
                <i class="fa fa-envelope"></i><strong>Conflicts</strong> Manager
                <span class="badge allConflicts"></span>
            </a>
        </li>

    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
        <div class="tab-pane fade active in" id="calendarsList">

            <table id="dtable1">
                <thead>
                <tr>
                    <th></th>
                    <th>id</th>
                    <th>title</th>
                    <th>listing</th>
                    <th>sync date</th>
                    <th></th>
                </tr>
                </thead>

                <tfoot>
                <tr>
                    <th></th>
                    <th>id</th>
                    <th>title</th>
                    <th>listing</th>
                    <th>sync date</th>
                    <th></th>

                </tr>
                </tfoot>
            </table>

            <script>

                function sanitizeData(dat) {
                    result = [];
                    Object.keys(dat).forEach(function (key) {
                        result.push(dat[key]);
                    })
                    return result;
                }

                // gCals = sanitizeData(<?php echo json_encode($google_Calendars)?>);
                //dat = sanitizeData(<?php echo json_encode($db_Calendars) ?>);
                gCals = [{
                    "calendar_id": "l4ljqatnprkju6jak926frk294@group.calendar.google.com",
                    "calendar_title": "#54 ESPECIAL Macarena",
                    "listing_id": "54",
                    "syncdate": "2015-11-10 00:05:39"
                }, {
                    "calendar_id": "mrsrmb6385usran824q57ufak0@group.calendar.google.com",
                    "calendar_title": "#55 ESPECIAL Ceilidh",
                    "listing_id": "55",
                    "syncdate": "2015-11-10 00:05:39"
                }];
                dat = [{
                    "calendar_id": "l4ljqatnprkju6jak926frk294@group.calendar.google.com",
                    "calendar_title": "#54 ESPECIAL Macarena",
                    "listing_id": "54",
                    "syncdate": "2015-11-10 00:05:39"
                }, {
                    "calendar_id": "mrsrmb6385usran824q57ufak0@group.calendar.google.com",
                    "calendar_title": "#55 ESPECIAL Ceilidh",
                    "listing_id": "55",
                    "syncdate": "2015-11-10 00:05:39"
                }];

                $(document).ready(function () {

                    var table = $('#dtable1').DataTable({

                        "pageLength": 50,
                        data: dat,
                        columns: [
                            {
                                "className": 'details-control',
                                "orderable": false,
                                data: null,
                                "defaultContent": "<button class='showBookings btn btn-small btn-default'>bookings <span class='glyphicon glyphicon-list'></span></button><button class='showEvents btn btn-small btn-default'>Events <span class='showEvents glyphicon glyphicon-cloud-download'></span></button><img class='loader '  style='display:none' src='images/ajax-loader-small.gif'/>"
                            },
                            {data: 'calendar_id'},
                            {data: 'calendar_title'},
                            {data: 'listing_id', "className": "listingId"},
                            {data: 'syncdate'},
                            {
                                "className": 'sync-control',
                                "orderable": false,
                                data: null,
                                "defaultContent": "<button class='syncCal btn btn-small btn-warning'>Sync Calendar <span class='glyphicon glyphicon-refresh'></span><img class='loader'  style='display:none' src='images/ajax-loader-small.gif'/>"
                            },
                        ],
                    });
                    $(document).on('click', '.showBookings', function (event) {
                        event.stopPropagation();

                        var tr = $(this).closest('tr');
                        $(tr).find('td:eq(0) > img').show();

                        var cell = tr.find('td:eq(3)').text();
                        var calend = tr.find('td:eq(1)').text();
                        $.post('getBookings.php', {listing: cell, cal: calend}, function (res) {
                            $('#dialog').html(res).dialog("open");
                            $(tr).find('td:eq(0) > img').hide();

                        });
                    });
                    $(document).on('click', '.showEvents', function (event) {
                        event.stopPropagation();

                        var tr = $(this).closest('tr');
                        $(tr).find('td:eq(0) > img').show();

                        var cell = tr.find('td:eq(1)').text();
                        $.post('getEvents.php', {calendar: cell}, function (res) {
                            $('#dialog').html(res).dialog("open");
                            $(tr).find('td:eq(0) > img').hide();

                        });

                    });

                    $(document).on('click', '.syncCal', function (event) {
                        event.stopPropagation();

                        var tr = $(this).closest('tr');
                        $(tr).find('td:eq(0) > img').show();

                        var cell = tr.find('td:eq(1)').text();
                        var gcal={0: {
                            calendar_id: tr.find('td:eq(1)').text(),
                            title: tr.find('td:eq(2)').text(),
                            listing_id: tr.find('td:eq(3)').text()
                        }
                        };
                        gcal=JSON.stringify(gcal);
                        $.post('conflictManager.php', {gCals:gcal,dbCals:gcal}, function (res) {
                            $('#dialog').html(res).dialog("open");
                            $(tr).find('td:eq(0) > img').hide();

                            var conflicts = res;
                            if (Object.keys(conflicts).length > 0) {
                                $.post('loadConflicts.php', {conflicts: conflicts}, function (data) {
                                    $('#dialog').html(data).dialog("open");

                                });

                            } else {
                                $('#dialog').html('No conflicts!!').dialog("open");                            }

                        });

                    });

                    $(".syncCalendars").on("click", function () {
                        $('[data-toggle="tabajax3"]').trigger('click');

                    });

                    $("#dialog").dialog({
                        autoOpen: false,
                        resizable: true,
                        modal: false,
                        width: 'auto',
                        minWidth: 600,
                        minHeight: 300
                    });
                });


            </script>


        </div>
        <div class="tab-pane fade" id="listingsList">


        </div>

        <div class="tab-pane fade" id="bookingsList">

        </div>
        <div class="tab-pane fade" id="conflictManager">
            <button class="startSync btn btn-large btn-warning" data-remote="conflictManager.php"
                    data-target="#conflictManager">Start sync <span class="glyphicon glyphicon-refresh"></span>
            </button>
            <div id="loading">
                <h4 class="alert-warning">Please wait till finish sync. It will delay sync process. Thanks.</h4>
                <img class='loading' src="images/ajax-loader.gif"/>
            </div>
            <span class="noConflicts alert-success"></span>


        </div>

    </div>

</div>


<div id="dialog">
</div>
<div class="container-fluid">
    <div class="row-fluid">
        <div class="col col-md-12">
            <h2 style="font-family: 'Lobster', Georgia, Times, serif;">Property Assign</h2>
            <hr style="width: 100%; height: 1px; color:#09C; background-color: #09C;">
        </div>
    </div>
    <div class="row-fluid">

        <div class="col-md-2">
            <h2 style="text-align: center">Visualizar</h2>
            <br>
            <br>

            <p>
                <button name="get_prop" style="font-size: 18px;" class="form-control btn btn-success" id="get_prop">
                    <span class="glyphicon glyphicon-eye-open"></span></button>
            </p>
        </div>

        <div class="col-md-4">
            <h2 style="text-align: center">Selecionar Calendário</h2>
            <br>
            <br>

            <p>
                <input name="autocomplete" style="text-align: center" placeholder="Calendario" class="form-control"
                       id="autocomplete">
            </p>
        </div>

        <div class="col-md-4">
            <h2 style="text-align: center">Editar listing_id</h2>
            <br>
            <br>

            <p>
                <input name="listing_id" style="text-align: center" placeholder="ID propriedade" class="form-control"
                       id="listing_id">
            </p>
        </div>

        <div class="col-md-2">
            <h2 style="text-align: center">Assign</h2>
            <br>
            <br>

            <p>
                <input type="text" name="calendar_id" id="calendar_id">
                <button name="upd_prop" style="font-size: 18px;" class="form-control btn btn-warning" id="upd_prop">
                    <span class="glyphicon glyphicon-save"></span></button>
            </p>
        </div>
    </div>

    <div class="row-fluid">
        <div class="col col-md-12">
            <br><br>
            <br><br>

            <h2 style="font-family: 'Lobster', Georgia, Times, serif;">Property Unassign</h2>
            <hr style="width: 100%; height: 1px; color:#09C; background-color: #09C;">
        </div>
    </div>
    <div class="row-fluid">
        <div class="col col-md-3">
        </div>
        <div class="col col-md-3">
            <h2 style="text-align: center">Inserir listing_id</h2>
            <br>
            <br>

            <p>
                <input name="error_listing_id" style="text-align: center" placeholder="ID propriedade"
                       class="form-control" id="error_listing_id">
            </p>
        </div>
        <div class="col col-md-3">
            <h2 style="text-align: center">Unassign</h2>
            <br>
            <br>

            <p>
                <input type="text" name="error_calendar_id" id="error_calendar_id">
                <button name="error_unassign_prop" style="font-size: 18px;" class="form-control btn btn-danger"
                        id="error_unassign_prop"><span class="glyphicon glyphicon-save"></span></button>
            </p>
        </div>
        <div class="col col-md-3">
        </div>
    </div>
    <div class="row-fluid">
        <div class="col col-md-12">
            <br><br>
            <br><br>

            <h2 style="font-family: 'Lobster', Georgia, Times, serif;">Calendar events mtime correction</h2>
            <hr style="width: 100%; height: 1px; color:#09C; background-color: #09C;">
        </div>
    </div>
    <div class="row-fluid">
        <div class="col col-md-3">
        </div>
        <div class="col col-md-3">
            <h2 style="text-align: center">Calendário</h2>
            <br>
            <br>

            <p>
                <input name="autocomplete_mtime" style="text-align: center" placeholder="Calendario"
                       class="form-control" id="autocomplete_mtime">
            </p>
        </div>
        <div class="col col-md-3">
            <h2 style="text-align: center">Correct</h2>
            <br>
            <br>

            <p>
                <input type="text" name="calendar_id_mtime" id="calendar_id_mtime">
                <button name="correct_mtime" style="font-size: 18px;" class="form-control btn btn-warning"
                        id="correct_mtime"><span class="glyphicon glyphicon-save"></span></button>
            </p>
        </div>
        <div class="col col-md-3">
        </div>
    </div>
    <div class="row-fluid" id="conteudos">
        <div class="col-md-12">
            <br>
            <br>

            <h2 style="font-family: 'Lobster', Georgia, Times, serif;">Server Messages</h2>
            <hr style="width: 100%; height: 1px; color:#09C; background-color: #09C;">
            <br>
            <br>

            <div id="" style="overflow:auto; height:250px;">
                <pre id="server_results"></pre>
            </div>
            <br>
            <br>
            <hr style="width: 100%; height: 1px; color:#09C; background-color: #09C;">
            <h2 style="font-family: 'Lobster', Georgia, Times, serif;">Debugging</h2>
            <br>
            <br>

            <div id="" style="overflow:auto; height:200px;">
                                                                                                                                                                                                        <pre>
<?PHP
echo "agora";
pArray($google_Calendars, "google_Calendars");
pArray($db_Calendars, "db_Calendars");

pArray($Calendars, "Calendars");

?>
                                                                                                                                                                                                        </pre>
            </div>

            <footer>
                <p style="padding-top: 5px; font-size: 11px; color:#F90;">Powered by <a href="http://www.comPonto.com">comPonto.com</a>
                </p>
            </footer>
        </div>
    </div>
</div>
<!-- /container -->

<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<script src="src/bootstrap-3.2.0/dist/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function () {


        var ac_config = {
            source: "api/autocomplete.php",
            select: function (event, ui) {
                $("#autocomplete").val(ui.item.calendar_title);
                $("#listing_id").val(ui.item.listing_id);
                $("#calendar_id").val(ui.item.calendar_id);
            },
            minLength: 1
        };
        $("#autocomplete").autocomplete(ac_config);

        var ac_config2 = {
            source: "api/autocomplete.php",
            select: function (event, ui) {
                $("#autocomplete_mtime").val(ui.item.calendar_title);
                $("#calendar_id_mtime").val(ui.item.calendar_id);
            },
            minLength: 1
        };
        $("#autocomplete_mtime").autocomplete(ac_config2);

        $('[data-toggle="tabajax"]').click(function (e) {
            //var gCals = sanitizeData(<?php echo json_encode($google_Calendars)?>;
            var $this = $(this),
                loadurl = $this.attr('href'),
                targ = $this.attr('data-target');
            $(targ).empty();
            $.post(loadurl, {gCals: gCals}, function (data) {
                $(targ).html(data);
            });

            $this.tab('show');
            return false;
        });
        $('[data-toggle="tabajax2"]').click(function (e) {
            $("#loading").show();
            var $this = $(this),
                loadurl = $this.attr('href'),
                targ = $this.attr('data-target');
            $(targ).empty();
            $.post(loadurl, {gCals: gCals}, function (data) {
                $(targ).html(data);
                $("#loading").hide();
            });

            $this.tab('show');
            return false;
        });
        $('[data-toggle="tabajax3"]').click(function (e) {
            var $this = $(this);
            $this.tab('show');
            return false;
        });
        $('.startSync').on('click',function (e) {

            $("#loading").show();
            var $this = $(this),
                loadurl = $this.attr('data-remote');
            //targ = $this.attr('data-target');
            $.post(loadurl, {dbCals: JSON.stringify(dat), gCals: JSON.stringify(gCals)}, function (data) {
                var conflicts = data;
                if (Object.keys(conflicts).length > 0) {
                    $.post('loadConflicts.php', {conflicts: JSON.stringify(conflicts)}, function (data) {
                        $('.noConflicts').html(data)

                    });

                } else {
                    $('.noConflicts').html("No conflicts!!")
                }
                $("#loading").hide();
            }, "json").fail(function () {
                alert("error");
            });

        });

        $("#dialog").dialog({
            autoOpen: false,
            resizable: false,
            modal: true,
            width: 'auto',
            minWidth: 600,
            minHeight: 300
        });


    });
</script>
<script src="js/warmrental.js"></script>
</body>
</html>