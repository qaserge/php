<html>
	<head>
		<title>PHP Search Form</title>
	</head>
	<body>
		<form method="post" action="search.php">
			<input type="text" name="q" placeholder="Search Query...">
			<select name="column">
				<option value="">Select Filter</option>
				<option value="name">name</option>
				<option value="ingredients">ingredients</option>
			</select>
			<input type="submit" name="submit" value="Find">
		</form>
	</body>
</html>
<?php
	if (isset($_POST['submit'])) {
		//$connection = new mysqli("HOST", "USER", "PASS", "DB_NAME", port_int);                
                $connect = new mysqli("localhost", "recipe", "R8rwsL5cJ22bI1M6", "recipe", 3333);
		$q = $connect->real_escape_string($_POST['q']);                     
		$column = $connect->real_escape_string($_POST['column']);    
		if ($column == "" || ($column != "name" && $column != "ingredients"))
		$column = "name";
                
                $sql = $connect->query("SELECT name FROM recipes WHERE $column LIKE '%$q%'");
                
		if ($sql->num_rows > 0) {
			while ($data = $sql->fetch_array())
				echo $data['name']  . "<br>";                                 
		} else
			echo "Your search query doesn't match any data!";
	}
?>

