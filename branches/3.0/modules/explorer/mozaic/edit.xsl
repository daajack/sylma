<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" version="1.0">
  <xsl:template name="edition">
    <div class="tools">
      <xsl:if test="@write='true'">
        <a href="javascript:void(0)" onclick="$(this).data('obj').delete();">
          <img src="/web/common/icones/delete.png" alt="S" title="Supprimer"/>
        </a>
        <a href="javascript:void(0)" onclick="$(this).data('obj').delete();">
          <img alt="S" title="Renommer" src="/web/common/icones/write.png"/>
        </a>
      </xsl:if>
    </div>
  </xsl:template>
</xsl:stylesheet>
