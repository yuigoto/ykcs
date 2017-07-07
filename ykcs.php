<?php
/**
 * YKCS : A simple color palette converter : Test Page
 * ======================================================================
 * The YKCS palette converter was built so I could easily convert a single GPL 
 * palette file into other palette formats to import in a certain range of 
 * graphic-related software. This way, I could have the same palettes, without 
 * having to type color values or use the eyedropper, no matter what graphic 
 * application I would use.
 * 
 * The original version of this script was focused on only my internal palette, 
 * the YKCS, so it used a very specific file format as an input.
 * 
 * This upgraded version uses a GPL palette format, with 3-column RGB values, 
 * as base for conversion and uses it to build palettes for use in the 
 * following formats:
 * - Adobe Color Format (ACO): RGB and CMYK;
 * - Corel Draw XML format: RGB and CMYK;
 * - Microsoft Expression Design XML: ARGB;
 * - LESS and SASS variable formats;
 * 
 * The color conversion methods came from my 'ZERO' PHP toolkit, hosted in:
 * - https://github.com/yuigoto/zero;
 * 
 * Algorithms to convert colors came from the web, but mostly from this gist:
 * - https://gist.github.com/felipesabino/5066336;
 * 
 * Also, the reference used to properly build the code for converting a 
 * palette and writing to the ACO format came mainly from these sources:
 * - http://www.nomodes.com/aco.html (specs about the ACO and other formats);
 * - http://stackoverflow.com/a/18283431 (about reading the ACO format);
 * 
 * IMPORTANT:
 * This class converts ONLY GPL palette files to other formats!
 * 
 * WHY YKCS?
 * It's a personal matter.
 * ----------------------------------------------------------------------
 * @author      Fabio Y. Goto <lab@yuiti.com.br>
 * @copyright   ©2016-2017 Fabio Y. Goto
 * @version     3.0.1
 * @license     MIT License
 */
class YKCS 
{
    /**
     * Associative array, holds the palette information.
     * 
     * There are two basic values:
     * - 'name': a string containing the palette name;
     * - 'slug': a URL-safe version of the palette name;
     * - 'guid': a unique GUID (used mostly on CorelDRAW's palettes);
     * - 'colors': an array, where each key is the color's name and its value 
     * is a simple, 1D, array containing the rgb values (indexes: 0, 1 and 2); 
     * 
     * @var array 
     */
    private $FILE_DATA;
    
    /**
     * Output path for the converted palettes.
     * 
     * @var string
     */
    private $OUTPUT_PATH = "output/";
    
    /**
     * File containing the source GPL-format palette.
     * 
     * @var string
     */
    private $SOURCE_FILE;
    
    /**
     * Stylesheet for the visual XML build.
     * 
     * @var string
     */
    private $STYLESHEET;
    
    
    
    /* Constructor / Destructor
     * --------------------------------------------------------------- */
    
    /**
     * Class constructor.
     * 
     * @param string $file 
     *      Path to a file containing a color palette in the GPL format, if not 
     *      declared, the class will use the default, public domain, Tango 
     *      Desktop Color palette
     */
    public function __construct( $file = "default/default.gpl" ) 
    {
        // If the output folder doesn't exist, create it
        if ( !is_dir( $this->OUTPUT_PATH ) ) mkdir( $this->OUTPUT_PATH );
        
        // Define source
        $this->SOURCE_FILE = ( trim( $file ) != "" ) 
                           ? trim( $file ) : "default/default.gpl";
        
        // Load stylesheet
        $this->STYLESHEET = file_get_contents( 'default/styles.xsl' );
        
        // Extract palette data into the FILE_DATA array
        $this->pullInfo();
    }
    
    
    
    /* Palette Builders
     * --------------------------------------------------------------- */
    
    /**
     * Builds every possible file within the class.
     * 
     * Does not return anything, only echoes the output
     */
    public function build() 
    {
        // Echo palette name
        echo '<h3>'.$this->FILE_DATA['name'].'</h3>';
        
        echo '<strong>Building ACO RGB:</strong> ';
        echo ( $this->buildACO() ) ? 'OK' : 'FAIL';
        echo '<br>';
        
        echo '<strong>Building ACO CMYK:</strong> ';
        echo ( $this->buildACO( true ) ) ? 'OK' : 'FAIL';
        echo '<br>';
        
        echo '<strong>Building Bitmap:</strong> ';
        echo ( $this->buildBitmap( true ) ) ? 'OK' : 'FAIL';
        echo '<br>';
        
        echo '<strong>Build Corel RGB:</strong> ';
        echo ( $this->buildCorel() ) ? 'OK' : 'FAIL';
        echo '<br>';
        
        echo '<strong>Build Corel CMYK:</strong> ';
        echo ( $this->buildCorel( true ) ) ? 'OK' : 'FAIL';
        echo '<br>';
        
        echo '<strong>Build Expression Design XML:</strong> ';
        echo ( $this->buildExpression() ) ? 'OK' : 'FAIL';
        echo '<br>';
        
        echo '<strong>Build LESS and SCSS RGB:</strong> ';
        echo ( $this->buildPrePros() ) ? 'OK' : 'FAIL';
        echo '<br>';
        
        echo '<strong>Build LESS and SCSS HEX:</strong> ';
        echo ( $this->buildPrePros( true ) ) ? 'OK' : 'FAIL';
        echo '<br>';
        
        echo '<strong>Build the Visual XML (NoIE):</strong> ';
        echo ( $this->buildVisualXML() ) ? 'OK' : 'FAIL';
        echo '<br>';
        
        echo '<strong>Build the Visual XML (IE):</strong> ';
        echo ( $this->buildVisualXML( true ) ) ? 'OK' : 'FAIL';
        
        echo '<br><br><small><strong>IMPORTANT:</strong> Don\'t open the CMYK ACO '
             .'palette on CorelDRAW, as it has a BUG that will wash out all ' 
             .'the colors when importing an ACO palette format in CMYK, RGB ' 
             .'is ok. This is a CorelDRAW bug, sadly.<br>' 
             .'<br>Check the output folder for all the converted files.' 
             .'</small>';
    }
    
    /**
     * Generates an ACO (Adobe Color) palette file, with a CMYK or RGB profile.
     * 
     * @param bool $cmyk 
     *      Should this compile the palette in CMYK format?
     * @return bool 
     *      TRUE on success, FALSE on failure 
     */
    public function buildACO( $cmyk = false ) 
    {
        // Define file name suffix
        $suff = ( $cmyk ) ? "-cmyk" : "-rgb";
        
        /**
         * ACO v1 data array.
         * 
         * @var array
         */
        $aco1 = array();
        
        /**
         * ACO v2 data array.
         * 
         * @var array
         */
        $aco2 = array();
        
        /**
         * The file output name.
         * 
         * @var string
         */
        $file = $this->FILE_DATA['slug'].$suff.'.aco';
        
        /**
         * The final output data holder. It'll be a binary data string.
         * 
         * @var string
         */
        $data = '';
        
        // Building data headers
        $bin1[] = "0001";
        $bin1[] = sprintf(
            "%04s", dechex( count( $this->FILE_DATA['colors'] ) )
        );
        $bin2[] = "0002";
        $bin2[] = sprintf(
            "%04s", dechex( count( $this->FILE_DATA['colors'] ) )
        );
        
        // Writing color values
        foreach ( $this->FILE_DATA['colors'] as $name => $vals ) {
            # DEFINE COLOR VALUE
            
            // First word for the color is always "0" for RGB, and "2" for CMYK
            $bin1[] = ( $cmyk ) ? "0002" : "0000";
            $bin2[] = ( $cmyk ) ? "0002" : "0000";
            
            // Define the color values for each type
            if ( $cmyk ) {
                // Define the HEX value for the current color
                $tint = $this->colorsDecimalToHex( $vals[0], $vals[1], $vals[2] );
                
                // Defining CMYK percentual values
                $tint = $this->colorsHexToProcess( $tint, false );
                
                // Converting CMYK do HEX values
                foreach( $tint as $part ) {
                    // Define current value
                    $curr = ( 1 - $part ) * 65535;
                    
                    $bin1[] = sprintf( "%04s", dechex( $curr ) );
                    $bin2[] = sprintf( "%04s", dechex( $curr ) );
                }
            } else {
                // Converting the RGB values to HEX values
                foreach( $vals as $tint ) {
                    $bin1[] = sprintf( "%04s", dechex( 65535 * ( $tint / 255 ) ) );
                    $bin2[] = sprintf( "%04s", dechex( 65535 * ( $tint / 255 ) ) );
                }
                
                // On RGB, last word for each color is always "0"
                $bin1[] = "0000";
                $bin2[] = "0000";
            }
            
            # DEFINE COLOR NAME (ACO 2 only)
            
            // Start populating the name (always start with "0")
            $bin2[] = "0000";
            
            // Define the length (+1) value of the color's name
            $bin2[] = sprintf( "%04s", dechex( strlen( $name ) + 1 ) );
            
            // Building the HEX values for the name, first unpacking the values
            $word = unpack( "H*", $name );
            // Then exploding each HEX character
            $word = explode( "\r\n", chunk_split( $word[1], 2 ) );
            
            // Adding each char
            foreach ( $word as $bits ) {
                if ( trim( $bits ) != '' ) {
                    $bin2[] = sprintf( "%04s", $bits );
                }
            }
            
            // Close the name with a "0"
            $bin2[] = "0000";
        }
        
        // Building the first fragment
        foreach ( $bin1 as $bin1bits ) {
            // Adds the word, if not empty
            if ( trim( $bin1bits ) != '' ) $data.= hex2bin( $bin1bits );
        } 
        
        // Building the second fragment
        foreach ( $bin2 as $bin2bits ) {
            // Adds the word, if not empty
            if ( trim( $bin2bits ) != '' ) $data.= hex2bin( $bin2bits );
        }
        
        // Saving the file
        if ( $open = fopen( $this->OUTPUT_PATH.$file, 'w+' ) ) {
            // Write the binary data and close
            fwrite( $open, $data );
            fclose( $open );
            
            // Return true
            return TRUE;
        }
        
        // Return false
        return FALSE;
    }
    
    /**
     * Generates a bitmap containing all the colors in the palette.
     * 
     * @return bool 
     *      TRUE on success, FALSE on failure 
     */
    public function buildBitmap() 
    {
        // Define image width (10px = 10 colors on each line)
        $imgW = 10;
        // Define image height
        $imgH = ceil( count( $this->FILE_DATA['colors'] ) / $imgW );
        // Creating image resource
        $imgs = imagecreatetruecolor( $imgW, $imgH );
        // Fill the image with white
        $fill = imagecolorallocate( $imgs, 255, 255, 255 );
        imagefill( $imgs, 0, 0, $fill );
        // Define color index for the array
        $_idx = 0;
        // Writing colors
        for ( $y = 0; $y < $imgH; $y++ ) {
            for ( $x = 0; $x < $imgW; $x++ ) {
                // If the color has been set
                if ( isset( $this->FILE_DATA['index'][$_idx] ) ) {
                    // Get the name
                    $name = $this->FILE_DATA['index'][$_idx];
                    // Get all color values
                    $tint = $this->FILE_DATA['colors'][$name];
                    // Allocate a color to the image
                    $tint = imagecolorallocate(
                        $imgs, $tint[0], $tint[1], $tint[2] 
                    );
                    // Draw the pixel into the image
                    imagesetpixel( $imgs, $x, $y, $tint );
                    // Increase index
                    $_idx += 1;
                }
            }
        }
        // Saving image
        return imagepng( 
            $imgs, 
            $this->OUTPUT_PATH.$this->FILE_DATA['slug'].'.png'
        );
    }
    
    /**
     * Generates a CorelDRAW XML palette format.
     * 
     * @param bool $cmyk 
     *      Should this compile the palette in CMYK format?
     * @return bool 
     *      TRUE on success, FALSE on failure 
     */
    public function buildCorel( $cmyk = false ) 
    {
        // Define file name suffix
        $suff = ( $cmyk ) ? "-cmyk" : "-rgb";
        
        // Define final file name
        $file = $this->FILE_DATA['slug'].$suff.'.xml';
        
        // Generate a SimpleXML Element
        $pall = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><palette/>', 
            null, 
            false
        );
        
        // Generate the GUID
        $guid = $this->guidMake( $this->FILE_DATA['name'] );
        
        // Add the GUID
        $pall->addAttribute( 'guid', $guid );
        
        // Add the title
        if ( $cmyk ) {
            $pall->addAttribute( 'name', $this->FILE_DATA['name'].' (CMYK)' );
        } else {
            $pall->addAttribute( 'name', $this->FILE_DATA['name'].' (RGB)' );
        }
        
        // Add colors node
        $colors = $pall->addChild( 'colors' );
        
        // Add colors page node
        $page = $colors->addChild( 'page' );
        
        // Building the colors
        foreach ( $this->FILE_DATA['colors'] as $name => $vals ) {
            // Define hex color value
            $_hex = $this->colorsDecimalToHex( $vals[0], $vals[1], $vals[2] );
            
            // Adding the color
            $addColor = $page->addChild( 'color' );
            
            // Add the 'cs' (colorspace) attribute
            $addColor->addAttribute( 'cs', ( $cmyk ) ? 'CMYK' : 'RGB' );
            
            // Add the name attribute
            $addColor->addAttribute( 'name', $name );
            
            // Define color values
            $tint = ( $cmyk ) 
                  ? $this->colorsHexToProcess( $_hex ) 
                  : $this->colorsHexToPercent( $_hex );
            
            // Adding the color values
            $addColor->addAttribute( 'tints', implode( ',', $tint ) );
        }
        
        // Saving the file
        if ( $open = fopen( $this->OUTPUT_PATH.$file, 'w+' ) ) {
            // Write the XML data and close
            fwrite( $open, $this->xmlToText( $pall ) );
            fclose( $open );
            
            // Return true
            return TRUE;
        }
        
        // Return false
        return FALSE;
    }
    
    /**
     * Builds a Microsoft Expression Design XML palette format file.
     * 
     * @return bool 
     *      TRUE on success, FALSE on failure 
     */
    public function buildExpression() 
    {
        // Define final file name
        $file = $this->FILE_DATA['slug'].'-expression.xml';
        
        // Generate a SimpleXML Element
        $pall = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><SwatchLibrary/>', 
            null, 
            false
        );
        
        // Adding attributes
        $pall->addAttribute( 'name', $this->FILE_DATA['name'] );
        $pall->addAttribute( 'xmlns:xsd', 'http://www.w3.org/2001/XMLSchema' );
        $pall->addAttribute( 
            'xmlns:xsi', 
            'http://www.w3.org/2001/XMLSchema-instance'
        );
        $pall->addAttribute( 
            'xmlns', 
            'http://schemas.microsoft.com/expression/design/2007'
        );
        
        // Adding all colors
        foreach ( $this->FILE_DATA['colors'] as $name => $vals ) {
            // Define hex color value
            $_hex = $this->colorsDecimalToHex( $vals[0], $vals[1], $vals[2] );
            
            // Add color node
            $temp = $pall->addChild( 'SolidColorSwatch' );
            $temp->addAttribute( 'Color', '#FF'.strtoupper( $_hex ) );
        }
        
        // Saving the file
        if ( $open = fopen( $this->OUTPUT_PATH.$file, 'w+' ) ) {
            // Write the XML data and close
            fwrite( $open, $this->xmlToText( $pall ) );
            fclose( $open );
            
            // Return true
            return TRUE;
        }
        
        // Return false
        return FALSE;
    }
    
    /**
     * Generates variables for every color, so they can be used with the LESS 
     * and SASS/SCSS CSS preprocessors.
     * 
     * @param bool $hex 
     *      If the variables will have HEX values, instead of the default 
     *      rgb() values
     * @return bool 
     *      TRUE on success, FALSE on failure 
     */
    public function buildPrePros( $hex = false ) 
    {
        // Define file name suffix
        $suff = ( $hex ) ? "-hex" : "-rgb";
        
        // Define final file name
        $file_less = $this->FILE_DATA['slug'].$suff.'.less';
        $file_scss = $this->FILE_DATA['slug'].$suff.'.scss';
        
        // Define a common header for the files
        $head[] = "/**";
        $head[] = " * ".$this->FILE_DATA['name'];
        $head[] = " */";
        
        // LESS and SCSS data holders
        $less = array();
        $scss = array();
        
        // Building the variables
        foreach ( $this->FILE_DATA['colors'] as $name => $vals ) {
            // Define the color value
            $tint = ( $hex ) 
                  ? "#".$this->colorsDecimalToHex( $vals[0], $vals[1], $vals[2] ) 
                  : "rgb( {$vals[0]}, {$vals[1]}, {$vals[2]} )";
            
            // Define color name
            $slug = $this->stringStrip( $name );
            $slug = str_replace( "_", "-", strtolower( $slug ) );
            
            // Adding variables and also comments with names
            $less[] = "@{$slug}: {$tint}; // {$name}";
            $scss[] = "\${$slug}: {$tint}; // {$name}";
        }
        
        // Saving files
        if ( $open_less = fopen( $this->OUTPUT_PATH.$file_less, 'w+' ) ) {
            // Write LESS file and close
            fwrite( $open_less, implode( "\r\n", array_merge( $head, $less ) ) );
            fclose( $open_less );
            
            // Write SCSS file and close
            $open_scss = fopen( $this->OUTPUT_PATH.$file_scss, 'w+' );
            fwrite( $open_scss, implode( "\r\n", array_merge( $head, $scss ) ) );
            fclose( $open_scss );
            
            // Return true
            return TRUE;
        }
        
        // Return false
        return FALSE;
    }
    
    /**
     * Generates a XML with styling, so you can just open it in a browser and 
     * see the whole palette and its values.
     * 
     * Think of the output as a cheatsheet for the palette. ;)
     * 
     * @param bool $forIE 
     *      If TRUE, renders the stylesheet in a separate file, since IE can't 
     *      read the embedded stylesheet properly.
     * @return bool 
     *      TRUE on success, FALSE on failure 
     */
    public function buildVisualXML( $forIE = false ) 
    {
        // Define file name suffix
        $suff = ( $forIE ) ? "-ie" : "";
        
        // Define final file name
        $file = $this->FILE_DATA['slug']."{$suff}-visual.xml";
        
        // Define stylesheet file name
        $styles = $this->FILE_DATA['slug']."{$suff}-style.xsl";
        
        // Building XML header
        $data = '<?xml version="1.0" encoding="UTF-8"?>';
        $data.= ( $forIE ) 
              ? '<?xml-stylesheet type="text/xml" href="'.$styles.'"?>' 
              : '<?xml-stylesheet type="text/xml" href="#stylesheet"?>';
        // Add DOCTYPE params
        $data.= "\r\n<!DOCTYPE doc[\r\n<!ATTLIST xsl:stylesheet\r\n id ID "
              . "#REQUIRED>\r\n]>\r\n";
        // Add main node
        $data.= '<palette>';
        // If not rendering for IE, embed the stylesheet
        if ( !$forIE ) {
            $data.= $this->STYLESHEET;
        } else {
            // Generate stylesheet file
            $open = fopen( $this->OUTPUT_PATH.$styles, 'w+' );
            fwrite( $open, $this->STYLESHEET );
            fclose( $open );
        }
        // Close main node
        $data.= '</palette>';
        
        // Initialize SimpleXML element
        $pall = new SimpleXMLElement( $data, null, false );
        
        // Add some base attributes
        $pall->addChild( 'name', $this->FILE_DATA['name'] );
        $pall->addChild( 'version', '1.0.0' );
        
        // Add color node
        $colors = $pall->addChild( 'colors' );
        
        // Add color values
        foreach ( $this->FILE_DATA['colors'] as $name => $vals ) {
            // Define hex color value
            $_hex = $this->colorsDecimalToHex( $vals[0], $vals[1], $vals[2] );
            
            // Add the single color node
            $node = $colors->addChild( 'color' );
            
            // Add name
            $node->addChild( 'name', $name );
            
            // Attribute
            $tint = $node->addChild( 'values' );
            
            // Add hex color values
            $tint->addAttribute( 'HEX', '#'.$_hex );
            
            // Add decimal RGB color values
            $tint->addAttribute( 'integerRGB', implode( ',', $vals ) );
            
            // Add percentual RGB color values
            $tint->addAttribute(
                'percentRGB', 
                implode( ',', $this->colorsHexToPercent( $_hex, true ) )
            );
            
            // Add percentual CMYK values
            $tint->addAttribute(
                'CMYK', 
                implode( ',', $this->colorsHexToProcess( $_hex, true ) )
            );
        }
        
        // Saving the file
        if ( $open = fopen( $this->OUTPUT_PATH.$file, 'w+' ) ) {
            // Write the XML data and close
            fwrite( $open, $this->xmlToText( $pall ) );
            fclose( $open );
            
            // Return true
            return TRUE;
        }
        
        // Return false
        return FALSE;
    }
    
    
    
    /* Helper Methods
     * --------------------------------------------------------------- */
    
    /**
     * Generates a Global Unique IDentifier (GUID) from a string or a random 
     * number (when $string isn't declared).
     * 
     * The $wrap argument, when TRUE, just puts the string inside curly braces.
     * 
     * Used, only, for the CorelDRAW XML palette.
     * 
     * @param string $string 
     *      String to be hashed (optional)
     * @param bool $wrap 
     *      If the return value should be wrapped in curly braces
     * @return string
     */
    private function guidMake( $string = '', $wrap = false ) 
    {
        // Checks string
        if ( '' != trim( $string ) ) {
            // Generating first generation ID
            $id = trim( $string ).date( 'YmdHis' );
        } else {
            // Generating a random value if not declared
            $id = rand( 0, 255 ).date( 'YmdHis' ).rand( 0, 255 );
        }
        
        // First encoding
        $id = str_rot13( sha1( base64_encode( $id ) ) );
        
        // Second encoding
        $id = md5( sha1( md5( sha1( strrev( $id ) ) ) ) );
        
        // Declaring REGEX flag for formatting
        $flag = "[a-z0-9]";
        $flag = "({$flag}{8})({$flag}{4})({$flag}{4})({$flag}{4})({$flag}{12})";
        
        // Applying formatting
        $id = preg_replace( "#{$flag}#", "$1-$2-$3-$4-$5", $id );
        
        // Returning and checking wrap
        return ( $wrap ) ? "{".strtoupper( $id )."}" : strtoupper( $id );
    }
    
    /**
     * Extracts the name, defines a slug for it and also gets names and values 
     * of every color in the palette. 
     */
    private function pullInfo() 
    {
        // Read the file
        $read = file_get_contents( $this->SOURCE_FILE );
        
        // Separate info from color values, "Linux/Unix" style
        $frag = preg_split( "/([\r\n]+)#([\r\n]+)/", $read );
        
        // Extract name
        preg_match( "/Name\s*:\s*(.*)([\r\n]+)/", $frag[0], $name );
        
        // Define info name
        $this->FILE_DATA['name'] = trim( $name[1] );
        
        // Building and defining slug
        $slug = $this->stringStrip( trim( $name[1] ) );
        $slug = str_replace( "_", "-", strtolower( $slug ) );
        
        // Define final slug
        $this->FILE_DATA['slug'] = $slug;
        
        // Declare colors array
        $this->FILE_DATA['colors'] = array();
        
        // Declare color index array
        $this->FILE_DATA['index'] = array();
        
        // Extracting colors
        $tint = preg_split( "/([\r\n]+)/", $frag[1] );
        
        // Adding the colors
        foreach ( $tint as $line ) {
            // Temporary pattern
            $patt = '^[\t\s]*([0-9]{1,3})[\t\s]*([0-9]{1,3})[\t\s]*([0-9]{1,3})[\t\s]*([a-zA-Z0-9\s]+)$';
            
            // Extract values to a temporary array
            preg_match( "/$patt/", $line, $temp );
            
            if ( !empty( $temp ) ) {
                // Adding color
                $this->FILE_DATA['colors'][ $temp[4] ] = array(
                    $temp[1], $temp[2], $temp[3] 
                );
                
                // Add index
                $this->FILE_DATA['index'][] = $temp[4];
            }
        }
    }
    
    /**
     * This method was built with sanitizing in mind.
     * 
     * The main purpose of this method is to provide a way to clean strings to 
     * be used as variables and file names.
     * 
     * Spaces are replaced with the underscore character ("_").
     * 
     * @param string $string 
     *      String to be sanitized
     * @return string 
     *      Clean string
     */
    private function stringStrip( $string ) 
    {
        // Checking encoding
        if ( false === mb_check_encoding( $string, 'UTF-8' ) ) {
            $string = utf8_encode( $string );
        }
        
        // Converting all characters into HTML entities
        $string = htmlentities( $string, ENT_COMPAT, 'UTF-8' );
        
        // Regex flag for entities
        $flag = "uml|acute|grave|circ|tilde|cedil|ring|slash|u";
        
        // Remove accents
        $string = preg_replace(
            "#&([a-zA-Z])({$flag});#", 
            "$1", 
            $string
        );
        
        // Encoding the rest of html entities
        $string = html_entity_decode( $string );
        
        // Checking underscore/spaces
        $string = str_replace( " ", "_", $string );
        
        // Returning, after one last regex
        return preg_replace( "#([\\\/\?\<\>:\*\|%|`|´]+)#", "", $string );
    }
    
    /**
     * Receives a SimpleXMLElement object, then uses DOMDocument to convert it 
     * into text, returning it as a string.
     * 
     * @param object $data 
     *      SimpleXMLElement object
     * @return string 
     *      String with the XML contents
     */
    private function xmlToText( $data ) 
    {
        // If class doesn't exist
        if ( !class_exists( 'DOMDocument' ) ) {
            // Kills process
            die( 'DOMDocument class not found.');
        }
        
        // Creating new DOMDocument
        $xmls = new DOMDocument( '1.0', 'UTF-8' );
        
        // Removing redundant white space
        $xmls->preserveWhiteSpace = false;
        
        // Formats output with indentation and extra spaces
        $xmls->formatOutput = true;
        
        // Loading XML from SimpleXMLElement object
        $xmls->loadXML( $data->asXML() );
        
        // Returning
        return $xmls->saveXML();
    }
    
    
    
    /* Color Conversion Methods
     * --------------------------------------------------------------- */
    
    /**
     * Converts decimal RGB color values ( 0 ~ 255 ) into its RGB HEX color 
     * value.
     * 
     * @param int $r 
     *      Red value, integer, from 0 to 255
     * @param int $g 
     *      Green value, integer, from 0 to 255
     * @param int $b 
     *      Blue value, integer, from 0 to 255
     * @return string 
     *      String with the RGB HEX value for the color
     */
    private function colorsDecimalToHex( $r, $g, $b ) 
    {
        return sprintf( "%02s", dechex( trim( $r ) ) )
              .sprintf( "%02s", dechex( trim( $g ) ) )
              .sprintf( "%02s", dechex( trim( $b ) ) );
    }
    
    /**
     * Converts a RGB HEX color value, 3 or 6 characters, into its own decimal 
     * counterparta (0 ~ 255), inside an associative array with its own 'r', 
     * 'g' and 'b' keys.
     * 
     * @param string $vars 
     *      RGB HEX value string
     * @return array 
     *      Associative array, with the 'r', 'g' and 'b' color values
     */
    private function colorsHexToDecimal( $vars ) 
    {
        // Trim and remove all unwanted characters
        $vars = preg_replace( "#([\W]+)#", '', trim( $vars ) );
        
        // If the colos doesn't have 3 or 6 characters, return false (invalid)
        if ( preg_match( "#^(?=(?:.{3}|.{6})$)[a-fA-F0-9]*$#", $vars ) < 1 ) {
            die( 'Invalid RGB HEX color.');
        }
        
        // Building colors
        if ( strlen( $vars ) == 3 ) {
            $varR = hexdec( substr( $vars, 0, 1 ).substr( $vars, 0, 1 ) );
            $varG = hexdec( substr( $vars, 1, 1 ).substr( $vars, 1, 1 ) );
            $varB = hexdec( substr( $vars, 2, 1 ).substr( $vars, 2, 1 ) );
        } else {
            $varR = hexdec( substr( $vars, 0, 2 ) );
            $varG = hexdec( substr( $vars, 2, 2 ) );
            $varB = hexdec( substr( $vars, 4, 2 ) );
        }
        
        // Returning
        return array(
            'r' => $varR, 
            'g' => $varG, 
            'b' => $varB
        );
    }
    
    /**
     * Converts a RGB HEX color value into its percentual equivalent, returning 
     * the values as separated 'r', 'g' and 'b' variables inside an associative 
     * array, in the same fasion ad 'colorsHexToDecimal()', in this class.
     * 
     * The default values returned are floating point percent ( 0.0 ~ 1.0 ), but 
     * if $int is declared as TRUE, these values are converted into full decimal 
     * percent values ( 0 ~ 100 ).
     * 
     * @param string $vars 
     *      RGB HEX color value string
     * @param bool $int 
     *      When TRUE, this method returns full decimal values
     * @return array 
     *      Associative array, with the 'r', 'g' and 'b' color values
     */
    private function colorsHexToPercent( $vars, $int = false ) 
    {
        // Checking $int
        $int = ( true === $int ) ? true : false;
        
        // Converting to decimal values
        $vars = $this->colorsHexToDecimal( $vars );
        
        // Converting into percentual values
        $vars['r'] = number_format( ( $vars['r'] / 255 ), 6 );
        $vars['g'] = number_format( ( $vars['g'] / 255 ), 6 );
        $vars['b'] = number_format( ( $vars['b'] / 255 ), 6 );
        
        // If $int is true
        if ( true === $int ) {
            $vars['r'] = round( $vars['r'] * 100 );
            $vars['g'] = round( $vars['g'] * 100 );
            $vars['b'] = round( $vars['b'] * 100 );
        }
        
        // Returning
        return $vars;
    }
    
    /**
     * Converts an RGB HEX color into the closest CMYK (process color) value, 
     * returning these values in an associative array, with 'c', 'm', 'y' and 
     * 'k', in the same fashion as the other HEX color converters in this class.
     * 
     * The default values returned are floating point percent ( 0.0 ~ 1.0 ), but 
     * if $int is declared as TRUE, these values are converted into full decimal 
     * percent values ( 0 ~ 100 ).
     * 
     * IMPORTANT:
     * The returned values may not be 100% precise and reliable. This method 
     * uses one way to convert RGB to CMYK, which I found over the internet. 
     * There may have some better ways to do this conversion.
     *  
     * @param string $vars 
     *      RGB HEX color value string
     * @param bool $int 
     *      When TRUE, this method returns full decimal values
     * @return array 
     *      Associative array, with the 'c', 'm', 'y' and 'k' color values
     */
    private function colorsHexToProcess( $vars, $int = false ) 
    {
        // Checking Int
        $int = ( true === $int ) ? true : false;
        
        // Converting to decimal values
        $vars = $this->colorsHexToPercent( $vars, false );
        
        // Calculating base values (K goes first because defines the rest)
        $pK = min( 1 - $vars['r'], 1 - $vars['g'], 1 - $vars['b'] );
        $pC = ( 1 - $pK == 0 ) ? 0 : ( 1 - $vars['r'] - $pK ) / ( 1 - $pK );
        $pM = ( 1 - $pK == 0 ) ? 0 : ( 1 - $vars['g'] - $pK ) / ( 1 - $pK );
        $pY = ( 1 - $pK == 0 ) ? 0 : ( 1 - $vars['b'] - $pK ) / ( 1 - $pK );
        
        // Returning
        return array(
            'c' => ( $int ) ? round( $pC * 100 ) : $pC, 
            'm' => ( $int ) ? round( $pM * 100 ) : $pM, 
            'y' => ( $int ) ? round( $pY * 100 ) : $pY, 
            'k' => ( $int ) ? round( $pK * 100 ) : $pK
        );
    }
}
