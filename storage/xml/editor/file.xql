<?xml version="1.0" encoding="utf-8"?>
<schema
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"

  xmlns:history="http://2016.sylma.org/storage/xml/editor/history"

  targetNamespace="http://2016.sylma.org/storage/xml/editor/file"
>

  <table name="editor_file">

    <field name="id" type="sql:id"/>

    <field name="path" type="sql:string"/>
    <field name="lock" type="sql:boolean"/>
    <field name="steps" type="sql:int" default="0"/>
    <field name="update" type="sql:timestamp"/>
    
    <foreign name="step" occurs="0..1" table="history:editor_history" import="history.xql"/>
    <reference name="history" table="history:editor_history" foreign="history:file" import="history.xql"/>

  </table>

</schema>

