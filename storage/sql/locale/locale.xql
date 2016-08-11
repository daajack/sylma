<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2016.sylma.org/storage/sql/locale"
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"

  xmlns:group="http://2013.sylma.org/core/user/group"
  xmlns:courtier="http://www.monlocal.ch/courtier"
  xmlns:societe="http://www.monlocal.ch/societe"
  xmlns:titre="http://www.monlocal.ch/titre"
>

  <table name="locale">

    <field name="id" type="sql:id"/>
    <field name="content" type="sql:string-long" locale="x"/>
    <field name="page" type="sql:string" default="null"/>

  </table>

</schema>

