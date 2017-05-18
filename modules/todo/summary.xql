<?xml version="1.0" encoding="utf-8"?>
<schema
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"

  targetNamespace="http://2013.sylma.org/modules/todo/summary"
>

  <table name="todo_summary" connection="common">

    <field name="id" type="sql:id"/>
    <field name="content" type="sql:string"/>

  </table>

</schema>

