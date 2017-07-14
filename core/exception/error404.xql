<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2013.sylma.org/core/exception/error404"
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"
>

  <table name="error404">
    <field name="url" type="sql:string" key="url"/>
    <field name="count" type="sql:int"/>
  </table>

</schema>

