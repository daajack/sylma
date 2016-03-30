<?xml version="1.0" encoding="utf-8"?>
<schema
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"

  xmlns:sub1="mysite:mymodule:sub1"
  xmlns:picture="mysite:mymodule:picture"

  targetNamespace="mysite:mymodule"
>

  <table name="mymodule">

    <field name="id" type="sql:id"/>

    <field name="name" type="sql:string"/>

    <reference name="pictures" table="picture:mymodule_picture" foreign="picture:parent" import="picture.xql"/>
    <reference name="sub1s" table="sub1:mymodule_sub1" foreign="sub1:parent" import="sub1.xql"/>

  </table>

</schema>

