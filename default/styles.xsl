<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" id="stylesheet" version="1.0">
    <xsl:template match="/">
        <html>
            <body style="font-family: 'Iosevka Term', monospaced; font-size: 12px;">
                <div style="width: 960px; margin: auto;">
                    <h1 style="margin: auto 10px; color: #697166; clear: both; font-weight: normal; font-size: 4em; line-height: 1em;">
                        <xsl:value-of select="palette/name"/>
                        <small style="font-size: 0.5em; margin-left: 10px;">
                            <xsl:value-of select="palette/version"/>
                        </small>
                    </h1>
                    <p style="line-height: 1em; margin: 10px; color: #9fa5a3;">
                        Colors in HEX triplets. For more detailed information about color values, including: Decimal RGB, Percentage RGB and CMYK (approx.), view the source code of this XML file.
                    </p>
                    <hr style="width: 100%; height: 1px; background: #c5cbcb; border: 0; margin: 10px; clear: both;"/>
                    <xsl:for-each select="palette/colors/color">
                        <xsl:variable name="color">
                            <xsl:value-of select="values/@HEX"/>
                        </xsl:variable>
                        <div style="width: 140px; height: 80px; margin: 10px; float: left; background: {$color}; display: block;">
                            <div style="background: #ffffff; margin: 10px auto; padding: 5px; opacity: 0.7;">
                                <h6 style="color: #2c2825; clear: both; font-weight: normal; font-size: 1.1337em; line-height: 1em; margin: auto;">
                                    <xsl:value-of select="name"/>
                                </h6>
                                <h3 style="color: #2c2825; clear: both; font-weight: normal; font-size: 1.5em; line-height: 1em; margin: auto;">
                                    <xsl:value-of select="values/@HEX"/>
                                </h3>
                            </div>
                        </div>
                    </xsl:for-each>
                </div>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>