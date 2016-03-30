<?xml version="1.0" encoding="utf-8"?>
<schema
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"

  xmlns:parent="mysite:mymodule"

  targetNamespace="mysite:mymodule_sub1"
>

  <table name="mymodule_sub1">

    <field name="id" type="sql:id"/>

    <field name="title" type="sql:string"/>
    <field name="description" type="sql:string-long"/>
    
    <foreign name="parent" occurs="0..1" table="parent:mymodule" import="mymodule.xql"/>

  </table>

</schema>

