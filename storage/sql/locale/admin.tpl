<?xml version="1.0" encoding="utf-8"?>
<tpl:templates
  xmlns:crud="http://2013.sylma.org/view/crud"
  xmlns:view="http://2013.sylma.org/view"
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns:js="http://2013.sylma.org/template/binder"
  
  xmlns:sql="http://2013.sylma.org/storage/sql"
>

  <tpl:template match="*" mode="label/value">
    <tpl:argument name="title"/>
    <sql:translate>
      <tpl:read select="$title"/>
    </sql:translate>
  </tpl:template>
  
  <tpl:template match="*" mode="form/save/content">
    <sql:translate>save</sql:translate>
  </tpl:template>
  
  <tpl:template match="*" mode="form/delete/content">
    <sql:translate>delete</sql:translate>
  </tpl:template>
  
  <tpl:template match="*" mode="form/cancel/content">
    <sql:translate>cancel</sql:translate>
  </tpl:template>

  <tpl:template match="*" mode="form/delete/yes">
    <sql:translate>yes</sql:translate>
  </tpl:template>
  
  <tpl:template match="*" mode="form/delete/no">
    <sql:translate>no</sql:translate>
  </tpl:template>
  
  <tpl:template match="*" mode="head/cell/title">
    <sql:translate>
      <tpl:apply mode="head/cell/title"/>
    </sql:translate>
  </tpl:template>

  <tpl:template mode="tab/content">

    <tpl:argument name="title"/>
    <tpl:argument name="path"/>

    <tpl:apply mode="tab/content">
      <sql:translate tpl:name="title">
        <tpl:read select="$title"/>
      </sql:translate>
      <tpl:read select="$path" tpl:name="path"/>
    </tpl:apply>

  </tpl:template>

</tpl:templates>
