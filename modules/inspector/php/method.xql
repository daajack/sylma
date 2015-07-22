<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2015.sylma.org/modules/inspector/php"
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:profiler="http://2015.sylma.org/modules/profiler"
>

  <table name="php_method" connection="test">

    <field name="id" type="sql:id"/>

    <field name="name" type="sql:string"/>
    <field name="fullname" type="sql:string" unique="x"/>
    <field name="modifiers" type="sql:int"/>
    <field name="description" type="sql:string" default="null"/>

    <field name="return_description" type="sql:string" default="null"/>

    <foreign name="return_scalar" occurs="0..1" table="php_scalar" import="scalar.xql" default="null"/>
    <foreign name="return_class" occurs="0..1" table="php_class" import="class.xql" default="null"/>
    <foreign name="return_interface" occurs="0..1" table="php_interface" import="interface.xql" default="null"/>

    <foreign name="class" occurs="1..1" table="php_class" import="class.xql"/>

    <reference name="callers" table="profiler:profiler_calls" foreign="profiler:target" import="../../profiler/calls.xql"/>
    <reference name="calls" table="profiler:profiler_calls" foreign="profiler:source" import="../../profiler/calls.xql"/>

  </table>

</schema>

