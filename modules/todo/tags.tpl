<?xml version="1.0" encoding="utf-8"?>
<tpl:templates
  xmlns:view="http://2013.sylma.org/view"
  xmlns="http://2014.sylma.org/html"
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns:sql="http://2013.sylma.org/storage/sql"
  xmlns:le="http://2013.sylma.org/action"
  
  xmlns:todo="http://2013.sylma.org/modules/todo"
  xmlns:tag="http://2013.sylma.org/modules/todo/tag"
>

  <tpl:template match="tag:todo_tag" mode="select-option-value">
    <tpl:read select="name"/>
  </tpl:template>

  <tpl:template match="todo:tags" mode="filter">
    <tpl:apply select="join()/name" mode="filter"/>
  </tpl:template>

  <tpl:template match="todo:tags" mode="filter/internal">
    <tpl:apply select="join()/name" mode="filter/internal"/>
  </tpl:template>

  <tpl:template match="todo:tags" mode="cell/content">
    <tpl:apply select="ref()" mode="tag/value"/>
  </tpl:template>

  <tpl:template match="*" mode="tag/value">
    <tpl:apply mode="select-option-value"/>
    <tpl:if test="position() != length()">, </tpl:if>
  </tpl:template>

  <tpl:template match="tag:todo_tag" mode="select-option-value" xmode="insert">
    <sql:filter name="project">
      <le:get-argument name="parent"/>
    </sql:filter>
    <tpl:apply mode="select-option-value"/>
  </tpl:template>

  <tpl:template match="tag:todo_tag" mode="select-option-value" xmode="update">
    <sql:filter name="project">
      <tpl:read select="/root()/project"/>
    </sql:filter>
    <tpl:apply mode="select-option-value"/>
  </tpl:template>

</tpl:templates>
