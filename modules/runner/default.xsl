<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:la="http://www.sylma.org/processors/action-builder" version="1.0">
  <xsl:template match="/">
    <la:layer>
      <div class="document">
        <la:event name="click"><![CDATA[alert('hello');
return true;]]></la:event>
        <xsl:apply-templates/>
      </div>
    </la:layer>
  </xsl:template>
  <xsl:template match="*">
    <div class="element namespace">
      <span>
        <xsl:value-of select="name()"/>
      </span>
      <xsl:if test="@*">
        <div class="attributes">
          <xsl:apply-templates select="@*"/>
        </div>
      </xsl:if>
      <xsl:if test="*">
        <div class="children">
          <xsl:apply-templates select="*"/>
        </div>
      </xsl:if>
    </div>
  </xsl:template>
  <xsl:template match="@*">
    <div class="attribute">
      <span class="name">
        <xsl:value-of select="name()"/>
      </span>
      <span class="operator">=</span>
      <span class="value">
        <xsl:value-of select="."/>
      </span>
    </div>
  </xsl:template>
</xsl:stylesheet>
