<?php

if (false) {
    $app = new \Slim\Slim();
}



function RandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randstring = '';
    for ($i = 0; $i < $length; $i++) {
        $randstring .= $characters[rand(0, strlen($characters))];
    }
    return $randstring;
}

$app->get('/admin/recipes/recipelist', function() use ($app) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
        $app->redirect('/forbidden');
        return;
    }      
    $list = DB::query("SELECT * FROM recipes");    
    $app->render('admin/recipelist.html.twig', array('list' => $list));
});


// ADMIN
// STATE 1: first show
$app->get('/admin/recipes/:action(/:id)', function($action, $id = 0) use ($app) {
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
        $recipe = DB::queryFirstRow("SELECT * FROM recipes WHERE id=%i", $id);
        if (!$recipe) {
            $app->notFound();
            return;
        }
        $app->render('admin/recipes_addedit.html.twig', array('v' => $recipe));
    }
})->conditions(array('action' => '(add|edit)'));

$app->post('/admin/recipes/:action(/:id)', function($action, $id = 0) use ($app, $log) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
        $app->redirect('/forbidden');
        return;
    }
    if (($action == 'add' && $id != 0) || ($action == 'edit' && $id == 0)) {
        $app->notFound(); // 404 page
        return;
    }               	
        
        $name = $app->request()->post('name');        
        $description = $app->request()->post('description');        
        $instruction = $app->request()->post('instruction');        
        $ingredients = $app->request()->post('ingredients');          
        $mealtype = $app->request()->post('mealtype'); 
        $cuisine = $app->request()->post('cuisine');        
        $diet = $app->request()->post('diet');
        
    //
    $errorList = array();
    // FIXME: sanitize html tags in name and description
    if (strlen($name) < 2 || strlen($name) > 50) {
        array_push($errorList, "Name must be 2-50 characters long");
        $name = "";
    }
    if (strlen($description) < 2 || strlen($description) > 500) {
        array_push($errorList, "Description must be 2-500 characters long");
        $description = "";
    }
    if (strlen($instruction) < 2 || strlen($instruction) > 2000) {
        array_push($errorList, "Instruction must be 2-2000 characters long");
        $instruction = "";
    }

    $recipeImage = $_FILES['recipeImage'];
    // echo "<pre>111\n"; print_r($recipeImage); //exit;
    if ($recipeImage['error'] != 0) {
        array_push($errorList, "File submission failed, make sure you've selected an image (1)");
    } else {
        $data = getimagesize($recipeImage['tmp_name']);
        if ($data == FALSE) {
            array_push($errorList, "File submission failed, make sure you've selected an image (2)");
        } else {
            if (!in_array($data['mime'], array('image/jpeg', 'image/gif', 'image/png'))) {
                array_push($errorList, "File submission failed, make sure you've selected an image (3)");
            } else {
                // FIXME: sanitize file name, otherwise a security hole, maybe
                $recipeImage['name'] = strtolower($recipeImage['name']);
                if (!preg_match('/.\.(jpg|jpeg|png|gif)$/', $recipeImage['name'])) {
                    array_push($errorList, "File submission failed, make sure you've selected an image (4)");
                }
                $info = pathinfo($recipeImage['name']);
                $recipeImage['name'] = preg_replace('[^a-zA-Z0-9_\.-]', '_', $recipeImage['name']);
                if (file_exists('images/' . $recipeImage['name'])) {
                    // array_push($errorList, "File submission failed, refusing to override existing file (5)");
                    $num = 1;
                    
                    while (file_exists('images/' . $info['filename'] . "_$num." . $info['extension'])) {
                        $num++;
                    }
                    $recipeImage['name'] = $info['filename'] . "_$num." . $info['extension'];
                }
                
            }
        }
    }
    //
    if ($errorList) { // STATE 2: failed submission
        $app->render('admin/recipes_addedit.html.twig', array(
            'errorList' => $errorList,
            'v' => array('id' => $id,
                'name' => $name, 
                'description' => $description,
                'instruction' => $instruction,
                'ingredients' => $ingredients,
                'mealtype' => $mealtype,
                'cuisine' => $cuisine,
                'diet'=> $diet)));
    } else { // STATE 3: successful submission
        $imagePath = 'images/' . $recipeImage['name'];
        // DANGERS: // images/../slimshop17.php
        // 1. what if name begins with .. and escapes to an upper directory?
        // 2. what if the file extension is dangerous, e.g. php
        // 3. file overriding
        // $log->debug("a $imagePath " . $recipeImage['tmp_name']);
        if (!move_uploaded_file($recipeImage['tmp_name'], $imagePath)) {
            $log->err("Error moving uploaded file: " . print_r($recipeImage, true));
            $app->redirect('/internalerror');
            return;
        }
        if ($action == 'add') {
            DB::insert('recipes', array(
                'name' => $name, 
                'description' => $description,
                'instruction' => $instruction,
                'ingredients' => $ingredients, 
                'mealtype' => $mealtype,
                'cuisine' => $cuisine,
                'diet' => $diet,
                'imagePath' => $imagePath,
                'fk_user' => $_SESSION['user']['id']));
                //'fk_user' => '2'));
            $app->render('admin/recipes_addedit_success.html.twig');
        } else {
            // remove the old file
            $oldImagePath = DB::queryFirstField("SELECT imagePath FROM recipes WHERE id=%i", $id);
            if ($oldImagePath != "" && file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
            DB::update('recipes', array(
                'name' => $name, 
                'description' => $description,
                'instruction' => $instruction,
                'ingredients' => $ingredients, 
                'mealtype' => $mealtype,
                'cuisine' => $cuisine,
                'diet' => $diet,
                'imagePath' => $imagePath,
                'fk_user' => $_SESSION['user']['id']));
                //'fk_user' => '1'), 'id=%i', $id);
            $app->render('admin/recipes_addedit_success.html.twig', array('savedId' => $id));
        }
    }
})->conditions(array('action' => '(add|edit)'));

$app->get('/admin/recipes/delete/:id', function($id) use ($app, $log) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
        $app->redirect('/forbidden');
        return;
    }//
    $item = DB::queryFirstRow("SELECT * FROM recipes WHERE id=%i", $id);
    if (!$item) {
        $app->notFound();
        return;
    }
    $app->render('admin/recipes_delete.html.twig', array('item' => $item));
});
$app->post('/admin/recipes/delete/:id', function($id) use ($app, $log) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
        $app->redirect('/forbidden');
        return;
    }//
    if ($app->request()->post('confirmed') == 'true') {
        DB::delete("recipes", "id=%i", $id);
        $app->render('admin/recipes_delete_success.html.twig');
    } else {
        $app->redirect('/internalerror');
        return;
    }
});



//USER

// STATE 1: first show
$app->get('/recipes/:action(/:id)', function($action, $id = 0) use ($app) {
    if (!isset($_SESSION['user']) ) {
        $app->redirect('/forbidden');
        return;
    }
    if (($action == 'add' && $id != 0) || ($action == 'edit' && $id == 0)) {
        $app->notFound(); // 404 page
        return;
    }
    if ($action == 'add') {
        $app->render('/add_recipe.html.twig');
    } else { // edit
        $recipe = DB::queryFirstRow("SELECT * FROM recipes WHERE id=%i", $id);
        if (!$recipe) {
            $app->notFound();
            return;
        }
        $app->render('/add_recipe.html.twig', array('v' => $recipe));
    }
})->conditions(array('action' => '(add|edit)'));

$app->post('/recipes/:action(/:id)', function($action, $id = 0) use ($app, $log) {
    if (!isset($_SESSION['user']) ) {
        $app->redirect('/forbidden');
        return;
    }
    if (($action == 'add' && $id != 0) || ($action == 'edit' && $id == 0)) {
        $app->notFound(); // 404 page
        return;
    }               	
        
        $name = $app->request()->post('name');        
        $description = $app->request()->post('description');
        $instruction = $app->request()->post('instruction');
        $ingredients = $app->request()->post('ingredients');
        $mealtype = $app->request()->post('mealtype');  
        $cuisine = $app->request()->post('cuisine');
        $diet = $app->request()->post('diet');
    //
    $errorList = array();
    // FIXME: sanitize html tags in name and description
    if (strlen($name) < 2 || strlen($name) > 50) {
        array_push($errorList, "Name must be 2-50 characters long");
        $name = "";
    }
    if (strlen($description) < 2 || strlen($description) > 500) {
        array_push($errorList, "Description must be 2-500 characters long");
        $description = "";
    }
    if (strlen($instruction) < 2 || strlen($instruction) > 2000) {
        array_push($errorList, "Instruction must be 2-2000 characters long");
        $instruction = "";
    }

    $recipeImage = $_FILES['recipeImage'];
    // echo "<pre>111\n"; print_r($recipeImage); //exit;
    if ($recipeImage['error'] != 0) {
        array_push($errorList, "File submission failed, make sure you've selected an image (1)");
    } else {
        $data = getimagesize($recipeImage['tmp_name']);
        if ($data == FALSE) {
            array_push($errorList, "File submission failed, make sure you've selected an image (2)");
        } else {
            if (!in_array($data['mime'], array('image/jpeg', 'image/gif', 'image/png'))) {
                array_push($errorList, "File submission failed, make sure you've selected an image (3)");
            } else {
                // FIXME: sanitize file name, otherwise a security hole, maybe
                $recipeImage['name'] = strtolower($recipeImage['name']);
                if (!preg_match('/.\.(jpg|jpeg|png|gif)$/', $recipeImage['name'])) {
                    array_push($errorList, "File submission failed, make sure you've selected an image (4)");
                }
                $info = pathinfo($recipeImage['name']);
                $recipeImage['name'] = preg_replace('[^a-zA-Z0-9_\.-]', '_', $recipeImage['name']);
                if (file_exists('images/' . $recipeImage['name'])) {
                    // array_push($errorList, "File submission failed, refusing to override existing file (5)");
                    $num = 1;
                    
                    while (file_exists('images/' . $info['filename'] . "_$num." . $info['extension'])) {
                        $num++;
                    }
                    $recipeImage['name'] = $info['filename'] . "_$num." . $info['extension'];
                }
                
            }
        }
    }
    //
    if ($errorList) { // STATE 2: failed submission
        $app->render('/add_recipe.html.twig', array(
            'errorList' => $errorList,
            'v' => array('id' => $id,
                'name' => $name, 
                'description' => $description,
                'instruction' => $instruction,
                'ingredients' => $ingredients,
                'mealtype' => $mealtype,
                'cuisine' => $cuisine,
                'diet'=> $diet)));
    } else { // STATE 3: successful submission
        $imagePath = 'images/' . $recipeImage['name'];
        
        if (!move_uploaded_file($recipeImage['tmp_name'], $imagePath)) {
            $log->err("Error moving uploaded file: " . print_r($recipeImage, true));
            $app->redirect('/internalerror');
            return;
        }
        if ($action == 'add') {
            DB::insert('recipes', array(
                'name' => $name,                 
                'description' => $description,
                'instruction' => $instruction,
                'ingredients' => $ingredients, 
                'mealtype' => $mealtype,
                'cuisine' => $cuisine,
                'diet' => $diet,
                'imagePath' => $imagePath,
                'fk_user' => $_SESSION['user']['id']));
                //'fk_user' => '2'));
            $app->render('/recipe_addedit_success.html.twig');
        } else {

        }
    }
})->conditions(array('action' => '(add|edit)'));