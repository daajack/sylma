<?xml version="1.0" encoding="utf-8"?>
<schema
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"

  targetNamespace="sylma:report"
>

  <table name="report" reflector="\sylma\modules\report\Report">

    <field name="id" type="sql:id"/>

    <field name="email" type="sql:string" default="null"/>
    <field name="url" type="sql:string" default="null"/>
    <field name="description" type="sql:string-long" default="null"/>
    
    <field name="infos" type="sql:string-long"/>

  </table>

</schema>

