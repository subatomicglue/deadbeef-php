
<?php

   /*
   deadbeef - fancy pants webpage renderer
   Copyright (c) 2006 kevin meinert all rights reserved

   This library is free software; you can redistribute it and/or
   modify it under the terms of the GNU Lesser General Public
   License as published by the Free Software Foundation; either
   version 2.1 of the License, or (at your option) any later version.

   This library is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
   Lesser General Public License for more details.

   You should have received a copy of the GNU Lesser General Public
   License along with this library; if not, write to the Free Software
   Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
   02110-1301  USA
   */
   
function lerp( $a, $b, $lerpval )
{
   return $a * (1 - $lerpval) + $b * $lerpval;
}

function clamp( $val, $lo, $hi )
{
   return max( $lo, min( $val, $hi ) );
}
   
/// return RGB (array of 3)
/// maps $value [0..1] to an interpolated color within the range of $colors
function tonemap( $colors, $value )
{
   $size = count( $colors );
   $value = clamp( $value, 0, 1 );
   $value_int = intval( $value );
   $value_int_float = floatval( $value_int );
   $value_size_int = intval( $value * $size );
   $index_2 = min( $size-1, $value_size_int + 1 );
   $lo = $colors[ $value_size_int ];
   $hi = $colors[ $index_2 ];
   $RGB = array( intval( lerp( $lo[0], $hi[0], $value - $value_int_float )),
               intval( lerp( $lo[1], $hi[1], $value - $value_int_float )),
               intval( lerp( $lo[2], $hi[2], $value - $value_int_float )) 
            );
   //echo $value . " " . $value_int_float . "\n";
   //   echo $RGB[0] . " " . $RGB[1] . " " . $RGB[2] . "\n";
   return $RGB;
}

// gen one "xx" byte glyph, give an array of RGB[3]'s for tonemap...
function genbyte( $tm_colors )
{
      $value = rand( 0, 255 );
      $rgb = tonemap( $tm_colors, $value / 255.0 );
      //echo $rgb[0] . " " . $rgb[1] . " " . $rgb[2] . "\n";
      return "<font color=\"#". str_pad( dechex( $rgb[0] ), 2, "00", STR_PAD_LEFT ) 
                           . str_pad( dechex( $rgb[1] ), 2, "00", STR_PAD_LEFT )
                           . str_pad( dechex( $rgb[2] ), 2, "00", STR_PAD_LEFT )
                           . "\">" . str_pad( dechex( $value ), 2, "00", STR_PAD_LEFT ) 
                           . "</font>";
}


// gen half a byte glyph - "x". give an array of RGB[3]'s for tonemap...
function gennibble( $tm_colors )
{
      $value = rand( 0, 15 );
      $rgb = tonemap( $tm_colors, $value / 255.0 );
      return "<font color=\"#". str_pad( dechex( $rgb[0] ), 2, "00", STR_PAD_LEFT ) 
                           . str_pad( dechex( $rgb[1] ), 2, "00", STR_PAD_LEFT )
                           . str_pad( dechex( $rgb[2] ), 2, "00", STR_PAD_LEFT )
                           . "\">" . str_pad( dechex( $value ), 1, "0", STR_PAD_LEFT ) 
                           . "</font>";
}

// generate one line of hexl33tOMGbaconROFLMFAO text
function genline( $tm_colors, $cols, $row, $rows )
{
   $s = "";
   $cols = $cols / 2;
   for ($x = 0; $x < $cols; $x += 1)
   {
      //$value = perlin( $x / $cols, $row / $rows, 1.0, 4.0 );
      $s .= genbyte( $tm_colors );
   }
   return $s;
}

/// get strlen of only the html text that is output on the screen
function getHtmlVisibleStrLen( $html_text )
{
   $text = preg_replace('/<[^>]*>/', "", $html_text);
   return strlen( $text );
}

/// given the text, find the offset to use, if the text is requesting to
/// override the default.
function detectOffsetOverride( $text, $offset, $cols )
{
   // centered text
   if (preg_match( "/<c>(.*?)<\/c>/", $text, $matches ))
   {
      $len = getHtmlVisibleStrLen( $matches[1] );
      return intval( ($cols - $len) / 2 );
   }
   // right justification
   else if (preg_match( "/<r(\s*pos=(\d+))?>(.*?)<\/r>/", $text, $matches ))
   {
      if (count( $matches ) == 1)
      {
         $len = getHtmlVisibleStrLen( $matches[1] );
      }
      else
      {
         $len = getHtmlVisibleStrLen( $matches[3] );
         $offset = $matches[2];
      }
      return $cols - $len - $offset;
   }
   // left justification
   else if (preg_match( "/<l(\s*pos=(\d+))?>(.*?)<\/l>/", $text, $matches ))
   {
      if (count( $matches ) == 1)
      {
         return $offset;
      }
      else
      {
         return $matches[2];
      }
   }
   return $offset;
}
// remove special formatting tags from text after placement
function cleanText( $text )
{
   $text = preg_replace('/<c>(.*?)<\/c>/', "\\1", $text);
   $text = preg_replace('/<r(\s*pos=(\d+))?>(.*?)<\/r>/', "\\3", $text);
   $text = preg_replace('/<l(\s*pos=(\d+))?>(.*?)<\/l>/', "\\3", $text);
   $text = preg_replace('/<h1>(.*?)<\/h1>/', "\\1", $text);
   return $text;
}

// call first to prepare the text.  adds newlines after certian special tags
function prepText( $text )
{
   $text = preg_replace('/<\/c>([^\n]+)/', "</c>\n\\1", $text);
   $text = preg_replace('/<\/l>([^\n]+)/', "</l>\n\\1", $text);
   $text = preg_replace('/<\/r>([^\n]+)/', "</r>\n\\1", $text);
   $text = preg_replace('/<\/h1>([^\n]+)/', "</h1>\n\\1", $text);
   $text = preg_replace('/([^\n]+)<c>/', "\\1\n<c>", $text);
   $text = preg_replace('/([^\n]+)(<l(\s*pos=(\d+))?>)/', "\\1\n\\2", $text);
   $text = preg_replace('/([^\n]+)(<r(\s*pos=(\d+))?>)/', "\\1\n\\2", $text);
   $text = preg_replace('/([^\n]+)<h1>/', "\\1\n<h1>", $text);
 
   return $text;
}
 

// these can be overridden by the user of deadbeefDrawPage()
$deadbeef_linkcolor = "#0090ff";
$deadbeef_alinkcolor = "#0090ff";
$deadbeef_vlinkcolor = "#0090ff";
$deadbeef_textcolor = "#ffffff";
$deadbeef_bgcolor = "#010101";
$deadbeef_tonemap = array( array(30,10,0), array(40,40,0), array(90,0,90), array(10, 30, 30 ) );
$deadbeef_title = "deadbeef";

// draw page - the main function to call to render your webpage.
// you can put the following special tags in your $text:
//  <c>some text</c>
//  <l>some text</l>
//  <r>some text</r>
// for 'l' and 'r' tags, you can also add a 'pos' attribute to change
// the indentation:
//  <l pos=16>some text</l>
// $text - the text to render, \n delimited lines of text, html tags
//         similar to <a href=> <b>, <i>, <blink> are ok...
// $textoffset - indentation from side margin to indent $text
// $cols - number of columns for the background
// $rows - number of rows for the background
//
// you can also set up custom page rendering attributes.  
// copy the deadbeef global variables into your php app to override these.
function deadbeefDrawPage( $text, $textoffset, $cols, $rows )
{
   global $deadbeef_linkcolor;
   global $deadbeef_alinkcolor;
   global $deadbeef_vlinkcolor;
   global $deadbeef_textcolor;
   global $deadbeef_bgcolor;
   global $deadbeef_tonemap;
   global $deadbeef_title;

   $text = prepText( $text );
   echo "<html><head>
   <title>$deadbeef_title</title>   
   </head>
   <body bgcolor=\"$deadbeef_bgcolor\" text=\"$deadbeef_textcolor\" link=\"$deadbeef_linkcolor\" alink=\"$deadbeef_alinkcolor\" vlink=\"$deadbeef_vlinkcolor\">
   <table border=0 width=100% height=100%>
   <tr valign=middle><td align=center><pre>\n";

   $textlines = explode( "\n", $text );
   $size_of_glyph = strlen( genbyte( $deadbeef_tonemap ) ); // size of one hex byte thingie
   $in_text = false;
   $page = "";
   for ($x = 0; $x < $rows; $x += 1)
   {
      $text2write = $textlines[$x];
      $text2write_len = getHtmlVisibleStrLen( $text2write );
      
      $detectedoffset = detectOffsetOverride( $text2write, $textoffset, $cols );
      // if offset is odd, add a char to the beginning, and ensure the offset is even
      if ($detectedoffset % 2)// odd
      {
         $text2write = gennibble( $deadbeef_tonemap ) . $text2write;
         $detectedoffset -= 1;
         $text2write_len = getHtmlVisibleStrLen( $text2write );
      }
      // if the string we're writing is odd, add a char to the end.
      if ($text2write_len % 2)// odd
      {
         $text2write .= gennibble( $deadbeef_tonemap );
         $text2write_len = getHtmlVisibleStrLen( $text2write );
      }
      $line_original = genline( $deadbeef_tonemap, $cols, $x, $rows );
      //echo $text2write . " " . $text2write_len . " " . $detectedoffset . "\n";
      $line = substr_replace( $line_original,
                            $text2write, 
                            ($detectedoffset/2) * $size_of_glyph,
                            ($text2write_len / 2) * $size_of_glyph );
      $page .= cleanText( $line ) . "\n";
   }

   echo $page;
   echo "</pre></td></tr></table></body></html>";
}

?>
