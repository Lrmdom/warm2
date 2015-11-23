<?PHP

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
include '../settings/global.php';
$mysql_server = $db_host;
$mysql_login = $db_username;
$mysql_password = $db_password;
$mysql_database = $db_database;

mysql_connect($mysql_server, $mysql_login, $mysql_password);
mysql_select_db($mysql_database);

$req = "SELECT * "
        . "FROM vrs6_calendars ORDER BY calendar_title ASC";

$query = mysql_query($req);
$i = 0;
while ($row = mysql_fetch_array($query)) {

    $calendars_db[$i]['id'] = $row['id'];
    $calendars_db[$i]['listing_id'] = $row['listing_id'];
    $calendars_db[$i]['calendar_id'] = $row['calendar_id'];
    $calendars_db[$i]['calendar_title'] = utf8_encode($row['calendar_title']);

    $i++;
}

// Cleaning up the term
$term = trim(strip_tags($_GET['term']));

// Rudimentary search
$matches = array();
foreach ($calendars_db as $cal) {
    if (stripos($cal['calendar_title'], $term) !== false) {
        // Add the necessary "value" and "label" fields and append to result set
        $cal['value'] = $cal['calendar_title'];
        //É isto q ele faz display
        $cal['label'] = "{$cal['calendar_title']}";
        //Isto fica acessivel no frontend
        $cal['data'] = " {$cal['calendar_id']} - {$cal['listing_id']}";
        $matches[] = $cal;
    }
}

// Truncate, encode and return the results
$matches = array_slice($matches, 0, 5);
print json_encode($matches);
?>