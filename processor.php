<?PHP

/*
  # Warmrental Google Sync
  # Powered by comPonto.com
  # API Processor
 */


//Debugging
//error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'settings/global.php';
require_once 'api/dwh.php';

if (empty($_POST)) {
    die("<p style=\\");
    exit();
} else {

    //Processamento de edição no backoffice, tem que vir antes dos require_once devido a ser AJAX
    if (isset($_POST['flag'])) {

        //$pk 	= $_POST['pk'];
        //$name 	= $_POST['name'];
        //$value 	= $_POST['value'];
        $flag = $_POST['flag'];

        if (!empty($flag)) {

            global $Actions;
            $Actions = array();

            switch ($flag) {
                case "login_submit":
                    $output = processLogin($_POST['username'], $_POST['password']);
                    echo json_encode($output);
                    exit;
                    break;
                case "logout_but":
                    $output = processLogout();
                    echo json_encode($output);
                    exit;
                    break;
                case "upd_property":
                    $output = processUpdProp($_POST['calendar_id'], $_POST['listing_id']);
                    echo json_encode($output);
                    exit;
                    break;
                case "unassign_property":
                    $output = processUnassign($_POST['listing_id']);
                    echo json_encode($output);
                    exit;
                    break;
                case "sync_close":
                    $output = processSync_close();
                    echo json_encode($output);
                    exit;
                    break;
                case "calendar_maintenance":
                    $output = processCal_maintenance();
                    echo json_encode($output);
                    exit;
                    break;
                case "calendar_mtime_correction":
                    $output = processCal_corrections($_POST['calendarID']);
                    echo json_encode($output);
                    exit;
                    break;
                case "get_mod_arrays":
                    $output = get_mod_Arrays();
                    echo json_encode($output);
                    exit;
                    break;
                case "sync_onlyDB":
                    $output = process_sync_onlyDB($_POST['mod_DB']);
                    echo json_encode($output);
                    exit;
                    break;
                case "sync_BOTH":
                    $output = process_sync_BOTH($_POST['mod_DB']);
                    echo json_encode($output);
                    exit;
                    break;
                case "process_conflicts":
                    $output = process_Conflicts($_POST['entrada'], $_POST['mode']);
                    echo json_encode($output);
                    exit;
                    break;
                case "sync_cancel_res":
                    $output = processCancelRes($_POST['canceladas'], $_POST['tot_google']);
                    echo json_encode($output);
                    exit;
                    break;
                case "get_property":
                    $output = processGetProp();
                    echo json_encode($output);
                    exit;
                    break;
                default:
                    $resposta = array();
                    $resposta['ok'] = true;
                    $resposta['responseText'] = "Error with: " . $_POST['flag'];
                    echo json_encode($resposta);
                    exit;
                    break;
            }


            /* header('HTTP 200 Ok', true, 200);
              echo "This field is required!"; */
        } else {
            /*
              In case of incorrect value or error you should return HTTP status != 200.
              Response body will be shown as error message in editable form.
             */

            header('HTTP 400 Bad Request', true, 400);
            echo "This field is required!";
        }
    }
}

//###############################  API PROCESSOR  #############################

/**
 * @param $listing_id
 * @return string
 */
function processUnassign($listing_id) {
    global $db_conn;
    global $service;
    $resposta['ok'] = 0;
    $result = array();

    //Fazer reset ao listing_id na tab calendarios

    $sql = "SELECT calendar_id FROM vrs6_calendars WHERE listing_id='$listing_id'";
    $rs = $db_conn->query($sql);


    if ($rs === false) {
        trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $db_conn->error, E_USER_ERROR);
    } else {
        $rows = $rs->num_rows;
    }
    //Record sets
    $rs->data_seek(0);

    $i = 0;
    while ($row = $rs->fetch_assoc()) {
        $resultado = $row['calendar_id'];
        $i++;
    }
    $rs->free();

    if (!isset($resultado)) {
        $resposta['responseText'] = "Listing_id não consta na BD.";
        $resposta['ok'] = 0;
        return json_encode($resposta);
    }
    $resposta['listing_id'] = $listing_id;
    $resposta['calendar_id'] = $resultado;

    $sql = "UPDATE vrs6_calendars SET listing_id=0 WHERE listing_id='$listing_id'";
    $rs = $db_conn->query($sql);

    //Apagar os 555 da tabela eventos
    $sql = "DELETE FROM vrs6_bookings WHERE (user_id=555 AND calendar_id='" . $resultado . "')";
    $rs = $db_conn->query($sql);

    //Apagar Eventos no Google
    $events = $service->events->listEvents($resultado);
    while (true) {
        foreach ($events->getItems() as $event) {

            delete_google_event($resultado, $event['id']);
            $resposta['eventos_apagados'][$event['id']] = $event;
        }

        $pageToken = $events->getNextPageToken();
        if ($pageToken) {
            $optParams = array('pageToken' => $pageToken);
            $events = $service->events->listEvents($cal_id, $optParams);
        } else {
            break;
        }
    }

    $resposta['ok'] = 1;
    $resposta['responseText'] = "Unassign concluido.";
    return json_encode($resposta);
}

/**
 * @return string
 */
function processCal_maintenance() {
    global $db_Calendars;
    global $google_Calendars;

    $resposta['Calendars'] = $db_Cals = $db_Calendars;
    $resposta['google_Calendars'] = $G_Cals = $google_Calendars;

    $resposta['ok'] = 0;
    $resposta['responseText'] = "";
    $del = "";

    foreach ($db_Cals as $cal) {
        $db_id = $cal['calendar_id'];
        $found = 0;
        $found_cal = $cal['calendar_title'];
        foreach ($G_Cals as $gcal) {
            $g_id = $gcal['calendar_id'];
            if ($db_id == $g_id) {
                $found = 1;
            }
        }

        if (!$found) {
            delete_db_calendar($db_id);
            $del .= '<span class="server_request">' . $found_cal . '</span>, ';
        }
    }

    if ($del != "") {
        $del = substr($del, 0, -2);
        $resposta['responseText'] = "Apagados os calendarios: " . $del;
        $resposta['ok'] = 1;
    } else {
        $resposta['responseText'] = "Manutenção concluida, nenhuma acção foi necessária.";
    }


    return json_encode($resposta);
}

/**
 * @param $cal_id
 * @return string
 */
function processCal_corrections($cal_id) {
    $resposta['ok'] = 1;
    $resposta['cal_id'] = $cal_id;
    $eventos = get_mod_googleEvents2($cal_id);
    $counter = 0;
    $eventoz = "";
    foreach ($eventos as $evento) {
        $updateData['summary'] = $evento['event_name'] . "#";
        $event_id = $evento['event_id'];
        $eventoz .= $evento['event_name'] . ", ";
        update_google_event($cal_id, $event_id, $updateData);
        $counter++;
    }
    $eventoz = substr($eventoz, 0, -2);
    $resposta['responseText'] = "Foram alterados " . $counter . " eventos." . $eventoz;
    return json_encode($resposta);
}

/**
 * @return string
 */
function processGetProp() {
    global $db_conn;

    //verificar se listing_id ja existe
    $sql = "SELECT * FROM vrs6_calendars WHERE listing_id > '0'";
    $rs = $db_conn->query($sql);

    if ($rs === false) {
        trigger_error('Wrong SQL: ' . $sql1 . ' Error: ' . $db_conn->error, E_USER_ERROR);
    } else {
        $num_rows = $rs->num_rows;
    }

    if ($num_rows < 1) {
        $resposta['ok'] = 0;
        $resposta['responseText'] = 'Nenhuma propriedade registada.';
    } else {
        $resposta['ok'] = 1;
        $resposta['responseText'] = 'Existem propriedades registadas.';
        //Record sets
        $rs->data_seek(0);
        $arrayProps = array();
        //Popular array Users
        $i = 0;
        while ($row = $rs->fetch_assoc()) {

            $arrayProps[$i]['id'] = $row['id'];
            $arrayProps[$i]['calendar_id'] = $row['calendar_id'];
            $arrayProps[$i]['calendar_title'] = $row['calendar_title'];
            $arrayProps[$i]['listing_id'] = $row['listing_id'];
            $i++;
        }
        $resposta['array'] = $arrayProps;
        $rs->free();
    }

    return json_encode($resposta);
}

/**
 * @param $calendar_id
 * @param $listing_id
 * @return string
 */
function processUpdProp($calendar_id, $listing_id) {
    global $db_conn;

    //verificar se listing_id ja existe
    $sql = "SELECT * FROM vrs6_calendars WHERE listing_id = '" . $listing_id . "'";
    $rs = $db_conn->query($sql);

    if ($rs === false) {
        trigger_error('Wrong SQL: ' . $sql1 . ' Error: ' . $db_conn->error, E_USER_ERROR);
    } else {
        $num_rows = $rs->num_rows;
    }

    if (($num_rows < 1) || $listing_id == 0) {

        $stmt = $db_conn->prepare("UPDATE vrs6_calendars SET listing_id = ?
		   WHERE calendar_id = ?");
        $stmt->bind_param('is', $listing_id, $calendar_id);
        $stmt->execute();

        $stmt = $db_conn->prepare("UPDATE vrs6_bookings SET calendar_id = ?
		   WHERE listing_id = ?");
        $stmt->bind_param('si', $calendar_id, $listing_id);
        $stmt->execute();

        $stmt->close();
        $resposta['ok'] = 1;
        $resposta['responseText'] = 'Propriedade editada com sucesso';
    } else {
        $resposta['ok'] = 0;
        $rs->data_seek(0);
        $arrayProps = array();
        //Popular array Users
        $i = 0;
        while ($row = $rs->fetch_assoc()) {

            $arrayProps[0]['id'] = $row['id'];
            $arrayProps[0]['calendar_id'] = $row['calendar_id'];
            $arrayProps[0]['calendar_title'] = $row['calendar_title'];
            $arrayProps[0]['listing_id'] = $row['listing_id'];
            $i++;
        }
        $resposta['array'] = $arrayProps;
        $rs->free();
        $resposta['responseText'] = 'ID de propriedade já existente no calendário <span class="server_request">' . $arrayProps[0]['calendar_title'] . '</span> (calendar_id: <span class="server_request">' . $arrayProps[0]['calendar_id'] . '</span>).';
    }

    return json_encode($resposta);
}

/**
 * @param $user
 * @param $pass
 * @return array
 */
function processLogin($user, $pass) {
    global $bo_Users;
    global $client;
    $resposta = array();

    $check_password = $pass;

    $resposta['ok'] = 0;
    foreach ($bo_Users as $row_Admin) {
        if ($row_Admin['password'] == $check_password and $user == $row_Admin['username']) {
            $_SESSION['bo_auth'] = 1;
            $_SESSION['email'] = $row_Admin['email'];
            $_SESSION['id_user'] = $row_Admin['id'];
            $_SESSION['username'] = $row_Admin['username'];
            $_SESSION['refresh_token'] = $row_Admin['refresh_token'];
            $resposta['ok'] = 1;
            $resposta['user'] = $row_Admin['username'];
        }
    }
    $authUrl = "#";
    if ($resposta['ok']) {
        set_include_path("src/" . PATH_SEPARATOR . get_include_path());
        require_once 'src/Google/Client.php';
        require_once 'settings/config_api.php';

        if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
            //Autenticação Ok, redirect para refresh.
            unset($_SESSION['access_token']);
            $authUrl = $client->createAuthUrl();
        } else {
            $authUrl = $client->createAuthUrl();
        }
    }

    $resposta['responseText'] = "";
    $resposta['authLink'] = $authUrl;
    $_SESSION['authLink'] = $authUrl;
    return $resposta;
}

/**
 * @return string
 */
function get_mod_Arrays() {
    //Globals
    global $service;
    global $db_conn;
    global $Calendars;
    global $google_Calendars;

    global $tot_google;
    global $mod_google_Events;

    //Start Vars
    $resposta = array();
    $resposta['google'] = array();
    $resposta['db'] = array();
    $resposta['tot_google'] = array();
    $resposta['tot_db'] = array();
    $resposta['accoes'] = array();
    $resposta['accoes'][0] = "[0] Sync Start";
    $mod_db_Events = array();
    $mod_google_Events = array();
    $canceladas = array();

    $del_gooogleEvts = array();

    $resposta['ok'] = 0;
    $resposta['google_flag'] = 0;
    $resposta['db_flag'] = 0;
    $resposta['cancel_flag'] = 0;

    $db_Events = array();
    $resposta["DELETED_FROM_GOOGLE"] = array();

    //Verificação de necessidades
    //Existem calendários a sincronizar?
    if (!(count($Calendars) > 0)) {
        $resposta['ok'] = 1;
        return json_encode($resposta);
    }


    $h = 0;
    //Check Google Calendars and DB
    foreach ($Calendars as $cal) {
        $cal_id = $cal['calendar_id'];

        //Verificar se todos os eventos de hj para a frente, com event id definido, com booking status diferente de 2, bb=1 e (bb=0 e adm status = 1) existem no google
        $del_gooogleEvts = get_google_deleted($cal_id);
        // echo $cal_id."<br>";
        if ($del_gooogleEvts != false) {
            $resposta["DELETED_FROM_GOOGLE"][$cal_id] = $del_gooogleEvts;
            foreach ($del_gooogleEvts as $recover) {
                if ($recover['proprietario']) {
                    //Cancelar na BD
                    //BB=0, BS=0, AS=0

                    $sql = "UPDATE vrs6_bookings SET `blank_booking`=0, `booking_status`=0, `admin_status`=0 WHERE `event_id`='" . $recover['event_id'] . "'";
                    update_db($sql);
                } else {
                    //Repor evento no google
                    recover_google_event($cal_id, $recover['event_id']);

                    if (isset($recover['email']) and ( $recover['email'] != '')) {

                        $subject = "Erro Actualização Calendário | Calendar Updating Problem";
                        //Notificar cliente e Wr via recover['email']
                        $html_mail = 'Olá, <br><br><p>Tentou apagar uma reserva inserida pela WarmRental do seu calendário.</p><p><u>Não é possível apagar reservas inseridas pela WarmRental</u> pois são reservas já validadas e confirmadas com o cliente.</p><p>Em caso de algum problema com a sua reserva, contacte-nos através do nosso email (<a href="mailto:info@warmrental.com">info@warmrental.com</a>) ou por telefone (+351 963202294)</p><br><p>Estamos aqui para ajudá-lo.<br>Obrigado,</p><p>Equipa Warmrental</p><p>=====================</p>Hi! <br><br><p>You tried to delete an WarmRental booking form your google calendar.</p><p><u> Delete WarmRental bookings is not possible</u>, because we\'re talking about already confirmed bookings by the guest.</p><p> In case of any problem with your booking, please contact us by email (<a href="mailto:info@warmrental.com">info@warmrental.com</a>) or by phone (+351 963202294)</p><br><p>We\'re here to help you.<br>Thank you,</p><p>WarmRental Team</p>
	';
                        enviar_email($recover['email'], $subject, $html_mail);

                        $subject = "Erro Actualização Calendário | Reserva WR APAGADA";
                        //Notificar cliente e Wr via recover['email']
                        $html_mail = '<p>Proprietário responsável pelo calendário da propriedade (' . $recover['listing_id'] . ') tentou <strong>APAGAR</strong> reserva enviada pela WarmRental do seu calendário.</p><p>Isto não é possível e reserva foi novamente inserida.</p><p>Acção: Contactar proprietário e questionar o que se passou.</p>';
                        enviar_email('erros@warmrental.com', $subject, $html_mail);
                    } else {

                        $subject = "Erro Actualização Calendário | Reserva WR APAGADA";
                        //Notificar cliente e Wr via recover['email']
                        $html_mail = '<p>ATENÇÃO! Proprietario não recebeu e-mail pois não consta nenhum e-mail na BD.</p><p>Proprietário responsável pelo calendário da propriedade (' . $recover['listing_id'] . ') tentou <strong>APAGAR</strong> reserva enviada pela WarmRental do seu calendário.</p><p>Isto não é possível e reserva foi novamente inserida.</p><p>Acção: Contactar proprietário e questionar o que se passou.</p>';
                        enviar_email('erros@warmrental.com', $subject, $html_mail);
                    }
                }
            }
        }


        //Get Google modifications
        $G = get_mod_googleEvents($cal_id);

        if (isset($G['flag']) and $G['flag'] == 1) {
            $resposta['google_flag'] = 1;
            $resposta['google'] = array_merge($resposta['google'], $G['eventos']);
            $resposta['tot_google'] = array_merge($resposta['tot_google'], $G['tot_google']);
        } elseif (isset($G['tot_google'])) {
            $resposta['tot_google'] = array_merge($resposta['tot_google'], $G['tot_google']);
        }

        //Get Database modifications
        $DB = get_mod_dbEvents($cal_id);

        if (isset($DB['flag']) and $DB['flag'] == 1) {
            $resposta['db_flag'] = 1;
            $resposta['db'] = array_merge($resposta['db'], $DB['eventos']);
            $resposta['tot_db'] = array_merge($resposta['tot_db'], $DB['tot_db']);
        } elseif (isset($DB['tot_db'])) {
            $resposta['tot_db'] = array_merge($resposta['tot_db'], $DB['tot_db']);
        }

        //Get canceled reservations to delete in google
        $control = check_canceled($cal_id);

        if ($control['cancel_flag']) {
            $canceladas[$cal_id] = $control['eventos'];
            $resposta['cancel_flag'] = 1;
        }
    }

    $resposta['canceladas'] = $canceladas;

    $resposta["conflitos_flag"] = 0;

    //Additional info - conflitos
    if (sizeof($resposta['tot_db']) > 0 and sizeof($resposta['tot_google']) > 0) {
        $tot_google = $resposta['tot_google'];
        $tot_db = $resposta['tot_db'];

        foreach ($tot_db as $key => $row) {
            $event_id = $resposta['tot_db'][$key]['event_id'];
            if (exists_G($tot_google, $event_id)) {
                //$resposta['tot_db'][$key]['exists_google'] = $event_id;
                $resposta['tot_db'][$key]['to_update'] = 1;
            } else {
                $resposta['tot_db'][$key]['to_insert'] = 1;
            }
        }

        foreach ($tot_google as $key => $linha) {
            $res = exists_DB($key);
            if ($res['ok']) {
                $resposta['tot_google'][$key]['db_bID'] = $res['bid'];
                $resposta['tot_google'][$key]['to_update'] = 1;
            } else {
                $resposta['tot_google'][$key]['to_insert'] = 1;
            }
        }

        //Adicionar conflitos DB-->Google ao array resposta db

        $conflitos = conflicts_db_google($resposta['db'], $resposta['tot_google'], $resposta['tot_db']);
        $database = $resposta['db'];
        foreach ($database as $key => $evento) {

            if (isset($conflitos["i" . $evento['booking_id']]['conflicts_free']) and $conflitos["i" . $evento['booking_id']]['conflicts_free'] == 0) {
                $resposta["conflitos_flag"] = 1;
            }
            $resposta['db']["i" . $evento['booking_id']]['conflitos'] = array();

            if (isset($conflitos["i" . $evento['booking_id']])) {
                $resposta['db']["i" . $evento['booking_id']]['conflitos'] = $conflitos["i" . $evento['booking_id']];
            }

            if (isset($resposta['tot_db']["i" . $evento['booking_id']]['to_update']) and $resposta['tot_db']["i" . $evento['booking_id']]['to_update'] == 1) {
                $resposta['db']["i" . $evento['booking_id']]['to_update'] = 1;
            } else {
                $resposta['db']["i" . $evento['booking_id']]['to_insert'] = 1;
            }
        }

        //Adicionar conflitos Google-->DB ao array resposta google

        $conflitos = conflicts_google_db($resposta['google'], $resposta['tot_google'], $resposta['tot_db']);
        $googlebase = $resposta['google'];
        foreach ($googlebase as $key => $evt) {

            if ($conflitos[$key]['conflicts_free'] == 0) {
                $resposta["conflitos_flag"] = 1;
            }

            $resposta['google'][$key]['conflitos'] = $conflitos[$key];

            if (isset($resposta['tot_google'][$key]['to_update'])) {
                $resposta['google'][$key]['to_update'] = 1;
            } else {
                $resposta['google'][$key]['to_insert'] = 1;
            }
        }
    }

    return json_encode($resposta);
}

/**
 * @param $bigarray
 * @param $mode
 * @return string
 */
function process_Conflicts($bigarray, $mode) {
    global $Actions;
    global $db_conn;

    $e = sizeof($Actions) + 1;

    $to_delete = array();
    $resposta['tot_google'] = array();
    $resposta['google'] = array();
    $resposta['tot_db'] = array();
    $resposta['db'] = array();
    $resposta['accoes'] = array();
    $resposta['erro_grave'] = 0;
    $resposta = $bigarray;

    if ($mode == "DB") {
        foreach ($bigarray['db'] as $key => $conflito) {
            $origem = $bigarray['db'][$key];
            if (!$conflito['conflitos']['conflicts_free']) {
                foreach ($conflito['conflitos']['conflitos'] as $chave => $linha) {
                    if (!isset($to_delete[$linha['event_id']])) {

                        $to_delete[$linha['event_id']] = $linha;
                        $to_delete[$linha['event_id']]['del_reason'] = "CODIGO [process_Conflicts 01]";
                        $to_delete[$linha['event_id']]['origem'] = $origem;

                        $Actions[$e] = "[PC01] Evento " . $linha['event_name'] . " (id:" . $linha['event_id'] . ") apagado devido a conflito com evento" . $origem['event_name'] . " (id:" . $origem['event_id'] . ")";
                        $e++;

                        //Apagar eventos em conflito no google e qq outra acção necessária
                        delete_google_event($linha['calendar_id'], $linha['event_id']);

                        //Retirar as entradas dos arrays google
                        if (isset($bigarray['google'][$linha['event_id']])) {
                            unset($resposta['google'][$linha['event_id']]);
                        }if (isset($bigarray['tot_google'][$linha['event_id']])) {
                            unset($resposta['tot_google'][$linha['event_id']]);
                        }

                        //Se for evento existente na BD repor com info da BD
                        $event_id = $linha['event_id'];

                        //$res = exists_DB ($linha['event_id']);
                        if (isset($linha['to_update'])) {
                            $bID = $linha['db_bID'];
                            $start_date_timestamp = strtotime($resposta['tot_db']["i" . $bID]['start_date']);
                            $new_start_date = date('Y-m-d', $start_date_timestamp);

                            $end_date_timestamp = strtotime($resposta['tot_db']["i" . $bID]['end_date']);
                            $new_end_date = date('Y-m-d', $end_date_timestamp);

                            $insertData['start_date'] = $new_start_date;
                            $insertData['end_date'] = $new_end_date;
                            $insertData['event_name'] = $resposta['tot_db']["i" . $bID]['event_name'];

                            $res = insert_google_event($linha['calendar_id'], $insertData);
                            $novo_id = $res['new_event_id'];
                            $sql = "UPDATE vrs6_bookings SET `event_id`='" . $novo_id . "' WHERE `booking_id`='" . $bID . "'";
                            update_db($sql);

                            $Actions[$e] = "[PC02] Evento " . $insertData['event_name'] . " (id:" . $novo_id . ", booking_id:" . $bID . ") inserido no Google";
                            $e++;
                        }
                    }
                }
            }
        }
    } elseif ($mode == "BOTH") {
        $apagados = array();
        //SINCRONIZAÇÃO DOIS SENTIDOS
        //Primeiro conflitos da BD
        if (isset($bigarray['db'])) {
            foreach ($bigarray['db'] as $key => $conflito) {
                $origem = $bigarray['db'][$key];
                if (!$conflito['conflitos']['conflicts_free']) {
                    foreach ($conflito['conflitos']['conflitos'] as $chave => $linha) {
                        if (!isset($to_delete[$linha['event_id']])) {

                            $to_delete[$linha['event_id']] = $linha;
                            $to_delete[$linha['event_id']]['del_reason'] = "CODIGO [process_Conflicts 01]";
                            $to_delete[$linha['event_id']]['origem'] = $origem;

                            $lastSync = strtotime($_SESSION['last_sync']);
                            $dbMod = strtotime($origem['mtime']);
                            $googMod = strtotime($linha['updated']);

                            //So apaga se nao for um conflito a resolver pela warmrental pessoalmente
                            if ($dbMod > $lastSync and $googMod > $lastSync) {
                                //Conflito entre proprietario e warmrental desde ultima sincronizacao
                                $Actions[$e] = "[ERRO] Evento de proprietario no google " . $linha['event_name'] . " (id:" . $linha['event_id'] . ", cal id:" . $linha['calendar_id'] . ") em conflito com evento warmrental" . $origem['event_name'] . " (id:" . $origem['event_id'] . ")";
                                $e++;

                                $db_conn->query("INSERT INTO sync_errors (calendar_id, start_date, end_date, conflict_with_bID) VALUES ('" . $linha['calendar_id'] . "', '" . $linha['start_date'] . "', '" . $linha['end_date'] . "', " . $origem['booking_id'] . ");");
                                $resposta['erro_grave'] = 1;
                            } else {
                                delete_google_event($linha['calendar_id'], $linha['event_id']);
                                $Actions[$e] = "[PC03] Evento " . $linha['event_name'] . " (id:" . $linha['event_id'] . ") apagado devido a conflito com evento" . $origem['event_name'] . " (id:" . $origem['event_id'] . ")";
                                $e++;
                                $apagados[$linha['event_id']] = 1;
                            }



                            //Retirar as entradas dos arrays google
                            if (isset($bigarray['google'][$linha['event_id']])) {
                                unset($resposta['google'][$linha['event_id']]);
                            }if (isset($bigarray['tot_google'][$linha['event_id']])) {
                                unset($resposta['tot_google'][$linha['event_id']]);
                            }

                            //Se for evento existente na BD repor com info da BD
                            $event_id = $linha['event_id'];

                            //$res = exists_DB ($linha['event_id']);
                            if (isset($linha['to_update'])) {
                                $bID = $linha['db_bID'];
                                $start_date_timestamp = strtotime($resposta['tot_db']["i" . $bID]['start_date']);
                                $new_start_date = date('Y-m-d', $start_date_timestamp);

                                $end_date_timestamp = strtotime($resposta['tot_db']["i" . $bID]['end_date']);
                                $new_end_date = date('Y-m-d', $end_date_timestamp);

                                $insertData['start_date'] = $new_start_date;
                                $insertData['end_date'] = $new_end_date;
                                $insertData['event_name'] = $resposta['tot_db']["i" . $bID]['event_name'];

                                $res = insert_google_event($linha['calendar_id'], $insertData);
                                $novo_id = $res['new_event_id'];
                                $sql = "UPDATE vrs6_bookings SET `event_id`='" . $novo_id . "' WHERE `booking_id`='" . $bID . "'";
                                update_db($sql);

                                $Actions[$e] = "[PC04] Evento " . $insertData['event_name'] . " (id:" . $novo_id . ", booking_id:" . $bID . ") inserido no Google";
                                $e++;
                            }
                        }
                    }
                }
            }
        }

        //Conflitos do google
        $resultado['DEL_db_google'] = array();
        $resultado['UPD_db_google'] = array();

        foreach ($bigarray['google'] as $key => $conflito) {
            $origem = $bigarray['google'][$key];
            if (!$conflito['conflitos']['conflicts_free']) {
                foreach ($conflito['conflitos']['conflitos'] as $chave => $linha) {
                    if (!isset($to_delete[$linha['event_id']])) {

                        //Se for evento existente na BD repor com info da BD
                        $event_id = $linha['event_id'];

                        if (isset($linha['to_update'])) {
                            //Quer dizer q o conflito é com evento existente na DB
                            if (isset($conflito['to_update'])) {
                                $to_delete[$key] = $conflito;

                                $to_delete[$key]["UPDATES"] = 1;


                                $to_delete[$linha['event_id']] = $linha;
                                $to_delete[$linha['event_id']]["UPDATES"] = 1;
                            } else {
                                $to_delete[$key] = $conflito;
                                $to_delete[$key]['reason'] = "Conflito com reserva na base de dados";
                            }
                            /*
                              $bID = $linha['db_bID'];
                              $start_date_timestamp = strtotime($resposta['tot_db']["i".$bID]['start_date']);
                              $new_start_date = date('Y-m-d', $start_date_timestamp);

                              $end_date_timestamp = strtotime($resposta['tot_db']["i".$bID]['end_date']);
                              $new_end_date = date('Y-m-d', $end_date_timestamp);

                              $insertData['start_date'] = $new_start_date;
                              $insertData['end_date']   = $new_end_date;
                              $insertData['event_name'] = $resposta['tot_db']["i".$bID]['event_name'];

                              $res 	 = insert_google_event($linha['calendar_id'], $insertData);
                              $novo_id = $res['new_event_id'];
                              $sql	 = "UPDATE vrs6_bookings SET `event_id`='".$novo_id."' WHERE `booking_id`='".$bID."'";
                              update_db($sql);
                             */
                        } else {
                            //Sobreposicoes que nao estao na BD
                            if ($conflito['created'] > $linha['created']) {
                                $to_delete[$key] = $conflito;
                                $to_delete[$key]['reason'] = "[PC05] Conflito com reserva google mais antiga (" . $linha['event_name'];
                            } else {
                                $to_delete[$linha['event_id']] = $linha;
                                $to_delete[$linha['event_id']]['reason'] = "[PC06] Conflito com reserva google mais antiga (" . $conflito['event_name'];
                            }
                        }
                    }
                }
            }
        }
        if (sizeof($to_delete) > 0) {
            $resultado = array();


            $i = 0;
            $y = 0;
            foreach ($to_delete as $row) {
                if (isset($row['UPDATES'])) {
                    //Fazer Update

                    $start_date_timestamp = strtotime($bigarray['tot_db']["i" . $row['db_bID']]['start_date']);
                    $new_start_date = date('Y-m-d', $start_date_timestamp);

                    $end_date_timestamp = strtotime($bigarray['tot_db']["i" . $row['db_bID']]['end_date']);
                    $new_end_date = date('Y-m-d', $end_date_timestamp);

                    $updData['start_date'] = $new_start_date;
                    $updData['end_date'] = $new_end_date;
                    $updData['summary'] = $bigarray['tot_db']["i" . $row['db_bID']]['event_name'];

                    update_google_event($row['calendar_id'], $row['event_id'], $updData);

                    $Actions[$e] = "[PC07] Evento google " . $updData['summary'] . " (id:" . $row['event_id'] . ") actualizado (start: " . $new_start_date . " end: " . $new_end_date . ")";
                    $e++;

                    $resultado['UPD_db_google'][$row['event_id']]['start_date'] = $new_start_date;
                    $resultado['UPD_db_google'][$row['event_id']]['end_date'] = $new_end_date;
                    $resultado['UPD_db_google'][$row['event_id']]['event_name'] = $updData['summary'];
                    $resultado['UPD_db_google'][$row['event_id']]['event_id'] = $row['event_id'];
                    $y++;
                } else {
                    //Apagar
                    if (!isset($resultado['DEL_db_google'][$row['event_id']])) {
                        //delete_google_event($row['event_id']);
                        $resultado['DEL_db_google'][$row['event_id']]['start_date'] = $row['start_date'];
                        $resultado['DEL_db_google'][$row['event_id']]['end_date'] = $row['end_date'];
                        $resultado['DEL_db_google'][$row['event_id']]['event_name'] = $row['event_name'];
                        $resultado['DEL_db_google'][$row['event_id']]['calendar_id'] = $row['calendar_id'];
                        $resultado['DEL_db_google'][$row['event_id']]['event_id'] = $row['event_id'];
                        if (isset($row['reason'])) {
                            $resultado['DEL_db_google'][$row['event_id']]['reason'] = $row['reason'];
                        } else {
                            $resultado['DEL_db_google'][$row['event_id']]['reason'] = "[PC09] sem razão definida";
                        }
                    }
                }
            }
        }
        $resposta['MODO'] = "BOTH";
    }

    if (isset($resultado['DEL_db_google'])) {
        foreach ($resultado['DEL_db_google'] as $apagar) {

            if (isset($resposta['google'][$apagar['event_id']]) and ! isset($apagados[$apagar['event_id']])) {
                unset($resposta['google'][$apagar['event_id']]);
                delete_google_event($apagar['calendar_id'], $apagar['event_id']);

                $Actions[$e] = "[PC10] Evento " . $apagar['event_name'] . " (id:" . $apagar['event_id'] . ") apagado do google. Razão: " . $apagar['reason'];
                $e++;
                $apagados[$apagar['event_id']] = 1;
                $resultado['DEL_db_google'][$apagar['event_id']]['reason'] = "[PC10] Evento " . $apagar['event_name'] . " (id:" . $apagar['event_id'] . ") apagado do google. Razão: " . $apagar['reason'];
            }
        }
        $resposta['Deleted'] = $resultado['DEL_db_google'];
    }

    if (isset($resultado['UPD_db_google'])) {
        foreach ($resultado['UPD_db_google'] as $apagar) {
            if (isset($resposta['google'][$apagar['event_id']])) {
                unset($resposta['google'][$apagar['event_id']]);
            }
        }
        $resposta['Updated'] = $resultado['UPD_db_google'];
    }

    $resposta['accoes'] = $Actions;
    return json_encode($resposta);
}

/**
 * @param $mod_db_Events
 * @return string
 */
function process_sync_onlyDB($mod_db_Events) {
    global $service;
    global $db_conn;
    global $Calendars;
    global $Actions;
    $Actions = $mod_db_Events['accoes'];
    $e = sizeof($Actions) + 1;

    $resposta['arrayRecebido'] = $mod_db_Events;

    if (isset($resposta['arrayRecebido']['tot_google']) and sizeof($resposta['arrayRecebido']['tot_google']) > 0) {
        $tot_google = $resposta['arrayRecebido']['tot_google'];
        $resposta['tot_google'] = $tot_google;
    }

    if (isset($resposta['arrayRecebido']['tot_db']) and sizeof($resposta['arrayRecebido']['tot_db']) > 0) {
        $tot_db = $resposta['arrayRecebido']['tot_db'];
        $resposta['tot_db'] = $tot_db;
        $db_Events = $resposta['arrayRecebido']['db'];
    }

    if (isset($db_Events) and sizeof($db_Events) > 0) {
        foreach ($db_Events as $dbEvent) {
            $cal_id = $dbEvent['calendar_id'];
            $event_id = $dbEvent['event_id'];

            $start_date_timestamp = strtotime($dbEvent['start_date']);
            $new_start_date = date('Y-m-d', $start_date_timestamp);

            $end_date_timestamp = strtotime($dbEvent['end_date']);
            $new_end_date = date('Y-m-d', $end_date_timestamp);

            if (isset($dbEvent['to_update'])) {
                //Actualiza o evento no google com info da DB

                $updateData['start_date'] = $new_start_date;
                $updateData['end_date'] = $new_end_date;
                $updateData['summary'] = $dbEvent['event_name'];

                update_google_event($cal_id, $event_id, $updateData);
                $Actions[$e] = "[DB01] Evento google " . $updateData['summary'] . " (id:" . $event_id . ") actualizado (start: " . $new_start_date . " end:" . $new_end_date . ")";
                $e++;
            } else {
                //Insere o evento no google


                $insertData['start_date'] = $new_start_date;
                $insertData['end_date'] = $new_end_date;
                if (!isset($dbEvent['event_name']) || $dbEvent['event_name'] == "") {
                    $insertData['event_name'] = "Sem nome";
                } else {
                    $insertData['event_name'] = $dbEvent['event_name'];
                }

                $res = insert_google_event($cal_id, $insertData);
                $novo_id = $res['new_event_id'];
                $sql = "UPDATE vrs6_bookings SET `event_id`='" . $novo_id . "' WHERE `booking_id`='" . $dbEvent['booking_id'] . "'";
                update_db($sql);
                $Actions[$e] = "[DB02] Evento " . $dbEvent['event_name'] . " (id:" . $novo_id . ", booking_id: " . $dbEvent['booking_id'] . ") inserido no google.";
                $e++;
            }
        }
    }

    $resposta["ACCOES"] = $Actions;
    ;
    return json_encode($resposta);
}

/**
 * @param $Changes
 * @return string
 */
function process_sync_BOTH($Changes) {
    global $db_conn;
    global $Actions;
    $Actions = $Changes['accoes'];
    $e = sizeof($Actions) + 1;

    $resposta = array();
    if (isset($Changes['db']) and sizeof($Changes['db']) > 0) {
        foreach ($Changes['db'] as $bd) {
            if (isset($bd['to_insert']) and $bd['to_insert'] == 1) {
                $start_date_timestamp = strtotime($bd['start_date']);
                $new_start_date = date('Y-m-d', $start_date_timestamp);

                $end_date_timestamp = strtotime($bd['end_date']);
                $new_end_date = date('Y-m-d', $end_date_timestamp);

                $insertData['start_date'] = $new_start_date;
                $insertData['end_date'] = $new_end_date;
                $insertData['event_name'] = $bd['event_name'];
                $bID = $bd['booking_id'];
                //SE ISTO FUNCIONAR IMPEC, SENAO ADICIONA LINHA A TABELA ERROS
                $res = insert_google_event($bd['calendar_id'], $insertData);
                $novo_id = $res['new_event_id'];
                $sql = "UPDATE vrs6_bookings SET `event_id`='" . $novo_id . "' WHERE `booking_id`='" . $bID . "'";
                update_db($sql);
                $resposta[$novo_id] = "[DB03] Novo evento adicionado ao google via BD - " . $bd['event_name'];
                $resposta["ADICIONADOGOOLE" . $bID] = $insertData;

                $Actions[$e] = "[DB03] Evento " . $bd['event_name'] . " (cal: " . $bd['calendar_id'] . ", id: " . $novo_id . ", booking_id: " . $bID . ") inserido no Google.";
                $e++;
            } else {
                $start_date_timestamp = strtotime($bd['start_date']);
                $new_start_date = date('Y-m-d', $start_date_timestamp);

                $end_date_timestamp = strtotime($bd['end_date']);
                $new_end_date = date('Y-m-d', $end_date_timestamp);

                $updData['start_date'] = $new_start_date;
                $updData['end_date'] = $new_end_date;
                $updData['summary'] = $bd['event_name'];

                $res = update_google_event($bd['calendar_id'], $bd['event_id'], $updData);
                $resposta[$bd['event_id']] = "[DB] Evento actualizado no google c info da BD- " . $bd['event_name'];
                $Actions[$e] = "[DB04] Evento " . $bd['event_name'] . " (id: " . $bd['event_id'] . ", booking_id: " . $bd['booking_id'] . ") actualizado no Google.";
                $e++;
            }
        }
    }

    if (isset($Changes['google']) and sizeof($Changes['google']) > 0) {
        foreach ($Changes['google'] as $google) {
            if (isset($google['to_insert']) and $google['to_insert'] == 1) {
                $start_date_timestamp = strtotime($google['start_date']);
                $new_start_date = date('Y-m-d H:i:s', $start_date_timestamp);

                $end_date_timestamp = strtotime($google['end_date']);
                $new_end_date = date('Y-m-d H:i:s', $end_date_timestamp);

                $insertData['start_date'] = $new_start_date;
                $insertData['end_date'] = $new_end_date;
                $insertData['event_name'] = $google['event_name'];

                $listing_id = get_listing_id($google['calendar_id']);

                //$sql_ins = "INSERT INTO vrs6_bookings (`user_id`, `listing_id`, `blank_booking`,  `admin_status`,  `booking_status`, `start_date`, `end_date`, `event_id`, `calendar_id`, `event_name`) VALUES ('555', '".$listing_id."', '1', '1', '1', '".$insertData['start_date']."', '".$insertData['end_date']."', '".$google['event_id']."', '".$google['calendar_id']."', '".$google['event_name']."')";

                $str = "SET @inDate = " . "'" . $db_conn->real_escape_string($insertData['start_date']) . "', @outDate = " . "'" . $db_conn->real_escape_string($insertData['end_date']) . "', @listingID = " . $db_conn->real_escape_string($listing_id) . ", @eventID = " . "'" . $db_conn->real_escape_string($google['event_id']) . "', @calendarID = " . "'" . $db_conn->real_escape_string($google['calendar_id']) . "', @eventName = " . "'" . $db_conn->real_escape_string($google['event_name']) . "'";

                // Prepare IN and OUT parameters
                $db_conn->query("SET @inDate = " . "'" . $db_conn->real_escape_string($insertData['start_date']) . "', @outDate = " . "'" . $db_conn->real_escape_string($insertData['end_date']) . "', @listingID = " . $db_conn->real_escape_string($listing_id) . ", @eventID = " . "'" . $db_conn->real_escape_string($google['event_id']) . "', @calendarID = " . "'" . $db_conn->real_escape_string($google['calendar_id']) . "', @eventName = " . "'" . $db_conn->real_escape_string($google['event_name']) . "'");
                $db_conn->query("SET @caso = FALSE");

                // Call sproc
                // IsSupervisor(IN username CHAR(20), OUT success BOOLEAN)
                if (!$db_conn->query("CALL insertRoutine(@inDate, @outDate, @listingID, @eventID, @calendarID, @eventName, @caso, @bID_out, @listID_out)"))
                    die("CALL failed: (" . $db_conn->errno . ") " . $db_conn->error);

                // Fetch OUT parameters
                if (!($res = $db_conn->query("SELECT @caso AS caso")))
                    die("Fetch failed: (" . $db_conn->errno . ") " . $db_conn->error);
                $row = $res->fetch_assoc();
                $caso = $row['caso'];

                if (!($res = $db_conn->query("SELECT @bID_out AS bID_out")))
                    die("Fetch failed: (" . $db_conn->errno . ") " . $db_conn->error);
                $row = $res->fetch_assoc();
                $bID_out = $row['bID_out'];

                if (!($res = $db_conn->query("SELECT @listID_out AS listID_out")))
                    die("Fetch failed: (" . $db_conn->errno . ") " . $db_conn->error);
                $row = $res->fetch_assoc();
                $listID_out = $row['listID_out'];


                // Return result
                if ($caso == "2") {
                    $resposta['2listing' . $google['event_id']] = $listing_id;
                    $resposta['inserted_db_from_google'][$google['event_id']]['listing_id'] = $listing_id;
                    $resposta['inserted_db_from_google'][$google['event_id']]["error_routine"] = $str;
                    $resposta['inserted_db_from_google'][$google['event_id']]["caso"] = $caso;
                    $resposta['inserted_db_from_google'][$google['event_id']]["bID_out"] = $bID_out;
                    $resposta['inserted_db_from_google'][$google['event_id']]["listID_out"] = $listID_out;
                    $resposta[$google['event_id']] = "[G] Evento inserido na BD via google- " . $google['event_name'];
                    $Actions[$e] = "[DB05] Evento " . $google['event_name'] . " (id: " . $google['event_id'] . ", calendario:" . $google['calendar_id'] . ") inserido na BD.";
                    $e++;
                } else {
                    $resposta['inserted_db_from_google'][$google['event_id']]['listing_id'] = $listing_id;
                    $resposta['inserted_db_from_google'][$google['event_id']]["error_routine"] = $str;
                    $resposta['inserted_db_from_google'][$google['event_id']]["caso"] = $caso;
                    $resposta['inserted_db_from_google'][$google['event_id']]["bID_out"] = $bID_out;
                    $resposta['inserted_db_from_google'][$google['event_id']]["listID_out"] = $listID_out;
                    $resposta[$google['event_id']] = "[G] ERRO - EVENTO DE PROPRIETARIO EM CONFLITO COM EVENTO WARMRENTAL";
                    $Actions[$e] = "[DB055] [G] ERRO - EVENTO DE PROPRIETARIO EM CONFLITO COM EVENTO WARMRENTA - Evento " . $google['event_name'] . " (id: " . $google['event_id'] . ", calendario:" . $google['calendar_id'] . ").";
                    $e++;

                    $elementos = get_email_listing($bID_out);
                    if (isset($elementos['email']) and ( $elementos['email'] != '')) {

                        $subject = "Erro Actualização Calendário | Calendar Updating Problem";
                        //Notificar cliente e Wr via recover['email']
                        $html_mail = 'Olá, <br><br><p>Tentou inserir uma reserva no seu calendário google num intervalo já preenchido por uma reserva WarmRental.</p><p><u>Atenção, isto poderá significar uma situação de overbooking.</u></p><p>Em caso de algum problema com a sua reserva, contacte-nos através do nosso email (<a href="mailto:info@warmrental.com">info@warmrental.com</a>) ou por telefone (+351 963202294)</p><br><p>Estamos aqui para ajudá-lo.<br>Obrigado,</p><p>Equipa Warmrental</p><p>=====================</p>Hi! <br><br><p>You tried to enter a reservation in your google calendar in a range already filled by an WarmRental reservation.</p><p><u> Attention, this could mean an overbooking situation.</u>.</p><p> In case of any problem with your booking, please contact us by email (<a href="mailto:info@warmrental.com">info@warmrental.com</a>) or by phone (+351 963202294)</p><br><p>We\'re here to help you.<br>Thank you,</p><p>WarmRental Team</p>';
                        enviar_email($elementos['email'], $subject, $html_mail);

                        $subject = "Erro Actualização Calendário | Reserva WR EDITADA";
                        //Notificar cliente e Wr via recover['email']
                        $html_mail = '<p>Proprietário responsável pelo calendário da propriedade (' . $elementos['listing_id'] . ') tentou inserir uma reserva num intervalo já preenchido pela WarmRental.</p><p>Isto não é possível e existe conflito a resolver no calendário google associado à propriedade ' . $elementos['listing_id'] . '.</p><p>Acção: Contactar proprietário e questionar o que se passou. Resolver coflitos no google.</p>';
                        enviar_email('erros@warmrental.com', $subject, $html_mail);
                    } else {

                        $subject = "Erro Actualização Calendário | Reserva WR EDITADA";
                        //Notificar cliente e Wr via recover['email']
                        $html_mail = '<p>ATENÇÃO! Proprietario não recebeu e-mail pois não existe nenhum definido na BD.</p><p>Proprietário responsável pelo calendário da propriedade (' . $elementos['listing_id'] . ') tentou inserir uma reserva num intervalo já preenchido pela WarmRental.</p><p>Isto não é possível e existe conflito a resolver no calendário google associado à propriedade ' . $elementos['listing_id'] . '.</p><p>Acção: Contactar proprietário e questionar o que se passou. Resolver coflitos no google.</p>';
                        enviar_email('erros@warmrental.com', $subject, $html_mail);
                    }
                }


                //$result= mysqli_query($db_conn, $sql_ins);
            } else {
                $start_date_timestamp = strtotime($google['start_date']);
                $new_start_date = date('Y-m-d H:i:s', $start_date_timestamp);

                $end_date_timestamp = strtotime($google['end_date']);
                $new_end_date = date('Y-m-d H:i:s', $end_date_timestamp);

                $insertData['start_date'] = $new_start_date;
                $insertData['end_date'] = $new_end_date;
                $insertData['event_name'] = $google['event_name'];

                $bID = $google['db_bID'];
                $warmrental = $google['warmrental'];
                $resposta["UPDATED_ARRAY"]['google'] = $google;

                if ($warmrental) {
                    $resposta[$google['event_id']] = "[G] Evento reposto no google via db- " . $google['event_name'];
                    $Actions[$e] = "[G09] Evento " . $google['event_name'] . " (id: " . $google['event_id'] . ", booking_id:" . $bID . ") reposto no google c info BD por se tratar de marcação warmrental.";

                    $elementos = get_email_listing($bID);
                    if (isset($elementos['email']) and ( $elementos['email'] != '')) {

                        $subject = "Erro Actualização Calendário | Calendar Updating Problem";
                        //Notificar cliente e Wr via recover['email']
                        $html_mail = 'Olá, <br><br><p>Tentou editar uma reserva inserida pela WarmRental do seu calendário.</p><p><u>Não é possível editar reservas inseridas pela WarmRental</u> pois são reservas já validadas e confirmadas com o cliente.</p><p>Em caso de algum problema com a sua reserva, contacte-nos através do nosso email (<a href="mailto:info@warmrental.com">info@warmrental.com</a>) ou por telefone (+351 963202294)</p><br><p>Estamos aqui para ajudá-lo.<br>Obrigado,</p><p>Equipa Warmrental</p><p>=====================</p>Hi! <br><br><p>You tried to edit an WarmRental booking form your google calendar.</p><p><u> Edit WarmRental bookings is not possible</u>, because we\'re talking about already confirmed bookings by the guest.</p><p> In case of any problem with your booking, please contact us by email (<a href="mailto:info@warmrental.com">info@warmrental.com</a>) or by phone (+351 963202294)</p><br><p>We\'re here to help you.<br>Thank you,</p><p>WarmRental Team</p>';
                        enviar_email($elementos['email'], $subject, $html_mail);

                        $subject = "Erro Actualização Calendário | Reserva WR EDITADA";
                        //Notificar cliente e Wr via recover['email']
                        $html_mail = '<p>Proprietário responsável pelo calendário da propriedade (' . $elementos['listing_id'] . ') tentou <strong>EDITAR</strong> reserva enviada pela WarmRental do seu calendário.</p><p>Isto não é possível e reserva foi novamente inserida como inicialmente.</p><p>Acção: Contactar proprietário e questionar o que se passou.</p>';
                        enviar_email('erros@warmrental.com', $subject, $html_mail);
                    } else {

                        $subject = "Erro Actualização Calendário | Reserva WR EDITADA";
                        //Notificar cliente e Wr via recover['email']
                        $html_mail = '<p>ATENÇÃO! Proprietario não recebeu e-mail pois não existe nenhum definido na BD.</p><p>Proprietário responsável pelo calendário da propriedade (' . $elementos['listing_id'] . ') tentou <strong>EDITAR</strong> reserva enviada pela WarmRental do seu calendário.</p><p>Isto não é possível e reserva foi novamente inserida como inicialmente.</p><p>Acção: Contactar proprietário e questionar o que se passou.</p>';
                        enviar_email('erros@warmrental.com', $subject, $html_mail);
                    }
                } else {
                    $sql = "UPDATE vrs6_bookings SET `event_name`='" . $insertData['event_name'] . "', `start_date`='" . $new_start_date . "', `end_date`='" . $new_end_date . "'  WHERE `booking_id`='" . $bID . "'";
                    update_db($sql);
                    $resposta[$google['event_id']] = "[G] Evento actualizado na BD via google- " . $google['event_name'];
                    $Actions[$e] = "[DB06] Evento " . $google['event_name'] . " (id: " . $google['event_id'] . ", booking_id:" . $bID . ") actualizado na BD (event_name:" . $insertData['event_name'] . ", start: " . $new_start_date . ", end: " . $new_end_date . ").";
                    $e++;
                }
            }
        }
    }

    $resposta["ACCOES"] = $Actions;
    return json_encode($resposta);
}

/**
 * @param $canceladas
 * @param $tot_Google
 * @return string
 */
function processCancelRes($canceladas, $tot_Google) {
    global $db_conn;
    global $Calendars;

    $resposta['ok'] = 0;


    foreach ($canceladas as $cancelada) {
        foreach ($cancelada as $evento) {
            if (isset($tot_Google[$evento['event_id']]['event_id']) and $tot_Google[$evento['event_id']]['event_id'] == $evento['event_id']) {

                $resposta['ok'] = 1;
                $resposta['to_del'][$evento['calendar_id']]['calendar_id'] = $evento['calendar_id'];
                $resposta['to_del'][$evento['calendar_id']]['event_id'] = $evento['event_id'];
                $resposta['to_del'][$evento['calendar_id']]['event_name'] = $evento['event_name'];
            }
        }
    }

    if ($resposta['ok']) {
        $i = 0;
        $resposta['apagados'] = "<br>";
        foreach ($resposta['to_del'] as $to_del) {
            delete_google_event($to_del['calendar_id'], $to_del['event_id']);

            $resposta['apagados'] .= $to_del['event_name'] . " (" . $to_del['calendar_id'] . " - " . $to_del['event_id'] . "),<br>";
            $i++;
        }
        $resposta['apagados'] = substr($resposta['apagados'], 0, -5);
        $resposta['apagados'] .= ".";
    }

    return json_encode($resposta);
}

/**
 * @return string
 */
function processSync_close() {
    global $db_conn;
    global $Calendars;

    $agora = date('Y-m-d H:i:s', time());

    $resposta['tempo_old'] = $_SESSION['last_sync'];
    $_SESSION['last_sync'] = $agora;
    $resposta['tempo_actual'] = $agora;

    //DATE_ADD(NOW(), INTERVAL 5 SECOND)
    $sql_upd = "UPDATE tbl_History SET `Calendars`='" . json_encode($Calendars) . "', `last_sync`=(now() + interval 3 second)  WHERE `id`=1";
    $upd = update_db($sql_upd);
    //$upd = 1;

    if ($upd) {
        $resposta['ok'] = 1;
    } else {
        $resposta['ok'] = 0;
    }
    $resposta['ATENCAO'] = $_SESSION['DEBUGGINGLASTSYNC'];
    return json_encode($resposta);
}

/**
 *
 */
function processLogout() {

    global $web_dir;

    //Destroying Session
    unset($_SESSION);
    session_destroy();

    header('Location: ' . $web_dir);
}

?>