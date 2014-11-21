<?php
/*
Email-Ready - Style Inliner and Code Conditions
for HTML emails.
by Mark Coleman

*/
##########################################
# This is my custom CSS parsing function #
##########################################

function parseCSS($CSS) {
	# Create single string with classes and styles on alternating lines
	# Strip out CSS comments, extra spaces, curly brackets, other extraneous stuff
	$allStyles = preg_replace(array('!/\*[^*]*\*+([^/][^*]*\*+)*/!','/<!--/','/-->/','/^\s+/','/\s\s+/','/\t/','/{/','/}/','/\n /','/,\s+/'),array('','','','',' ','',"\n","\n","\n",","),$CSS);

	# break string created above into an array, by line
	# filter out empty 
	$tt = explode("\n",$allStyles);
	
	# Organize classes and styles into multidimentional array
	$items = array();
	$allItems = array();
	$j=0;
	for ($i=0; $i<count($tt); $i+=2) {
		$j = ($i+1)/2;			
		$items[$j] =  array('labels' => trim($tt[$i]," "),'styles' => trim($tt[$i+1]," "));
		if ($tt[$i] == "" ) unset($items[$j]);
		$j = $j+1;
	}
	foreach($items as $test) {
		$exploded = explode(",",$test[labels]);
		for($i=0; $i<count($exploded); $i++) {
			$new = array('labels' => $exploded[$i], 'styles' => $test[styles]);
			array_push($allItems,$new);
		}
	}	
	# Further break compound style labels into arrays
	for ($i=0; $i<count($allItems); $i++) {
		$explStyles = explode(' ',$allItems[$i][labels]);
		$allItems[$i][labels] = $explStyles;
	}
	# Call another function to sort array properly for inlining function
	usort($allItems, "sortStyleArray");
	
	# Makes a global variable to contain the CSS array  
	global $styleArray;
	$styleArray = $allItems;
	return($styleArray);
}



##########################################
#      Style array sorting function      #
##########################################
# Helper function that works with usort() in the parseCSS() function above to sort the style array. The style array needs to be sorted so that the styles with the most labels (classes, IDs, tags) are first in the array. This order is important for proper inlining.

function sortStyleArray($a, $b) {
	return count($a["labels"]) < count($b["labels"]);
}



###############################################
#####  Width/Height Styles to Attributes  #####
###############################################
function styleToAttr($code,$attr) {
	$start = $attr.":"; //echo $start.'<br />';
	foreach ($code->find('img[style]') as $element) {
		if($element->$attr == false) {
			$style = $element->style;
			if(stristr($style,$start)) { 
				$init = strpos($style,$start); //echo $ini.'<br>'; 
				$init += strlen($start); //echo $ini.'<br>';
				$len = strpos($style,";",$init) - $init; //echo $len.'<br>';
		   		$attval = trim(substr($style,$init,$len)); //echo $all.'<br>';
			}
		$element->$attr = $attval;
		}
	}
return $html;
}



###############################################
#####    Shorthand to Longhand Margins    #####
###############################################
# Function is working, but not currently implemented in the processor, because it's not clear it's needed. May serve as a basis for exploding and longhanding shorthanded font: styles, which may be useful.
/*
function styleExplode($code,$var) {
	//echo $var;
	foreach ($code->find('[style]') as $element) {
		$start = $var.":"; $end = ";";
		$fullStyles = $element->style; 
		//echo $fullStyles.' TESTTEXT<br />';
		if(stristr($fullStyles,$start)) { 
			$ini = strpos($fullStyles,$start); //echo $ini; 
			$ini += strlen($start);
			$len = strpos($fullStyles,$end,$ini) - $ini;
	    	$all = substr($fullStyles,$ini,$len);
			//echo $all." ALL<br />";
			$oldSty = $var.":".$all;
			//echo $oldSty." Old Margin<br />";
			$allTrim = trim($all," ");
			//echo $allTrim." ALL-Trimmed<br />";
			$margins = explode(" ",$all); //print_r($margins); echo count($margins).'<br />';
			if(count($margins) == 2) { 
				$newSty = $var.'-top: '.$margins[1].';'.$var.'-right: '.$margins[1].';'.$var.'-bottom: '.$margins[1].';'.$var.'-left: '.$margins[1];
				//echo $newMarg.'<br />';
			}
			if(count($margins) == 3) { 
				$newSty = $var.'-top: '.$margins[1].';'.$var.'-right: '.$margins[2].';'.$var.'-bottom: '.$margins[1].';'.$var.'-left: '.$margins[2];
				//echo $newMarg.'<br />';
			}
			if(count($margins) == 4) { 
				$newSty = $var.'-top: '.$margins[1].';'.$var.'-right: '.$margins[2].';'.$var.'-bottom: '.$margins[3];
				//echo $newMarg.'<br />';
			}
			if(count($margins) == 5) { 
				$newSty = $var.'-top: '.$margins[1].';'.$var.'-right: '.$margins[2].';'.$var.'-bottom: '.$margins[3].';'.$var.'-left: '.$margins[4];
				//echo $newMarg.'<br />';
			}
			else { break 0; }
			$update = str_replace($oldSty,$newSty,$fullStyles);
			$element->style = $update;
		}
	}
return $html;
}
*/


##########################################
#        Image blocking function         #
##########################################
# This function searches for img tags, and then checks the styles
# If display:inline is set, it changes it to display:block
# If display:block is set, it does nothing
# If a style attribute is present, but display:block is not set, it added it to the style
# If no style attribute is present in the img tag, it adds the style attribute, including display:block

function imgDisplayBlock($code) {
	/*# Finds TD elements with only img tags in them, or with img tags nested in a tags, and sets line-height: 0; in td inline style
	foreach ($code->find('td') as $element) {
		if(count($element->children()) == 1 && $element->first_child()->tag == 'img') {
			$element->style .= 'line-height: 0;';
		}
		if(count($element->children()) == 1 && $element->first_child()->tag == 'a' && $element->first_child()->first_child()->tag == 'img') {
			$element->style .= 'line-height: 0;';
		}
		
		# EXPERIMENTAL Finds where nested td p img where the p element includes the img, but no additional text, grabs the nested img element out of p element, then replaces p element with the formerly nested img element
		if(count($element->children()) == 1 && $element->first_child()->tag == 'p' && $element->first_child()->first_child()->tag == 'img') {
			if(trim(implode($element->first_child()->find('text'))) == "") { 
				$stuff = $element->first_child()->first_child();
				$element->first_child()->outertext = "";
				$element->innertext = $stuff;
			}
			$element->style .= 'line-height: 0;';
		} 
		else {}
	}*/				
	foreach ($code->find('td img') as $element) {
		if (isset($element->style)) {
			if (strpos($element,'display:inline') || strpos($element,'display: inline')) {
				$old = $element->style; $element->style = str_replace('inline','block',$old);}
			if (strpos($element,'display:block') or strpos($element,'display: block')) { }
			elseif(strpos($element,';"') or strpos($element,'; "')) { $element->style .= 'display: block;'; }
			elseif($element->style == "" or $element->style == " ") { $element->style .= 'display: block;'; }
			else { $element->style .= ';display: block;'; }
		}
		else {  $element->style .= "display: block;"; }
	}
return $html;
}



##########################################
#        Img border zero function        #
##########################################

function aImgBorderZero($code) {
	foreach ($code->find('td a img') as $element) {
		$element->border = '0';
	}
return $html;
}
	
	
	
##########################################
#         Tag closing function           #
##########################################

function closeSingleTags($code) {
	foreach($code->find('br') as $br) {
		$brsub = substr(trim($br->outertext),-2);
		if($brsub == " >") {
			$br->outertext = preg_replace('/ >/'," />",$br);
		}
		elseif($brsub != "/>") {	
			$br->outertext = preg_replace("(>)"," />",$br);
		}
		else {
			$br->outertext = preg_replace('("/>)','" />',$br);
		}
	}

	foreach($code->find('hr') as $hr) {
		$hrsub = substr(trim($hr->outertext),-2);
		if($hrsub == " >") {
			$hr->outertext = preg_replace('/ >/'," />",$hr);
		}
		elseif($hrsub != "/>") {	
			$hr->outertext = preg_replace("(>)"," />",$hr);
		}
		else {
			$hr->outertext = preg_replace('("/>)','" />',$hr);
		}
	}

	foreach($code->find('img') as $img) {
		$imgsub = substr(trim($img->outertext),-2);
		if($imgsub == " >") {
			$img->outertext = preg_replace('/ >/'," />",$img);
		}
		elseif($imgsub != "/>") {	
			$img->outertext = preg_replace("(>)"," />",$img);
		}
		else { 
			$img->outertext = preg_replace('("/>)','" />',$img);
		}
	}
return $html;
}

?>