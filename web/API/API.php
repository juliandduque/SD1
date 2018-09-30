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
    // Get the username and password from the JSON payload
    $datetime = ($jsonPayload['time']);
    $frequency = $jsonPayload['frequency'];
    $deviceID = $jsonPayload['deviceID'];

		file_put_contents("php://stderr", $deviceID.PHP_EOL);

    // Check for various error-inducing situations
    if (strlen($datetime) > 45) {
        returnError('Time cannot exceed 45 characters.');
    } else if (strlen($datetime) <= 0) {
        returnError('Time cannot be empty.');
    } else if (strlen($deviceID) > 45) {
        returnError('deviceID cannot exceed 45 characterrs.');
    } else if (strlen(frequency) <= 0) {
        returnError('frequency cannot be empty.');
    } else if (strlen($deviceID) <= 0) {
        returnError('deviceID cannot be empty.');
    } else {
  // This block uses prepared statements and parameterized queries to protect against SQL injection
        // MySQL query to add the username and password into the database
        $query = $dbConnection->prepare("INSERT INTO data (datetime, Frequency, deviceID, key) VALUES ('?', '?', '?', '?')");
        $query->bind_param('siss', $datetime, $frequency, $deviceID, $datetime.$frequency.$deviceID);
        $query->execute();
		
        // Result from the query
        $result = $query->get_result();

        // Check to see if the insertion was successful...
        if ($result) {
            // If successful, return JSON success response
            returnSuccess('Record created.');
        } else {
            // If not successful, return JSON error response
            returnError($dbConnection->error);
        }
    }
}
