<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:lx="http://ns.sylma.org/xslt" xmlns:func="http://exslt.org/functions" xmlns:exsl="http://exslt.org/common" version="1.0" extension-element-prefixes="lx func exsl">
  <func:function name="lx:get-month">
    <xsl:param name="i"/>
    <func:result>
      <xsl:choose>
        <xsl:when test="$i = 1 or $i = 'Jan'">janvier</xsl:when>
        <xsl:when test="$i = 2 or $i = 'Feb'">février</xsl:when>
        <xsl:when test="$i = 3 or $i = 'Mar'">mars</xsl:when>
        <xsl:when test="$i = 4 or $i = 'Apr'">avril</xsl:when>
        <xsl:when test="$i = 5 or $i = 'May'">mai</xsl:when>
        <xsl:when test="$i = 6 or $i = 'Jun'">juin</xsl:when>
        <xsl:when test="$i = 7 or $i = 'Jul'">juillet</xsl:when>
        <xsl:when test="$i = 8 or $i = 'Aou'">août</xsl:when>
        <xsl:when test="$i = 9 or $i = 'Sep'">septembre</xsl:when>
        <xsl:when test="$i = 10 or $i = 'Oct'">octobre</xsl:when>
        <xsl:when test="$i = 11 or $i = 'Nov'">novembre</xsl:when>
        <xsl:when test="$i = 12 or $i = 'Dec'">décembre</xsl:when>
      </xsl:choose>
    </func:result>
  </func:function>
</xsl:stylesheet>
