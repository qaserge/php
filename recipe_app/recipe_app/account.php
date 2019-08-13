<?php
//LOGIN
// STATE 1: first show
$app->get('/login', function() use ($app) {
    $app->render('login.html.twig');
});

$app->post('/login', function() use ($app, $log) {
    $email = $app->request()->post('email');
    $password = $app->request()->post('password');
    //
    $loginSuccessful = false;
    $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);    
    if ($user) {
        if ($user['password'] == $password) {
            $loginSuccessful = true;
        }        
    }    
    //
    if (!$loginSuccessful) { // array not empty -> errors present
        $log->info(sprintf("Login failed, email=%s, from IP=%s", $email, getUserIpAddr()));
        $app->render('login.html.twig', array('error' => true));
    } else { // STATE 3: successful submission
        unset($user['password']);
        $_SESSION['user'] = $user;
        $log->info(sprintf("Login successful, email=%s, from IP=%s", $email, getUserIpAddr()));
        $app->render('login_success.html.twig');
    }
});

//LOGOUT
$app->get('/logout', function() use ($app) {
    unset($_SESSION['user']);
    $app->render('logout.html.twig');
});


//REGISTER
// STATE 1: first show
$app->get('/register', function() use ($app) {
    $app->render('register.html.twig');
});

$app->post('/register', function() use ($app, $log) {
    $name = $app->request()->post('name');
    $email = $app->request()->post('email');    
    $pass1 = $app->request()->post('pass1');
    $pass2 = $app->request()->post('pass2');
    //
    $errorList = array();
    
    // FIXME: sanitize html tags
    if (strlen($name) < 2 || strlen($name) > 50) {
        array_push($errorList, "Name must be 2-50 characters long");
        $name = "";
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE) {
        array_push($errorList, "Email invalid");
        $email = "";
    } else {
        // FIXME: Make sure email is not already registered !
    }
    if ($pass1 != $pass2) {
        array_push($errorList, "Passwords do not match");        
    } else {
        if ((strlen($pass1) < 6)
                || (preg_match("/[A-Z]/", $pass1) == FALSE )
                || (preg_match("/[a-z]/", $pass1) == FALSE )
                || (preg_match("/[0-9]/", $pass1) == FALSE )) {
            array_push($errorList, "Password must be at least 6 characters long, "
                    . "with at least one uppercase, one lowercase, and one digit in it");
        }
    }
    if ($errorList) { // STATE 2: failed submission
        $app->render('register.html.twig', array(
            'errorList' => $errorList,
            'v' => array('name' => $name, 'email' => $email)
            ));
    } else { // STATE 3: successful submission
        DB::insert('users', array('name' => $name, 'email' => $email, 'password' => $pass1));
        $userId = DB::insertId();
        $log->debug("User registed with id=" . $userId);
        $app->render('register_success.html.twig');
    }
});

//$app->run();