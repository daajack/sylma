<?xml version="1.0" encoding="utf-8"?>
<tpl:collection
  xmlns="http://2014.sylma.org/html"
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns:js="http://2013.sylma.org/template/binder"
  xmlns:le="http://2013.sylma.org/action"
  
  xmlns:sql="http://2013.sylma.org/storage/sql"
>
  
  <le:context name="css">
    <le:file>form.less</le:file>
  </le:context>
  
  <tpl:template match="sql:string" mode="container">

    <tpl:argument name="alias" default="alias('form')"/>
    <tpl:argument name="title" default="title()"/>
    <tpl:argument name="type" default="'text'"/>
    <tpl:argument name="object" default="alias('key')"/>

    <tpl:if test="is-translated()">
      <tpl:apply mode="locale/container">
        <tpl:read select="$alias" tpl:name="alias"/>
        <tpl:read select="$title" tpl:name="title"/>
        <tpl:read select="$type" tpl:name="type"/>
        <tpl:read select="$object" tpl:name="object"/>
      </tpl:apply>
      <tpl:else>
        <tpl:apply mode="container">
          <tpl:read select="$alias" tpl:name="alias"/>
          <tpl:read select="$title" tpl:name="title"/>
          <tpl:read select="$type" tpl:name="type"/>
          <tpl:read select="$object" tpl:name="object"/>
        </tpl:apply>
      </tpl:else>
    </tpl:if>

  </tpl:template>
  
  <tpl:template match="*" mode="locale/container">

    <tpl:argument name="alias" default="alias('form')"/>
    <tpl:argument name="title" default="title()"/>
    <tpl:argument name="type" default="'text'"/>
    <tpl:argument name="object" default="alias('key')"/>

    <div class="field-container clearfix field-{$type} {$object} locale" js:class="sylma.locale.Container" js:parent-name="container">
      
      <js:include>/#sylma/ui/Clonable.js</js:include>
      <js:include>/#sylma/crud/Field.js</js:include>
      <js:include>Container.js</js:include>
      <js:include>Field.js</js:include>

      <tpl:apply mode="container/init"/>

      <js:name>
        <tpl:read select="$object"/>
      </js:name>
      <tpl:apply mode="label">
        <tpl:read tpl:name="alias" select="$alias"/>
        <tpl:read tpl:name="title" select="$title"/>
      </tpl:apply>
      <div class="actions-locale" js:class="sylma.ui.Base" js:name="actions">
        <tpl:apply select="translations()" mode="locale/action"/>
      </div>
      <div class="inputs" js:class="sylma.ui.Base" js:name="inputs">
        <tpl:apply select="translations()" mode="locale/input">
          <tpl:read select="$type" tpl:name="type"/>
        </tpl:apply>
      </div>
    </div>

  </tpl:template>

  <tpl:template match="*" mode="label/value">
    <tpl:argument name="title"/>
    <sql:translate>
      <tpl:read select="$title"/>
    </sql:translate>
  </tpl:template>
  
  <tpl:template match="*" mode="fieldset/legend">
    <legend>
      <sql:translate>
        <tpl:read select="title()"/>
      </sql:translate>
    </legend>
  </tpl:template>

  <tpl:template match="*" mode="locale/action">

    <span class="action" js:class="sylma.ui.Base">
      <js:name>
        <tpl:read select="language()"/>
      </js:name>
      <js:event name="click">%object%.getParent('container').select(%object%.sylma.key);</js:event>
      <tpl:read select="language()"/>
    </span>
    
  </tpl:template>

  <tpl:template match="*" mode="locale/input">

    <tpl:argument name="type"/>
    
    <div class="input" js:class="sylma.locale.Field">
      <js:name>
        <tpl:read select="language()"/>
      </js:name>
      
      <tpl:apply mode="register"/>

      <tpl:apply mode="input/build">
        <tpl:read select="alias('form')" tpl:name="alias"/>
        <tpl:read select="$type" tpl:name="type"/>
      </tpl:apply>

    </div>
    
  </tpl:template>

</tpl:collection>
