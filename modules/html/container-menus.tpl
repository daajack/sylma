<?xml version="1.0" encoding="utf-8"?>
<view:view
  xmlns:view="http://2013.sylma.org/view"
  xmlns="http://2014.sylma.org/html"
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns:xl="http://2013.sylma.org/storage/xml"
  xmlns:js="http://2013.sylma.org/template/binder"
  xmlns:le="http://2013.sylma.org/action"
  xmlns:builder="http://2013.sylma.org/parser/reflector/builder"

  xmlns:menus="http://2013.sylma.org/modules/menus"
  
  builder:return="result"
>

  <tpl:import>/#sylma/modules/menus/main.tpl</tpl:import>

  <tpl:template match="menus:menus">
    <div>
      <tpl:apply mode="toggler"/>
      <ul id="{@id}" class="clearfix">
        <tpl:apply select="*"/>
      </ul> 
    </div>
  </tpl:template>
  
  <tpl:template match="*" mode="toggler">
    <span class="title-page"><tpl:read select="title"/></span>
    <a href="javascript:void(0)" class="menus-toggler" js:class="sylma.ui.Base">
      <js:event name="click">
        document.body.toggleClass('menus-open');
      </js:event>
      <div class="wrapper">
        <span class="line1"/>
        <span class="line2"/>
        <span class="line3"/>
      </div>
    </a>
  </tpl:template>
      
  <tpl:template match="menus:title"/>
  
  <tpl:template match="menus:menu" mode="content">
    <a href="{@href}">
      <span class="title"><tpl:read select="@title"/></span>
      <span class="description"><tpl:read select="description"/></span>
    </a>
  </tpl:template>

</view:view>
