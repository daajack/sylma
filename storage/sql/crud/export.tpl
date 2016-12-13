<?xml version="1.0" encoding="utf-8"?>
<tpl:templates
  xmlns:view="http://2013.sylma.org/view"
  xmlns:tpl="http://2013.sylma.org/template"
  
  xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
  xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
>
  
  <tpl:template>
    <worksheet>
      <sheetPr filterMode="false">
        <pageSetUpPr fitToPage="false"/>
      </sheetPr>
      <sheetViews>
        <sheetView windowProtection="false" showFormulas="false" showGridLines="true" showRowColHeaders="true" showZeros="true" rightToLeft="false" tabSelected="true" showOutlineSymbols="true" defaultGridColor="true" view="normal" topLeftCell="A1" colorId="64" zoomScale="100" zoomScaleNormal="100" zoomScalePageLayoutView="100" workbookViewId="0">
          <selection pane="topLeft" activeCell="A3" activeCellId="0" sqref="A3"/>
        </sheetView>
      </sheetViews>
      <sheetFormatPr defaultRowHeight="12.8"></sheetFormatPr>
      <cols>
        <col collapsed="false" hidden="false" max="1025" min="1" style="0" width="11.3418367346939"/>
      </cols>
      <sheetData>
        <tpl:apply select="static()" mode="head"/>
        <tpl:apply select="*" mode="row"/>
      </sheetData>
      <printOptions headings="false" gridLines="false" gridLinesSet="true" horizontalCentered="false" verticalCentered="false"/>
      <pageMargins left="0.7875" right="0.7875" top="1.05277777777778" bottom="1.05277777777778" header="0.7875" footer="0.7875"/>
      <pageSetup paperSize="9" scale="100" firstPageNumber="1" fitToWidth="1" fitToHeight="1" pageOrder="downThenOver" orientation="portrait" usePrinterDefaults="false" blackAndWhite="false" draft="false" cellComments="none" useFirstPageNumber="true" horizontalDpi="300" verticalDpi="300" copies="1"/>
      <headerFooter differentFirst="false" differentOddEven="false">
        <oddHeader>&amp;C&amp;&quot;Times New Roman,Normal&quot;&amp;12&amp;A</oddHeader>
        <oddFooter>&amp;C&amp;&quot;Times New Roman,Normal&quot;&amp;12Page &amp;P</oddFooter>
      </headerFooter>
    </worksheet>
  </tpl:template>

  <tpl:template match="*" mode="head">
    <row>
      <tpl:apply use="list-cols" mode="head/cell"/>
    </row>
  </tpl:template>

  <tpl:template match="*" mode="head/cell">
    <c s="0" t="inlineStr">
      <is>
        <t>
          <tpl:apply mode="head/cell/title"/>
        </t>
      </is>
    </c>
  </tpl:template>
  
  <tpl:template match="*" mode="row">
    <row>
      <tpl:apply use="list-cols" mode="cell"/>
    </row>
  </tpl:template>
  
  <tpl:template match="*" mode="cell">
    <c s="0" t="inlineStr">
      <is>
        <t>
          <tpl:apply mode="cell/content"/>
        </t>
      </is>
    </c>
  </tpl:template>
    
</tpl:templates>
