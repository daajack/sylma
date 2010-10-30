<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:func="http://exslt.org/functions" xmlns:lx="http://ns.sylma.org/xslt" version="1.0" extension-element-prefixes="func">
  <xsl:param name="max-length">100</xsl:param>
  <xsl:param name="headers"/>
  <xsl:param name="module"/>
  <xsl:variable name="doc-headers" select="document($headers)"/>
  <xsl:import href="/sylma/xslt/string.xsl"/>
  <xsl:template match="/*">
    <tbody>
      <xsl:apply-templates select="*"/>
    </tbody>
  </xsl:template>
  <xsl:template match="*">
    <xsl:variable name="id" select="@xml:id"/>
    <tr>
      <td class="tools">
        <a href="{$module}/admin/edit/{$id}/{intitule-urlize}">E</a>
        <a href="{$module}/admin/delete/{$id}/{intitule-urlize}">S</a>
        <a href="{$module}/admin/view/{$id}/{intitule-urlize}">V</a>
      </td>
      <xsl:for-each select="*">
        <xsl:if test="$doc-headers/*/*[@name = local-name(current())]">
          <xsl:call-template name="field"/>
        </xsl:if>
      </xsl:for-each>
    </tr>
  </xsl:template>
  <xsl:template name="field">
    <td>
      <xsl:value-of select="lx:string-resume(., $max-length)"/>
    </td>
  </xsl:template>
</xsl:stylesheet>
