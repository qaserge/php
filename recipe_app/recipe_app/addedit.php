<?php

// STATE 1: first show
$app->get('/admin/dashboard/:action(/:id)', function($action, $id = 0) use ($app) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
        $app->redirect('/forbidden');
        return;
    }
    if (($action == 'add' && $id != 0) || ($action == 'edit' && $id == 0)) {
        $app->notFound(); // 404 page
        return;
    }
    if ($action == 'add') {
        $app->render('admin/recipes_addedit.html.twig');
    } else { // edit
        $product = DB::queryFirstRow("SELECT * FROM recipes WHERE id=%i", $id);
        if (!$product) {
            $app->notFound();
            return;
        }
        $app->render('admin/recipes_addedit.html.twig', array('v' => $product));
    }
})->conditions(array('action' => '(add|edit)'));

$app->post('/admin/recipes/:action(/:id)', function($action, $id = 0) use ($app) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
        $app->redirect('/forbidden');
        return;
    }
    if (($action == 'add' && $id != 0) || ($action == 'edit' && $id == 0)) {
        $app->notFound(); // 404 page
        return;
    }
    //
    $name = $app->request()->post('name');
    $description = $app->request()->post('description');
    $instruction = $app->request()->post('instruction');
    //
    $errorList = array();
    // FIXME: sanitize html tags in name and description
    if (strlen($name) < 2 || strlen($name) > 50) {
        array_push($errorList, "Recipe name must be 2-50 characters long");
        $name = "";
    }
    if (strlen($description) < 2 || strlen($description) > 500) {
        array_push($errorList, "Recipe description must be 2-500 characters long");
        $description = "";
    }
    if (strlen($instruction) < 2 || strlen($instruction) > 2000) {
        array_push($errorList, "Recipe instructions must be 2-2000 characters long");
        $price = "";
    }
    if ($errorList) { // STATE 2: failed submission
        $app->render('admin/recipes_addedit.html.twig', array(
            'errorList' => $errorList,
            'v' => array('id' => $id,
                'name' => $name, 'description' => $description,
                'instruction' => $instruction)));
    } else { // STATE 3: successful submission
        if ($action == 'add') {
            DB::insert('products', array('name' => $name, 'description' => $description,
                'instruction' => $instruction, 'imagePath' => ''));
            $app->render('admin/recipes_addedit_success.html.twig');
        } else {
            DB::update('recipes', array('name' => $name, 'description' => $description,
                'instruction' => $instruction, 'imagePath' => ''), 'id=%i', $id);
            $app->render('admin/recipes_addedit_success.html.twig', array('savedId' => $id));
        }
    }
})->conditions(array('action' => '(add|edit)'));