<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:func="http://exslt.org/functions" xmlns:lc="http://www.sylma.org/schemas" xmlns:lx="http://ns.sylma.org/xslt" xmlns:dbx="http://www.sylma.org/modules/dbx" version="1.0" extension-element-prefixes="func lx">
  <xsl:import href="../../schemas/functions.xsl"/>
  <xsl:import href="/sylma/xslt/string.xsl"/>
  <xsl:param name="model"/>
  <xsl:param name="module"/>
  <xsl:variable name="doc-model" select="document($model)"/>
  <xsl:template match="/*">
    <thead>
      <tr>
        <th class="tools"> </th>
        <xsl:variable name="n-order" select="*[3]/dbx:order"/>
        <xsl:variable name="order" select="$n-order/."/>
        <xsl:variable name="order-dir">
          <xsl:choose>
            <xsl:when test="*[3]/dbx:order/@dir != 'a'">a</xsl:when>
            <xsl:otherwise>d</xsl:otherwise>
          </xsl:choose>
        </xsl:variable>
        <xsl:apply-templates select="*[3]/dbx:element">
          <xsl:with-param name="order" select="$order"/>
          <xsl:with-param name="order-dir" select="$order-dir"/>
        </xsl:apply-templates>
      </tr>
    </thead>
  </xsl:template>
  <xsl:template match="dbx:element">
    <xsl:param name="order"/>
    <xsl:param name="order-dir"/>
    <xsl:variable name="element" select="/*/*[1]/*[local-name() = current()/@name]"/>
    <th>
      <a>
        <xsl:attribute name="href">
          <xsl:value-of select="concat($module, '/admin/list?order=', @name)"/>
          <xsl:if test="$order = @name">
            <xsl:value-of select="concat('&amp;order-dir=', $order-dir)"/>
          </xsl:if>
        </xsl:attribute>
        <xsl:if test="$element">
          <xsl:value-of select="lx:first-case(lc:get-title($element))"/>
        </xsl:if>
      </a>
    </th>
  </xsl:template>
</xsl:stylesheet>
