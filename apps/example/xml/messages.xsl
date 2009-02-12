<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:template match="messages">
    <div class="messages">
      <xsl:apply-templates/>
    </div>
  </xsl:template>
  <xsl:template match="messages/*">
    <xsl:if test="*">
      <xsl:variable name="statut">
        <xsl:value-of select="name()"/>
      </xsl:variable>
      <ul class="message-{$statut}">
        <xsl:apply-templates/>
      </ul>
    </xsl:if>
  </xsl:template>
  <xsl:template match="message">
    <li>
      <xsl:copy-of select="content/.">
        <xsl:apply-templates/>
      </xsl:copy-of>
    </li>
  </xsl:template>
</xsl:stylesheet>
