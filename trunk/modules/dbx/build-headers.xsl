<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:lc="http://www.sylma.org/schemas" xmlns:dbx="http://www.sylma.org/modules/dbx" version="1.0">
  <xsl:template match="/*/*[1]">
    <dbx:headers>
      <xsl:apply-templates mode="root"/>
    </dbx:headers>
  </xsl:template>
  <xsl:template match="*" mode="root">
    <dbx:element name="{local-name()}"/>
  </xsl:template>
</xsl:stylesheet>
