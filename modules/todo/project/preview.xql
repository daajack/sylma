<?xml version="1.0" encoding="utf-8"?>
<schema
  xmlns="http://2013.sylma.org/storage/sql"
  xmlns:xs="http://www.w3.org/2001/XMLSchema"
  xmlns:ssd="http://2013.sylma.org/schema/ssd"
  
  xmlns:project="http://2013.sylma.org/modules/todo/project"

  targetNamespace="http://2013.sylma.org/modules/todo/project/preview"
>

  <table name="todo_project_preview" connection="common">

    <field name="id" type="sql:id"/>
    <field name="name" type="sql:string-short"/>
    <field name="path" type="sql:string-short"/>
    <field name="size" type="sql:string-short"/>
    <field name="extension" type="sql:string-short"/>

    <foreign name="project" occurs="0..1" table="project:todo_project" import="../project.xql"/>

  </table>

</schema>

