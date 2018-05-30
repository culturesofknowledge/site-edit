<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php

define( 'CFG_PREFIX', 'cofk' );
define( 'CFG_SYSTEM_TITLE', 'Reformat Selden End material for front end website' );
define( 'CULTURES_OF_KNOWLEDGE_MAIN_SITE', 'http://www.history.ox.ac.uk/cofk/' );

define( 'CONSTANT_DATABASE_TYPE', 'live' ); # database type is irrelevant as we are working from a file
define( 'MAX_LINE_LENGTH', 1000000 );

require_once "common_components.php";
require_once "proj_components.php";

$tags = array(
    '<em>',
    '</em>',
    '<strong>',
    '</strong>',
    '<p>',
    '</p>',
    '<lb/>',
    '<note>',
    '</note>',
    '<blockquote>',
    '</blockquote>',
    '<entry>',
    '</entry>',
    '<table>',
    '</table>',
    '<row>',
    '</row>',
    '<tbody>',
    '</tbody>',
    '<emph render="bold">',
    '<emph render=""bold"">',
    '<emph render="italic">',
    '<emph render=""italic"">',
    '<emph render="underline">',
    '<emph render=""underline"">',
    '<emph render="super">',
    '<emph render=""super"">',
    '<emph render=""altrender"" altrender=""foreign"">',
    '</emph>' 
);

$infile = getenv( 'COFK_CSV_FILE' );
if( ! $infile ) die( "Set infile in environment variable COFK_CSV_FILE!" );
$outfile = $infile . '_new';
echo "Input file = $infile" . NEWLINE;
echo "Output file = $outfile" . NEWLINE;

$db_connection = new DBQuery ( 'postgres' );
$cofk = new Project( $db_connection );

$inhand = fopen( $infile, 'r' );
$outhand = fopen( $outfile, 'w' );

$i = 0;
$line = fgets( $inhand, MAX_LINE_LENGTH );
while( $line ) {
  $i++;
  if( $i % 1000 == 0 ) echo ' ' . $i;
  $line = $cofk->app_reinstate_foreign_characters( $line );

  # Will also need to get rid of other HTML tags and entities as far as possible
  # because '<p>', '&amp;' etc are all being visibly displayed on the front end at present.
  $line = html_entity_decode( $line, ENT_QUOTES, 'UTF-8' );

  foreach( $tags as $tag ) {
    switch( $tag ) {

      case '<note>':
        $replacement = ' [';
        break;
      case '</note>':
        $replacement = '] ';
        break;

      case '<blockquote>':
        $replacement = ' ""';
        break;
      case '</blockquote>':
        $replacement = '"" ';
        break;

      default:
        $replacement = ' ';
    }
    $line = str_replace( $tag, $replacement, $line );
  }

  fwrite( $outhand, $line );
  $line = fgets( $inhand, MAX_LINE_LENGTH );
}
fclose( $inhand );
fclose( $outhand );
echo NEWLINE;
echo 'Finished' . NEWLINE;
?>

