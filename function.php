<?php
/**
 * PHP Form Saver
 *
 * LICENSE
 * This source file is subject to the Apache License, Version 2.0
 * It is also available through the world-wide-web at this URL:
 * http://www.opensource.org/licenses/Apache-2.0
 *
 * @category   Form
 * @package    Form_saver
 * @license    http://www.opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 * @version    0.1:
 */

/*
 * Save a generic form into a specified database table
 *
 * This function will attempt create a query which can be used to save all the contents of a form into a table:
 * Logic:
 * 1. Loop thru the POST or GET, reading the "keys" and "values"
 * 2. Check if key is to be ignored, if yes, get rid of it
 * 3. Check if key is aliased, if yes, effect the alias
 * 4. Sometimes, the key will be an array itself, e.g. in multi select list boxes, in such cases, what to do? convert it into a string with a separator
 * 5. Check if there are any functions to apply to the data, if yes, apply it
 * 6. Check if key target column is numerical, if so, do not wrap it in single quotes
 * 7. Create SQL Snippet, i.e. the "table_column = $value_from_form, " part of the Insert query
 *
 * @param string $formDataArray An associative array containing the form data
 * @param array $ignoreFields Optional Array containing names of fields to be ignored when saving
 * @param array $aliases Optional Associative array containing aliases of HTML form fields to database table fields
 * @param array $numericalTableColumns Array containing table fields which are numbers
 * @param string $functionsToApply String containing native PHP or user functions to apply to each value before inserting into database, codeigniter style, e.g. stri_tags|trim|myFunction
 * @param string $multiSelectSeparator If a form field returns array data, simply convert it into a string with this separator
 * @return string $sqlSnippet String containing the actual insert part of an insert query
 * @access public
 * */
function processForm($formDataArray, $ignoreFields, $aliases, $numericalTableColumns, $functionsToApply, $multiSelectSeparator)
{
	//init variables
	$ignoredFieldsIsArray = 1; //a flag to denote whether $ignoreFields is array or string, default is array
	$aliasesProvided = 1; //a flag to denote whether $aliases is an array, default is array
	$numericalTableColumnsProvided = 1;
	$functionsToApplyProvided = 1;
	$functionsArray = array();
	$functionName = '';
	$dataArray = '';
	$dataArrayRefinedStepOne = array(); //this will contain the fields which are NOT to be ignored
	$dataArrayRefinedStepTwo = array(); //this will contain the $dataArrayRefinedStepOne fields that have been aliased
	$dataArrayRefinedStepThree = array(); //this will contain the $dataArrayRefinedStepTwo fields after applying functions
	$dataArrayRefinedStepFour = array();
	$dataArrayRefinedStepFive = array();
	$dataHolderKey = ''; //variable to temporarily contain array key data when popping and pushing
	$dataHolderValue = ''; //variable to temporarily contain array value data when popping and pushing
	$sqlSnippet = '';
	$i = 0;
	$j = 0;

	//STEP 0: data check, sanitization and validations
	//we need to make sure that the main array is actually an array, this is most critical
	if (!is_array($formDataArray)) {
		echo '<h1>Error: main array must be array</h1>';
		print_r($formDataArray);
		return false;
	}

	//check ignoredFields if it is an array or a simple string
	if (!is_array($ignoreFields)) {
		//we have a simple string
		$ignoredFieldsIsArray = 0;
	}

	//check aliases
	if (!is_array($aliases)) {
		//assume no aliases specified
		$aliasesProvided = 0;
	}

	//check if there are functions to be applied to the data before creating the SQL snippet
	if (strlen(trim($functionsToApply))<1) {
		//assume no aliases specified
		$functionsToApplyProvided = 0;
	}

	//check if there are numerical fields to be inserted into
	if (!is_array($numericalTableColumns)) {
		//assume no aliases specified
		$numericalTableColumnsProvided = 0;
	}

	//check if the multi select separator has been set, if not, default it to |
	if (strlen(trim($multiSelectSeparator))==0) {
		$multiSelectSeparator = '|';
	}

	//start the main loop. Engine, Start!
	$dataArray = $formDataArray;
	//echo "<h2>Data Array: Similar to POST</h2>";
	//print_r($dataArray);

	//STEP 1
	foreach ($dataArray as $htmlFieldName => $htmlFieldValue) {
		//STEP 2
		//check if current field is to be ignored
		if ($ignoredFieldsIsArray == 1) {
			if (!in_array($htmlFieldName, $ignoreFields)) {
				//this field is NOT to be ignored
				$dataArrayRefinedStepOne["$htmlFieldName"] = $htmlFieldValue;
			}
		}
		else {
			//there are no fields to ignore, add all of them into the refinery
			$dataArrayRefinedStepOne["$htmlFieldName"] = $htmlFieldValue;
		}
	}
	//echo "<h2>Data Array Refined Step One: Remove ignored fields</h2>";
	//print_r($dataArrayRefinedStepOne);

	//STEP 3
	//go through the refined array of keys and values and apply the aliases, but only IFF we have aliases
	if ($aliasesProvided == 1) {
		foreach ($dataArrayRefinedStepOne as $htmlFieldName => $htmlFieldValue) {
			if (array_key_exists($htmlFieldName, $aliases)) {
				//YAY! This field has an alias! Replace the key with the alias
				$dataHolderKey = $aliases[$htmlFieldName];
				$dataArrayRefinedStepTwo["$dataHolderKey"] = $htmlFieldValue;
				$dataHolderKey = '';
			}
			else {
				//This field has no alias, add it to the second refinery
				$dataArrayRefinedStepTwo["$htmlFieldName"] = $htmlFieldValue;
			}
		}
	}
	//echo "<h2>Data Array Refined Step Two: Implement aliases for the keys</h2>";
	//print_r($dataArrayRefinedStepTwo);

	//STEP 4
	//Check if value is an array, if so, convert it into a string with a separator
	foreach ($dataArrayRefinedStepTwo as $htmlFieldName => $htmlFieldValue) {
		if (is_array($htmlFieldValue)) {
			$dataHolderValue = arrayToString($htmlFieldValue, $multiSelectSeparator);

			//check if the conversion was ok
			if ($dataHolderValue !== FALSE) {
				$dataArrayRefinedStepThree["$htmlFieldName"] = $dataHolderValue;
			}
			else {
				//@todo: Find a better way to deal with this error
				echo "<br/>Error converting array to string: <br/> ";
			}
			$dataHolderValue = '';
		}
		else {
			//nothing to do
			$dataArrayRefinedStepThree["$htmlFieldName"] = $htmlFieldValue;
		}
	}
	//echo "<h2>Data Array Refined Step Three: Multiselect fields to strings</h2>";
	//print_r($dataArrayRefinedStepThree);

	//STEP 5
	//go through $dataArrayRefinedStepThree and apply functions, if any, to the value
	if ($functionsToApplyProvided == 1) {
		//get each individual function to apply, using | as the separator
		$functionsArray = explode('|', $functionsToApply);

		//we need to loop in reverse order, e.g. if the string is strip_tags|trim, the result should be strip_tags(trim())
		$functionsArray = array_reverse($functionsArray);
		$j = count($functionsArray) - 1;

		foreach ($dataArrayRefinedStepThree as $htmlFieldName => $htmlFieldValue) {
			//loop thru the function names, checking if they exist either in PHP or userscope
			for ($i = 0; $i <= $j; $i++) {
		    	$functionName = $functionsArray[$i];
				//check if function is useable
				if (is_callable($functionName) ) {
					//function exists, run value through it
					$dataHolderValue = call_user_func("$functionName", $htmlFieldValue);

					//check if the function ran ok
					if ($dataHolderValue === FALSE) {
						//@todo: What to do if function returned an error?
						echo "<br/>Error in running user function: ".$functionName.'<br/> ';
					}
					else {
						//add it to the fourth refinery
						$dataArrayRefinedStepFour["$htmlFieldName"] = $dataHolderValue;
					}

					//reset the variable
					$dataHolderValue = '';
				}
				else {
					//none of the function names seem to exist anywhere, so just ignore them
					$dataArrayRefinedStepFour["$htmlFieldName"] = $htmlFieldValue;
				}
			}
		}
	}
	else {
		//no functions to apply, add the data to the fourth refinery as is
		$dataArrayRefinedStepFour = $dataArrayRefinedStepThree;
	}
	//echo "<h2>Data Array Refined Step Four: Apply user functions</h2>";
	//print_r($dataArrayRefinedStepFour);

	//STEP 6
	//check if there are any fields to be saved in a number column, if so, make sure NOT to append the single quotes
	if ($numericalTableColumnsProvided == 1) {
		foreach ($dataArrayRefinedStepFour as $htmlFieldName => $htmlFieldValue) {
			if (in_array($htmlFieldName, $numericalTableColumns)) {
				//This field is numerical, do not append the single quotes
				$dataArrayRefinedStepFive["$htmlFieldName"] = "$htmlFieldValue";
			}
			else {
				//This field is a string, append the single quotes
				$dataArrayRefinedStepFive["$htmlFieldName"] = "'$htmlFieldValue'";
			}
		}
	}
	else {
		//nothing to do, add data to the fifth refinery as is
		$dataArrayRefinedStepFive = $dataArrayRefinedStepFour;
	}
	//echo "<h2>Data Array Refined Step Five: Single quotes for strings</h2>";
	//print_r($dataArrayRefinedStepFive);

	//STEP 7
	//create the insert script
	foreach ($dataArrayRefinedStepFive as $databaseFieldName => $databaseFieldValue) {
		$sqlSnippet .= "$databaseFieldName=$databaseFieldValue, ";
	}

	//remove the trailing comma
	$sqlSnippet = substr($sqlSnippet, 0, (strlen(trim($sqlSnippet)))-1);

	return $sqlSnippet
}

/**
 * This function will take an array, loop through it and print it out as a string separated by the specified separator
 *
 * This might be useful for saving multiselect data into one field as a string
 *
 * @param string $arrayData	The array with the data
 * @param string $separator The separator, e.g. a comma or a bar or space
 * @return 	string $arrayDataString	The string data
 * @access 	public
*/
function arrayToString($arrayData, $separator)
{
	if (!is_array($arrayData)) {
		return false;
	}

	$arrayDataString = '';
	foreach ($arrayData as $value) {
		$arrayDataString .= $value.$separator;
	}

	//remove the trailing separator
	$arrayDataString = substr($arrayDataString, 0, (strlen(trim($arrayDataString)))-1);

	return $arrayDataString;
}