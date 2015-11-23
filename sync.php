<?PHP
/*
# Warmrental Google Sync
# Powered by comPonto.com
*/


error_reporting(E_ALL);
ini_set('display_errors', 1);

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
	
    <title>Calendar Synchronyzation</title>
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
	</style><link rel="stylesheet" type="text/css" href="src/jquery-ui-1.11.0.custom/jquery-ui.css">   
    <!-- Bootstrap core CSS -->
    <link href="src/bootstrap-3.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="css/slick_lf.css" media="screen" />

    <!-- Custom styles for this template -->
    <link href="css/calendars.css" rel="stylesheet">
    <script src="js/jquery-2.1.1.min.js"></script>
    <script src="src/jquery-ui-1.11.0.custom/jquery-ui.js"></script>
  </head>

<body>


<?PHP
		if ($exists_Error) {
?>
    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron error">
      <div class="container">
		<button name="logout" class="btn btn-danger logoutBut" id="logoutbut"><span class="glyphicon glyphicon-log-out"></span>  Logout</button>
        <div class="login_page">
        Warmrental Google Sync 
        <h1>Warmrental.com</h1>
        </div>
        <p class="lato"><span style="color: red;">Olá <strong><?PHP echo $_SESSION['username'];?></strong></span>, existem neste momento <span style="color: red;"> <?PHP echo $num_erros; ?> </span> erros a resolver relativos a conflitos entre reservas de proprietário e reservas warmrental, nomeadamente:<br><span style="font-size: 16px;"><?PHP echo $error_string; ?></span></p>

<?PHP			
		} else {
?>	
    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
      <div class="container">
		<button name="logout" class="btn btn-danger logoutBut" id="logoutbut"><span class="glyphicon glyphicon-log-out"></span>  Logout</button>
        <div class="login_page">
        Warmrental Google Sync 
        <h1>Warmrental.com</h1>
        </div>		
        <p class="lato"><span style="color: red;">Olá <strong><?PHP echo $_SESSION['username'];?></strong></span>, existem neste momento <span style="color: red;"> <?PHP echo $_SESSION['num_props']; ?> </span>propriedades registadas com calendários atribuidos. Clique em Sincronizar para iniciar a sincronização.</p>

<?PHP			
		}
?>

        <div class="row">
        	<div class="col-md-2">
            </div>
        	<div class="col-md-2">
            </div>
        	<div class="col-md-2">
            	<p><a href="calendarsOLD.php" style="width: 100%" class="btn btn-success btn-lg" role="button"><span class="glyphicon glyphicon-calendar"></span>  Calendários </a></p>
            </div>
        	<div class="col-md-2">
            	<p><a id="sync_start" style="width: 100%" class="btn btn-success btn-lg" role="button"><span class="glyphicon glyphicon-refresh"></span> Sincronizar </a></p>
            </div>
        	<div class="col-md-2">
            </div>
        	<div class="col-md-2">
            </div>
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <div class="row-fluid">       
	  </div>
      
      <div class="row-fluid" id="conteudos">
        <div class="col-md-12">
		  <h2 style="font-family: 'Lobster', Georgia, Times, serif;">Server Messages</h2>
          <hr style="width: 100%; height: 1px; color:#09C; background-color: #09C;">
          <br>
          <div id="" style="overflow:auto; height:250px;">
              <pre id="server_results"></pre>
          </div>
          <br>
		  <h2 style="font-family: 'Lobster', Georgia, Times, serif;">Debugging</h2>
          <hr style="width: 100%; height: 1px; color:#09C; background-color: #09C;">
          <br>
          <div style="overflow:auto; height:200px;">
          	<pre id="debug_div" >
            </pre>
    	  </div>
          
          <div id="dl_conteudos">
          </div>
            
          <footer>
            <p style="padding-top: 5px; font-size: 11px; color:#F90;">Powered by <a href="http://www.comPonto.com">comPonto.com</a></p>
          </footer>
      </div>
    </div> 
</div> <!-- /container -->

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="src/bootstrap-3.2.0/dist/js/bootstrap.min.js"></script>
  

    <!-- Application core JavaScript
    ================================================== -->    
	<script src="js/api.js"></script> 
	<script src="js/sync.js"></script>
    
    
</body>
</html>