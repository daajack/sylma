<?xml version="1.0" encoding="utf-8"?>
<schema
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"

  xmlns:sub1="mysite:mymodule:sub1"
  xmlns:image="mysite:mymodule:image"

  targetNamespace="mysite:mymodule"
>

  <table name="mymodule">

    <field name="id" type="sql:id"/>

    <field name="name" type="sql:string"/>
    <field name="update" type="sql:datetime"/>

    <reference name="images" table="image:mymodule_image" foreign="image:parent" import="image.xql"/>
    <reference name="sub1s" table="sub1:mymodule_sub1" foreign="sub1:parent" import="sub1.xql"/>

  </table>

</schema>

