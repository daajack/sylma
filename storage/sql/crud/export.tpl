<?xml version="1.0" encoding="utf-8"?>
<tpl:templates
  xmlns:view="http://2013.sylma.org/view"
  xmlns:tpl="http://2013.sylma.org/template"
  xmlns:sql="http://2013.sylma.org/storage/sql"
  
  xmlns:m="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
  xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
>

  <tpl:template>

    <tpl:apply mode="dummy"/>

    <tpl:apply mode="order/prepare"/>

    <tpl:apply select="init()"/>
    <tpl:apply select="counter()"/>

    <sql:order>
      <tpl:read select="dummy()/sylma-order"/>
    </sql:order>

    <tpl:apply select="static()" mode="init/internal"/>

    <tpl:apply mode="export"/>

  </tpl:template>

  <tpl:template mode="export">
    <m:worksheet>
      <m:sheetPr filterMode="false">
        <m:pageSetUpPr fitToPage="false"/>
      </m:sheetPr>
      <m:sheetViews>
        <m:sheetView windowProtection="false" showFormulas="false" showGridLines="true" showRowColHeaders="true" showZeros="true" rightToLeft="false" tabSelected="true" showOutlineSymbols="true" defaultGridColor="true" view="normal" topLeftCell="A1" colorId="64" zoomScale="100" zoomScaleNormal="100" zoomScalePageLayoutView="100" workbookViewId="0">
          <m:selection pane="topLeft" activeCell="A3" activeCellId="0" sqref="A3"/>
        </m:sheetView>
      </m:sheetViews>
      <m:sheetFormatPr defaultRowHeight="12.8"></m:sheetFormatPr>
      <m:cols>
        <m:col collapsed="false" hidden="false" max="1025" min="1" style="0" width="11.3418367346939"/>
      </m:cols>
      <m:sheetData>
        <tpl:apply select="static()" mode="export/head"/>
        <tpl:apply select="*" mode="export/row"/>
      </m:sheetData>
      <m:printOptions headings="false" gridLines="false" gridLinesSet="true" horizontalCentered="false" verticalCentered="false"/>
      <m:pageMargins left="0.7875" right="0.7875" top="1.05277777777778" bottom="1.05277777777778" header="0.7875" footer="0.7875"/>
      <m:pageSetup paperSize="9" scale="100" firstPageNumber="1" fitToWidth="1" fitToHeight="1" pageOrder="downThenOver" orientation="portrait" usePrinterDefaults="false" blackAndWhite="false" draft="false" cellComments="none" useFirstPageNumber="true" horizontalDpi="300" verticalDpi="300" copies="1"/>
      <m:headerFooter differentFirst="false" differentOddEven="false">
        <m:oddHeader>&amp;C&amp;&quot;Times New Roman,Normal&quot;&amp;12&amp;A</m:oddHeader>
        <m:oddFooter>&amp;C&amp;&quot;Times New Roman,Normal&quot;&amp;12Page &amp;P</m:oddFooter>
      </m:headerFooter>
    </m:worksheet>
  </tpl:template>

  <tpl:template match="*" mode="export/head">
    <m:row>
      <tpl:apply use="list-cols" mode="export/head/cell"/>
    </m:row>
  </tpl:template>

  <tpl:template match="*" mode="export/head/cell">
    <m:c s="0" t="inlineStr">
      <m:is>
        <m:t>
          <tpl:apply mode="head/cell/title"/>
        </m:t>
      </m:is>
    </m:c>
  </tpl:template>
  
  <tpl:template match="*" mode="export/row">
    <tpl:apply mode="row/filter"/>
    <m:row>
      <tpl:apply use="list-cols" mode="export/cell"/>
    </m:row>
  </tpl:template>
  
  <tpl:template match="*" mode="export/cell">
    <m:c s="0" t="inlineStr">
      <m:is>
        <m:t>
          <tpl:apply mode="cell/content"/>
        </m:t>
      </m:is>
    </m:c>
  </tpl:template>
    
</tpl:templates>
