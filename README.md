YKCS Color Palette Converter Utility
------------------------------------

The **YKCS** color palette converter was built out of my necessity to have the same color swatches whenever I've used different raster/vector graphics editors.

Since I was kinda lazy to go and type all the colors on some of them, I decided I'd build a special tool to convert all the colors for me. As a plus, I also built it so it would convert all the colors in that specific palette to the CMYK color space, since sometimes I have to do this conversion for some clients (and since I'm kinda bad finding the color only by seeing it).

-----

### How to Use

Just include **`ykcs.php`** anywhere you like, make an instance of it (declaring a color palette file is optional, if left blank it will use the default palette).

```php
// Include YKCS
include 'ykcs.php';

// Make an instance (color palette file is optional)
$ykcs = new YKCS();

// Use any of the build functions, like
$ykcs->build();
```

The output files will be placed inside the **`output`** folder, in the project root.

**This script ONLY accepts palettes in the GPL (GIMP) format for input!**

-----

### Methods

Here goes some basic info on each method available for palette conversion:

 - **`build`**: builds all possible formats below;
 - **`buildACO`**: converts to the Adobe Color (ACO) format, with RGB colors by default (CMYK is optional);
 - **`buildBitmap`**: makes a PNG file, with every color in the palette in the RGB color space;
 - **`buildCorel`**: converts to the CorelDRAW XML palette format, with RGB colors by default (CMYK is optional);
 - **`buildExpression`**: converts to the Microsoft Expression Design XML palette format, in the RGB color space;
 - **`buildPrePros`**: writes the colors as variables for LESS and SCSS preprocessors, in RGB HEX format (RGB integer format is optional);
 - **`buildVisualXML`**: writes a visual cheat sheet, with styling, in XML format, which you can view in your browser. There's an optional, boolean, value that can be set if you want to build the file with an external XML stylesheet (as Internet Explorer will only render properly if done like this). These XML files **WILL NOT** render properly on _Microsoft Edge_;

-----

### Author

This project was built and is maintained by **Fabio Y. Goto**.

-----

### Why YKCS?

Personal reasons.

-----

### License

The script is **MIT licensed**, see **`LICENSE.md`** for details on it.

The default color palette is the public domain **Tango! Desktop Project** palette, which you can find **[here](http://tango.freedesktop.org/)**.

-----

_**(c)2016-2017 Fabio Y. Goto**_