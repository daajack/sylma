<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template match="/">
    <xsl:text>sylma.methods = {</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>};</xsl:text>
  </xsl:template>
  <xsl:template match="/*/*">
    <xsl:text>  '</xsl:text>
    <xsl:value-of select="@id"/>
    <xsl:text><![CDATA[' : function(e) {
]]></xsl:text>
    <xsl:value-of select="."/>
    <xsl:text>}</xsl:text>
    <xsl:if test="position() != last()">
      <xsl:text><![CDATA[,
]]></xsl:text>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>
