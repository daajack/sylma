<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2015.sylma.org/modules/inspector/php"
  xmlns="http://2013.sylma.org/storage/sql"
>

  <table name="php_argument" connection="test">

    <field name="id" type="sql:id"/>

    <field name="name" type="sql:string"/>
    <field name="description" type="sql:string"/>
    <field name="default" type="sql:string"/>
    <field name="position" type="sql:int"/>

    <foreign name="scalar" occurs="0..1" table="php_scalar" import="scalar.xql"/>
    <foreign name="class" occurs="0..1" table="php_class" import="class.xql"/>
    <foreign name="interface" occurs="0..1" table="php_interface" import="interface.xql"/>

    <foreign name="method" occurs="0..1" table="php_method" import="method.xql"/>

  </table>

</schema>

