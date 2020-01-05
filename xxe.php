#!/usr/bin/env php
<?php

/**
* Usage: php xxe.php <xml_file>
*/

/**
 * A custom external entity handler.
 *
 * @param resource $parser
 * @param string $openEntityNames
 * @param string $base
 * @param string $systemId
 * @param string $publicId 172.16.135.132
 * @return integer
 */
function externalEntityRefHandler(
    $parser,
    $openEntityNames,
    $base,
    $systemId,
    $publicId
) {
    global $externalEntities;

    if (!empty($systemId)) {
    	if (substr($systemId, 0, 4) === "file") {
    		$externalEntities[$openEntityNames] = @file_get_contents($systemId);
    	} else {
	        $ch = curl_init(); 

	        if (!$ch) {
	            die("Couldn't initialize a cURL handle"); 
	        }

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, $systemId);
			$contents = curl_exec($ch);
			$externalEntities[$openEntityNames] = $contents;
			curl_close($ch);
    	}
    }

    return (integer) (
        !empty($publicId)
        || !empty($externalEntities[$openEntityNames])
    );
}

// Get the arguments except the first one (the script name)
$arguments = array_slice($argv, 1);

// Get the name of the XML file
$xmlFileName = current($arguments);

// If no XML file name was provided, throw an error.
if (empty($xmlFileName)) {
    echo 'Please provide an XML file. Usage: php xxe.php xml_file_name' . PHP_EOL . PHP_EOL;
} else {
	// Parse the XML file
    $xml = file_get_contents($xmlFileName);
}

// Make sure we can fetch external entities.
libxml_disable_entity_loader(false);

// Create a new xml parser.
$parser = xml_parser_create('UTF-8');

// Set a custom entity handler.
xml_set_external_entity_ref_handler($parser, "externalEntityRefHandler");

// A list of gathered external entities and their contents.
$externalEntities = array();

// Parse the XML.
if (xml_parse($parser, $xml, true) === 1) {
    // Success.
    echo 'These are the results of your XML attack:' . PHP_EOL . PHP_EOL;

    var_dump($externalEntities);
} else {
    echo 'The XXE attack did not work. Try again!' . PHP_EOL;
}

// Free the parser.
xml_parser_free($parser);
