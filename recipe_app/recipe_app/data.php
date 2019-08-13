<?php

include('database_connection.php');

if(isset($_POST["action"]))
{
        $query = "SELECT * FROM recipes ";

        if(isset($_POST["mealtype"]))
	{
		$mealtype_filter = implode("','", $_POST["mealtype"]);
		$query .= "WHERE mealtype IN ('".$mealtype_filter."')";
	}
        if(isset($_POST["cuisine"]))
	{
		$cuisine_filter = implode("','", $_POST["cuisine"]);
		$query .= "WHERE cuisine IN ('".$cuisine_filter."')";
	}
        if(isset($_POST["diet"]))
	{
		$diet_filter = implode("','", $_POST["diet"]);
		$query .= "WHERE diet IN ('".$diet_filter."')";
	}
	
	$statement = $connect->prepare($query);
	$statement->execute();
	$result = $statement->fetchAll();
	$total_row = $statement->rowCount();
	$output = '';
        
	if($total_row > 0)
	{
		foreach($result as $row)
		{
                    $textDescription = strip_tags($row['description']);
                    if (strlen($textDescription) > 80) {
                        $textDescription = substr($textDescription, 0, 80) . "...";
                    }
			$output .= '
			<div class="col-sm-4 col-lg-4 col-md-3">
				
                                <div style="border:1px solid #ccc; border-radius:5px; padding:16px; margin-bottom:16px; height:450px;">
					<img src="'. $row['imagePath'] .'" alt="" class="img-responsive" height="150" width="200">
					<p align="center"><strong><a href="'.$row['id'].'">'. $row['name'] .'</a></strong></p>
                                        '. $textDescription.' <br /><hr>    
					<p>
                                        mealtype : '. $row['mealtype'].' <br />
					cuisine : '. $row['cuisine'] .' <br />
					diet : '. $row['diet'] .' <br />					 
                                        </p>
				</div>

			</div>
			';
		}
	}
	else
	{
		$output = '<h3>No Data Found</h3>';
	}
	echo $output;
}

?>