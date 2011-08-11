<?php
include_once 'function.php';
?>
<h2>Demo Form Auto Saver</h2>
<form action="<?php echo $_SERVER['PHP_SELF'];?>" method="POST" enctype="multipart/form-data">
<em>The field below will be ignored by the function</em><br/>
Table to save the data: <input type="text" name="table" ><br/>
<hr>
<em>All the fields below will NOT be ignored</em><br/>
First Name: <input type="text" name="fname" ><br/>
Age: <input type="text" name="age" ><br/>
Gender:
<select name="gender" >
<option>--Select Gender</option>
<option>Male</option>
<option>Female</option>
</select><br/>
Receive Newsletter?:
<input name="newsletter" type="radio" value="1"/>Yes
<input name="newsletter" type="radio" value="0"/>No<br/>

Programming Languages:
<input name="language[]" value="PHP" type="checkbox" /> PHP
<input name="language[]" value="JSP" type="checkbox" /> JSP
<input name="language[]" value="ASP.Net" type="checkbox" /> ASP.Net <br/><br/>

<input type="submit" value="Submit" name="Submit" />
</form><br/><br/>
<?php
	if (isset($_POST['Submit'])) {
		//echo "<h2>Below is the Post Array</h2>";
		//print_r($_POST);

		//below you can manipulate the form data before passing it to the function
		$formDataArray = $_POST;

		//names of fields to ignore
		$ignoreFields = array('table','Submit');

		//aliases
		$aliases = array('fname' => 'name');

		//fields which will be saved to a numerical column
		$numericalTableColumns = array('age', 'newsletter');

		//functions to apply to each data
		//@todo: make is such these functions are passed as associative array, specific functions for each form field
		$functionsToApply = 'ucwords|strip_tags|trim';

		//incase we have fields which user can select multiple items, convert them into a string and use this separator
		$multiSelectSeparator = '|';

		$result = processForm($formDataArray, $ignoreFields, $aliases, $numericalTableColumns, $functionsToApply, $multiSelectSeparator);
		echo "INSERT INTO mydb.mytable VALUES($result)";
	}
?>