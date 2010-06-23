<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:la="/sylma/processors/action-builder/schema" version="1.0">
  <xsl:template match="la:layout | la:layer | la:object">
    <xsl:variable name="object-name">
      <xsl:call-template name="get-name">
        <xsl:with-param name="default">no-name</xsl:with-param>
      </xsl:call-template>
    </xsl:variable>
    <xsl:element name="{$object-name}">
      <xsl:copy-of select="@key"/>
      <init>
        <xsl:call-template name="attributes-to-nodes">
          <xsl:with-param name="attributes" select="@path | @extend-base | @extend-class | @id-node"/>
        </xsl:call-template>
        <xsl:if test="(local-name() = 'layout') or (local-name() = 'layer')">
          <xsl:call-template name="containers"/>
        </xsl:if>
      </init>
      <properties>
        <xsl:apply-templates select="la:property"/>
        <xsl:apply-templates select="la:layout | la:layer | la:object"/>
        <xsl:apply-templates select="la:group"/>
      </properties>
      <xsl:if test="la:method | la:property/la:method">
        <xsl:call-template name="methods"/>
      </xsl:if>
      <is-sylma-object>true</is-sylma-object>
    </xsl:element>
  </xsl:template>
  <xsl:template match="la:property">
    <xsl:choose>
      <xsl:when test="*">
        <xsl:element name="{@name}">
          <xsl:apply-templates/>
          <is-sylma-property>true</is-sylma-property>
        </xsl:element>
      </xsl:when>
      <xsl:otherwise>
        <xsl:element name="{@name}">
          <xsl:value-of select="."/>
        </xsl:element>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="la:group">
    <xsl:element name="{@name}">
      <xsl:if test="not(@key-type)">
        <xsl:attribute name="key-type">index</xsl:attribute>
      </xsl:if>
      <xsl:if test="@key-type = 'assoc'">
        <xsl:attribute name="attribute-key">key</xsl:attribute>
      </xsl:if>
      <xsl:apply-templates select="la:layout | la:layer | la:object"/>
      <no-name key="is-sylma-array">true</no-name>
    </xsl:element>
  </xsl:template>
</xsl:stylesheet>
