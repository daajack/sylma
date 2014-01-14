<?xml version="1.0" encoding="utf-8"?>
<tpl:templates
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"
  xmlns:js="http://2013.sylma.org/template/binder"
  xmlns:le="http://2013.sylma.org/action"
  xmlns:cls="http://2013.sylma.org/core/factory"

  xmlns:xl="http://2013.sylma.org/storage/xml"
  xmlns:menus="http://2013.sylma.org/modules/menus"
>

  <tpl:settings>
    <cls:classes>
      <cls:base>\sylma\modules\menus</cls:base>
      <cls:class alias="tree" name="Main">
        <cls:classes>
          <cls:class alias="cached" name="Cached"/>
        </cls:classes>
      </cls:class>
    </cls:classes>
  </tpl:settings>

  <tpl:template match="menus:menus">
    <ul id="{@id}">
      <tpl:apply select="*"/>
    </ul>
  </tpl:template>

  <tpl:template match="menus:menu">
    <li>
      <tpl:token name="class">
        <tpl:read select="check-active()"/>
      </tpl:token>
      <tpl:apply mode="content"/>
    </li>
  </tpl:template>

  <tpl:template match="menus:menu" mode="title">
    <tpl:read select="@title"/>
  </tpl:template>

  <tpl:template match="menus:menu" mode="content">
    <a href="{@href}">
      <tpl:apply mode="title"/>
    </a>
  </tpl:template>

</tpl:templates>
