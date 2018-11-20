<?php

// Add file with connection-related functions
require 'Connection.php';

// Receive decoded JSON payload from client
$jsonPayload = getJSONPayload();

file_put_contents("php://stderr", "starting connection with database !!!".PHP_EOL);
// Establish a connection to the database
$dbConnection = establishConnection();

file_put_contents("php://stderr", "connection with database established!!!".PHP_EOL);

file_put_contents("php://stderr", "starting function:". $jsonPayload['function'].PHP_EOL);

// Call the client-requested function
callVariableFunction($dbConnection, $jsonPayload);

/* *************** */
/* Functions Below */
/* *************** */

/**
 * Call a variable function passed as a string from the client-side
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 */
function callVariableFunction($dbConnection, $jsonPayload)
{
    // Get function name (as string) from the JSON payload
    $function = $jsonPayload['function'];

    // Ensure that the function exists and is callable
    if (is_callable($function)) {
        // Use the JSON payload 'function' string field to call a PHP function
        $function($dbConnection, $jsonPayload);
    } else {
        // If the function is not callable, return a JSON error response
        returnError('JSON payload tried to call undefined PHP function ' . $function . '()');
    }
}



/**
 * Create a new record
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param object $jsonPayload Decoded JSON stdClass object
 */
function createRecord($dbConnection, $jsonPayload)
{
	$array = $jsonPayload['array'];
	$query;

	foreach($array as $value)
	{
		//file_put_contents("php://stderr", "Frequency being added:".$value['frequency'].PHP_EOL);
		// Get the username and password from the JSON payload
		$frequency = $value['frequency'];
		$deviceID = $value['deviceID'];
		$strength = $value['strength'];
		$time = date("Y-m-d h:i:sa");

		// Check for various error-inducing situations
		if (strlen($deviceID) > 45) {
			returnError('deviceID cannot exceed 45 characterrs.');
		} else if (strlen($frequency) <= 0) {
			returnError('frequency cannot be empty.');
		} else if (strlen($deviceID) <= 0) {
			returnError('deviceID cannot be empty.');
		} else {
			// This block uses prepared statements and parameterized queries to protect against SQL injection
			// MySQL query to add the username and password into the database
			$query = $dbConnection->prepare("INSERT INTO `chatterboxDB`.`data` (`datetime`, `Frequency`, `deviceID`, `strength`) VALUES ('".$time."', '".$frequency."', '".$deviceID."', '".$strength."')");
			$query->execute();    
		
			 // Result from the query
			$result = $query->get_result();

			file_put_contents("php://stderr", "Record created Frequency:".$frequency." Strength:".$strength." deviceID:".$deviceID.PHP_EOL);
		}
	}
	returnSuccess('Records created');
}

/**
 * Get the most relevant posts for a particular user (based on tag likes)
 *
 * @json Payload : 
 * @json Response: (multiple) frequency, strength, deviceID
 *
 * @param mysqli $dbConnection MySQL connection instance
 * @param array $jsonPayload Decoded JSON object
 */
function getLatestRecords($dbConnection, $jsonPayload)
{
	$frequency = $jsonPayload['Frequency'];
    /*$statement = "SELECT 'datetime', 'Frequency', 'deviceID', 'strength'
				  FROM 'chatterboxDB'.'data' 'D1'
					WHERE 'datetime' = (SELECT MAX('datetime') FROM 'chatterboxDB'.'data' 'D2' WHERE 'D1'.'deviceID' = 'D2'.'deviceID')
					GROUP BY 'deviceID'";*/

	$statement = "SELECT * 
				  FROM (SELECT * FROM chatterboxDB.data WHERE Frequency = ".$frequency. ") A
			      INNER JOIN (SELECT max(datetime) mv, deviceID 
								FROM (SELECT * FROM chatterboxDB.data WHERE Frequency = ".$frequency. ") C
								GROUP BY deviceID) B
				  ON A.deviceID= B.deviceID
				  AND A.datetime = B.mv;";
    
    $query = $dbConnection->prepare($statement);
    $query->execute();
    $result = $query->get_result();
    $query->close();
    // Verify post(s) were found
    if ($result->num_rows <= 0) {
        returnError('No posts found: ' . $dbConnection->error);
    }
    $postResults = [];
    // NOTE: $userID is the ID of the actual user fetching the posts
    //       $row['userID'] is the ID of each user that created the post(s) being fetched
    while ($row = $result->fetch_assoc()) {
        $recordInformation = [
            'Frequency'   => $row['Frequency'],
            'strength'   => $row['strength'],
            'deviceID' => $row['deviceID']
        ];
        $postResults[] = $recordInformation;
    }
    returnSuccess('Post(s) found.', $postResults);
}

function getFrequencies($dbConnection, $jsonPayload)
{

	$statement = "SELECT DISTINCT Frequency
					FROM chatterboxDB.data D1
					order by Frequency;";
    
    $query = $dbConnection->prepare($statement);
    $query->execute();
    $result = $query->get_result();
    $query->close();
    // Verify post(s) were found
    if ($result->num_rows <= 0) {
        returnError('No posts found: ' . $dbConnection->error);
    }
    $postResults = [];
    // NOTE: $userID is the ID of the actual user fetching the posts
    //       $row['userID'] is the ID of each user that created the post(s) being fetched
    while ($row = $result->fetch_assoc()) {
        $recordInformation = [
            'Frequency'   => $row['Frequency'],
        ];
        $postResults[] = $recordInformation;
    }
    returnSuccess('Post(s) found.', $postResults);
}