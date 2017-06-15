<?xml version="1.0" encoding="utf-8"?>
<schema
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"

  xmlns:user="http://2013.sylma.org/core/user"
  xmlns:file="http://2016.sylma.org/storage/xml/editor/file"

  targetNamespace="http://2016.sylma.org/storage/xml/editor/history"
>

  <table name="editor_history">

    <field name="id" type="sql:id"/>

    <field name="type" type="sql:string-short"/>
    <field name="path" type="sql:string" default="null"/>
    <field name="content" type="sql:string-long" default="null"/>
    <field name="arguments" type="sql:string" default="null"/>
    <field name="disabled" type="sql:boolean" default="0"/>

    <field name="update" type="sql:datetime"/>

    <foreign name="file" occurs="0..1" table="file:editor_file" import="file.xql"/>
    <foreign name="user" occurs="0..1" table="user:user" import="/#sylma/modules/users/schema.xql"/>

  </table>

</schema>

