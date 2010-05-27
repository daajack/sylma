<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:lx="/sylma/xslt" version="1.0" extension-element-prefixes="lx">
  <xsl:template name="lx:string-resume">
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
          <xsl:call-template name="lx:substring-before-last">
            <xsl:with-param name="string" select="$last-string"/>
            <xsl:with-param name="search" select="$search"/>
          </xsl:call-template>
        </xsl:variable>
        <xsl:value-of select="concat($first-string, $last-formated, ' ...')"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$string"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template name="lx:substring-before-last">
    <xsl:param name="string"/>
    <xsl:param name="search"/>
    <xsl:variable name="after-string">
      <xsl:call-template name="lx:substring-after-last">
        <xsl:with-param name="string" select="$string"/>
        <xsl:with-param name="search" select="$search"/>
      </xsl:call-template>
    </xsl:variable>
    <xsl:value-of select="substring($string, 1, string-length($string) - string-length($after-string) - 1)"/>
  </xsl:template>
  <xsl:template name="lx:substring-after-last">
    <xsl:param name="string"/>
    <xsl:param name="search"/>
    <xsl:choose>
      <xsl:when test="contains($string, $search)">
        <xsl:call-template name="lx:substring-after-last">
          <xsl:with-param name="string" select="substring-after($string, $search)"/>
          <xsl:with-param name="search" select="$search"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$string"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:stylesheet>
