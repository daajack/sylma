<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template match="/*">
    <table class="list">
      <thead>
        <tr>
          <xsl:apply-templates select="headers"/>
        </tr>
      </thead>
      <tbody>
        <xsl:apply-templates select="row"/>
      </tbody>
    </table>
  </xsl:template>
  <xsl:template match="headers/*">
    <th>
      <xsl:value-of select="text()"/>
    </th>
  </xsl:template>
  <xsl:template match="//row">
    <tr>
      <xsl:apply-templates/>
    </tr>
  </xsl:template>
  <xsl:template match="//row/*">
    <td>
      <xsl:choose>
        <xsl:when test="position() = 1">
          <a href="{/*/@path_to}{text()}">
            <xsl:value-of select="text()"/>
          </a>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="text()"/>
        </xsl:otherwise>
      </xsl:choose>
    </td>
  </xsl:template>
</xsl:stylesheet>
