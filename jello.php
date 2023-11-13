<?php
// trello2docjira
//
// note: turning off bold titles allows copy/paste in to JIRA easier

const OUTPUT_BOLD_TITLES = TRUE;
const OUTPUT_CSV = FALSE;
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
//usort($listCards, card_position_sort);

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
	 	if (OUTPUT_CSV == FALSE) {
		 	output_txt_title($output);
		}
	}

	if (sizeof($lists[$list_id]) == 0) {
		continue;
	}

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
	 	
 		if (OUTPUT_CSV == TRUE) {
			output_csv_card($output);
 		}
 		else {
	 		output_txt_card($output);
 		}
	 	 	
	}
	
}

// -------------------------------------------------------------

function output_txt_heading($output) {

	global $H2_USED;

	if (OUTPUT_BOLD_TITLES == TRUE) {
		echo '# '.$output['list_name'];
	}
	else {
		echo $output['list_name'];
	}
 	echo "\n\n";

 	$H2_USED = FALSE;

}

function output_txt_title($output) {

	global $H2_USED;

	if ($H2_USED) {
		echo '---------------';	
		echo "\n\n";
	}
	
	if (OUTPUT_BOLD_TITLES == TRUE) {
		echo '# ';
	}
	echo $output['list_name'];

 	echo "\n\n";

 	$H2_USED = TRUE;

}

function output_txt_card($output) {

	global $H2_USED;

	if (OUTPUT_BOLD_TITLES == TRUE) {
		if ($H2_USED) {
			echo '## ';
		}
		else {
			echo '## ';
		}
		echo $output['card_name'].PHP_EOL.PHP_EOL;
	}
	else {
		echo $output['card_name'].PHP_EOL.PHP_EOL;
	}

	if (sizeof($output['labels']) > 0) {
		$labels_text = '';
		foreach ($output['labels'] as $label) {
			// echo 'label:"'.$label['name'].'"';
			if ($label['name'] != '') {
				$labels_text .= $label['name'];
				if ($label != '' && ($label !== end($output['labels']))) {
					$labels_text .= ', ';
				}
			}
		}
		if ($labels_text != '') {
			echo '(_'.$labels_text.'_)'.PHP_EOL.PHP_EOL;
		}
	}	

	if (isset($output['card_desc']) && $output['card_desc'] != '') {
		// replace hyphen bullets with asterisks
		echo $output['card_desc'].PHP_EOL.PHP_EOL;
	}

	if (sizeof($output['attachments']) > 0) {
		$attachments_text = '';
		foreach ($output['attachments'] as $attachment) {
			// $attachments_text .= $attachment['url'];
			echo $attachment['url'].PHP_EOL;
		}
		echo PHP_EOL;
	}

	if (isset($output['card_estimate']) && $output['card_estimate'] != '') {
		echo "Estimate: ".$output['card_estimate'].PHP_EOL;
	}

}

// TODO: add labels?
function output_csv_card($output) {

	echo "\"".$output['list_name']."\",";
	echo "\"".$output['card_name']."\",";
	echo "\"";
	if ($output['card_desc'] <> '') {
		echo $output['card_desc'];			
	}
	echo "\",";	
	if ($output['card_estimate'] <> '') {
		echo $output['card_estimate'];
	}
	echo PHP_EOL;

}
?>
