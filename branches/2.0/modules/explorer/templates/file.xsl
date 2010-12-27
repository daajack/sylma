<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:la="http://www.sylma.org/processors/action-builder" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template name="file">
    <la:layer key="{@full-path}" class="file">
      <la:property name="path">
        <xsl:value-of select="@full-path"/>
      </la:property>
      <la:property name="name">
        <xsl:value-of select="@name"/>
      </la:property>
      <la:property name="owner">
        <xsl:value-of select="@owner"/>
      </la:property>
      <la:property name="group">
        <xsl:value-of select="@group"/>
      </la:property>
      <la:property name="mode">
        <xsl:value-of select="@mode"/>
      </la:property>
      <la:property name="sylma-update-path">
        <xsl:value-of select="$directory"/>
        <xsl:text>/resource</xsl:text>
      </la:property>
      <div class="resource {name()}">
        <la:event name="mouseenter"><![CDATA[var tools = %ref-object%.rootObject.tools;

if (!tools.isLocked()) return tools.show(this);
else return false;]]></la:event>
        <la:event name="mouseleave"><![CDATA[var tools = %ref-object%.rootObject.tools;

if (!tools.isLocked()) return tools.hide();
return false;]]></la:event>
        <la:event name="click" limit="self,$.preview,$&gt;span"><![CDATA[var tools = %ref-object%.rootObject.tools;

if (tools.isLocked()) {
  
  tools.hide(true);
  tools.show(this);
  
  return false;
}

return true;]]></la:event>
        <xsl:choose>
          <xsl:when test="contains('jpg,jpeg,gif,png', @extension)">
            <la:property name="isImage">true</la:property>
            <a rel="lightbox[group]" href="{@full-path}" class="preview clear-block">
              <img src="{@full-path}?width=96&amp;height=50"/>
            </a>
          </xsl:when>
          <xsl:otherwise>
            <div class="preview">
              <input type="hidden"/>
            </div>
          </xsl:otherwise>
        </xsl:choose>
        <span>
          <xsl:value-of select="@name"/>
        </span>
      </div>
    </la:layer>
  </xsl:template>
</xsl:stylesheet>
