<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template name="resource">
    <xsl:param name="type"/>
    <div class="resource {$type}">
      <div class="preview">
        <input type="hidden"/>
      </div>
      <span>
        <xsl:value-of select="@name"/>
      </span>
    </div>
  </xsl:template>
</xsl:stylesheet>
