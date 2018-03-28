<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2013.sylma.org/modules/stepper/test/lang"
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"
  xmlns:sql="http://2013.sylma.org/storage/sql"

  xmlns:user="http://2013.sylma.org/modules/stepper/test/user"
>

  <table name="lang_user" connection="test">
    <field name="id" type="sql:id"/>
    <field name="name" type="sql:string-short"/>
  </table>

</schema>

