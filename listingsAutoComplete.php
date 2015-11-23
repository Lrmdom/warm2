
<?PHP

/**
 * Created by PhpStorm.
 * User: led
 * Date: 22-10-2015
 * Time: 18:35
 */
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once 'api/backend/functions.php';
$req = "SELECT * FROM vrs6_listings where title_1 LIKE '%".$_GET['term']."%' ORDER BY listing_id ASC";
//var_dump($req);die();
$rs1 = $db_conn->query($req);
$listings= [];
$i = 0;

while($row = $rs1->fetch_assoc()){
    $listings[$i]['listing_id'] = $row['listing_id'];
    $listings[$i]['title_1'] = $row['title_1'];
    $i++;
}
$matches = array();
foreach ($listings as $list) {
    if (stripos($list['title_1'], $_GET['term']) !== false) {
        // Add the necessary "value" and "label" fields and append to result set
        $list['value'] = $list['listing_id'];
        //É isto q ele faz display
        $list['label'] = "{$list['title_1']}";
        //Isto fica acessivel no frontend
        $list['data'] = " {$list['listing_id']} - {$list['title_1']}";
        $matches[] = $list;
    }
}
//var_dump($matches);
// Truncate, encode and return the results
//$matches = array_slice($matches, 0, 5);
echo json_encode($matches);
?>