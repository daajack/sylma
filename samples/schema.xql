<?xml version="1.0" encoding="utf-8"?>
<schema
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"

  xmlns:sample2="mysite:sample2"
  xmlns:sample3="mysite:sample3"

  targetNamespace="mysite:sample1"
>

  <table name="sample1">

    <field name="id" type="sql:id"/>

    <field name="name" type="sql:string" title="full name"/>
    <field name="alias" type="sql:string-short" default="null"/>
    <field name="description" type="sql:string-long"/>
    <field name="priority" type="sql:int" default="0"/>
    <field name="duration" type="sql:float" default="null"/>

    <field name="update" type="sql:datetime"/>
    <field name="insert" type="sql:datetime" default="now()" alter-default="null"/>

    <foreign name="simple" occurs="0..1" table="sample2:sample2" import="sample2.xql"/>
    <foreign name="multiple" occurs="0..n" table="sample2:sample2" import="sample2.xql" junction="sample1_sample2"/>
    
    <reference name="samples3" table="sample3:sample3" foreign="sample3:sample1" import="sample3.xql"/>

  </table>

</schema>

