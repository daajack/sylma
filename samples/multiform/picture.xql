<?xml version="1.0" encoding="utf-8"?>
<schema
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"

  xmlns:parent="mysite:mymodule"

  targetNamespace="mysite:mymodule:picture"
>

  <table name="mymodule_picture">

    <field name="id" type="sql:id"/>

    <field name="name" type="sql:string-short"/>
    <field name="path" type="sql:string-short"/>
    <field name="size" type="sql:string-short"/>
    <field name="extension" type="sql:string-short"/>
    <field name="position" type="sql:int"/>

    <foreign name="parent" occurs="0..1" table="parent:mymodule" import="mymodule.xql"/>

  </table>

</schema>

