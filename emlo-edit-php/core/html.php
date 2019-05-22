<?php
/*
 * PHP class for writing HTML
 * Note that this version overrides the 'common' version from the 'Aeolus' subversion repository 
 * with a version specific to EMLO/Cultures of Knowledge and IMPAcT.
 * Author: Sushila Burgess
 *
 */

define( 'NEWLINE', "\n" );
define( 'CARRIAGE_RETURN', "\r" );
define( 'LINEBREAK', "<br/>" );
define( 'SPACE', ' &nbsp; ' );
define( 'AMPERSAND', ' &amp; ' );

define('PAGE_TOP', 'aeolus_page_top_anchor');
define('PAGE_BOTTOM', 'aeolus_page_bottom_anchor');

# The following colours are defined in the University branding toolkit, and are the colours used
# when the system is run in 'standalone' mode, i.e. not under the CMS.
define( 'OXFORD_BLUE',  '#002147' );
define( 'PASTEL_BLUE',  '#c5d2e0' );
define( 'PASTEL_OLIVE', '#e1deae' );
define( 'MID_GREEN'   , '#7ca295' );
define( 'DARK_RED'    , '#822433' );

# Variations for Hartlib basic database
define( 'HARTLIB_MAROON', '#800000' );
define( 'HARTLIB_NAVY', '#002147' );
define( 'HARTLIB_GREY', '#f2f2f2' );

# Variations for EMLO
define( 'EMLO_DEEP_BLUE', '#315581' );  # rgb( 49, 85, 129 )
define( 'EMLO_MID_BLUE', '#5c7ea7' );  # rgb( 92, 126, 167 )
define( 'EMLO_PALE_BLUE', '#a7bfd6' );  # rgb( 167, 191, 214 )
define( 'EMLO_BLUE_GREY', '#d3d3de' );  # rgb( 211, 211, 222 )

# Variations for IMPAcT database
define( 'PALE_BEIGE', '#e9e2cf' );
define( 'IMPACT_HEADER_COLOUR', PALE_BEIGE );
define( 'IMPACT_HIGHLIGHT1', HARTLIB_GREY );
define( 'IMPACT_HIGHLIGHT2', HARTLIB_GREY );

define( 'DEFAULT_COL1_FIELD_LABEL_WIDTH_PX', 130 );
define( 'DEFAULT_COL1_FIELD_VALUE_POS_PX', 140 );

define( 'DEFAULT_COL2_FIELD_LABEL_WIDTH_PX', 520 );
define( 'DEFAULT_COL2_FIELD_VALUE_POS_PX', 540 );

define( 'TEST_SERVER', 'aeolus.bodleian.ox.ac.uk' );
define( 'TEST_INDICATOR_COLOUR', '#ffffdf' );


class HTML extends Application_Entity {
  #-----------------------------------------------------------------

	static function html_start() {

    //echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" ';
    //echo NEWLINE;
    //echo '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
    //echo NEWLINE;

    echo '<!DOCTYPE html><html>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------
	static function html_end() {
    echo NEWLINE;
    echo '</html>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------
	static function html_head_start() {

    echo NEWLINE;
    echo '<head>';
    echo NEWLINE;

    echo '<meta http-equiv="Content-Type" content="text/html; charset=' 
         . Application_Entity::get_character_encoding() . '">';
    echo NEWLINE;
    echo '<meta http-equiv="Content-Language" content="en-gb">';
    echo NEWLINE;

    $page_title = trim( CFG_SYSTEM_TITLE );
    echo "<title>$page_title</title>" . NEWLINE;

    if( PROJ_FAVICON > '' )
      echo '<link rel="icon" type="image/png" href="' . PROJ_FAVICON . '">' . NEWLINE;
  }
  #-----------------------------------------------------------------

	static function call_htmlentities( $value, $quote_conversion = NULL, $charset = NULL ) {

    if( ! $quote_conversion ) $quote_conversion = ENT_QUOTES;
    if( ! $charset ) $charset = Application_Entity::get_character_encoding();

    return htmlentities( $value, $quote_conversion, $charset );
  }
  #-----------------------------------------------------------------

	static function header_text_colour() {

    if( PROJ_COLLECTION_CODE == DATA_COLLECTION_TOOL_SUBSYSTEM )
      return EMLO_BLUE_GREY;
    return HARTLIB_NAVY;
  }
  #-----------------------------------------------------------------

	static function footer_text_colour() {

    return HTML::header_text_colour();
  }
  #-----------------------------------------------------------------

	static function header_background_colour() {

    if( $_SERVER[ 'SERVER_NAME' ] == TEST_SERVER ) return TEST_INDICATOR_COLOUR;

    if( Application_Entity::get_system_prefix() == 'impt' )
      return IMPACT_HEADER_COLOUR;

    elseif( PROJ_COLLECTION_CODE == DATA_COLLECTION_TOOL_SUBSYSTEM )
      return EMLO_MID_BLUE;

    return HARTLIB_GREY;
  }
  #-----------------------------------------------------------------

	static function footer_background_colour() {

    return HTML::header_background_colour();
  }
  #-----------------------------------------------------------------

	static function write_stylesheet( $for_cms_deployment = FALSE, $banner_only = FALSE, $printable = FALSE ) {

	// Mattt: I've moved these to css files...
     // Matt: I've turned it back on cause it does other stuff like printing...

	 // echo '<!-- link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/foundation/6.3.0/css/foundation.min.css" media="screen" / -->';

	//if( ! $for_cms_deployment ) {
	//	echo '<link rel="stylesheet" type="text/css" href="base_style.css" media="screen" />';
	//	echo '<link rel="stylesheet" type="text/css" href="base_style_print.css" media="print" />';
	//}
  	//else {
	//	echo '<link rel="stylesheet" type="text/css" href="base_style_cms.css" media="screen" />';
	//}

	//return;

    echo '<style type="text/css">' . NEWLINE;

    #---------
    # Defaults
    #---------
    if( ! $for_cms_deployment ) {

		echo 'html {background-color:#5c7ea7;margin:0;}';

      echo ' body {'                                                     . NEWLINE;
      echo "   font-family: georgia, 'times new roman', times, serif; "  . NEWLINE;
      echo '   font-size: 11pt;'                                          . NEWLINE;
      echo '   color: black;'                                            . NEWLINE;
      echo '   background:  white;'                                      . NEWLINE;
      echo '   width:  auto;'                                            . NEWLINE;
      echo ' }'                                                          . NEWLINE;

	echo 'table {';
	echo '  background-color:white;';
	echo '}';
	

      if( ! $printable ) {
        echo ' body {'                                                       . NEWLINE;
	  echo 'margin: 6px;';
	  //echo 'max-width: 1200px;';
       // echo '   margin-left: 10px;'                                         . NEWLINE;
        //echo '   margin-right: 10px;'                                        . NEWLINE;
        echo ' }'                                                            . NEWLINE;
      }

      if( ! $printable ) {
        echo ' div.innerbody {' . NEWLINE;
        echo '   margin-left: 10px;'                                    . NEWLINE;
        echo '   margin-right: 10px;'                                      . NEWLINE;
        echo '   margin-bottom: 10px;'                                  . NEWLINE;
		  echo 'padding-top:10px;';
        echo ' }'                                                       . NEWLINE;
      }

      echo ' h1 {'                                                    . NEWLINE; 
      echo '   font-size: 20pt;'                                      . NEWLINE;
      echo '   font-weight: normal;'                                  . NEWLINE;
      echo '   margin-top: 20px; '                                    . NEWLINE;
      echo '   margin-bottom: 10px; '                                 . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' h2 {'                                                    . NEWLINE; 
      echo '   font-size: 18pt;'                                      . NEWLINE;
      echo '   font-weight: normal;'                                  . NEWLINE;
      echo '   margin-top: 10px; '                                    . NEWLINE;
      echo '   margin-bottom: 10px; '                                 . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' h3 {'                                                    . NEWLINE; 
      echo '   font-size: 14pt;'                                      . NEWLINE;
      echo '   font-weight: normal;'                                  . NEWLINE;
      echo '   margin-top: 8px; '                                     . NEWLINE;
      echo '   margin-bottom: 8px; '                                  . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' h4 {'                                                    . NEWLINE; 
      echo '   font-size: 11pt;'                                      . NEWLINE;
      echo '   font-weight: bold;'                                    . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' a:link  {'                                               . NEWLINE; 
      echo '   color: ' .  HTML::get_contrast1_colour() . ';'         . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' a:active, a:hover, a:visited  {'                                      . NEWLINE; 
      echo '   color: ' .  HTML::get_contrast2_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                    . NEWLINE;

      echo ' input, textarea {'                                       . NEWLINE;
      echo '   font-family: Verdana, Helvetica, Arial, sans-serif; '  . NEWLINE;
		echo '   font-size: 9pt;'                                      . NEWLINE;
		echo '   padding:5px;'                                      . NEWLINE;
		echo '   line-height:1.2;'                                      . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo 'section { margin: 40px 0;}';

      echo ' fieldset {'                                                      . NEWLINE;
      echo '   background-color: ' .  HTML::header_background_colour() . ';'  . NEWLINE;
      echo '   border-style: solid;'                                          . NEWLINE;
      echo '   border-color: ' .  HTML::get_contrast1_colour() . ';'          . NEWLINE;
      echo '   border-width: 1px;'                                            . NEWLINE;
      echo '   padding: 5px;'                                                 . NEWLINE;
      echo ' }'                                                               . NEWLINE;

      echo ' legend {'                                                   . NEWLINE;
      echo '   font-weight: bold;'                                       . NEWLINE;
      echo '   color: ' .  HTML::get_contrast1_colour() . ';'            . NEWLINE;
      echo '   margin-left: 20px;'                                       . NEWLINE;
      echo ' }'                                                          . NEWLINE;

		echo ' p, li, td {'                                                    . NEWLINE;
		echo '   line-height:1.4;'                                      . NEWLINE;
		echo ' }'                                                       . NEWLINE;


		echo ' td {'                                                    . NEWLINE;
      echo '   vertical-align:top;'                                   . NEWLINE;
      echo '   text-align:left;'                                      . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' th {'                                                    . NEWLINE;
      echo '    vertical-align:bottom;'                               . NEWLINE;
      echo '    text-align:left;'                                     . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' form {'                                                  . NEWLINE; 
      echo '   margin-top: 0px; '                                     . NEWLINE;
      echo '   margin-bottom: 0px; '                                  . NEWLINE;
      echo '   padding: 2px; '                                        . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' hr { ' . NEWLINE;
      echo '   color:#f2f2f2;;'              . NEWLINE;  # apparently used by IE
      echo '   background-color: #f2f2f2;'   . NEWLINE;  # apparently used by Firefox
      echo '   height: 6px;'  . NEWLINE;
      echo '   border-style: none;'  . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' hr.pale { '                                                   . NEWLINE;
      echo '   color: ' . HTML::get_highlight1_colour() . ';'              . NEWLINE;  # apparently used by IE
      echo '   background-color: ' . HTML::get_highlight1_colour() . ';'   . NEWLINE;  # apparently used by Firefox
      echo '   height: 1px;'                                               . NEWLINE;
      echo '   border-style: none;'                                        . NEWLINE;
      echo ' }'                                                            . NEWLINE;

      #--------
      # Banner
      #--------

      echo ' .banner {'                                                                        . NEWLINE; 
      echo '   color: ' . HTML::header_text_colour() . ';'                                     . NEWLINE;
      echo '   background-color: #d3d3de;'                   . NEWLINE;
      echo ' }'                                                                                . NEWLINE;

      echo ' .banner h1  {'                                           . NEWLINE; 
      echo '   color: ' .  HTML::header_text_colour() . ';'           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .banner h1, .printbanner h1  {'                          . NEWLINE; 
      echo '   font-style: italic;'                                   . NEWLINE;
      echo '   font-size: 22pt;'                                      . NEWLINE;
      echo '   margin-top: 20px; '                                    . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .banner h2 {'                                            . NEWLINE; 
      echo '   color: ' .  HTML::header_text_colour() . ';'           . NEWLINE;
      echo '   margin-left: 15px; '                                    . NEWLINE;
      echo '   font-variant-caps: petite-caps;';
		echo ' 	padding-top: 10px;';
      echo ' }'                                                       . NEWLINE;

      echo ' .banner a:link, .banner a:visited  {'                    . NEWLINE; 
      echo '   font-weight: bold;'                                    . NEWLINE;
      echo '   text-decoration: none;'                                . NEWLINE;
      echo '   color: ' .  HTML::header_text_colour() . ';'           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .banner a:active, .banner a:hover  {'                                 . NEWLINE; 
      echo '   font-weight: bold;'                                                 . NEWLINE;
      echo '   text-decoration: none;'                                             . NEWLINE;
      echo '   color: ' .  HTML::get_contrast2_colour() . ';'                      . NEWLINE;
      echo ' }'                                                                    . NEWLINE;

      echo ' .banner br, .printbanner br {'                                        . NEWLINE;
      echo '   line-height: 15px;'                                                 . NEWLINE;
      echo '   clear: left;'                                                       . NEWLINE;
      echo ' }'                                                                    . NEWLINE;

      echo ' img.bannerlogo, img.printbannerlogo {'                                        . NEWLINE;
      echo '   border-style: solid;'                                                       . NEWLINE;
      echo '   border-top-width: 0px;'                                                     . NEWLINE;
      echo '   border-left-width: 15px;'                                                   . NEWLINE;
      echo '   border-right-width: 25px;'                                                  . NEWLINE;
      echo '   border-bottom-width: 15px;'                                                 . NEWLINE;
      echo '   float:left; clear:left; '                                                   . NEWLINE;
      echo '   height: ' . PROJ_LOGO_HEIGHT_PX . 'px; '                                    . NEWLINE;
      echo ' }'                                                                            . NEWLINE;

      echo ' img.bannerlogo {'                                                             . NEWLINE;
      echo '   border-color: #d3d3de;'                   . NEWLINE;
      echo ' }'                                                                            . NEWLINE;

      echo ' img.printbannerlogo {'                                                        . NEWLINE;
      echo '   border-color: white;'                                                       . NEWLINE;
      echo ' }'                                                                            . NEWLINE;

      echo ' .banner form {'                                          . NEWLINE; 
      echo '   display: inline; '                                     . NEWLINE;
      echo '   padding: 0px; '                                        . NEWLINE;
      echo '   margin-left: 0px; '                                    . NEWLINE;
      echo '   margin-right: 0px; '                                   . NEWLINE;
      echo '   float:right; '                                         . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .banner input  {'                                                                 . NEWLINE;
      echo '   color: ' . HTML::header_text_colour() . ' ;'                                    . NEWLINE;
      echo '   background-color: ' .  HTML::header_background_colour() . ';'                   . NEWLINE;
      echo '   border-style: none;'                                                            . NEWLINE;
      echo '   border-width: 0px;'                                                             . NEWLINE;
      echo '   padding: 0px; '                                                                 . NEWLINE;
      echo ' }'                                                                                . NEWLINE;

      if( $banner_only ) {
        echo '</style>' . NEWLINE;
        return;
      }

      #--------
      # Footer 
      #--------
      echo ' .footimgs img {'                                                                  . NEWLINE;
      echo '   vertical-align: middle;'                                                        . NEWLINE;
      echo ' }'                                                                                . NEWLINE;

      echo ' .footerlinks {'                                                                   . NEWLINE; 
      echo '   color: '  .  HTML::footer_text_colour() . ';'                                   . NEWLINE;
      echo '   background-color: ' . HTML::footer_background_colour() . ';'                    . NEWLINE;
      echo '   border-top: solid 2px #434871';
      echo ' }'                                                                                . NEWLINE;

      echo ' .footerlinks a:link, .footerlinks a:visited  {'          . NEWLINE; 
      echo '   font-weight: bold;'                                    . NEWLINE;
      echo '   text-decoration: none;'                                . NEWLINE;
      echo '   color: '  .  HTML::get_contrast1_colour() . ';'        . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .footerlinks a:active, .footerlinks a:hover  {'                       . NEWLINE; 
      echo '   font-weight: bold;'                                                 . NEWLINE;
      echo '   text-decoration: none;'                                             . NEWLINE;
      echo '   color: ' .  HTML::get_contrast2_colour() . ';'                      . NEWLINE;
      echo ' }'                                                                    . NEWLINE;

      echo ' .footnotes {'                                                         . NEWLINE; 
      echo '   background-color: ' .  HTML::footer_background_colour() . ';'       . NEWLINE;
      echo '   color: ' . HTML::footer_text_colour() . ';'                         . NEWLINE;
      echo ' }'                                                                    . NEWLINE;

      echo ' a.footimg img  {'                                        . NEWLINE; 
      echo '   border: none;'                                         . NEWLINE;
      echo '   margin-top: 15px;'                                     . NEWLINE;
      echo '   margin-right: 15px;'                                   . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' img.printfootimg  {'                                     . NEWLINE; 
      echo '   border: none;'                                         . NEWLINE;
      echo '   margin-top: 15px;'                                     . NEWLINE;
      echo '   margin-right: 15px;'                                   . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      #-----------
      # Main menu
      #-----------
      echo '.mainmenu br {line-height: 15px ; clear: left}'           . NEWLINE;

      echo ' .mainmenu a:link, .boldlink a:link {'                    . NEWLINE;
      echo '   font-weight: bold;'                                    . NEWLINE;
      echo '   font-size: 11pt;'                                      . NEWLINE;
      echo '   text-decoration: none;'                                . NEWLINE;
      echo '   color: ' .  HTML::get_contrast1_colour() . ';'         . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .boldlink a:active, .boldlink a:hover, .boldlink a:visited, ';
      echo ' .mainmenu a:active, .mainmenu a:hover, .mainmenu a:visited  {'         . NEWLINE; 
      echo '   font-weight: bold;'                                                  . NEWLINE;
      echo '   font-size: 11pt;'                                                    . NEWLINE;
      echo '   text-decoration: none;'                                              . NEWLINE;
      echo '   color: ' .  HTML::get_contrast2_colour( $for_cms_deployment ) . ';'  . NEWLINE;
      echo ' }'                                                                     . NEWLINE;

      echo ' .bold {'                                                 . NEWLINE;
      echo '   font-weight: bold;'                                    . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo '.mainmenu ul {
              margin-top: 0;
              }';

      echo '.mainmenu li {
                padding: 3px 0;
                list-style: none;
            }';

      echo  '.mainmenu li.item_16, .mainmenu li.item_25, 
              .mainmenu li.item_39, .mainmenu li.item_76,
              .mainmenu li.item_83, .mainmenu li.item_89,
              .mainmenu li.item_106 {
                padding-bottom: 12px;
                border-bottom: 1px solid #eee;
                margin-bottom: 8px;
            }';

      echo '.mainmenu li a {
              width: 100% !important;
              display: block;
              padding: 3px 5px;
          }';

      echo '.mainmenu li a:hover {
                background-color: #f2dcdc;
            }';

      #-------------
      # Reports menu
      #-------------
      echo ' .reportsmenu {'                                                             . NEWLINE;
      echo '   background: ' .  HTML::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                          . NEWLINE;

      echo ' .reportsmenu table {'                                                             . NEWLINE;
      echo '   border-left-style: solid;'                                                      . NEWLINE;
      echo '   border-left-color: ' .  HTML::get_contrast1_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo '   border-left-width: 20px;'                                                       . NEWLINE;
      echo '   border-collapse: collapse;'                                                     . NEWLINE;
      echo ' }'                                                                                . NEWLINE;

      echo ' .reportsmenu td, reportsmenu th {'                       . NEWLINE;
      echo '    border-style: solid;'                                 . NEWLINE;
      echo '    border-width: 1px;'                                   . NEWLINE;
      echo '    border-color: white;'                                 . NEWLINE;
      echo '    border-collapse: collapse;'                           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      #----------------
      # Tables: non-CMS
      #----------------
      echo ' td.rightaligned, p.rightaligned {'                       . NEWLINE;
      echo '   text-align:right;'                                     . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' th.rightaligned {'                                       . NEWLINE;
      echo '   text-align:right;'                                     . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' th.topaligned {'                                         . NEWLINE;
      echo '   vertical-align:top;'                                   . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' td.contrast1, th.contrast1 {'                                               . NEWLINE;
      echo '   background: ' .  HTML::get_contrast1_colour( $for_cms_deployment ) . ';'  . NEWLINE;
      echo ' }'                                                                          . NEWLINE;

      echo ' td.contrast2, th.contrast2 {'                                               . NEWLINE;
      echo '   background: ' .  HTML::get_contrast2_colour( $for_cms_deployment ) . ';'  . NEWLINE;
      echo ' }'                                                                          . NEWLINE;

      echo ' .highlight1 td, .highlight1 th {'                                           . NEWLINE;
      echo '   background: ' .  HTML::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                          . NEWLINE;

      echo ' .highlight2 td, .highlight2 th {'                                           . NEWLINE;
      echo '   background: ' .  HTML::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                          . NEWLINE;

      echo ' table.datatab {'                                         . NEWLINE;
      echo '    margin-top: 5px;'                                     . NEWLINE;
      echo '    border-style: solid;'                                 . NEWLINE;
      echo '    border-width: 1px;'                                   . NEWLINE;
      echo '    border-color: black;'                                 . NEWLINE;
      echo '    border-collapse: collapse;'                           . NEWLINE;
      echo '    outline: white 10px solid;';
      echo '    background-color: white;';
      echo ' }'                                                       . NEWLINE;

      echo ' .datatab td {'                                           . NEWLINE;
      echo '    border-style: solid;'                                 . NEWLINE;
      echo '    border-width: 1px;'                                   . NEWLINE;
      echo '    border-color: black;'                                 . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .datatab th {'                                           . NEWLINE;
      echo '    border-style: solid;'                                 . NEWLINE;
      echo '    border-width: 1px;'                                   . NEWLINE;
      echo '    border-color: black;'                                 . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .spacepadded td {'                                       . NEWLINE;
      echo '    padding: 5px;'                                        . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .spacepadded th {'                                       . NEWLINE;
      echo '    padding: 5px;'                                        . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .widelyspacepadded td {'                                 . NEWLINE;
      echo '    padding: 10px;'                                       . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .widelyspacepadded th {'                                 . NEWLINE;
      echo '    padding: 10px;'                                       . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' table.nointernalborders {'                               . NEWLINE;
      echo '    border-collapse: collapse;'                           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' table.boxed {'                                           . NEWLINE;
      echo '    border-style: solid;'                                 . NEWLINE;
      echo '    border-width: 1px;'                                   . NEWLINE;
      echo '    border-color: black;'                                 . NEWLINE;
      echo '    border-collapse: collapse;'                           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' td.boxed {'                                              . NEWLINE;
      echo '    border-style: solid;'                                 . NEWLINE;
      echo '    border-width: 1px;'                                   . NEWLINE;
      echo '    border-color: black;'                                 . NEWLINE;
      echo '    border-collapse: collapse;'                           . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' table.contrast1_boxed {'                                                     . NEWLINE;
      echo '   border-style: solid;'                                                      . NEWLINE;
      echo '   border-color: ' .  HTML::get_contrast1_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo '   border-width: 1px;'                                                        . NEWLINE;
      echo '   border-collapse: collapse;'                                                . NEWLINE;
      echo ' }'                                                                           . NEWLINE;

      echo ' table.contrast2_boxed {'                                                     . NEWLINE;
      echo '   border-style: solid;'                                                      . NEWLINE;
      echo '   border-color: ' .  HTML::get_contrast2_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo '   border-width: 1px;'                                                        . NEWLINE;
      echo '   border-collapse: collapse;'                                                . NEWLINE;
      echo ' }'                                                                           . NEWLINE;

      echo ' table.bottomaligned {'                                   . NEWLINE;
      echo '   vertical-align:bottom;'                                . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .bottomaligned td {'                                     . NEWLINE;
      echo '   vertical-align:bottom;'                                . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' table.centeraligned {'                                   . NEWLINE;
      echo '   vertical-align:middle;'                                . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .centeraligned td {'                                     . NEWLINE;
      echo '   vertical-align: middle;'                                . NEWLINE;
      echo ' }'                                                       . NEWLINE;
    }

    #-------------------------------
    # Tables: CMS as well as non-CMS
    #-------------------------------

    echo ' .searchablefields table {'                                                   . NEWLINE;
    echo '   border-style: solid;'                                                      . NEWLINE;
    echo '   border-color: #a8bad0;' . NEWLINE;
    echo '   border-width: 1px;'                                                        . NEWLINE;
    echo '   border-collapse: collapse;'                                                . NEWLINE;
    echo ' }'                                                                           . NEWLINE;

    echo ' .searchablefields td {'                                  . NEWLINE;
    echo '   padding: 6px;'                                         . NEWLINE;
    echo '   vertical-align:middle;'                                . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .searchablefields th {'                                  . NEWLINE;
    echo '   padding: 5px;'                                         . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .searchablefieldname {'                                                           . NEWLINE;
    echo '   text-align:right;'                                                              . NEWLINE;
    echo ' }'                                                                                . NEWLINE;
    echo NEWLINE;

    echo ' .searchablefieldvalue {'                                                          . NEWLINE;
    echo '   text-align:right;'                                                              . NEWLINE;
    echo ' }'                                                                                . NEWLINE;
    echo NEWLINE;

    echo ' td.sectionhead {'                                                             . NEWLINE;
    echo '   font-weight: bold;'                                                         . NEWLINE;
    if( ! $for_cms_deployment ) echo '   font-size: 11pt;'                               . NEWLINE;
    if( ! $for_cms_deployment ) echo '   font-style: italic;'                            . NEWLINE;
    echo ' }'                                                                            . NEWLINE;
    echo NEWLINE;


    echo ' .queryresults table {'                                   . NEWLINE;
    echo '    margin-top: 5px;'                                     . NEWLINE;
    echo '    border-style: solid;'                                 . NEWLINE;
    echo '    border-width: 1px;'                                   . NEWLINE;
    echo '    border-color: black;'                                 . NEWLINE;
    echo '    border-collapse: collapse;'                           . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .queryresults td {'                                      . NEWLINE;
    echo '    border-style: solid;'                                 . NEWLINE;
    echo '    border-width: 2px;'                                   . NEWLINE;
    echo '    border-color: #e3e3e3;'                                 . NEWLINE;
    echo '    padding: 13px 9px;'                                        . NEWLINE;
    echo '    vertical-align:top;'                                  . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .queryresults th {'                                      . NEWLINE;
    echo '    border-style: solid;'                                 . NEWLINE;
    echo '    border-width: 2px;'                                   . NEWLINE;
    echo '    border-color: #e3e3e3;'                                 . NEWLINE;
    echo '    padding: 5px;'                                        . NEWLINE;
	  echo '      background-color: #f2f2f2;'                         . NEWLINE;
	  echo '      vertical-align: middle;'                         . NEWLINE;
	  echo '      text-align: center;'                         . NEWLINE;
	  echo '      padding: 10px 0;'                         . NEWLINE;
	  echo ' }'                                                       . NEWLINE;
	  

    if( ! $for_cms_deployment ) { # CMS apparently already does space-padding of listitems
      echo ' .queryresults ul, .printableacrosspage ul {'           . NEWLINE;
      echo '    margin-top: 0px;'                                   . NEWLINE;
      echo '    margin-bottom: 0px;'                                . NEWLINE;
      echo '    margin-left: 2px;'                                  . NEWLINE;
      echo '    padding-left: 10px;'                                . NEWLINE;
      echo '    padding-top: 0px;'                                  . NEWLINE;
      echo ' }'                                                     . NEWLINE;
      echo ' .queryresults li, .printableacrosspage li {'           . NEWLINE;
      echo '    margin-bottom: 8px;'                                . NEWLINE;
      echo '    margin-left: 0px;'                                  . NEWLINE;
      echo ' }'                                                     . NEWLINE;

      echo ' .printabledownpage ul {'                               . NEWLINE;
      echo '    margin-top: 0px;'                                   . NEWLINE;
      echo '    margin-bottom: 0px;'                                . NEWLINE;
      echo ' }'                                                     . NEWLINE;
      echo ' .printabledownpage li {'                               . NEWLINE;
      echo '    margin-bottom: 8px;'                                . NEWLINE;
      echo ' }'                                                     . NEWLINE;

      echo ' .queryresults ol, .printableacrosspage ol {'           . NEWLINE;
      echo '    margin-top: 0px;'                                   . NEWLINE;
      echo '    margin-bottom: 0px;'                                . NEWLINE;
      echo '    margin-left: 8px;'                                  . NEWLINE;
      echo '    padding-left: 10px;'                                . NEWLINE;
      echo '    padding-top: 0px;'                                  . NEWLINE;
      echo ' }'                                                     . NEWLINE;
    };

    echo NEWLINE;

    echo ' .printableacrosspage td {'                               . NEWLINE;
    echo '    vertical-align:top;'                                  . NEWLINE;
    echo '    padding: 10px;'                                       . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .printableacrosspage th {'                               . NEWLINE;
    echo '    padding: 10px;'                                       . NEWLINE;
    echo ' }'                                                       . NEWLINE;
    echo NEWLINE;

    echo ' .printabledownpage td {'                                 . NEWLINE;
    echo '    vertical-align:top;'                                  . NEWLINE;
    echo '    padding: 5px;'                                        . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .printabledownpage th {'                                 . NEWLINE;
    echo '    padding: 5px;'                                        . NEWLINE;
    echo ' }'                                                       . NEWLINE;
    echo NEWLINE;


    #-----------------------------------
    # Error messages, warnings 
    # and highlighting of important info
    #-----------------------------------

    echo ' div.highlight1, span.highlight1, p.highlight1 {'                            . NEWLINE;
    echo '   background: ' .  HTML::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo ' }'                                                                          . NEWLINE;

    echo ' div.highlight2, span.highlight2, p.highlight2 {'                            . NEWLINE;
    echo '   background: ' .  HTML::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo ' }'                                                                          . NEWLINE;

    echo ' p.contrast1 {'                                                              . NEWLINE;
    echo '   background: ' .  HTML::get_contrast1_colour( $for_cms_deployment ) . ';'  . NEWLINE;
    echo ' }'                                                                          . NEWLINE;

    echo ' p.contrast2 {'                                                              . NEWLINE;
    echo '   background: ' .  HTML::get_contrast2_colour( $for_cms_deployment ) . ';'  . NEWLINE;
    echo ' }'                                                                          . NEWLINE;

    echo ' td.highlight1, th.highlight1  {'                                            . NEWLINE;
    echo '   background: ' .  HTML::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo ' }'                                                                          . NEWLINE;

    echo ' td.highlight2, th.highlight2 {'                                             . NEWLINE;
    echo '   background: ' .  HTML::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo ' }'                                                                          . NEWLINE;

    echo NEWLINE;

    echo '.errmsg {'                                                                         . NEWLINE;
    echo '   background-color: ' .  HTML::get_contrast2_colour( $for_cms_deployment ) . ';'  . NEWLINE;
    echo '   font-size: 11pt;'                                                               . NEWLINE;
    echo '   font-weight: bold;'                                                             . NEWLINE;
    echo '   color: white;'                                                                  . NEWLINE;
    echo ' }'                                                                                . NEWLINE;

    echo '.warning {'                                                                        . NEWLINE;
    echo '   background-color: ' .  HTML::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo '   color: ' .  HTML::get_contrast2_colour( $for_cms_deployment ) . ';'             . NEWLINE;
    echo '   font-size: 11pt;'                                                               . NEWLINE;
    echo '   font-weight: bold;'                                                             . NEWLINE;
    echo ' }'                                                                                . NEWLINE;
    echo NEWLINE;

    #-------------
    # Entry fields
    #-------------
    echo ' input.highlight1, select.highlight1, option.highlight1 {'                         . NEWLINE;
    echo '   background-color: ' .  HTML::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo ' }'                                                                                . NEWLINE;

    echo ' input.highlight2, select.highlight2, option.highlight2 {'                         . NEWLINE;
    echo '   background-color: ' .  HTML::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo ' }'                                                                                . NEWLINE;

    echo ' input.bold, option.bold {'                               . NEWLINE;
    echo '   font-weight: bold;'                                    . NEWLINE;
    echo ' }'                                                       . NEWLINE;
    echo NEWLINE;

    #------------------
    # Forms and buttons 
    #------------------
    echo ' .dataentrytextfields label  {'                            . NEWLINE;
    echo '   position: absolute; '                                   . NEWLINE;
    echo '   text-align:right; '                                     . NEWLINE;
    echo '   width: ' . DEFAULT_COL1_FIELD_LABEL_WIDTH_PX . 'px;'    . NEWLINE;
    echo ' }'                                                        . NEWLINE;

    echo ' .dataentrytextfields input, .dataentrytextfields textarea {' . NEWLINE;
    echo '   margin-left: ' . DEFAULT_COL1_FIELD_VALUE_POS_PX . 'px;'   . NEWLINE; 
    echo ' }'                                                           . NEWLINE;

    echo ' .buttonrow form {'                                       . NEWLINE; 
    echo '   display: inline; '                                     . NEWLINE;
    echo '   float:left; '                                          . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .buttonrow br { '                                        . NEWLINE;
    echo '   clear: left; '                                         . NEWLINE;
    echo '   line-height: 35px; '                                   . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    if( ! $for_cms_deployment ) {
      echo ' .buttonrow a:link, .buttonrow a:visited  {'              . NEWLINE; 
      echo '   font-weight: bold;'                                    . NEWLINE;
      echo '   text-decoration: none;'                                . NEWLINE;
      echo '   color: ' .  HTML::get_contrast1_colour() . ';'         . NEWLINE;
      echo ' }'                                                       . NEWLINE;

      echo ' .buttonrow a:active, .buttonrow a:hover  {'                           . NEWLINE; 
      echo '   font-weight: bold;'                                                 . NEWLINE;
      echo '   text-decoration: none;'                                             . NEWLINE;
      echo '   color: ' .  HTML::get_contrast2_colour( $for_cms_deployment ) . ';' . NEWLINE;
      echo ' }'                                                                    . NEWLINE;
    }
    echo NEWLINE;


    echo ' .pagination form {'                                      . NEWLINE; 
    echo '   display: inline; '                                     . NEWLINE;
    echo '   float:left; '                                          . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' input.pagelist  {'                                                                . NEWLINE;
    echo '   color: ' .  HTML::get_contrast1_colour() . ';'                                  . NEWLINE;
    echo '   background-color: ' .  HTML::get_highlight1_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo '   border-style: solid;'                                                           . NEWLINE;
    echo '   border-width: 2px;'                                                             . NEWLINE;
    echo '   border-color: white ;'                                                          . NEWLINE; 
    echo '   padding: 2px; '                                                                 . NEWLINE;
    echo ' }'                                                                                . NEWLINE;

    echo ' input.currpage  {'                                                                . NEWLINE;
    echo '   font-weight: bold;'                                                             . NEWLINE;
    echo '   color: ' .  HTML::get_contrast2_colour() . ';'                                  . NEWLINE;
    echo '   background-color: ' .  HTML::get_highlight2_colour( $for_cms_deployment ) . ';' . NEWLINE;
    echo '   border-style: solid;'                                                           . NEWLINE;
    echo '   border-width: 1px;'                                                             . NEWLINE;
    echo '   border-color: ' . HTML::get_contrast2_colour() . ' ;'                           . NEWLINE;
    echo '   padding: 2px; '                                                                 . NEWLINE;
    echo ' }'                                                                                . NEWLINE;


    echo ' input.dummypage  {'                                                               . NEWLINE;
    echo '   color: ' . HTML::get_contrast1_colour()  . ';'                                  . NEWLINE;
    echo '   background-color: white ;'                                                      . NEWLINE;
    echo '   border-style: solid;'                                                           . NEWLINE;
    echo '   border-left-width: 2px;'                                                        . NEWLINE;
    echo '   border-right-width: 2px;'                                                       . NEWLINE;
    echo '   border-top-width: 4px;'                                                         . NEWLINE;
    echo '   border-bottom-width: 4px;'                                                      . NEWLINE;
    echo '   border-color: white;'                                                           . NEWLINE;
    echo '   float:left; '                                                                   . NEWLINE;
    echo ' }'                                                                                . NEWLINE;

    echo ' .pagination br { '                                                                . NEWLINE;
    echo '   clear: left; '                                                                  . NEWLINE;
    echo '   line-height: 35px; '                                                            . NEWLINE;
    echo ' }'                                                                                . NEWLINE;
    echo NEWLINE;

    echo ' span.narrowspaceonleft {'                                                         . NEWLINE;
    echo '   margin-left: 5px;'                                                              . NEWLINE;
    echo ' }'                                                                                . NEWLINE;
    echo NEWLINE;

    echo ' span.widespaceonleft {'                                                           . NEWLINE;
    echo '   margin-left: 20px;'                                                             . NEWLINE;
    echo ' }'                                                                                . NEWLINE;
    echo NEWLINE;

    echo ' .choosepresentation {'                                                              . NEWLINE;
    echo '   background-color: ' .  HTML::get_highlight1_colour( $for_cms_deployment ) . ';'   . NEWLINE;
    echo '   border: none;'                                                                    . NEWLINE;
    echo '   padding-top: 10px;'                                                               . NEWLINE;
    echo '   padding-bottom: 10px;'                                                            . NEWLINE;
    echo '   padding-left: 10px;'                                                              . NEWLINE;
    echo ' }'                                                                                  . NEWLINE;

    echo ' .choosepresentation input {'                                                        . NEWLINE;
    echo '   margin-left: 2px;'                                                                . NEWLINE;
    echo '   margin-right: 2px;'                                                               . NEWLINE;
    echo ' }'                                                                                  . NEWLINE;
    echo NEWLINE;

    echo ' .choosepresentation label {'                                                        . NEWLINE;
    echo '   margin-right: 2px;'                                                               . NEWLINE;
    echo ' }'                                                                                  . NEWLINE;
    echo NEWLINE;

    echo '</style>' . NEWLINE;
  }
  #-----------------------------------------------------------------

	static function get_highlight1_colour( $for_cms_deployment = FALSE ) {

    if( Application_Entity::get_system_prefix() == 'impt' )
      return IMPACT_HIGHLIGHT1;

    elseif( PROJ_COLLECTION_CODE == DATA_COLLECTION_TOOL_SUBSYSTEM )
      return EMLO_PALE_BLUE;

    if( $_SERVER[ 'SERVER_NAME' ] == TEST_SERVER ) return TEST_INDICATOR_COLOUR;
    return HARTLIB_GREY;
  }
  #-----------------------------------------------------------------

	static function get_highlight2_colour( $for_cms_deployment = FALSE ) {

    if( Application_Entity::get_system_prefix() == 'impt' )
      return IMPACT_HIGHLIGHT2;

    elseif( PROJ_COLLECTION_CODE == DATA_COLLECTION_TOOL_SUBSYSTEM )
      return EMLO_BLUE_GREY;

    if( $_SERVER[ 'SERVER_NAME' ] == TEST_SERVER ) return TEST_INDICATOR_COLOUR;
    return HARTLIB_GREY;
  }
  #-----------------------------------------------------------------

	static function get_contrast1_colour( $for_cms_deployment = FALSE ) {

    if( PROJ_COLLECTION_CODE == DATA_COLLECTION_TOOL_SUBSYSTEM )
      return EMLO_DEEP_BLUE;
    return HARTLIB_NAVY;
  }
  #-----------------------------------------------------------------

	static function get_contrast2_colour( $for_cms_deployment = FALSE ) {

    if( PROJ_COLLECTION_CODE == DATA_COLLECTION_TOOL_SUBSYSTEM )
      return EMLO_DEEP_BLUE;
    return HARTLIB_MAROON;
  }
  #-----------------------------------------------------------------

	static function get_contrast3_colour( $for_cms_deployment = FALSE ) {

    if( PROJ_COLLECTION_CODE == DATA_COLLECTION_TOOL_SUBSYSTEM )
      return EMLO_MID_BLUE;

    return HARTLIB_NAVY;
  }
  #-----------------------------------------------------------------

	static function get_oxford_blue( $for_cms_deployment = FALSE ) {
    return OXFORD_BLUE;
  }
  #-----------------------------------------------------------------

	static function html_head_end() {
    echo NEWLINE;
    echo '</head>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

	static function body_start( $focus_form = NULL, $focus_field = NULL, $required_anchor = NULL ) {

    echo NEWLINE;
    echo '<body ';

    if(( $focus_form && $focus_field ) || $required_anchor ) {
      echo ' onLoad="';

      if( $required_anchor ) {
        echo "location.href='";
        if( substr( $required_anchor, 0, 1 ) != '#' ) echo '#';
        echo "$required_anchor'; ";
      }

      if( $focus_form && $focus_field ) {  # Set focus on a particular field
        echo 'document.';
        echo $focus_form;
        echo '.';
        echo $focus_field;
        echo '.focus() ';
      }
      echo '"';
    }

    echo ' >';
    echo NEWLINE;

    HTML::div_start( 'class="innerbody"' );
  }

	static function menu() {
		echo '<style>
				#menu {
					background-color: #22294e;
					width: 100%;
					height: 2px;
					position: sticky;
               top: 0;
				}
				#menu ul {
				  list-style-type: none;
				  margin: 0;
				  padding: 0;
				  overflow: hidden;
				  background-color: #22294e;
				  float: right;
				}
				
				#menu li {
				  float: left;
				}
				
				#menu li a, .dropbtn {
				  display: inline-block;
				  color: white;
				  text-align: center;
				  padding: 4px 16px;
				  text-decoration: none;
				}
				
				#menu li a:hover, .dropdown:hover .dropbtn {
				  background-color: #a7bfd6;
				  color:black;
				}
				
				#menu li.dropdown {
				  display: inline-block;
				}
				
				#menu .dropdown-content {
				  display: none;
				  position: absolute;
				  background-color: #f9f9f9;
				  min-width: 160px;
				  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
				  z-index: 1;
				}
				
				#menu .dropdown-content a {
				  color: black;
				  padding: 12px 16px;
				  text-decoration: none;
				  display: block;
				  text-align: left;
				}
				
				#menu .dropdown-content a:hover {background-color: #f1f1f1}
				
				#menu .dropdown:hover .dropdown-content {
				  display: block;
				}</style>';
		echo '<div id="menu">
				<ul>
				  <li class="dropdown">
					 <a href="javascript:void(0)" class="dropbtn">Works</a>
					 <div class="dropdown-content">
						<a href="union.php?menu_item_id=70">Search (compact)</a>
						<a href="union.php?menu_item_id=72">Search (expanded)</a>
						<a href="union.php?menu_item_id=16">Add new</a>
					 </div>
				  </li>
				  <li class="dropdown">
					 <a href="javascript:void(0)" class="dropbtn">People</a>
					 <div class="dropdown-content">
						<a href="?menu_item_id=23">Search/edit/merge</a>
						<a href="?menu_item_id=24">Search</a>
						<a href="?menu_item_id=25">Add new</a>
					 </div>
				  </li>
				  <li class="dropdown">
					 <a href="javascript:void(0)" class="dropbtn">Places</a>
					 <div class="dropdown-content">
						<a href="?menu_item_id=37">Search/edit/merge</a>
						<a href="?menu_item_id=38">Search</a>
						<a href="?menu_item_id=39">Add new</a>
					 </div>
				  </li>
				  <li class="dropdown">
				  	 <a href="javascript:void(0)" class="dropbtn">Repositories</a>
					 <div class="dropdown-content">
						<a href="?menu_item_id=74">Search/edit/merge</a>
						<a href="?menu_item_id=75">Search</a>
						<a href="?menu_item_id=76">Add new</a>
					 </div>
				  </li>
				  <li class="dropdown">
				  	 <a href="javascript:void(0)" class="dropbtn">Uploads</a>
					 <div class="dropdown-content">
						<a href="?menu_item_id=131">Display</a>
						<a href="?menu_item_id=129">Search</a>
						<a href="?menu_item_id=178">New</a>
					 </div>
				  </li>
				  <li><a href="?menu_item_id=83">Audit</a></li>
				  <li><a href="?logout=1">Logout</a></li>
				</ul>
			</div>';
	}

  #-----------------------------------------------------------------
	static function body_end() {

    echo NEWLINE;
    HTML::div_end( 'innerbody' );

	echo '<script type="text/javascript" src="smoothscroll.js"></script>';

    echo NEWLINE;
    echo '</body>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------
	static function h1_start() {
    echo '<h1>';
  }
  #-----------------------------------------------------------------
	static function h1_end() {
    echo '</h1>';
  }
  #-----------------------------------------------------------------
  static function h2_start( $style ) {
    echo '<h2 style="' . $style . '">';
  }
  #-----------------------------------------------------------------
	static function h2_end() {
    echo '</h2>';
  }
  #-----------------------------------------------------------------
  static function h3_start() {
    echo '<h3>';
  }
  #-----------------------------------------------------------------
	static function h3_end() {
    echo '</h3>';
  }
  #-----------------------------------------------------------------
	static function h4_start() {
    echo '<h4>';
  }
  #-----------------------------------------------------------------
	static function h4_end() {
    echo '</h4>';
  }
  #-----------------------------------------------------------------
	static function h5_start() {
    echo '<h5>';
  }
  #-----------------------------------------------------------------
	static function h5_end() {
    echo '</h5>';
  }
  #-----------------------------------------------------------------
  static function bold_start() {
    echo '<strong>';
  }
  #-----------------------------------------------------------------
  static function bold_end() {
    echo '</strong>';
  }
  #-----------------------------------------------------------------
  static function italic_start() {
    echo '<em>';
  }
  #-----------------------------------------------------------------
  static function italic_end() {
    echo '</em>';
  }
  #-----------------------------------------------------------------

  static function linebreak( $parms = NULL ) {

    echo '<br ',$parms, '/>';
  }

  #-----------------------------------------------------------------

  static function space() {

    echo SPACE;
  }

  #-----------------------------------------------------------------

	static function comment( $parms = NULL ) {

    echo '<!-- ';
    if( $parms ) echo $parms;
    echo '-->' . NEWLINE;
  }
  #-----------------------------------------------------------------

  static function new_paragraph( $parms = NULL ) {

    echo '<p ', $parms, '/>';
  }
  #-----------------------------------------------------------------

	static function div_start( $parms = NULL ) {

    echo NEWLINE . '<div ';
    if( $parms ) echo $parms;
    echo '>' . NEWLINE;
  }
  #-----------------------------------------------------------------

	static function div_end( $div_class = NULL, $div_id = NULL ) {

    echo NEWLINE . '</div>';
    echo NEWLINE;
    if( $div_class ) HTML::comment("End class $div_class");
    if( $div_id )    HTML::comment("End ID $div_id");
  }
  #-----------------------------------------------------------------

  static function span_start( $parms = NULL ) {

    echo NEWLINE . '<span ';
    if( $parms ) echo $parms;
    echo '>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  static function span_end( $parms = NULL ) {

    echo NEWLINE . '</span>';
    echo NEWLINE;
    if( $parms ) HTML::comment("End $parms");
  }
  #-----------------------------------------------------------------

  static function form_start( $class_name = NULL, $method_name = NULL, $form_name = '', $form_target = '',
                       $onsubmit_validation = FALSE, $form_destination='', $form_method='POST',
                       $parms = NULL ) {

    echo '<form action="';

    if( $form_destination != '' ) 
      echo $form_destination;
    else
      echo $_SERVER['PHP_SELF'];
    echo '" ';

    if( "$form_name" != "" ) {
      echo ' name="' . $form_name . '" ';
      echo ' id="' . $form_name . '" ';
    }
    elseif( "$method_name" != "" ) {
      $form_name = $class_name . '_' . $method_name;
      echo ' name="' . $form_name . '" ';
      echo ' id="' . $form_name . '" ';
    }

    switch( strtolower( $form_target )) {
     case '_self':
     case '_blank':
     case '_top':
     case '_parent':
       echo ' target="' . strtolower( $form_target ) . '" ';
       break;
     default:
       break;
    }

    if( $onsubmit_validation ) echo ' onsubmit="return js_check_form_valid( this )" ' ;
    
    if( $parms ) echo ' ' . $parms . ' ';

    echo '  method="' . $form_method . '">';
    echo NEWLINE;

    if( $class_name ) {
        echo '<input type="hidden" name="class_name"  value="';
        echo HTML::call_htmlentities( $class_name );
        echo '" />';
    }

    if( $method_name ) {
        echo '<input type="hidden" name="method_name"  value="';
        echo HTML::call_htmlentities( $method_name );
        echo '" />';
    }

    if( array_key_exists( 'session_token', $_SESSION ) && $_SESSION['session_token'] ) {
      echo '<input type="hidden" name="' . SESSION_TOKEN_FIELD . '"  value="';
      echo HTML::call_htmlentities( $_SESSION['session_token'] );
      echo '" />';
    }

    echo '<input type="hidden" name="requires_validation" id="requires_validation" value="0">';
    echo '<input type="hidden" name="validation_err" id="validation_err" value="0">';
    echo '<input type="hidden" name="cancel_submission" id="cancel_submission" value="0">';

    return $form_name;
  }
  #-----------------------------------------------------------------

	static function form_end() {
    echo '</form>';
  }
  #-----------------------------------------------------------------

  static function link( $href, $displayed_text, $title = '', $target = '_self', $accesskey = '', $tabindex = 1,
                 $extra_parms = NULL ) {

    echo '<a href="', $href, '" ';

    if( $title != '' )     echo ' title="', $title, '" ';
    if( $target != '' )    echo ' target="', $target, '" ';
    if( $accesskey != '' ) echo ' accesskey="', $accesskey, '" ';
    if( $tabindex > 0 )    echo ' tabindex="', $tabindex, '" ';

    if( $extra_parms ) echo ' ', $extra_parms, ' ';

    echo '>';

    echo $displayed_text;

    echo '</a>';
  }
  #-----------------------------------------------------------------

	static function return_link( $href, $displayed_text, $title = '', $target = '_self', $accesskey = '', $tabindex = 1,
                        $extra_parms = NULL ) {

    $linkval = '<a href="' . $href . '" ';

    if( $title != '' )     $linkval = $linkval . ' title="' . $title . '" ';
    if( $target != '' )    $linkval = $linkval . ' target="' . $target . '" ';
    if( $accesskey != '' ) $linkval = $linkval . ' accesskey="' . $accesskey . '" ';
    if( $tabindex > 0 )    $linkval = $linkval . ' tabindex="' . $tabindex . '" ';

    if( $extra_parms ) echo ' ' . $extra_parms . ' ';

    $linkval = $linkval . '>';

    $linkval = $linkval . $displayed_text;

    $linkval = $linkval . '</a>';
    return $linkval;
  }
  #-----------------------------------------------------------------

	static function link_start( $href, $title = '', $target = '_self', $accesskey = '', $tabindex = 1,
                       $extra_parms = NULL ) {

    echo '<a href="' . $href;

    # Add session token to all links which take you to another page.
    #if( ! strstr( $href, '#' )) { # not a local link within the same page
    #  if( strstr( $href, '?' ))   # there is already one parameter in the href
    #    echo '&' . SESSION_TOKEN_FIELD . '=' . rawurlencode( $_SESSION['session_token'] );
    #  else
    #    echo '?' . SESSION_TOKEN_FIELD . '=' . rawurlencode( $_SESSION['session_token'] );
    #}
    echo '" ';

    if( $title != '' )     echo ' title="' . $title . '" ';
    if( $target != '' )    echo ' target="' . $target . '" ';
    if( $accesskey != '' ) echo ' accesskey="' . $accesskey . '" ';
    if( $tabindex > 0 )    echo ' tabindex="' . $tabindex . '" ';

    if( $extra_parms ) echo ' ' . $extra_parms . ' ';

    echo '>' . NEWLINE;
  }
  #-----------------------------------------------------------------

	static function link_end() {
    echo '</a>';
  }
  #-----------------------------------------------------------------

  static function anchor( $anchor_name = NULL ) {

    if( $anchor_name != NULL ) {
      echo '<a name="' . $anchor_name . '" id="' . $anchor_name . '" ></a>';
    }
  }
  #-----------------------------------------------------------------

  static function field_id_from_fieldname( $fieldname, $input_instance = 0 ) {

    $field_id = $fieldname;
    if( $input_instance ) {
      if( substr( $field_id, -2 ) == '[]' ) # it's a PHP array, but not a valid ID for CSS
        $field_id = substr( $field_id, 0, -2 );
      $field_id = $field_id . $input_instance;
    }
    return $field_id;
  }
  #-----------------------------------------------------------------

	static function hidden_field( $fieldname, $value = NULL, $input_instance = 0 ) {

    echo NEWLINE;

    $field_id = HTML::field_id_from_fieldname( $fieldname, $input_instance );

    echo '<input type="hidden" name="' . $fieldname . '" id="' . $field_id . '"  value="';
    echo HTML::call_htmlentities( $value );
    echo '" />' . NEWLINE;

  }
  #-----------------------------------------------------------------

  static function input_field( $fieldname, $label = '', $value = NULL, $in_table = FALSE, $size = NULL, $tabindex=1,
                        $label_parms = NULL, $data_parms = NULL, $input_parms = NULL, $input_instance = 0,
                        $trailing_text = NULL ) {

    $field_id = HTML::field_id_from_fieldname( $fieldname, $input_instance );

    echo NEWLINE;
    if( $in_table ) { 
      echo '<td';
      if( $label_parms ) echo ' ' . $label_parms . ' ';
      echo '>';
    }

    echo '<label for="' . $field_id . '">';
    $label = trim( $label );
    echo $label;
    $last_char = substr( $label, -1 );
    if( $label != '' && $last_char != ':' && $last_char != '.' && $last_char != '*' && $last_char != '£' ) {
		echo ':';
	}
    echo ' </label>';

    if( $in_table ) {
      echo '</td>';
      echo '<td';
      if( $data_parms ) echo ' ' . $data_parms . ' ';
      echo '>';
    }

    echo '<input type="text" name="' . $fieldname . '" id="' . $field_id . '" value="';
    echo HTML::call_htmlentities( $value );
    echo '"';
    if( $size != NULL ) echo ' size="' . $size . '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    if( $input_parms != NULL ) echo " $input_parms ";
    echo ' />';

    if( $trailing_text ) echo ' ' . $trailing_text;

    if( $in_table ) echo '</td>';
    echo NEWLINE;

  }
  #-----------------------------------------------------------------

	static function password_field( $fieldname, $label = '', $in_table = FALSE, $size = NULL, $tabindex=1 ) {

    echo NEWLINE;
    if( $in_table ) echo '<td>';

    echo '<label for="' . $fieldname . '">';
    $label = trim( $label );
    echo $label;
    $last_char = substr( $label, -1 );
    if( $label != '' && $last_char != ':' && $last_char != '.' ) echo ':';
    echo ' </label>';

    if( $in_table ) echo '</td><td>';

    echo '<input type="password" name="' . $fieldname . '" id="' . $fieldname . '" value=""';

    if( $size != NULL ) echo ' size="' . $size . '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    echo ' />';

    if( $in_table ) echo '</td>';
    echo NEWLINE;

  }
  #-----------------------------------------------------------------

	static function checkbox( $fieldname, $label, $is_checked = NULL, $value_when_checked = 1, $in_table = FALSE,
                     $tabindex=1, $input_instance = NULL, $parms = NULL, $label_on_left = FALSE ) {

    $field_id = HTML::field_id_from_fieldname( $fieldname, $input_instance );

    echo NEWLINE;
    if( $in_table ) echo '<td>';

    if( $label_on_left ) {
      echo '<label for="' . $field_id . '">';
      $label = trim( $label );
      echo $label;
      echo '</label>';
    }

    echo '<input type="checkbox" name="' . $fieldname . '" id="' . $field_id . '" value="';
    echo HTML::call_htmlentities( $value_when_checked );
    echo '"';
    if( $is_checked ) echo ' CHECKED ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    if( $parms ) echo ' ' . $parms . ' ';
    echo ' />';

    if( $in_table ) echo '</td><td>';

    if( ! $label_on_left ) {
      echo '<label for="' . $field_id . '">';
      $label = trim( $label );
      echo $label;
      echo '</label>';
    }

    if( $in_table ) echo '</td>';
    echo NEWLINE;

  }
  #-----------------------------------------------------------------

	static function checkbox_with_label_on_left( $fieldname, $label, $is_checked = NULL, $value_when_checked = 1,
                                        $in_table = FALSE, $tabindex=1, $input_instance = NULL, $parms = NULL ) {

    HTML::checkbox( $fieldname, $label, $is_checked, $value_when_checked, 
                    $in_table, $tabindex, $input_instance, $parms, $label_on_left = TRUE );
  }
  #-----------------------------------------------------------------

	static function radio_button( $fieldname, $label, $value_when_checked, $current_value = 0, $tabindex=1,
                         $button_instance=0, $script=NULL ) {

    echo NEWLINE;

    $id = $fieldname;
    if( $button_instance ) $id = $id . $button_instance;
 
    echo '<label for="' . $id . '">';

    echo '<input type="radio" name="' . $fieldname . '" id="' . $id . '" value="';
    echo HTML::call_htmlentities( $value_when_checked );
    echo '"';
    if( $current_value == $value_when_checked ) echo ' CHECKED ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';

    if( $script ) echo ' ' . $script . ' ';

    echo ' />';

    $label = trim( $label );
    echo $label;
    echo '</label>';

    echo NEWLINE;

  }
  #-----------------------------------------------------------------

	static function printed_checkbox( $fieldname, $label, $is_checked = NULL, $value_when_checked = NULL, $in_table = FALSE,
                             $tabindex=1 ) {
    echo '[';
    if( $is_checked )
      echo ' X ';
    else
      echo SPACE . SPACE;
    echo ']=';
    echo $label;
  }
  #-----------------------------------------------------------------

  static function dropdown_start( $fieldname, $label, $in_table = FALSE, $script = NULL, $tabindex=1, 
                           $label_parms = NULL, $input_instance = 0 ) {

    echo NEWLINE;

    $field_id = HTML::field_id_from_fieldname( $fieldname, $input_instance );

    if( $in_table ) echo '<td ' . $label_parms . ' >';
    if( strlen( $label ) > 0 ) echo '<label for="' . $fieldname . '">';
    $label = trim( $label );
    echo $label;
    $last_char = substr( $label, -1 );
    if( strlen($label) > 0 && $last_char != ':' && $last_char != '.' && $last_char != '*' ) echo ':';
    if( strlen( $label ) > 0 ) echo ' </label>';

    if( $in_table ) echo '</td><td>';

    echo '<select name="' . $fieldname . '" id="' . $field_id . '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" '; 

    if( $script ) echo $script;  # N.B. You need the "on" event and the actual script in double quotes

    echo ' >';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  static function dropdown_option( $internal_value, $displayed_value, $selection = NULL, $parms = NULL ) {

    echo '<option value="' . $internal_value . '" ';
    if( $selection == $internal_value ) echo ' selected';
    if( $parms ) echo ' ' . $parms . ' ';
    echo '>';
    echo $displayed_value;
    echo '</option>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

	static function dropdown_optgroup_start( $label = NULL ) {
    echo NEWLINE;
    echo '<optgroup label="';
    echo $label;
    echo '">';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

	static function dropdown_optgroup_end() {

    echo NEWLINE;
    echo '</optgroup>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  static function dropdown_end( $in_table = FALSE ) {
    echo NEWLINE;
    echo '</select>';
    if( $in_table ) echo '</td>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

	static function textarea_start( $fieldname, $rows = 3, $cols = 50, $label=NULL, $textarea_parms=NULL, $label_parms=NULL,
                           $input_instance = NULL ) {

    echo NEWLINE;

    $field_id = HTML::field_id_from_fieldname( $fieldname, $input_instance );

    if( $label ) {
      echo '<label for="' . $field_id . '"';
      if( $label_parms ) echo ' ' . $label_parms . ' ';
      echo '>';
      $label = trim( $label );
      echo $label;
      $last_char = substr( $label, -1 );
      if( $label != '' && $last_char != ':' && $last_char != '.' && $last_char != '*' && $last_char != '£' ) echo ':';
      echo ' </label>';
    }

    echo '<textarea name="' . $fieldname . '" id="' . $field_id . '" rows="' . $rows . '" cols="' . $cols . '" ';
    if( $textarea_parms ) echo $textarea_parms;
    echo ' tabindex="1" >';
  }
  #-----------------------------------------------------------------

	static function textarea_end() {

    echo '</textarea>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------


  static function textarea( $fieldname, $rows=3, $cols=50, $value=NULL, $label=NULL, $textarea_parms=NULL, $label_parms=NULL,
                     $input_instance = NULL )
  {

    echo NEWLINE;

    $field_id = HTML::field_id_from_fieldname( $fieldname, $input_instance );

    if( $label ) {
      echo '<label for="' . $field_id . '"';
      if( $label_parms ) echo ' ' . $label_parms . ' ';
      echo '>';
      $label = trim( $label );
      echo $label;
      $last_char = substr( $label, -1 );
      if( $label != '' && $last_char != ':' && $last_char != '.' && $last_char != '*' && $last_char != '£' ) echo ':';
      echo ' </label>';
    }

    echo '<textarea name="' . $fieldname . '" id="' . $field_id . '" rows="' . $rows . '" cols="' . $cols . '" ';
    if( $textarea_parms ) echo $textarea_parms;
    echo ' tabindex="1" >';
    echo $value;
    echo '</textarea>';
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

	static function submit_button( $button_name = 'ok_button', $value = 'OK', $tabindex = 1, $other_parms = NULL ) {

    echo NEWLINE;

    echo '<input type="submit" name="' . $button_name . '"  value="';
    echo HTML::call_htmlentities( $value );
    echo '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    if( $other_parms ) 
      echo ' ' . $other_parms . ' ';
    elseif( $button_name == 'save_button' )
      echo ' accesskey="S" ';
    echo ' />' . NEWLINE;

  }
  #-----------------------------------------------------------------

	static function submit_and_cancel_button( $button_name = 'cancel_button', $value = 'Cancel', $tabindex = 1,
                                     $other_parms = NULL ) {

    echo NEWLINE;

    echo '<input type="submit" name="' . $button_name . '"  value="';

    echo HTML::call_htmlentities( $value );
    echo '" ';

    #----------------------------------------
    # Cancel the form's "onsubmit" validation
    #----------------------------------------
    echo  ' onclick="js_drop_form_validation( this )" ';

    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';

    if( $other_parms ) 
      echo ' ' . $other_parms . ' ';

    echo ' />' . NEWLINE;

  }
  #-----------------------------------------------------------------

	static function cancel_button( $button_name = 'cancel_button', $value = 'Cancel', $tabindex = 1 ) {

    echo NEWLINE;

    echo '<input type="reset" name="' . $button_name . '"  value="';
    echo HTML::call_htmlentities( $value );
    echo '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    echo ' />' . NEWLINE;

  }
  #-----------------------------------------------------------------

	static function button( $button_name = 'button', $value = 'Button', $tabindex = 1, $other_parms = NULL ) {

    echo NEWLINE;

    echo '<input type="button" name="' . $button_name . '"  value="';
    echo HTML::call_htmlentities( $value );
    echo '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    if( $other_parms ) 
      echo ' ' . $other_parms . ' ';
    echo ' />' . NEWLINE;

  }
  #-----------------------------------------------------------------

	static function file_upload_field( $fieldname, $label = '', $value = NULL, $size = NULL, $tabindex=1,
                              $label_parms = NULL, $input_parms = NULL, $input_instance = 0 ) {

    $field_id = HTML::field_id_from_fieldname( $fieldname, $input_instance );

    echo NEWLINE;

    echo '<label for="' . $field_id . '">';
    $label = trim( $label );
    echo $label;
    $last_char = substr( $label, -1 );
    if( $label != '' && $last_char != ':' && $last_char != '.' && $last_char != '*' && $last_char != '£' ) echo ':';
    echo ' </label>';

    echo '<input type="file" name="' . $fieldname . '" id="' . $field_id . '" value="';
    echo HTML::call_htmlentities( $value );
    echo '"';
    if( $size != NULL ) echo ' size="' . $size . '" ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    if( $input_parms != NULL ) echo " $input_parms ";
    echo ' />';

    echo NEWLINE;

  }
  #-----------------------------------------------------------------

	static function multiple_file_upload_field( $fieldname, $label = '', $value = NULL, $size = NULL,
                                       $tabindex=1, $label_parms = NULL, $input_parms = NULL, 
                                       $input_instance = 0 ) {

    if( $input_parms )
      $input_parms .= ' multiple="true"';
    else
      $input_parms = 'multiple="true"';

    HTML::file_upload_field( $fieldname, $label, $value, $size, $tabindex,
                              $label_parms, $input_parms, $input_instance );
  }
  #-----------------------------------------------------------------

  static function label( $label_text = NULL, $label_id = NULL, $parms = NULL ) {  # free-standing label, not necessarily attached to field

    echo '<label ';
    if( $label_id ) echo ' id="' . $label_id . '" ';
    if( $parms ) echo ' ' . $parms . ' ';
    echo '>';

    echo $label_text;

    echo '</label>' . NEWLINE;
  }
  #-----------------------------------------------------------------

  static function ulist_start( $parms = NULL ) {
    echo '<ul ',$parms, '>';
  }
  #-----------------------------------------------------------------

  static function ulist_end() {
    echo '</ul>';
  }
  #-----------------------------------------------------------------

  static function listitem_start( $parms = NULL ) {
    echo '<li ', $parms, '>';
  }
  #-----------------------------------------------------------------

  static function listitem_end() {
    echo '</li>';
  }
  #-----------------------------------------------------------------

  static function listitem( $the_value = NULL, $parms = NULL ) {
    echo '<li ', $parms, '>', $the_value, '</li>';
  }
  #-----------------------------------------------------------------

  static function horizontal_rule( $parms = NULL ) {

    echo NEWLINE, '<hr ', $parms, '/>', NEWLINE;
    echo LINEBREAK;
  }
  #-----------------------------------------------------------------

	static function bullet_point() {
    echo ' &bull; ';
  }
  #-----------------------------------------------------------------

	static function return_bullet_point() {
    return ' &bull; ';
  }
  #-----------------------------------------------------------------

	static function table_start( $parms = NULL ) {
    echo '<table ', $parms, '>';
  }
  #-----------------------------------------------------------------

	static function table_end() {
    echo '</table>';
  }
  #-----------------------------------------------------------------

	static function table_caption( $caption = NULL, $attribs = NULL, $style = 'italic' ) {
    echo '<caption ', $attribs, '>';

    if( $style == 'italic' )
      echo '<i>';
    elseif( $style == 'bold' )
      echo '<b>';

    echo $caption;

    if( $style == 'italic' )
      echo '</i>';
    elseif( $style == 'bold' )
      echo '</b>';

    echo '</caption>';
  }
  #-----------------------------------------------------------------

  static function tablerow_start( $parms = NULL ) {
    echo '<tr ', $parms, '>';
  }
  #-----------------------------------------------------------------

  static function tablerow_end() {
    echo '</tr>';
  }
  #-----------------------------------------------------------------

	static function new_tablerow( $parms = NULL ) {  # end current table row and start a new one

    echo '</tr><tr ',$parms,'>';
  }
  #-----------------------------------------------------------------

  static function tabledata_start( $parms = NULL ) {
    echo '<td ', $parms, '>';
  }
  #-----------------------------------------------------------------

  static function tabledata_end() {
    echo '</td>';
  }
  #-----------------------------------------------------------------

  static function tabledata( $the_data = NULL, $parms = NULL ) {
    echo '<td ', $parms, '>';
    echo $the_data;
    if( "$the_data" == "" ) echo SPACE;
    echo '</td>';
  }
  #-----------------------------------------------------------------

	static function table_head_start( $parms = NULL ) {
    echo '<thead';
    if ( $parms ) echo ' ' . $parms;
    echo '>';
  }
  #-----------------------------------------------------------------

  static function table_head_end() {
    echo '</thead>';
  }
  #-----------------------------------------------------------------

  static function table_body_start( $parms = NULL ) {
    echo '<tbody ', $parms, '>';
  }
  #-----------------------------------------------------------------

  static function table_body_end() {
    echo '</tbody>';
  }
  #-----------------------------------------------------------------

  static function column_header( $label = NULL, $parms = NULL ) {

    echo '<th ', $parms, '>';

    echo $label;
    if( "$label" == "" ) echo SPACE;

    echo '</th>';
  }
  #-----------------------------------------------------------------

	static function page_top_anchor() {

    echo '<a name="', PAGE_TOP, '" ></a>';
  }
  #-----------------------------------------------------------------

	static function page_bottom_anchor() {

    echo '<a name="', PAGE_BOTTOM, '" ></a>';
  }
  #-----------------------------------------------------------------

	static function link_to_page_top( $tabindex = 1, $title = 'Top of Page', $accesskey = '' ) {

    echo NEWLINE;
    echo '<A href="#' . PAGE_TOP  . '" target="_self" ';
    if( $title != '' )     echo ' title="' . $title . '" ';
    if( $accesskey != '' ) echo ' accesskey="' . $accesskey . '" ';
    if( $tabindex > 0 )    echo ' tabindex="' . $tabindex . '" ';
    echo '>' . NEWLINE;
    echo $title;
    echo NEWLINE . '</A>' . NEWLINE;
    if( $accesskey != '' ) echo ' (shortcut key ' . $accesskey . ')';
  }
  #-----------------------------------------------------

	static function link_to_page_bottom( $tabindex = 1, $title = 'Bottom of Page', $accesskey = '' ) {

    echo NEWLINE;
    echo '<A href="#' . PAGE_BOTTOM  . '" target="_self" ';
    if( $title != '' )     echo ' title="' . $title . '" ';
    if( $accesskey != '' ) echo ' accesskey="' . $accesskey . '" ';
    if( $tabindex > 0 )    echo ' tabindex="' . $tabindex . '" ';
    echo '>' . NEWLINE;
    echo $title;
    echo NEWLINE . '</A>' . NEWLINE;
    if( $accesskey != '' ) echo ' (shortcut key ' . $accesskey . ')';
  }
  #-----------------------------------------------------

	static function jump_to_work( $tabindex = 1, $title = 'Jump to Work', $accesskey = '' ) {

        echo NEWLINE;
        echo '<input type="text" name="jump-to-work" id="jump-to-work" value="" placeholder="Work ID" style="border:1px solid #bbb;height:19px;width:73px;margin-left: 25px"/><button onclick="jumpToWork()">Goto</button>';
        echo '<script>function jumpToWork() {
	            var workid = document.getElementById("jump-to-work").value.replace(/\D/g,\'\');
                window.location = document.location.pathname + "?iwork_id=" + workid;
              }</script>';
    }
    #-----------------------------------------------------

	static function small_start() {
    echo '<small>';
  }
  #-----------------------------------------------------

	static function small_end() {
    echo '</small>';
  }
  #-----------------------------------------------------

	static function small( $the_text ) {
    echo '<small>';
    echo $the_text;
    echo '</small>';
    echo NEWLINE;
  }
  #-----------------------------------------------------

	static function write_javascript_function( $script_body ) {

    echo NEWLINE;
    echo '<script type="text/javascript">';
    echo NEWLINE;
    echo '<!--';
    echo NEWLINE;

    echo $script_body;
    echo NEWLINE;

    echo '// -->';
    echo NEWLINE;
    echo '</script>';
    echo NEWLINE;
  }
  #-----------------------------------------------------
	static function fieldset_start( $legend, $fieldset_name = NULL, $parms = NULL, $legend_parms = NULL ) {

    echo NEWLINE;
    echo '<fieldset ';
    if( $fieldset_name ) echo ' id="' . $fieldset_name . '" ';
    if( $parms ) echo ' ' . $parms . ' ';
    echo ' >';

    # Legend
    echo NEWLINE;
    echo '<legend ';
    if( $fieldset_name ) echo ' id="' . $fieldset_name . '_legend" ';
    if( $legend_parms ) echo ' ' . $legend_parms . ' ';
    echo ' >';
    echo HTML::call_htmlentities( $legend );
    echo '</legend>';
    
    echo NEWLINE;
  }
  #-----------------------------------------------------

	static function fieldset_end( $desc ) {
    echo NEWLINE;
    echo '</fieldset>';
    if( $desc ) HTML::comment( 'End ' . $desc );
    echo NEWLINE;
  }
  #-----------------------------------------------------

	static function echo_quote() {
		// https://github.com/rakibtg/PHP-random-quotes-generator
		$quotes = json_decode( file_get_contents( __DIR__ . '/quotes.json' ), false );
		$quote = $quotes[mt_rand( 0, count( $quotes ) )];

		$author = $quote->author;
		if( $author == '' ) {
			$author = "Unknown";
		}
		echo '<blockquote><q>' . $quote->text . '</q><footer>— ' . $author . ' told me that.</footer></blockquote>';
	}

	static function echo_bot( $number ) {
		if( !$number ) {
			$number = 41;
		}
		echo '<img src="https://robohash.org/' . count($number) . '.png?set=set3" width="100" height="100">';
	}

	static function echo_bot_quote( $number ) {
		echo '<div>';

			echo '<div style="float:left">';
			HTML::echo_bot($number);
			echo '</div>';

			echo '<div><br/>';
			HTML::echo_quote();
			echo '</div>';

		echo '</div>';
	}
}
