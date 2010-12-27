<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:lc="http://www.sylma.org/schemas" xmlns:func="http://exslt.org/functions" version="1.0" extension-element-prefixes="func exsl">
  <func:function name="lc:get-model">
    <xsl:param name="source" select="."/>
    <xsl:variable name="id" select="$source/@lc:model"/>
    <func:result select="/*/lc:schemas//lc:model[@id=$id]"/>
  </func:function>
  <func:function name="lc:get-schema">
    <xsl:param name="source" select="."/>
    <xsl:variable name="model" select="lc:get-model($source)"/>
    <xsl:if test="$model/@base">
      <func:result select="/*/lc:schemas/lc:base[@name=$model/@base]"/>
    </xsl:if>
  </func:function>
  <func:function name="lc:get-element">
    <xsl:param name="source" select="."/>
    <xsl:variable name="model" select="lc:get-model($source)"/>
    <xsl:if test="$model">
      <xsl:choose>
        <xsl:when test="$model/@element">
          <func:result select="//lc:element[@id = $model/@element]"/>
        </xsl:when>
        <xsl:when test="lc:get-schema($source/..)">
          <xsl:variable name="model-name" select="$model/@name"/>
          <func:result select="lc:get-schema($source/..)/*/lc:element[@name = $model-name]"/>
        </xsl:when>
      </xsl:choose>
    </xsl:if>
  </func:function>
  <func:function name="lc:get-title">
    <xsl:param name="source" select="."/>
    <xsl:choose>
      <xsl:when test="$source/@lc:title">
        <func:result select="$source/@lc:title"/>
      </xsl:when>
      <xsl:when test="boolean(lc:get-model($source))">
        <func:result select="lc:get-model($source)/@name"/>
      </xsl:when>
      <xsl:otherwise>
        <func:result select="local-name($source)"/>
      </xsl:otherwise>
    </xsl:choose>
  </func:function>
  <func:function name="lc:get-statut">
    <func:result select="lc:get-model()/@statut"/>
  </func:function>
  <func:function name="lc:get-values">
    <func:result select="/*/*[2]/lc:key-ref[@full-name = local-name(current())]"/>
  </func:function>
  <func:function name="lc:get-path">
    <xsl:choose>
      <xsl:when test="lc:get-model()/@full-name">
        <func:result select="lc:get-model()/@full-name"/>
      </xsl:when>
      <xsl:otherwise>
        <func:result select="local-name()"/>
      </xsl:otherwise>
    </xsl:choose>
  </func:function>
  <func:function name="lc:get-name">
    <xsl:variable name="name" select="local-name()"/>
    <xsl:choose>
      <xsl:when test="count(../*[local-name()=$name]) &gt; 1">
        <func:result select="concat($name, '[', position(), ']')"/>
      </xsl:when>
      <xsl:otherwise>
        <func:result select="$name"/>
      </xsl:otherwise>
    </xsl:choose>
  </func:function>
  <func:function name="lc:is-complex">
    <xsl:param name="source" select="."/>
    <xsl:if test="lc:get-schema($source)">
      <func:result select="boolean(lc:get-schema($source)/@complex = 'true')"/>
    </xsl:if>
  </func:function>
  <func:function name="lc:is-simple">
    <func:result select="not(lc:is-complex())"/>
  </func:function>
  <func:function name="lc:get-type">
    <xsl:param name="source" select="."/>
    <xsl:if test="not(lc:is-complex($source))">
      <xsl:choose>
        <xsl:when test="lc:get-schema($source)">
          <func:result select="lc:get-schema($source)/@type"/>
        </xsl:when>
        <xsl:otherwise>
          <func:result select="lc:get-model($source)/@type"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:if>
  </func:function>
  <func:function name="lc:is-string">
    <func:result select="boolean(lc:get-type() = 'xs:string')"/>
  </func:function>
  <func:function name="lc:is-boolean">
    <func:result select="boolean(lc:get-type() = 'xs:boolean')"/>
  </func:function>
  <func:function name="lc:is-date">
    <func:result select="boolean(lc:get-type() = 'xs:date')"/>
  </func:function>
  <func:function name="lc:is-enum">
    <func:result select="lc:is-simple() and lc:get-schema() and lc:get-schema()/lc:restriction/lc:enumeration"/>
  </func:function>
  <func:function name="lc:is-keyref">
    <func:result select="boolean(@lc:key-ref)"/>
  </func:function>
</xsl:stylesheet>
