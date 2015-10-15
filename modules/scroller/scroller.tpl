<?xml version="1.0" encoding="utf-8"?>
<view:view
  xmlns:view="http://2013.sylma.org/view"
  xmlns="http://2014.sylma.org/html"
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns:le="http://2013.sylma.org/action"
  xmlns:js="http://2013.sylma.org/template/binder"

  xmlns:xl="http://2013.sylma.org/storage/xml"
>

  <tpl:template match="*" mode="scroller/init">

    <js:include>Main.js</js:include>
    <js:include>Fx.Scroll.js</js:include>
    <js:include>Fx.SmoothScroll.js</js:include>

  </tpl:template>

</view:view>
