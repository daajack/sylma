<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:template match="/*">
    <xsl:if test="@total &gt; 1">
      <div id="pager">
        <xsl:choose>
          <xsl:when test="@page &gt; 1">
            <span id="pager-previous">
              <a href="{@directory}/{@page - 1}">&lt;&lt;&lt;</a>
            </span>
          </xsl:when>
          <xsl:otherwise>
            <span id="pager-previous">&lt;&lt;&lt;</span>
          </xsl:otherwise>
        </xsl:choose>
        <span id="pager-current">
          <xsl:value-of select="@page"/>
        </span>
        <span id="pager-separator">/</span>
        <xsl:choose>
          <xsl:when test="@page != @total">
            <span id="pager-total">
              <a href="{@directory}/{@total}">
                <xsl:value-of select="@total"/>
              </a>
            </span>
            <span id="pager-next">
              <a href="{@directory}/{@page + 1}">&gt;&gt;&gt;</a>
            </span>
          </xsl:when>
          <xsl:otherwise>
            <span id="pager-total">
              <xsl:value-of select="@total"/>
            </span>
            <span id="pager-next">&gt;&gt;&gt;</span>
          </xsl:otherwise>
        </xsl:choose>
      </div>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>
