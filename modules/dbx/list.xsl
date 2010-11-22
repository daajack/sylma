<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:func="http://exslt.org/functions" xmlns:lx="http://ns.sylma.org/xslt" xmlns:dbx="http://www.sylma.org/modules/dbx" version="1.0" xmlns:ls="http://www.sylma.org/security" extension-element-prefixes="func">
  <xsl:param name="max-length">100</xsl:param>
  <xsl:param name="module"/>
  <xsl:import href="/sylma/xslt/string.xsl"/>
  <xsl:template match="/*">
    <tbody>
      <xsl:choose>
        <xsl:when test="*">
          <xsl:apply-templates select="*" mode="root"/>
        </xsl:when>
        <xsl:otherwise>
          <tr>
            <td colspan="99">
              <p class="no-result">Aucun résultat</p>
            </td>
          </tr>
        </xsl:otherwise>
      </xsl:choose>
    </tbody>
  </xsl:template>
  <xsl:template match="*" mode="root">
    <xsl:variable name="id" select="@xml:id"/>
    <xsl:variable name="self" select="."/>
    <tr>
      <td class="tools">
        <a href="{$module}/admin/edit/{$id}" title="Editer">E</a>
        <a href="{$module}/admin/delete/{$id}" ls:mode="710" ls:group="famous" ls:owner="root">S</a>
        <a href="{$module}/admin/view/{$id}" title="Voir (pas implémenté)">V</a>
      </td>
      <xsl:apply-templates select="*" mode="field"/>
    </tr>
  </xsl:template>
  <xsl:template match="*" mode="field">
    <td>
      <xsl:if test=".">
        <xsl:value-of select="lx:string-resume(., $max-length)"/>
      </xsl:if>
    </td>
  </xsl:template>
</xsl:stylesheet>
