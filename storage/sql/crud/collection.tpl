<?xml version="1.0" encoding="utf-8"?>
<crud:crud
  xmlns:crud="http://2013.sylma.org/view/crud"
  xmlns:view="http://2013.sylma.org/view"
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"
  xmlns:sql="http://2013.sylma.org/storage/sql"
  xmlns:js="http://2013.sylma.org/template/binder"
  xmlns:le="http://2013.sylma.org/action"
  xmlns:ls="http://2013.sylma.org/parser/security"
>

  <tpl:import>/#sylma/crud/collection/root.tpl</tpl:import>
  <tpl:import>common.tpl</tpl:import>
  <tpl:import>foreign.tpl</tpl:import>

  <tpl:template match="sql:foreign" mode="input/foreign/events">
    <js:event name="change">
      %object%.update();
    </js:event>
  </tpl:template>

  <tpl:template match="sql:foreign" mode="input/default">
    <tpl:text>&lt; All &gt;</tpl:text>
  </tpl:template>

  <tpl:template match="sql:foreign">
    <tpl:apply select="ref()" mode="cell/content"/>
  </tpl:template>

  <tpl:template match="sql:datetime">
    <tpl:read select="format()"/>
  </tpl:template>

  <tpl:template match="sql:datetime" mode="label"/>
  <tpl:template match="sql:datetime" mode="filter">

    <js:include>DateFilter.js</js:include>

    <tpl:argument name="alias" default="alias()"/>
    <th class="filter-container" js:class="sylma.crud.collection.FilterContainer">
      <js:option name="name">
        <tpl:read select="$alias"/>
      </js:option>
      <js:option name="operators">
        <le:array explode=",">&gt;,&lt;,=,!</le:array>
      </js:option>

      <tpl:register/>
      <div class="filter hidder template" js:class="sylma.crud.DateFilter" js:parent-name="filter">
        <input type="hidden" value="=" js:node="operator"/>
        <span class="operator" js:node="operator_display">
          <js:event name="click">%object%.toggleOperator();</js:event>
        </span>
        <tpl:apply mode="date">
          <tpl:read select="'{$alias}[0]'" tpl:name="alias"/>
        </tpl:apply>
        <tpl:apply mode="input/clear"/>
      </div>
      <button type="button" class="add" data-name="{alias('form')}">
        <js:event name="click">%object%.addEmptyFilter();</js:event>
        +
      </button>
    </th>
  </tpl:template>

  <tpl:template match="sql:datetime" mode="input/value"/>

  <tpl:template match="sql:datetime" mode="input/events"/>

  <tpl:template match="sql:datetime" mode="filter/internal">
    <sql:filter optional="x" op="=">
      <tpl:read select="/root()/dummy()/default()" tpl:name="value">
        <tpl:read select="'{alias()}'"/>
      </tpl:read>
    </sql:filter>
    <!--
    <sql:filter optional="x" op="&lt;=">
      <le:get-argument optional="x" source="post">
        <le:name>
          <tpl:read select="'{alias()}_to'"/>
        </le:name>
      </le:get-argument>
    </sql:filter>
    -->
  </tpl:template>

</crud:crud>
