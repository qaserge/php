<?php

session_cache_limiter(false);
session_start();

require_once 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
// create a log channel

$log = new Logger('main');
$log->pushHandler(new StreamHandler('logs/everything.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));

if ($_SERVER['SERVER_NAME']=='localhost'){ //localhost
DB::$user = 'recipe';
DB::$password = 'R8rwsL5cJ22bI1M6'; 
DB::$dbName = 'recipe';
DB::$encoding = 'utf8';
DB::$port = 3333;
DB::$error_handler = 'database_error_handler';
DB::$nonsql_error_handler = 'database_error_handler';
}
 else { //ipd17.com
DB::$user = 'cp4928_recipe';
DB::$password = 'nCU)gvM@Y~ai'; // Prom
DB::$dbName = 'cp4928_recipe';
DB::$encoding = 'utf8';
//DB::$port = 3333;
DB::$error_handler = 'database_error_handler';
DB::$nonsql_error_handler = 'database_error_handler';
}

function database_error_handler($params) {
    global $app, $log;
    $log->error("SQL Error: " .$params['error']);
    if (isset($params['query'])){
        $log->error("SQL Error: " .$params['query']);
    }
    $app->render("internal_error.html.twig");
    http_response_code(500);
  die; // don't want to keep going if a query broke
}

// Slim creation and setup
$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig()
        ));

$view = $app->view();
$view->parserOptions = array(
    'debug' => true,
    'cache' => dirname(__FILE__) . '/cache'
);
$view->setTemplatesDirectory(dirname(__FILE__) . '/templates');

$view->getEnvironment()->addGlobal('sessionUser', @$_SESSION['user']);      


\Slim\Route::setDefaultConditions(array(
    'id' => '[1-9][0-9]*'
));

function getUserIpAddr(){
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}


require_once 'admin.php';
//require_once 'register.php';
//require_once 'login.php';
//require_once 'logout.php';
require_once 'addedit.php';
require_once 'account.php';
//require_once 'index.php';
//require_once 'createxml.php';
//require_once 'livesearch.php';
//require_once 'test.php';
//require_once 'index.php';

$app->get('/search', function() use($app){ 
//    $search = $app->request()->post('search');
//    $searchResult = DB::query("SELECT * FROM recipes WHERE concat(name, description, instruction, ingredients) like '%" .$search. "%'");
    
        $searchResult ="";
        $app->render('search.html.twig', array('searchResult'=>$searchResult));
    if (isset($_POST['submit2'])) {
		$search = $app->request()->post('search');
                $searchResult = DB::query("SELECT * FROM recipes WHERE concat(name, description, instruction, ingredients) like '%" .$search. "%'");
                $app->render('search.html.twig', array('searchResult'=>$searchResult));
//     if ($searchResult->num_rows > 0) {
//			while ($data = $searchResult->fetch_array())
//				echo $data['name']  . "<br>";                                 
//		} else
//			echo "Your search query doesn't match any data!";
	}
        
    });


$app->get('/', function() use ($app) {       
    $mealtype = DB::query('SELECT DISTINCT(mealtype) FROM recipes ORDER BY mealtype DESC'); 
    $cuisine = DB::query('SELECT DISTINCT(cuisine) FROM recipes ORDER BY cuisine DESC');
    $diet = DB::query('SELECT DISTINCT(diet) FROM recipes ORDER BY diet DESC');   
    

    
        
    $app->render('/index.html.twig', array('mealtype' => $mealtype, 'cuisine' => $cuisine, 'diet'=>$diet));
    
});

//$app->get('/', function() use ($app) {        
//    $cuisine = DB::query('SELECT DISTINCT(cuisine) FROM recipes ORDER BY cuisine DESC');    
//    $app->render('/index.html.twig', array('cuisine' => $cuisine));
//});

//$app->get('/', function() use ($app) {
//    $list = DB::query("SELECT R.name, R.description, R.instruction, U.name AS 'User', C.name AS 'Cuisine', D.name AS 'Diet' FROM recipes AS R INNER JOIN users AS U ON R.fk_user_id = U.id INNER JOIN mealtypes AS MT ON R.fk_mealtype = MT.id INNER JOIN cuisines AS C ON R.fk_cuisine = C.id INNER JOIN diets AS D ON R.fk_diet = D.id");
//
//    $app->render('index.html.twig', array('list' => $list));
//});

$app->get('/internalerror', function() use ($app, $log) {
    $app->render("internal_error.html.twig");
});

$app->get('/forbidden', function() use ($app) {
    $app->render('forbidden.html.twig');
});

//$app->get('/'.$recipeId, function() use ($app) {
//    $recipe = DB::queryFirstRow("SELECT * FROM recipes WHERE id='" .$recipeId. "'");
//    $app->render('recipe.html.twig');
//});

$app->get('/:id', function($id) use ($app, $log) {
    $recipe = DB::queryFirstRow("SELECT * FROM recipes WHERE id=%i", $id);
    $app->render('recipe.html.twig', array('recipes' => $recipe));
});

$app->post('/', function() use ($app, $log) {    
    $search = $app->request()->post('search'); 
    $searchResult = DB::query("select * from recipes where concat(name,description,instruction,ingredients) like '%".$search."%'");
    $app->render('search.html.twig', array('searchResult' => $searchResult));
});

$app->post('/:id', function($id) use ($app, $log) {    
    $search = $app->request()->post('search'); 
    $searchResult = DB::query("select * from recipes where concat(name,description,instruction,ingredients) like '%".$search."%'");
    $app->render('search.html.twig', array('searchResult' => $searchResult));
});

$app->get('/isemailregistered/(:email)', function($email = "") use ($app, $log) {
    $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    if ($user) {
        echo "Email already registered";
    }
});
//search by recipe
$app->get('/search', function() use ($app, $log) {
   $name = $app->request()->post('name');
   $errorList = array();
  if (strlen($name) < 2 || strlen($name) > 50) {
      array_push($errorList, "Ingredeint name must be 2-50 characters long");
      $name = "";
  }else {
    $list = DB::query("SELECT * FROM ingredients WHERE name LIKE %ss", $name);
  }
    $app->render('search.html.twig');
});

//ADD RECIPE
/*
$app->get('/addrecipe', function() use ($app, $log) {

  if (!isset($_SESSION['user'])) {
          $cuisineList = DB::query('SELECT cuisine FROM recipes');
  }

    $app->render('add_recipe.html.twig', array('cuisineList' => $cuisineList, 'isUser' => TRUE));
});

$app->get('/addrecipe', function() use ($app, $log) {

  if (!isset($_SESSION['user'])) {
          $mealList = DB::query('SELECT mealtype FROM recipes');
  }

    $app->render('add_recipe.html.twig', array('mealList' => $mealList, 'isUser' => TRUE));
});

$app->get('/addrecipe', function() use ($app, $log) {

  if (!isset($_SESSION['user'])) {
          $dietList = DB::query('SELECT diet FROM recipes');
  }

    $app->render('add_recipe.html.twig', array('dietList' => $dietList, 'isUser' => TRUE));
});




function enumCuisineDropdown($recipe, $cuisine, $echo = false)
{
   $selectCuisineDropdown = "<select name=\"$cuisine\">";
   $result = mysql_query("SELECT * FROM recipes WHERE TABLE_NAME = '$recipe' AND COLUMN_NAME = '$cuisine'")
   or die (mysql_error());

    $row = mysql_fetch_array($result);
    $enumList = explode(",", str_replace("'", "", substr($row['COLUMN_TYPE'], 5, (strlen($row['COLUMN_TYPE'])-6))));

    foreach($enumList as $value)
         $selectCuisineDropdown .= "<option value=\"$value\">$value</option>";

    $selectDropdown .= "</select>";

    if ($echo)
        echo $selectCuisineDropdown;

    return $selectCuisineDropdown;
}
*/



/*****************************************************************/








/***********************************************************/

$app->get('/session', function() {
    echo '<pre>';
    print_r($_SESSION);
});

$app->run();
