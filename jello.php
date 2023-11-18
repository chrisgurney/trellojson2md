<?php
// trello2docjira
//
// note: turning off bold titles allows copy/paste in to JIRA easier

const OUTPUT_TO_FILES = TRUE;
const OUTPUT_PATH = './output/';

const FORMAT_BOLD_TITLES = TRUE;
const FORMAT_AS_CSV = FALSE;
const SKIP_HYPHENS = FALSE;

$H2_USED = FALSE;

//
// read JSON file
// 

$json_string = file_get_contents($argv[1]);
$json = json_decode($json_string, TRUE);

//
// get the IDs for all lists
//

$lists_json = $json['lists'];
$list_names = array();
foreach($lists_json as $list_json) {
	if ($list_json['closed'] <> 1) {
		$list_names[$list_json['id']] = $list_json['name'];
	}
}

// DEBUG
// print_r($list_names);

//
// get all the cards for each list
// TODO: skip cards in closed lists
//

$cards_json = $json['cards'];
$lists = array();
foreach ($cards_json as $card) {
	if ($card['closed'] <> 1) {
		if (!isset($lists[$card['idList']])) {
			$lists[$card['idList']] = array();
		}
		array_push($lists[$card['idList']], $card);
	}
}

// DEBUG
// print_r($lists);
// exit;

//TODO: double-check docs to see if sorting is necessary
// usort($listCards, card_position_sort);

$output = array();
foreach ($list_names as $list_id => $list_name) {

 	$output['list_name'] = $list_name;

	// skip list names that begin with a hyphen
	if (($list_name == "") || 
		  (SKIP_HYPHENS && (strpos($list_name, '-') === 0))) {
		// DEBUG
		//echo "Skipping ".$list_name."...\n";
		continue;
	};

	// DEBUG
	// echo $list_name."\n\n";
	// continue;

	// treat list names that begin with * as heading 1
	// TODO: think of less hacky way of doing this
	if (strpos($list_name, '*') === 0) {
		$output['list_name'] = substr($list_name, 1);
		output_txt_heading($output);
	}
	else {
		if (FORMAT_AS_CSV == FALSE) {
			output_txt_title($output);
		}
	}

	if (isset($lists[$list_id])) {

		foreach ($lists[$list_id] as $card) {

			$output = array();
			$output['list_name'] = $list_names[$list_id];		
			$output['card_name'] = $card['name'];
			$output['card_desc'] = $card['desc'];
			$output['labels'] = $card['labels'];
			$output['attachments'] = $card['attachments'];
			 if (isset($card['pluginData'][0]['value'])) {
				$card_fields = json_decode($card['pluginData'][0]['value'], TRUE);
				$output['card_estimate'] = $card_fields['fields']['u6g4FEpY-jHCRmY'];
			 }
			 
			 if (FORMAT_AS_CSV == TRUE) {
				output_csv_card($output);
			 }
			 else {
				output_txt_card($output);
			 }
				  
		}

	} 
	
}

// -------------------------------------------------------------

function output_txt_heading($output) {

	global $H2_USED;

	if (FORMAT_BOLD_TITLES == TRUE) {
		echo '# '.$output['list_name'];
	}
	else {
		echo $output['list_name'];
	}
 	echo PHP_EOL.PHP_EOL;

 	$H2_USED = FALSE;

}

function output_txt_title($output) {

	global $H2_USED;

	if ($H2_USED) {
		echo PHP_EOL.'---'.PHP_EOL.PHP_EOL;
	}
	
	if (FORMAT_BOLD_TITLES == TRUE) {
		echo '# ';
	}
	echo $output['list_name'];

 	echo PHP_EOL.PHP_EOL;

 	$H2_USED = TRUE;

}

function output_txt_card($output) {

	global $H2_USED;

	$txt_card_output = '';

	if (FORMAT_BOLD_TITLES == TRUE) {
		$txt_card_output .= '## ';
	}

	if (!OUTPUT_TO_FILES) {
		echo $output['card_name'].PHP_EOL.PHP_EOL;
	}

	if (sizeof($output['labels']) > 0) {
		$labels_text = '';
		foreach ($output['labels'] as $label) {
			if ($label['name'] != '') {
				$labels_text .= '#'.strtolower($label['name']);
				if ($label != '' && ($label !== end($output['labels']))) {
					$labels_text .= ' ';
				}
			}
		}
		if ($labels_text != '') {
			$txt_card_output .= $labels_text.PHP_EOL.PHP_EOL;
		}
	}	

	if (isset($output['card_desc']) && $output['card_desc'] != '') {
		// replace hyphen bullets with asterisks
		$txt_card_output .= $output['card_desc'].PHP_EOL.PHP_EOL;
	}

	if (sizeof($output['attachments']) > 0) {
		$attachments_text = '';
		foreach ($output['attachments'] as $attachment) {
			// $attachments_text .= $attachment['url'];
			$txt_card_output .= $attachment['url'].PHP_EOL;
		}
		$txt_card_output .= PHP_EOL;
	}

	if (isset($output['card_estimate']) && $output['card_estimate'] != '') {
		$txt_card_output .= "Estimate: ".$output['card_estimate'].PHP_EOL;
	}

	if (OUTPUT_TO_FILES) {
		$card_filename = $output['card_name'];
		$card_filename = str_replace(array('\\','/',':','*','?','"','<','>','|','https','http'),' ',$card_filename);
		$card_filename = preg_replace('!\s+!', ' ', $card_filename);
		$card_filename = trim($card_filename);
		$card_filename = rtrim($card_filename,'.');
		$card_filepath = OUTPUT_PATH.$card_filename.'.md';

		if ($card_filename == '---') {
			return;
		}

		echo '[['.$card_filename.']]'.PHP_EOL;

		$card_file = fopen($card_filepath, "w") or die("Unable to open file: ".$card_filepath);
		fwrite($card_file, $txt_card_output);
		fclose($card_file);
	}
	else {
		$txt_card_output .= '---'.PHP_EOL.PHP_EOL;
		echo $txt_card_output;
	}

}

// TODO: add labels?
function output_csv_card($output) {

	$csv_card_output = '';

	$csv_card_output .= "\"".$output['list_name']."\",";
	$csv_card_output .= "\"".$output['card_name']."\",";
	$csv_card_output .= "\"";
	if ($output['card_desc'] <> '') {
		$csv_card_output .= $output['card_desc'];			
	}
	$csv_card_output .= "\",";	
	if (isset($output['card_estimate']) && $output['card_estimate'] != '') {
		$csv_card_output .= $output['card_estimate'];
	}
	$csv_card_output .= PHP_EOL;

	echo $csv_card_output;

}
?>
