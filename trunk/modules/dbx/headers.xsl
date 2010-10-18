<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:func="http://exslt.org/functions" xmlns:lc="http://www.sylma.org/schemas" xmlns:lx="http://ns.sylma.org/xslt" version="1.0" extension-element-prefixes="func lx">
  <xsl:import href="../../schemas/functions.xsl"/>
  <xsl:import href="/sylma/xslt/string.xsl"/>
  <xsl:param name="headers"/>
  <xsl:param name="module"/>
  <xsl:variable name="doc-headers" select="document($headers)"/>
  <xsl:template match="/*">
    <thead>
      <tr>
        <th class="tools"> </th>
        <xsl:choose>
          <xsl:when test="$headers">
            <xsl:for-each select="*[1]/*">
              <xsl:if test="$doc-headers/*/*[@name = local-name(current())]">
                <xsl:apply-templates select="."/>
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
    <xsl:variable name="name" select="lc:get-name()"/>
    <th>
      <a href="{$module}/admin/list/order={local-name()}">
        <xsl:value-of select="lx:first-case(lc:get-title())"/>
      </a>
    </th>
  </xsl:template>
</xsl:stylesheet>
