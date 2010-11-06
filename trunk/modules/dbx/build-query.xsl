<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:lc="http://www.sylma.org/schemas" xmlns:dbx="http://www.sylma.org/modules/dbx" version="1.0">
  <xsl:output method="text"/>
  <xsl:import href="../../schemas/functions.xsl"/>
  <xsl:template match="/*">
element <xsl:value-of select="$parent-name"/> {
    <xsl:call-template name="loop"/>
}
  </xsl:template>
  
  <xsl:template name="loop">
    <xsl:variable name="headers" select="*[3]"/>
  for $self in <xsl:value-of select="$parent-path"/>/*
    <xsl:apply-templates select="$headers/dbx:element" mode="prepare"/>
    order by <xsl:apply-templates select="$headers/dbx:order"/>
    return element {name($self)} {
      $self/@*, <xsl:apply-templates select="$headers/dbx:element" mode="result"/>
    }
  </xsl:template>
  
  <xsl:template match="dbx:element" mode="prepare">
    <xsl:variable name="ref" select="/*/*[2]/lc:key-ref[@name = current()/@name]"/>
    <xsl:if test="$ref">
      <xsl:value-of select="concat('let $sylma-', @name, ' := ', $ref/.)"/>
      <xsl:choose>
        <xsl:when test="$ref/@key-constrain"><xsl:value-of select="$ref/@key-constrain"/></xsl:when>
        <xsl:otherwise>[@key = $self/<xsl:value-of select="concat($prefix, @name)"/>/text()]</xsl:otherwise>
      </xsl:choose>
    </xsl:if>
  </xsl:template>
  
  <xsl:template match="dbx:order">
    <xsl:variable name="order-dir">
      <xsl:choose>
        <xsl:when test="@dir = 'a'">ascending</xsl:when>
        <xsl:otherwise>descending</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="n-order" select="*[1]/*[local-name() = current()]"/>
    <xsl:variable name="order-name">
      <xsl:choose>
        <xsl:when test="$n-order/@lc:key-ref">
          <xsl:value-of select="concat('$sylma-', .)"/><xsl:if test="$n-order/@lc:ref-view"><xsl:value-of select="concat('/', $n-order/@lc:ref-view)"/></xsl:if>
        </xsl:when>
        <xsl:otherwise><xsl:value-of select="concat('$self/', $prefix, .)"/></xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="order-call">
      <xsl:variable name="type" select="lc:get-type($n-order)"/>
      <xsl:choose>
        <xsl:when test="not($n-order/@lc:key-ref) and $type">
          <xsl:variable name="order-typed" select="concat($type, '(', $order-name, ')')"/>
          <xsl:choose>
            <xsl:when test="$type = 'xs:string'"><xsl:value-of select="concat('upper-case(', $order-typed, ')')"/></xsl:when>
            <xsl:otherwise><xsl:value-of select="$order-typed"/></xsl:otherwise>
          </xsl:choose>
        </xsl:when>
        <xsl:otherwise><xsl:value-of select="concat('upper-case(', $order-name, ')')"/></xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:value-of select="concat($order-call, ' ', $order-dir)"/>
  </xsl:template>
  
  <xsl:template match="dbx:element" mode="result">
    <xsl:variable name="ref" select="/*/*[2]/lc:key-ref[@name = current()/@name]"/>
    <xsl:variable name="name" select="concat($prefix, @name)"/>
    <xsl:choose>
      <xsl:when test="$ref">
        <xsl:variable name="view">
          <xsl:choose>
            <xsl:when test="@view"><xsl:value-of select="@view"/></xsl:when>
            <xsl:when test="$ref/@lc:ref-view"><xsl:value-of select="$ref/@lc:ref-view"/></xsl:when>
          </xsl:choose>
        </xsl:variable>
        element <xsl:value-of select="$name"/> {
          attribute lc:value {$self/<xsl:value-of select="$name"/>/text()},
          <xsl:choose>
            <xsl:when test="$view != ''">xs:string($sylma-<xsl:value-of select="concat(@name, '/', $view)"/>)}</xsl:when>
            <xsl:otherwise>xs:string($sylma-<xsl:value-of select="@name"/>)}</xsl:otherwise>
          </xsl:choose>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="concat('$self/', $name)"/>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:if test="not(position() = last())">,</xsl:if>
  </xsl:template>
</xsl:stylesheet>
