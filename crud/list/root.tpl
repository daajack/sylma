<?xml version="1.0" encoding="utf-8"?>
<tpl:collection
  xmlns:view="http://2013.sylma.org/view"
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"
  xmlns:sql="http://2013.sylma.org/storage/sql"
  xmlns:js="http://2013.sylma.org/template/binder"
  xmlns:le="http://2013.sylma.org/action"
  xmlns:ls="http://2013.sylma.org/parser/security"
  xmlns:crud="http://2013.sylma.org/view/crud"
>

  <tpl:template mode="js">
    <js:include>Container.js</js:include>
    <js:include>Table.js</js:include>
    <js:include>Head.js</js:include>
    <js:include>Row.js</js:include>
  </tpl:template>

  <tpl:template>

    <tpl:apply mode="title">
      <tpl:read tpl:name="title" select="static()/title()"/>
    </tpl:apply>

    <div>
      <div style="margin-bottom: 1em" class="clearfix">
        <a class="button">
          <tpl:token name="href">
            <le:path/>/insert
          </tpl:token>
          Insert
        </a>
        <a class="button" ls:owner="root" ls:group="admin" ls:mode="700">
          <tpl:token name="href">
            <le:path>/sylma/storage/sql/alter</le:path>?path=<view:get-schema/>
          </tpl:token>
          Structure
        </a>
        <tpl:apply mode="actions"/>
      </div>
      <table js:class="sylma.crud.list.Table" js:name="table" class="sylma-list sql-{static()/name()}">
        <tpl:apply select="static()" mode="head/row"/>
        <crud:include path="list"/>
      </table>
    </div>

  </tpl:template>

  <tpl:template match="*" mode="head/row">
    <thead js:class="sylma.ui.Base" js:name="head">
      <tr>
        <th>
          <a>action</a>
        </th>
        <tpl:apply use="list-cols" mode="head/cell"/>
      </tr>
    </thead>
  </tpl:template>

  <tpl:template match="*" mode="head/cell">
    <th>
      <a href="#" js:class="sylma.crud.list.Head">
        <js:option name="name"><tpl:apply select="alias()"/></js:option>
        <js:event name="click">
          return %object%.update();
        </js:event>
        <tpl:apply select="title()"/>
      </a>
    </th>
  </tpl:template>

  <!-- Internal list -->

  <tpl:template mode="internal">

    <tpl:apply mode="init"/>

    <tbody js:name="container" js:class="sylma.crud.list.Container">

      <tpl:apply mode="init-container"/>

      <js:option name="path">
        <crud:path/>
      </js:option>
      <js:option name="send.order">
        <le:get-argument name="order"/>
      </js:option>

      <tpl:if test="has-children()">

        <tpl:apply select="*" mode="row"/>

        <tpl:else>
          <tr>
            <td colspan="99">
              <p class="sylma-noresult">No result</p>
            </td>
          </tr>
        </tpl:else>
      </tpl:if>

      <tr>
        <td colspan="99">
          <tpl:apply select="pager()"/>
        </td>
      </tr>

    </tbody>

  </tpl:template>

  <tpl:template mode="init">

    <tpl:apply mode="init-pager"/>

    <le:argument name="order" format="string">
      <le:default>
        <tpl:apply select="$$list-order"/>
      </le:default>
    </le:argument>

    <sql:order>
      <le:get-argument name="order"/>
    </sql:order>

  </tpl:template>

  <tpl:template match="*" mode="row/init">
    <js:option name="url" cast="x">
      <le:path/>/update?id=<tpl:read select="id"/>
    </js:option>
  </tpl:template>

  <tpl:template match="*" mode="row">
    <tr js:class="sylma.crud.list.Row">
      <tpl:apply mode="row/init"/>
      <js:event name="click">
        %object%.onClick(e);
      </js:event>
      <tpl:apply mode="init-row"/>
      <td>
        <tpl:apply mode="row/action"/>
      </td>
      <tpl:apply use="list-cols" mode="cell"/>
    </tr>
  </tpl:template>

  <tpl:template match="*" mode="row/action">
    <a title="Editer" class="button">
      <tpl:token name="href">
        <le:path/>/update?id=<tpl:read select="id"/>
      </tpl:token>
      E
    </a>
  </tpl:template>

  <tpl:template match="*" mode="cell">
    <td>
      <tpl:apply/>
    </td>
  </tpl:template>

</tpl:collection>
