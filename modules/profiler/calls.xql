<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2015.sylma.org/modules/profiler"
  xmlns="http://2013.sylma.org/storage/sql"
  
  xmlns:php="http://2015.sylma.org/modules/inspector/php"
>

  <table name="profiler_calls" connection="test">

    <foreign name="source" occurs="0..1" table="php:php_method" key="fullname" import="../inspector/php/method.xql"/>
    <foreign name="target" occurs="0..1" table="php:php_method" key="fullname" import="../inspector/php/method.xql"/>

    <field name="ct" type="sql:int" default="null"/>
    <field name="wt" type="sql:int" default="null"/>
    <field name="cpu" type="sql:int" default="null"/>
    <field name="mu" type="sql:int" default="null"/>
    <field name="pmu" type="sql:int" default="null"/>

  </table>

</schema>

