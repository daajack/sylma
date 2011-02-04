<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:func="http://exslt.org/functions" xmlns:lc="http://www.sylma.org/schemas" xmlns:lx="http://ns.sylma.org/xslt" version="1.0" extension-element-prefixes="func lx">
  <xsl:import href="../../schemas/functions.xsl"/>
  <xsl:import href="/sylma/xslt/string.xsl"/>
  <xsl:import href="/sylma/xslt/date.xsl"/>
  <xsl:template match="/*">
    <div>
      <xsl:apply-templates select="*[1]" mode="title"/>
      <xsl:apply-templates select="." mode="annotations"/>
      <xsl:apply-templates select="*[1]/*" mode="field"/>
    </div>
  </xsl:template>
  <xsl:template match="*" mode="title"/>
  <xsl:template match="*" mode="annotations">
    <xsl:variable name="messages" select="lc:get-model()/lc:annotations/lc:message"/>
    <xsl:if test="$messages">
      <div class="view-message">
        <xsl:apply-templates select="$messages"/>
      </div>
    </xsl:if>
  </xsl:template>
  <xsl:template match="*" mode="field">
    <xsl:variable name="name" select="lc:get-name()"/>
    <xsl:variable name="class">
      <xsl:choose>
        <xsl:when test="not(lc:get-model())">unknown</xsl:when>
        <xsl:when test="lc:is-keyref()">keyref</xsl:when>
        <xsl:when test="lc:is-string()">string</xsl:when>
        <xsl:when test="lc:is-date()">date</xsl:when>
        <xsl:when test="lc:is-integer()">integer</xsl:when>
        <xsl:otherwise>default</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="not(@lc:editable = 'false')">
        <div class="field clear-block type-{$class}">
          <xsl:apply-templates select="." mode="label"/>
          <xsl:apply-templates select="." mode="value"/>
          <xsl:apply-templates select="." mode="annotations"/>
        </div>
      </xsl:when>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="*" mode="label">
    <label>
      <xsl:value-of select="lx:first-case(lc:get-title())"/>
      <xsl:text> : </xsl:text>
    </label>
  </xsl:template>
  <xsl:template match="*" mode="value">
    <xsl:choose>
      <xsl:when test=". = '' and not(lc:is-boolean())">
        <em class="left">-</em>
      </xsl:when>
      <xsl:when test="@lc:line-break">
        <pre class="field-content">
          <xsl:if test="string-length(.) &gt; 250">
            <xsl:attribute name="style">width: auto;</xsl:attribute>
          </xsl:if>
          <xsl:value-of select="."/>
        </pre>
      </xsl:when>
      <xsl:otherwise>
        <div class="field-content">
          <xsl:choose>
            <xsl:when test="lc:is-date()">
              <xsl:value-of select="lx:format-date(.)"/>
            </xsl:when>
            <xsl:when test="lc:is-boolean()">
              <xsl:variable name="icone">
                <xsl:choose>
                  <xsl:when test=". = '0' or . = '' or . = 'false'">delete</xsl:when>
                  <xsl:otherwise>ok</xsl:otherwise>
                </xsl:choose>
              </xsl:variable>
              <img src="{$directory}/images/{$icone}.png"/>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="."/>
            </xsl:otherwise>
          </xsl:choose>
        </div>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="lc:message">
    <div>
      <xsl:copy-of select="node()"/>
    </div>
  </xsl:template>
</xsl:stylesheet>
