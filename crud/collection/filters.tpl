<?xml version="1.0" encoding="utf-8"?>
<tpl:collection
  xmlns:crud="http://2013.sylma.org/view/crud"
  xmlns:view="http://2013.sylma.org/view"
  xmlns="http://2014.sylma.org/html"
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns:js="http://2013.sylma.org/template/binder"
  xmlns:le="http://2013.sylma.org/action"
  xmlns:cls="http://2013.sylma.org/core/factory"

  xmlns:sql="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"


>

  <tpl:import>../input.tpl</tpl:import>
  <tpl:import>../type/numeric.tpl</tpl:import>
  <tpl:import>../type/date.tpl</tpl:import>

  <tpl:template match="*" mode="filters">

    <tpl:apply mode="list/filters/js"/>
    <tpl:apply mode="list/filters/css"/>

    <tr class="filters" js:name="filters" js:parent-name="filters" js:class="sylma.crud.collection.Filters">
      <js:option name="datas">
        <tpl:read select="/root()/dummy()/query()"/>
      </js:option>
      <tpl:apply mode="filters/content"/>
    </tr>

  </tpl:template>

  <tpl:template match="*" mode="filters/content">
    <th class="thfirst">
      <div class="filter-container title" js:class="sylma.crud.collection.FilterContainer"/>
    </th>
    <tpl:apply use="list-cols" mode="filter/container" xmode="update"/>
  </tpl:template>

  <tpl:template match="*" mode="filter/container">
    <th valign="top">
      <tpl:apply mode="filter"/>
    </th>
  </tpl:template>

  <tpl:template match="*" mode="filter">
    <div class="filter-container" js:class="sylma.crud.collection.FilterContainer">
      <input type="hidden" name="{alias()}[0][logic]" value="or"/>
      <tpl:apply mode="filter/init"/>
      <tpl:apply mode="filter/content"/>
      <js:option name="name">
        <tpl:read select="alias()"/>
      </js:option>
      <button type="button" class="add" data-name="{alias()}">
        <js:event name="click">%object%.addEmptyFilter();</js:event>
        +
      </button>
    </div>
  </tpl:template>

  <tpl:template match="*" mode="filter/content">
    <div class="filter hidder template" js:class="sylma.crud.collection.Filter">
      <tpl:register/>
      <input type="hidden" value="=" js:node="operator"/>
      <span class="operator" js:node="operator_display">
        <js:event name="click">%object%.toggleOperator();</js:event>
      </span>
      <input type="text" js:node="input">
        <tpl:apply mode="input/events"/>
      </input>
      <tpl:apply mode="input/clear"/>
    </div>
  </tpl:template>

  <tpl:template match="*" mode="filter/operator">
    <input type="hidden" value="=" js:node="operator"/>
    <span class="operator" js:node="operator_display">
      <js:event name="click">%object%.toggleOperator();</js:event>
    </span>
  </tpl:template>

  <tpl:template match="*" mode="filter/init">
    <js:option name="operators">
      <le:array explode=",">=,&gt;,&lt;,!</le:array>
    </js:option>
  </tpl:template>

  <tpl:template match="xs:string" mode="filter/init">
    <js:option name="operators">
      <le:array explode=",">=,!</le:array>
    </js:option>
  </tpl:template>

  <tpl:template match="*" mode="input/clear">
    <span class="close">
      <js:event name="click">
        %object%.clear();
      </js:event>
      <tpl:text>✕</tpl:text>
    </span>
  </tpl:template>

  <tpl:template match="*" mode="input/events">
<!--
    <js:event name="change">
      %object%.update();
    </js:event>
-->
    <js:event name="focus">
      %object%.enter();
    </js:event>
    <js:event name="input">
      %object%.update();
    </js:event>
    <js:event name="blur">
      %object%.leave();
    </js:event>
  </tpl:template>

  <tpl:template match="*" mode="input/events" xmode="foreign">
    <js:event name="change">
      %object%.update();
    </js:event>
  </tpl:template>

  <tpl:template match="*" mode="filter/internal">
    <sql:filter optional="x" op="in">
      <tpl:read/>
    </sql:filter>
  </tpl:template>

  <tpl:template match="xs:string" mode="filter/internal">
    <tpl:argument name="key" default="alias()"/>
    <tpl:apply mode="filter/internal/text">
      <tpl:read select="$key" tpl:name="key"/>
    </tpl:apply>
  </tpl:template>

  <tpl:template match="*" mode="filter/internal/text">
    <tpl:argument name="key"/>
    <sql:filter optional="x" op="search">
      <tpl:read/>
    </sql:filter>
  </tpl:template>

  <tpl:template match="*" mode="date/clear"/>

</tpl:collection>
