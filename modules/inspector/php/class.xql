<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2015.sylma.org/modules/inspector/php"
  xmlns="http://2013.sylma.org/storage/sql"
>

  <table name="php_class" connection="test">

    <field name="id" type="sql:id"/>

    <field name="name" type="sql:string"/>
    <field name="name_short" type="sql:string-short"/>
    <field name="description" type="sql:string" default="null"/>

    <foreign name="file" occurs="0..1" table="php_file" import="file.xql"/>
    <foreign name="namespace" occurs="0..1" table="php_namespace" import="namespace.xql" default="null"/>

    <foreign name="extends" occurs="0..n" table="php_class" junction="php_extends" import="class.xql" default="null"/>
    <foreign occurs="0..n" name="interfaces" table="php_interface" import="interface.xql" junction="php_implements"/>

    <reference name="methods" table="php_method" foreign="class" import="method.xql"/>

  </table>

</schema>

