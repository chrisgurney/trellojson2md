<?php
// trello2docjira
//
// note: turning off bold titles allows copy/paste in to JIRA easier

const OUTPUT_LIST_FILES = TRUE;
const OUTPUT_CARD_FILES = FALSE;
const OUTPUT_PATH = './output/';

const FORMAT_BOLD_TITLES = FALSE;
const FORMAT_AS_CSV = FALSE;
const SKIP_HYPHENS = FALSE;

$H2_USED = FALSE;
$OUTPUT_FILE;

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

	if (OUTPUT_LIST_FILES) {
		$list_filename = get_filename($output['list_name']);
		$list_filepath = OUTPUT_PATH.$list_filename.'.md';

		echo '- [['.$list_filename.']]'.PHP_EOL;

		if (isset($OUTPUT_FILE) && is_resource($OUTPUT_FILE)) {
			fclose($OUTPUT_FILE);
		}
		$OUTPUT_FILE = fopen($list_filepath, "w") or die("Unable to open file: ".$list_filepath);		
	}
	else {
		// treat list names that begin with * as heading 1
		// TODO: think of less hacky way of doing this
		if (strpos($list_name, '*') === 0) {
			$output['list_name'] = substr($list_name, 1);
			echo format_txt_heading($output);
		}
		else {
			if (FORMAT_AS_CSV == FALSE) {
				echo format_txt_title($output);
			}
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
				$txt_card = format_txt_card($output);
				if (OUTPUT_CARD_FILES) {
					$card_filename = get_filename($output['card_name']);
					$card_filepath = OUTPUT_PATH.$card_filename.'.md';
			
					if ($card_filename == '---') {
						continue;
					}
			
					echo '- [['.$card_filename.']]'.PHP_EOL;
			
					$OUTPUT_FILE = fopen($card_filepath, "w") or die("Unable to open file: ".$card_filepath);
					fwrite($OUTPUT_FILE, $txt_card);
					fclose($OUTPUT_FILE);
				}
				else if (OUTPUT_LIST_FILES) {
					fwrite($OUTPUT_FILE, $txt_card);
					fwrite($OUTPUT_FILE, '---'.PHP_EOL.PHP_EOL);
				}
				else {
					$txt_card .= '---'.PHP_EOL.PHP_EOL;
					echo $txt_card;
				}
			 }
				  
		}

	}
	
	if (OUTPUT_LIST_FILES) {
		fclose($OUTPUT_FILE);
	}
	
}

// -------------------------------------------------------------

function format_txt_heading($output) {

	global $H2_USED;

	$txt_output = '';

	if (FORMAT_BOLD_TITLES == TRUE) {
		$txt_output .= '# ';
	}
	$txt_output .= $output['list_name'].PHP_EOL.PHP_EOL;

 	$H2_USED = FALSE;

	return $txt_output;

}

function format_txt_title($output) {

	global $H2_USED;

	$txt_output = '';

	if ($H2_USED) {
		$txt_output .= PHP_EOL.'---'.PHP_EOL.PHP_EOL;
	}

	if (FORMAT_BOLD_TITLES == TRUE) {
		$txt_output .= '# ';
	}
	$txt_output .= $output['list_name'].PHP_EOL.PHP_EOL;

 	$H2_USED = TRUE;

	return $txt_output;

}

function format_txt_card($output) {

	$txt_card_output = '';

	if (FORMAT_BOLD_TITLES == TRUE) {
		$txt_card_output .= '## ';
	}

	// output URLs if they're used as card titles, so we don't lose them when we re-format the filename
	if (!OUTPUT_CARD_FILES || str_starts_with($output['card_name'], 'http')) {
		$txt_card_output .= $output['card_name'].PHP_EOL.PHP_EOL;
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

	return $txt_card_output;

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

function get_filename($string) {
	$card_filename = $string;
	$card_filename = str_replace(array('\\','/','#',':','*','?','"','<','>','|','https','http'),' ',$card_filename);
	$card_filename = str_replace('[','(',$card_filename);
	$card_filename = str_replace(']',')',$card_filename);
	$card_filename = preg_replace('!\s+!', ' ', $card_filename);
	$card_filename = trim($card_filename);
	$card_filename = rtrim($card_filename,'.');
	return $card_filename;
}
?>