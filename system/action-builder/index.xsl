<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:la="/system/action-builder/schema" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:import href="object.xsl"/>
  <xsl:template match="/">
    <root>
      <xsl:apply-templates select="la:layout | la:layer | la:object"/>
    </root>
  </xsl:template>
  <xsl:template name="containers">
    <xsl:if test="not(@extend-class)">
      <object-extend>
        <xsl:text>/sylma.classes.</xsl:text>
        <xsl:value-of select="local-name()"/>
      </object-extend>
    </xsl:if>
  </xsl:template>
  <xsl:template name="methods">
    <methods>
      <xsl:for-each select="la:method">
        <xsl:element name="{@id}">
          <name>
            <xsl:value-of select="@event"/>
          </name>
          <xsl:for-each select="@path-node | @id-node">
            <xsl:element name="{name()}">
              <xsl:value-of select="."/>
            </xsl:element>
          </xsl:for-each>
        </xsl:element>
      </xsl:for-each>
    </methods>
  </xsl:template>
  <xsl:template name="attributes-to-nodes">
    <xsl:param name="attributes"/>
    <xsl:for-each select="$attributes">
      <xsl:element name="{name()}">
        <xsl:value-of select="."/>
      </xsl:element>
    </xsl:for-each>
  </xsl:template>
  <xsl:template name="get-name">
    <xsl:param name="default"/>
    <xsl:choose>
      <xsl:when test="@name">
        <xsl:value-of select="@name"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$default"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:stylesheet>
