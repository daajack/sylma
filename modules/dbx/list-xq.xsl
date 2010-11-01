<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:lc="http://www.sylma.org/schemas" version="1.0">
  <xsl:output method="text"/>
  <xsl:template match="/*">
  <xsl:variable name="keys" select="*[2]/lc:key-ref"/>
let $page := <xsl:value-of select="$page"/>
let $pageSize := <xsl:value-of select="$page-size"/>
let $start := ($page * $pageSize) - $pageSize
let $order-name := 'beruf'(:'[$order]':)

let $result := 
  for $self in <xsl:value-of select="$parent-path"/>/*
  <xsl:for-each select="$keys">
    let $sylma-<xsl:value-of select="@name"/> := <xsl:value-of select="."/>
    <xsl:choose>
      <xsl:when test="@key-constrain"><xsl:value-of select="@key-constrain"/></xsl:when>
      <xsl:otherwise>[position() = $self/<xsl:value-of select="concat($prefix, @name)"/>/text()]</xsl:otherwise>
    </xsl:choose>
  </xsl:for-each>
    (:order by $sylma-beruf/@name <xsl:value-of select="$order-dir"/>:)
  <xsl:choose>
    <xsl:when test="$keys">
    return element {name($self)} {
      $self/@*,
      for $child in $self/*
        return typeswitch ($child)
          <xsl:for-each select="$keys">
            <xsl:variable name="name" select="concat($prefix, @name)"/>
            case element(<xsl:value-of select="$name"/>) return element <xsl:value-of select="$name"/> {
              attribute lc:value {$child/text()},
            <xsl:choose>
              <xsl:when test="@key-view">xs:string($sylma-<xsl:value-of select="concat(@name, '/', @key-view)"/>)}</xsl:when>
              <xsl:otherwise>xs:string($sylma-<xsl:value-of select="@name"/>)}</xsl:otherwise>
            </xsl:choose>
          </xsl:for-each>
            default return $child
    }
    </xsl:when>
    <xsl:otherwise>
    return $self
    </xsl:otherwise>
  </xsl:choose>

let $pageTotal := ceiling((count($result) div $pageSize))

return
  element <xsl:value-of select="$parent-name"/> {
    attribute total {$pageTotal}, attribute page {$page}, attribute lc:ns {'null'},
    subsequence($result, ($start + 1), $pageSize)}
  </xsl:template>
</xsl:stylesheet>
