<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2013.sylma.org/view/test/sample1"
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema"
>

  <table name="user" connection="sylma_bad_connection">
    <field name="id" type="sql:id"/>
    <field name="name" type="xs:string"/>
    <field name="email" type="xs:string"/>
  </table>

</schema>

