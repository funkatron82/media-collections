<?php header("Content-type: text/css; charset: UTF-8"); 
global $content_width;
?>
@charset "utf-8";
/* CSS Document */

#cedmc-toolbar .cedmc-icon {
	display: inline-block;
	width: 18px;
	height: 18px;
	vertical-align: text-top;
	margin: 0 2px;	
}

#cedmc-toolbar .button {
	padding-left: 5px;	
}

#cedmc-toolbar .primary-bar {
	float:left;	
}

#cedmc-toolbar .status {
	float: right;	
}

#cedmc-toolbar:after  {
	content: "";
	clear: both;
	display: block;	
}
#cedmc-main #cedmc-toolbar,
#cedmc-main #cedmc-preview {
	margin-top:10px;	
}

#cedmc-toolbar {
	padding: 2px;
	line-height:30px;
	vertical-align:middle;
}

#cedmc-toolbar .update,
#cedmc-toolbar select {
	vertical-align:middle;	
}

#cedmc-preview{
	min-width: 400px;
    max-width: <?php echo $content_width;  ?> px;
}
