<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2013.sylma.org/modules/stepper/test/country"
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"
  xmlns:sql="http://2013.sylma.org/storage/sql"

  xmlns:user="http://2013.sylma.org/modules/stepper/test/user"
  xmlns:lang="http://2013.sylma.org/modules/stepper/test/lang"
>

  <table name="country_user" connection="test">
    
    <field name="id" type="sql:id"/>
    <field name="name" type="sql:string-short"/>
    
    <foreign name="user" occurs="1..1" table="user:user_duplicate_refs" import="user_duplicate_refs.xql"/>
    <foreign name="lang" occurs="1..1" table="lang:user_lang" import="user_duplicate_refs_lang.xql"/>
    
  </table>

</schema>

