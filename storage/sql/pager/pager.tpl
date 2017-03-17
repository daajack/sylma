<?xml version="1.0" encoding="utf-8"?>
<view:view
  xmlns:crud="http://2013.sylma.org/view/crud"
  xmlns:view="http://2013.sylma.org/view"
  xmlns="http://2014.sylma.org/html"
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"
  xmlns:sql="http://2013.sylma.org/storage/sql"
  xmlns:js="http://2013.sylma.org/template/binder"
  xmlns:le="http://2013.sylma.org/action"
  xmlns:ls="http://2013.sylma.org/parser/security"
>

  <tpl:template mode="pager/init">

    <js:include>Pager.js</js:include>

  </tpl:template>

  <!-- @deprecated, use pager/post, dummy or get instead -->
  <tpl:template mode="pager/argument">
    <tpl:deprecated/>
  </tpl:template>

  <tpl:template mode="pager/post">

    <tpl:apply mode="pager/init"/>

    <le:argument name="sylma-page" format="integer" source="post">
      <le:default>1</le:default>
    </le:argument>

    <sql:pager>
      <sql:current>
        <le:get-argument name="sylma-page" source="post"/>
      </sql:current>
      <sql:count>
        <tpl:apply mode="pager/count"/>
      </sql:count>
    </sql:pager>

  </tpl:template>

  <tpl:template mode="pager/get">

    <tpl:apply mode="pager/init"/>

    <le:argument name="sylma-page" format="integer">
      <le:default>1</le:default>
    </le:argument>

    <sql:pager>
      <sql:current>
        <le:get-argument name="sylma-page"/>
      </sql:current>
      <sql:count>
        <tpl:apply mode="pager/count"/>
      </sql:count>
    </sql:pager>

  </tpl:template>

  <tpl:template match="*" mode="pager/dummy">

    <tpl:apply mode="pager/init"/>

    <sql:pager>
      <sql:current>
        <tpl:read select="dummy()/sylma-page"/>
      </sql:current>
      <sql:count>
        <tpl:apply mode="pager/count"/>
      </sql:count>
    </sql:pager>

  </tpl:template>

  <tpl:template mode="pager/count">20</tpl:template>

  <tpl:template match="sql:pager">

    <tpl:apply select="init()"/>

    <div class="pager" js:class="sylma.crud.Pager" js:name="pager">

      <div class="clearfix">
        
        <tpl:apply mode="pager/previous"/>
        <tpl:apply mode="pager/pages"/>
        <tpl:apply mode="pager/next"/>

        <input type="hidden" name="sylma-page" value="{current}" js:node="input"/>

      </div>
    </div>

  </tpl:template>

  <tpl:template match="*" mode="pager/previous">

    <a href="javascript:void(0)" title="Page précédente" class="button previous">
      <tpl:if test="is-first()">
        <tpl:token name="class">form-disable</tpl:token>
      </tpl:if>
      <js:option name="prev">
        <tpl:apply select="previous"/>
      </js:option>
      <js:event name="click">
        %object%.goPage(%object%.get('prev'), e);
      </js:event>
      <tpl:apply mode="pager/previous/content"/>
    </a>

  </tpl:template>

  <tpl:template match="*" mode="pager/previous/content">←</tpl:template>

  <tpl:template match="*" mode="pager/current">

    <span class="infos">

      <a href="javascript:void(0)" title="Première page" class="page current">
        <tpl:if test="is-first()">
          <tpl:token name="class">form-disable</tpl:token>
        </tpl:if>
        <js:event name="click">
          %object%.goPage(1, e);
        </js:event>
        <tpl:read select="current"/>
      </a>

      <span class="separator">/</span>

      <a href="javascript:void(0)" title="Dernière page" class="page total">
        <tpl:if test="is-last()">
          <tpl:token name="class">form-disable</tpl:token>
        </tpl:if>
        <js:option name="last">
          <tpl:apply select="last"/>
        </js:option>
        <js:event name="click">
          %object%.goPage(%object%.get('last'), e);
        </js:event>
        <tpl:read select="last"/>
      </a>

    </span>

  </tpl:template>

  <tpl:template match="*" mode="pager/pages">
      <tpl:apply select="pages()" mode="pager/page"/>
  </tpl:template>

  <tpl:template match="*" mode="pager/page">
    <tpl:variable name="value">
      <tpl:read select="page/value"/>
    </tpl:variable>
    <tpl:variable name="valuecurrent">
      <tpl:read select="current/value"/>
    </tpl:variable>
    <tpl:variable name="valuemin"><tpl:read select="($valuecurrent - $value)"/></tpl:variable>
    <tpl:variable name="compare"><tpl:read select="($value &lt;= $valuecurrent + 4 and $value >= $valuecurrent - 4)"/></tpl:variable>
    <tpl:variable name="comparemin"><tpl:read select="($valuecurrent &lt; 5 and $value &lt;= 9)"/></tpl:variable>
    <tpl:variable name="compareminend"><tpl:read select="($valuecurrent > last - 4 and page > last - 9)"/></tpl:variable>
    <tpl:variable name="comparelast"><tpl:read select="(page = $valuecurrent + 4 and page != last and $value > 9)"/></tpl:variable>
    <tpl:variable name="comparefirst"><tpl:read select="(page = $valuecurrent - 4 and page != 1 and $valuecurrent &lt; last - 4)"/></tpl:variable>
    <tpl:variable name="comparelastmin"><tpl:read select="(page = 9 and $valuecurrent &lt;= 5)"/></tpl:variable>
    <tpl:variable name="comparefirstmin"><tpl:read select="(page = last - 8 and $valuecurrent >= last - 4)"/></tpl:variable>
    
    <tpl:if test="last &lt;= 9 or $value = $valuecurrent or $comparemin or $compare or $compareminend">  
      
      <tpl:if test="$comparefirst or $comparefirstmin">
        
        <tpl:apply mode="pager/page/content">
          <tpl:read select="'1'" tpl:name="page"/>
        </tpl:apply>
        <tpl:text>|</tpl:text>
        <tpl:if test="page != 2">
          <tpl:text> ... |</tpl:text>
        </tpl:if>
      </tpl:if> 
      
      <tpl:apply mode="pager/page/content"/>

      <tpl:if test="page != last">|</tpl:if>
      
      <tpl:if test="$comparelast or $comparelastmin">
        
        <tpl:if test="page + 1 != last">
          <tpl:text> ... |</tpl:text>
        </tpl:if>  
        
        <tpl:apply mode="pager/page/content">
          <tpl:read select="last" tpl:name="page"/>
        </tpl:apply>
      </tpl:if>
      
    </tpl:if>  
  </tpl:template>
  
  <tpl:template match="*" mode="pager/page/content">
    <tpl:argument name="page" default="page"/>
    <a href="javascript:void(0)" class="page {($page = current ? 'current' : '')}" data-page="{$page}">
      <js:event name="click">
        %object%.goPage(this.get('data-page'), e);
      </js:event>
      <tpl:read select="$page"/>
    </a>
  </tpl:template>
        
  <tpl:template match="*" mode="pager/next">

    <a href="javascript:void(0)" title="Page suivante" class="button next">
      <tpl:if test="is-last() or page = 0">
        <tpl:token name="class">form-disable</tpl:token>
      </tpl:if>
      <js:option name="next">
        <tpl:apply select="next"/>
      </js:option>
      <js:event name="click">
        %object%.goPage(%object%.get('next'), e);
      </js:event>
      <tpl:apply mode="pager/next/content"/>
    </a>

  </tpl:template>

  <tpl:template match="*" mode="pager/next/content">→</tpl:template>

</view:view>
