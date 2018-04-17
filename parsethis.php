<?php
$stream = fopen('enwiki-20180401-pages-logging.xml', 'r');
$parser = xml_parser_create();
xml_set_character_data_handler($parser, "data");
xml_set_element_handler($parser,"start","stop");
$elementName = '';
$element = array();
$logTitles = array();
$elements = 0;

function start($parser,$element_name,$element_attrs) {
    global $elementName;
    $elementName = $element_name;
}

function data($parser, $xml_data) {
    global $element, $elementName, $logTitles, $elements;
    $element[$elementName] = $xml_data;
    if ( isset( $element['action'] ) && $element['action'] !== 'delete' ) {
        return;
    }
    $xml_data = trim( $xml_data );
    if ( $elementName === 'LOGTITLE' && $xml_data ) {
        #if ( !in_array( $xml_data, $logTitles ) ) {
            $logTitles[] = $xml_data;
            $element = array();
            $elementName = '';
            $elements++;
        #}
    }
}

function stop(){
    return;
}

$my_file = 'file.txt';
$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file); //implicitly creates file

// set up the handlers here
$lastElement = 0;
while (($data = fread($stream, 16384))) {
    xml_parse($parser, $data); // parse the current chunk
    if ( $elements > $lastElement + 100000 ) {
        echo "Elements: $elements\n";
        $lastElement = $elements;
    }
}
xml_parse($parser, '', true); // finalize parsing
xml_parser_free($parser);
echo "Sorting...\n";
sort( $logTitles );
echo "Removing duplicates...\n";
$logTitles = array_unique( $logTitles );
foreach ( $logTitles as $logTitle ) {
    fwrite($handle, $logTitle . "\n" );
}
fclose($stream);