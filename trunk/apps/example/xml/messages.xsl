<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template match="messages">
    <div class="messages">
      <xsl:apply-templates/>
    </div>
  </xsl:template>
  <xsl:template match="messages/*">
    <xsl:if test="*">
      <ul class="message-{name()}">
        <xsl:apply-templates/>
      </ul>
    </xsl:if>
  </xsl:template>
  <xsl:template match="message">
    <li>
      <xsl:copy-of select="content/node()"/>
    </li>
  </xsl:template>
</xsl:stylesheet>
