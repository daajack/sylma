<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:func="http://exslt.org/functions" xmlns:lc="http://www.sylma.org/schemas" xmlns:lx="http://ns.sylma.org/xslt" version="1.0" extension-element-prefixes="func lx">
  <xsl:import href="../../schemas/functions.xsl"/>
  <xsl:import href="/sylma/xslt/string.xsl"/>
  <xsl:template match="/*">
    <form method="POST" action="{@lc:action}">
      <xsl:apply-templates select="lc:get-model(*[1])/lc:annotations/lc:message"/>
      <xsl:apply-templates select="*[1]/@*"/>
      <xsl:apply-templates select="*[1]/*"/>
    </form>
  </xsl:template>
  <xsl:template match="*">
    <xsl:variable name="name" select="lc:get-name()"/>
    <xsl:variable name="id" select="concat('field-', lc:get-name())"/>
    <div class="field">
      <label for="{$id}">
        <xsl:value-of select="lx:first-case(lc:get-title())"/>
        <xsl:text> :</xsl:text>
      </label>
      <xsl:choose>
        <xsl:when test="not(lc:get-model())">
          <textarea name="{$name}" id="{$id}" style="background-color: #eee">
            <xsl:value-of select="."/>
          </textarea>
        </xsl:when>
        <xsl:when test="lc:is-string()">
          <xsl:choose>
            <xsl:when test="lc:is-enum()">
              <select name="{$name}" id="{$id}">
                <option>&lt; choisissez &gt;</option>
                <xsl:apply-templates select="lc:get-schema()/lc:restriction/lc:enumeration"/>
              </select>
            </xsl:when>
            <xsl:when test="lc:get-element()/@line-break">
              <textarea id="{$id}" name="{$name}">
                <xsl:value-of select="."/>
              </textarea>
            </xsl:when>
            <xsl:otherwise>
              <input type="text" value="{.}" name="{$name}" id="{$id}"/>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:when>
        <xsl:when test="lc:is-date()">
          <input type="text" name="{$name}" id="{$id}" value="{.}"/>
        </xsl:when>
        <xsl:otherwise>
          <textarea id="{$id}" name="{$name}">
            <xsl:value-of select="."/>
          </textarea>
        </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="@*"/>
      <xsl:apply-templates select="lc:get-model()/lc:annotations/lc:message"/>
    </div>
  </xsl:template>
  <xsl:template match="@*">
    <xsl:if test="namespace-uri() != 'http://www.sylma.org/schemas'">
      <input type="hidden" value="{.}" name="{local-name()}"/>
    </xsl:if>
  </xsl:template>
  <xsl:template match="lc:enumeration">
    <option value="{position()}">
      <xsl:value-of select="."/>
    </option>
  </xsl:template>
  <xsl:template match="lc:message">
    <div class="field-message">
      <xsl:copy-of select="."/>
    </div>
  </xsl:template>
</xsl:stylesheet>
