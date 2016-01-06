<?xml version="1.0" encoding="utf-8"?>
<schema
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"

  xmlns:stat="http://2013.sylma.org/modules/todo/statut"
  xmlns:task="http://2013.sylma.org/modules/todo"

  targetNamespace="http://2013.sylma.org/modules/todo/project"
>

  <table name="todo_project" connection="common">

    <field name="id" type="sql:id"/>

    <field name="name" type="sql:string"/>
    <field name="description" type="sql:string-long" default="null"/>
    <field name="insert" type="sql:datetime" default="now()" alter-default="null"/>

    <foreign name="statut" title="statut" occurs="0..1" table="stat:todo_statut" import="statut.xql" default="1"/>

    <reference name="tasks" table="task:todo" foreign="task:project" import="schema.xql"/>

  </table>

</schema>

