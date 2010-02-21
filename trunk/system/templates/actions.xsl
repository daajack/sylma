<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template match="controler">
    <div id="msg-actions" class="clear-block">
      <xsl:apply-templates select="action"/>
    </div>
  </xsl:template>
  <xsl:template match="action">
    <div class="msg-action">
      <div class="msg-action-main">
        <xsl:if test="sub-actions">
          <div class="msg-action-sub-weights">
            <xsl:for-each select="stats/*">
              <xsl:apply-templates select=".">
                <xsl:with-param name="color" select="@sub-weight-color"/>
                <xsl:with-param name="sub">1</xsl:with-param>
              </xsl:apply-templates>
            </xsl:for-each>
          </div>
        </xsl:if>
        <div class="msg-action-label">
          <a href="{$path-editor}?path={@path}">
            <xsl:value-of select="@path"/>
          </a>
        </div>
        <xsl:if test="files">
          <div class="msg-action-files">
            <xsl:for-each select="files/file">
              <a href="{$path-editor}?path={@full-path}">
                <xsl:value-of select="@full-path"/>
              </a>
            </xsl:for-each>
          </div>
        </xsl:if>
      </div>
      <xsl:if test="sub-actions">
        <div class="msg-action-sub clear-block">
          <xsl:for-each select="sub-actions/action">
            <div class="msg-action-weights">
              <xsl:for-each select="stats/*">
                <xsl:apply-templates select=".">
                  <xsl:with-param name="color" select="@weight-color"/>
                </xsl:apply-templates>
              </xsl:for-each>
            </div>
            <xsl:apply-templates select="."/>
          </xsl:for-each>
        </div>
      </xsl:if>
    </div>
  </xsl:template>
  <xsl:template match="stat">
    <xsl:param name="color" select="string('rgb(123, 123, 123)')"/>
    <xsl:param name="sub"/>
    <div style="background-color: {$color}" class="msg-action-stat msg-action-stat-{@name}">
      <xsl:attribute name="title">
        <xsl:value-of select="@name"/>
        <xsl:text> : </xsl:text>
        <xsl:choose>
          <xsl:when test="$sub">
            <xsl:value-of select="@value"/>
            <xsl:value-of select="concat(' (', @sub-value, ')')"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="@sub-value"/>
            <xsl:value-of select="concat(' (', @total-value, ')')"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:attribute>
      <span>
        <xsl:value-of select="substring(@name, 1, 1)"/>
      </span>
    </div>
  </xsl:template>
</xsl:stylesheet>
