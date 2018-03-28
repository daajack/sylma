<?xml version="1.0" encoding="utf-8"?>
<schema
  targetNamespace="http://2013.sylma.org/modules/stepper/test/user01"
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"
  xmlns:sql="http://2013.sylma.org/storage/sql"

  xmlns:city="http://2013.sylma.org/modules/stepper/test/city"
  xmlns:country="http://2013.sylma.org/modules/stepper/test/country"
>

  <table name="user_duplicate_refs" connection="test">
    <field name="id" type="sql:id"/>
    <field name="name" type="sql:string-short"/>
    <reference name="city" table="city:city_user" foreign="city:user" import="user_duplicate_refs_city.xql"/>
    <reference name="country" table="country:country_user" foreign="country:user" import="user_duplicate_refs_country.xql"/>
  </table>

</schema>

