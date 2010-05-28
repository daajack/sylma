<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:la="/sylma/processors/action-builder/schema" version="1.0">
  <xsl:import href="directory.xsl"/>
  <xsl:import href="file.xsl"/>
  <xsl:template match="/*">
    <la:group name="resources" key-type="assoc">
      <xsl:apply-templates select="/*//*">
        <xsl:sort select="name()"/>
      </xsl:apply-templates>
    </la:group>
  </xsl:template>
  <xsl:template match="/*//*">
    <xsl:choose>
      <xsl:when test="name() = 'directory'">
        <xsl:call-template name="directory"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="file"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:stylesheet>
