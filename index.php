<?php
/**
 * YKCS : A simple color palette converter : Test Page
 * ======================================================================
 * This is the test page for the color converter, please read the comments on 
 * `ykcs.php` and also `README.md` (though checking the source is better) for 
 * more details on how to use this.
 * 
 * YKCS can only convert from GPL palette files as an input!
 * ----------------------------------------------------------------------
 * @author      Fabio Y. Goto <lab@yuiti.com.br>
 * @copyright   ©2016~2017 Fabio Yuiti Goto
 * @version     3.0.1
 * @license     MIT License
 */

// Set error reporting to ON, because there might have some bugs :P
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include YKCS
include 'ykcs.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <title>YKCS Color Converter :: Test Page</title>
</head>
<body>
    <h1>YKCS Color Palette Converter</h1>
    <h6>by Fabio Y. Goto</h6>
    <hr>
    <?php
    // Palette is optional, if not present, uses the default palette
    $ykcs = new YKCS();
    $ykcs->build();
    ?>
    <hr>
    <small><em>&copy;2016 Fabio Y. Goto</em></small>
</body>
</html>
