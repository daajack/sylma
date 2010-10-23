<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:func="http://exslt.org/functions" xmlns:lc="http://www.sylma.org/schemas" xmlns:lx="http://ns.sylma.org/xslt" version="1.0" extension-element-prefixes="func lx">
  <xsl:import href="../../schemas/functions.xsl"/>
  <xsl:import href="/sylma/xslt/string.xsl"/>
  <xsl:param name="headers"/>
  <xsl:param name="module"/>
  <xsl:param name="order"/>
  <xsl:param name="order-dir"/>
  <xsl:variable name="doc-headers" select="document($headers)"/>
  <xsl:template match="/*">
    <thead>
      <tr>
        <th class="tools"> </th>
        <xsl:choose>
          <xsl:when test="$headers">
            <xsl:variable name="order" select="lx:substring-after-last($order, ':')"/>
            <xsl:variable name="order-dir">
              <xsl:choose>
                <xsl:when test="$order-dir != 'ascending'">a</xsl:when>
                <xsl:otherwise>d</xsl:otherwise>
              </xsl:choose>
            </xsl:variable>
            <xsl:for-each select="*[1]/*">
              <xsl:if test="$doc-headers/*/*[@name = local-name(current())]">
                <xsl:apply-templates select=".">
                  <xsl:with-param name="order" select="$order"/>
                  <xsl:with-param name="order-dir" select="$order-dir"/>
                </xsl:apply-templates>
              </xsl:if>
            </xsl:for-each>
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="*[1]/*[not(@lc:editable = 'false')]"/>
          </xsl:otherwise>
        </xsl:choose>
      </tr>
    </thead>
  </xsl:template>
  <xsl:template match="*">
    <xsl:param name="order"/>
    <xsl:param name="order-dir"/>
    <xsl:variable name="name" select="lc:get-name()"/>
    <th>
      <a>
        <xsl:attribute name="href">
          <xsl:value-of select="concat($module, '/admin/list?order=', local-name())"/>
          <xsl:if test="$order = local-name()">
            <xsl:value-of select="concat('&amp;order-dir=', $order-dir)"/>
          </xsl:if>
        </xsl:attribute>
        <xsl:value-of select="lx:first-case(lc:get-title())"/>
      </a>
    </th>
  </xsl:template>
</xsl:stylesheet>
