<?php
# HTML Code Processor for improving compatbility of custom-coded HTML emails with email client software. Uses Simple HTML DOM Parser, an open-source shell to accessing PHP DOM capabilities for basic parsing, and my own functions for doing the necessary stripping and further parsing, and inlining of styles, etc.    .................by Mark Coleman, 2011
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>

	<head>
		<title>HTML Code Processor - Results</title>
	</head>
	<body style="background-color: #000000;">
<table style="margin: 0 auto;"><tbody><tr><td>
<h1 style="font-family: Arial;text-align: center;color: #00ffff;margin-bottom: 0px;">EMAIL-READY</h1>
<h3 style="font-family: Arial;text-align: center;color: #00ffff;margin-top: 0px;">CSS style inliner and Code Conditioner</h3>

<?php

# include simple HTML DOM library
include('simple_html_dom.php');

# include custom functions library by Mark Coleman
include('functions.php');

# Get posted form data and parse it into a multidimensional array using PHP Simple HTML DOM 
$select = $_POST['select'];
$theCSS = stripcslashes($_POST['inputcss']);

$theCode = stripcslashes($_POST['inputcode']);
$html = str_get_html($theCode);

# Makes sure before going any further than all styles already inline have a semicolon after the last style defined.
foreach($html->find('[style]') as $element) {
	$sty = substr(trim($element->style," "), -1);
	if($sty != ";") { 
		$element->style = trim($element->style," ").';';
	}
}


###############################################
#####        Output Original Code         #####
###############################################
# Re-displays original code in a readonly textarea
echo '<h5 style="font-family: Arial;color: #ff7f00;text-align:center;margin:0;">Original Code (for reference)</h5>';
echo '<textarea style="border: 3px #282828 solid;background-color: #F5E6E6;padding:10px;font-size:10pt;" readonly cols="100" rows="4" >'.$theCode.'</textarea><br />';


# The goal here is to remove any HTML comment strings from the CSS. Dreamweaver likes them before the first and last styles for compatibility with ANCIENT web browsers, but they now do little
$html->find('style', 0)->innertext = preg_replace(array('/<!--/','/-->/'),array("\n","\n"),$html->find('style', 0)->innertext);

# If no selections were made on input form, slap the user, else go do what they asked
if(empty($select))  {
	echo '<div style="color: #ff7f00;text-align: center;font-size: 16pt;">Sorry, no options were selected on the input form.<br />Only the original code will be displayed.<br />Please go back and <a href="index.html" onClick="window.history.go(-1)" style="color:#00ffff;">try again</a>.</div><br />'; 
}
else { 

$patt = array();
$repl = array();


###############################################
##### Start of inliner and header cleanup #####
###############################################

	if($select[0] == "yes") { 
		# This extracts any styles enclosed in <style> tags in the original code
		if(trim($theCSS) != "") {
			$element = $theCSS;
		}
		else { 
		$element = $html->find('style',0)->innertext; 
		}
		
		
		# This sends the styles to my custom CSS Parsing function in the functions.php file
		parseCSS($element);

		# This reads the styles from the style array
		for($i=0; $i<count($styleArray); $i++) {
			$styleLabel = $styleArray[$i][labels][0].' '.$styleArray[$i][labels][1].' '.$styleArray[$i][labels][2];
			$theStyles = $styleArray[$i][styles];
				foreach ($html->find($styleLabel) as $e) {
					$e->style .= $theStyles;
					unset($e->class);
					unset($e->id);
				}
		}

	# Removes style section and previous links to external style sheets from the HTML DOM
	foreach($html->find('link[rel=stylesheet]') as $e) { $e->outertext = ""; }
	$html->find('style', 0)->innertext = '';
	$html->find('style', 0)->outertext = '';
	}

##############################################
#####      Image "Blocker" Section       #####
##############################################
# If the "block-style images" option was selected on input form, calls imgDisplayBlock in functions.php. This function makes sure "display:block" style is in every img tag. This function needs to come after the inline style, or the inline style function

if($select[1] == "yes") { 
imgDisplayBlock($html);

/*  Possible addition of warning to display when multiple imates in some td tags - not reliable in testing. sometimes displayed and marked tr elements... So, commented out for now.
echo '<div style="color: #ff0000;"><br />';
foreach($html->find('td') as $td) {
	$imgct = count($td->find('img'));
		if($imgct >> 1) { 
			$td->outertext = $td->outertext." ... ATTENTION ATTENTION\n"; 
			echo 'WARNING: display:block fix may not fix all vertical spacing issues for this code, because there are multiple images in one or more<br />td elements. " ... ATTENTION ATTENTION" has been appended to the affected td element/s below. Remove the ATTENTION<br />markers before testing, and if the images in this td display improperly, either join these images into one image or place each into a<br />separate td element and adjust colspan attributes in td elements of other rows as needed to fix remaining vertical spacing issues. <br /><br />';
			$textcolor = "#ff0000";
		}
	}
echo '</div>'; 
*/
}
aImgBorderZero($html);

###############################################
#####  Width/Height Styles to Attributes  #####
###############################################
# If selected, these functions must be done before the TD width from child IMG Functions, which will not otherwise find width and height attributes for images if they are specified only in the inline style. 
if($select[9] == "yes") {
	styleToAttr($html,"width"); $html->save();
	styleToAttr($html,"height"); $html->save();
}

###############################################
#####   TD width from child IMG Section   #####
###############################################
# Placeholder for code of function call to add width attributes from child img tags to parent td tags.

if($select[2] == "yes") { 
	foreach($html->find('td') as $td) {
		foreach($td->find('img') as $img) {
			$w = $img->width;
			$td->width = $w; // += would sum the widths, but may not often be useful
			$h = $img->height;
			$td->height = $h; // += would sum the heights, but may not often be useful
		}
	}
}

###############################################
#####   Set table padding/spacing to 0    #####
###############################################
# Adds zero border, cellpadding, cellspacing to any table tags in which these attributes are not already set

if($select[3] == "yes") {
	foreach($html->find('table') as $table) {
		if(isset($table->cellpadding)) { }
		else { $table->cellpadding = "0"; }
		if(isset($table->cellspacing)) { }
		else { $table->cellspacing = "0"; }
		if(isset($table->border)) { }
		else { $table->border = "0"; }
	}
} 

###############################################
#####       Strip Comments Section        #####
###############################################

# Strip HTML comments
if($select[4] == "yes") { 
	foreach($html->find('comment') as $es) {
		if(strpos($es->innertext,'<!--[if') === false) { 
			$es->outertext = ''; 
		} 
	}
}

# Strip conditional comments
if($select[5] == "yes") { 
	foreach($html->find('comment') as $es) {
		if(strpos($es->innertext,'<!--[if') !== false) { 
			$es->outertext = ''; 
		}
	} 
}

# Strip CSS comments
if($select[6] == "yes") { 
	$patt = array('!/\*[^*]*\*+([^/][^*]*\*+)*/!','/\n\n/');
	$repl = array("","\n");
$element = $html->find('style', 0);
$element = preg_replace($patt,$repl,$element);
}

# Strip !important
if($select[7] == "yes") { 
	$patt = array('/ !important /','/ ! important /','/ !important/','/ ! important/');
	$repl = array("","","","");
$element = $html->find('style', 0);
$element = preg_replace($patt,$repl,$element);
}

# Strip out empty and obsolete spans
if($select[8] == "yes") {
	foreach($html->find('span') as $span) {
		if($span->innertext == "") {
			$span->outertext = "";
		}
		else {
			if(isset($span->style)) { }
			else { $span->outertext = $span->innertext;
			}
		}
	}
}

###############################################
#####  Width/Height Styles to Attributes  #####
###############################################
if($select[9] == "yes") {
	styleToAttr($html,"width"); $html->save();
	styleToAttr($html,"height"); $html->save();
}

###############################################
#####   Close br, hr, img tags (XHTML)    #####
###############################################
# This function appears to work reliably with other functions that manipulate image tags, only when it is run after them. The method used to add the closing slash to the tag may somehow affect traversion of elements in the DOM array.
if($select[10] == "yes") {
	closeSingleTags($html);
	$html->save();
}

###############################################
#####    Shorthand to Longhand Margins    #####
###############################################

// styleExplode($html,"margin");
// styleExplode($html,"padding");


###############################################
#####    Save Changes to PHP HTML DOM     #####
###############################################
$html->save();

###############################################
#####       Output Processed Code         #####
###############################################
# Displays the Processed Code, add some linefeeds for readability, removed all html comments. The iff statement here simply prevents display out output code if ONLY the Widths option is selected
$patt = array_merge(array('/\s\s+/'),$patt);
$repl = array_merge(array("\n"),$repl);
$theOutput = preg_replace($patt,$repl,$html);

} #end of statements when $select is not empty
?>
<!-- <form action="preview.php" method="post"> --><div style="text-align:center;margin:4px;"><button onclick="javascript:history.go(-1)">Go BACK</button></div>
<br /><h5 style="font-family: Arial; color: #00ff00;text-align:center;margin: 0;">Processed HTML Code Output  <!-- <input type="submit" value="PREVIEW IN BROWSER" /> --></h5>
<textarea style="color:<?php echo $textcolor; ?>;border: 3px #282828 solid;background-color: #E6F5E7;padding: 10px;font-size: 10pt;" readonly cols="100" rows="50" name="outputcode"><?php echo $theOutput; ?></textarea>
<!-- </form> -->
<br /><br /><br />
<br />
<div style="text-align: center;font-size: 8pt; color: #999999;">HTML Code Processor - by Mark Coleman</div>
</td></tr></tbody></table>
<?php

?>
</body>
</html>