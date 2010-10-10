<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:lx="http://ns.sylma.org/xslt" xmlns:func="http://exslt.org/functions" xmlns:exsl="http://exslt.org/common" version="1.0" extension-element-prefixes="lx func exsl">
  <func:function name="lx:format-date">
    <xsl:param name="date"/>
    <xsl:param name="format" select="default"/>
    <xsl:variable select="number(substring($date, 6, 2))" name="day"/>
    <func:result>
      <xsl:value-of select="$day"/>
      <xsl:value-of select="concat(' ', document('../../sylma/xslt/months.xml')//month[@abbr = substring($date, 9, 3)], ' ')"/>
      <xsl:value-of select="substring($date, 13, 4)"/>
    </func:result>
  </func:function>
</xsl:stylesheet>
