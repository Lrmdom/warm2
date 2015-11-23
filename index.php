<?PHP
/*
  # Warmrental Google Sync
  # Powered by comPonto.com
 */
//error_reporting(E_ALL);
ini_set('display_errors', 1);
//Check if session start is needed
if (session_id() == '') {
    session_name("warmrental_google_sync_session");
    session_start();
}

require_once 'settings/global.php';
require_once 'api/dwh.php';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml"><head>

        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

        <title>WarmRental Google Sync</title>

        <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="author" content="comPonto.com" />
            <meta name="description" content="WarmRental Google Sync by comPonto.com" />
            <meta name="Resource-type" content="Document" />
            <meta http-equiv="X-UA-Compatible" content="IE=edge" />

            <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="http://www.warmrental.com/favicon.ico">
                <link rel="stylesheet" type="text/css" href="css/slick_lf.css" media="screen" />
                <link rel="stylesheet" type="text/css" href="css/warmrental.css" media="screen" />

                <!--[if IE 9]>
                    <script type="text/javascript" src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
                    <script type="text/javascript" src="js/placeholder.js"></script>
                <![endif]-->

                <script src="js/jquery-2.1.1.js"></script>
                <script type="text/javascript">''</script>
                <script src="js/warmrental.js"></script>
                <?PHP
                if (isset($_SESSION['bo_auth'])) {
                    ?>
                    <script>
                        $(document).ready(function () {
                            $("#connectBut").click(function (event) {
                                window.location.replace('<?PHP echo $_SESSION['authLink']; ?>');
                                return false;
                            });
                        });
                    </script>
                    <?PHP
                }
                ?>
                </head>

                <body>
                    <div id="page-2">

                        <div class="login_page">
                            Warmrental Google Sync
                            <h1>Warmrental.com</h1>
                        </div>

                        <!-- *************************** -->
                        <!-- START COPYING FROM HERE     -->
                        <!-- *************************** -->

                        <section id="slick">
                            <!-- Social buttons -->
                            <div class="sb">
                                <a style="width: 75px; background: #E76565; color: #fff;" href="api/logout.php" class="entypo-thumbs-up">  Logout</a>
                            <!--<a href="#" class="gc entypo-google-circles"><span class="slick-tip right">Login with Google</span></a>
                                <a href="#" class="tw entypo-twitter"><span class="slick-tip right">Login with Twitter</span></a>-->
                            </div>
                            <!-- Login form -->
                            <div class="login-form">
                                <!-- Title -->
                                <?PHP
                                if (isset($_SESSION['bo_auth'])) {
                                    ?>
                                    <div class="title">Access Gate</div>
                                    <?PHP
                                } else {
                                    ?>
                                    <div class="title">Login</div>
                                    <?PHP
                                }
                                ?>

                                <!-- Intro text -->
                                <p id="info_box" class="intro">
                                    <?PHP
                                    if (!isset($_SESSION['oauth_error'])) {
                                        if (isset($_SESSION['bo_auth'])) {
                                            ?>
                                            <span style="color: green; font-sixe: 14px;"><strong>Olá</strong> <?PHP echo $_SESSION['username']; ?> ! Carregue em <strong>RECONECTAR</strong> para fazer um refresh à conecção com o Google.</span>
                                            <?PHP
                                        } else {
                                            ?>
                                            <b>Olá.</b> Por favor introduza credenciais de acesso à central de sincronização warmrental <-> google. Obrigado.
                                            <?PHP
                                        }
                                    } else {
                                        ?>
                                        <b>ATENÇÃO</b> Para aceder às funcionalidades de sincronização deve autorizar o acesso à API Google Calendar, por favor reconecte e aceite a permissão de acesso. Obrigado.

                                        <?PHP
                                    }
                                    ?>

                                </p>
                                <!-- Form fields -->
                                <form action="api/processor.php" name="login_form" id="login_form" method="post">
                                    <input name="flag" type="hidden" value="login_submit" />
                                    <!-- Username input -->
                                    <div class="field">
                                        <input name="username" placeholder="Username" type="text" id="username" required />
                                        <span class="entypo-user icon"></span>
                                        <span class="slick-tip left">Inserir username</span>
                                    </div>
                                    <!-- Password input -->
                                    <div class="field">
                                        <input name="password" placeholder="Password" type="password" id="password" required />
                                        <span class="entypo-lock icon"></span>
                                        <span class="slick-tip left">Inserir password</span>
                                    </div>
                                    <div class="clrfx mt-10"></div>
                                    <!-- Signed in button -->
                                    <?PHP
                                    if (isset($_SESSION['bo_auth'])) {
                                        ?>
                                        <input type="button" value="Reconectar" class="send" id="connectBut">
                                            <?PHP
                                        } else {
                                            ?>
                                            <input type="button" style="border: none; outline: none; background: #333; cursor: none;" value="Conectar" class="send" id="connectBut" disabled>
                                                <?PHP
                                            }
                                            ?>
                                            <!-- Send button -->
                                            <input type="submit" class="send" form="login_form" id="loginBut" name="send" />
                                            <!--<span class="entypo-note-beamed"></span>-->
                                            </form>
                                            <!-- / Form fields -->
                                            </div>
                                            <p style="padding-top: 5px; font-size: 11px; color:#F90;">Powered by <a href="http://www.comPonto.com">comPonto.com</a></p>
                                            <!-- / Login form -->
                                            </section>

                                            <!-- *************************** -->
                                            <!-- END COPYING HERE            -->
                                            <!-- *************************** -->

                                            </div>

                                            </p>
                                            </body>
                                            </html>