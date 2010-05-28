<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:la="/sylma/processors/action-builder/schema" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
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
      <div class="resource {name()}">
        <la:event name="mouseenter"><![CDATA[return sylma.explorer.tools.show(this);]]></la:event>
        <la:event name="mouseleave"><![CDATA[return sylma.explorer.tools.hide();]]></la:event>
        <div class="preview">
          <input type="hidden"/>
        </div>
        <span>
          <xsl:value-of select="@name"/>
        </span>
      </div>
    </la:layer>
  </xsl:template>
</xsl:stylesheet>
