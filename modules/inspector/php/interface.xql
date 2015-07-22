<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2015.sylma.org/modules/inspector/php"
  xmlns="http://2013.sylma.org/storage/sql"
>

  <table name="php_interface" connection="test">

    <field name="id" type="sql:id"/>

    <field name="name" type="sql:string"/>
    <field name="description" type="sql:string" default="null"/>

    <foreign name="file" occurs="0..1" table="php_file" import="file.xql"/>
    <foreign name="namespace" occurs="0..1" table="php_namespace" import="namespace.xql"/>

  </table>

</schema>

