<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:lx="http://ns.sylma.org/xslt" xmlns:func="http://exslt.org/functions" version="1.0" extension-element-prefixes="func">
  <func:function name="lx:string-resume">
    <xsl:param name="string"/>
    <xsl:param name="max-length"/>
    <xsl:param name="search" select="' '"/>
    <xsl:param name="word-length" select="20"/>
    <xsl:choose>
      <xsl:when test="string-length($string) &gt; $max-length">
        <xsl:variable name="r-string" select="substring($string, 1, $max-length)"/>
        <xsl:variable name="first-string" select="substring($r-string, 1, $max-length - $word-length)"/>
        <xsl:variable name="last-string" select="substring($r-string, $max-length - $word-length + 1)"/>
        <xsl:variable name="last-formated">
          <xsl:value-of select="lx:substring-before-last($last-string, $search)"/>
        </xsl:variable>
        <func:result select="concat($first-string, $last-formated, ' ...')"/>
      </xsl:when>
      <xsl:otherwise>
        <func:result select="$string"/>
      </xsl:otherwise>
    </xsl:choose>
  </func:function>
  <func:function name="lx:substring-before-last">
    <xsl:param name="string"/>
    <xsl:param name="search"/>
    <xsl:variable name="after-string">
      <xsl:value-of select="lx:substring-after-last($string, $search)"/>
    </xsl:variable>
    <func:result select="substring($string, 1, string-length($string) - string-length($after-string) - 1)"/>
  </func:function>
  <func:function name="lx:substring-after-last">
    <xsl:param name="string"/>
    <xsl:param name="search"/>
    <xsl:choose>
      <xsl:when test="contains($string, $search)">
        <func:result select="lx:substring-after-last(substring-after($string, $search), $search)"/>
      </xsl:when>
      <xsl:otherwise>
        <func:result select="$string"/>
      </xsl:otherwise>
    </xsl:choose>
  </func:function>
</xsl:stylesheet>
