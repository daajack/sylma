<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2013.sylma.org/view/test/sample1"
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema"
>

  <table name="user_foreign_self" connection="test">

    <field name="id" type="sql:id"/>

    <field name="name" type="sql:string-short"/>
    <foreign occurs="0..n" name="parents" table="user_foreign_self" import="user_foreign_self.xql" junction="user_user"/>

  </table>

</schema>

