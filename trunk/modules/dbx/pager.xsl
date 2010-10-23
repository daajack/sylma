<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:param name="path"/>
  <xsl:template match="/*">
    <xsl:if test="@total &gt; 1">
      <div class="pager">
        <xsl:choose>
          <xsl:when test="@page &gt; 1">
            <span class="pager-previous">
              <a href="{$path}?page={@page - 1}">&lt;&lt;&lt;</a>
            </span>
          </xsl:when>
          <xsl:otherwise>
            <span class="pager-previous">&lt;&lt;&lt;</span>
          </xsl:otherwise>
        </xsl:choose>
        <span class="pager-current">
          <xsl:value-of select="@page"/>
        </span>
        <span class="pager-separator">/</span>
        <xsl:choose>
          <xsl:when test="@page != @total">
            <span class="pager-total">
              <a href="{$path}?page={@total}">
                <xsl:value-of select="@total"/>
              </a>
            </span>
            <span class="pager-next">
              <a href="{$path}?page={@page + 1}">&gt;&gt;&gt;</a>
            </span>
          </xsl:when>
          <xsl:otherwise>
            <span class="pager-total">
              <xsl:value-of select="@total"/>
            </span>
            <span id="pager-next">&gt;&gt;&gt;</span>
          </xsl:otherwise>
        </xsl:choose>
      </div>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>
