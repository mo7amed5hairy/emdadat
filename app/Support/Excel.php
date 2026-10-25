<?php

namespace App\Support;

use ZipArchive;

class Excel
{
    /**
     * توليد ملف Excel (xlsx) حقيقي بدون أي حزم خارجية.
     * البيانات داخل النص تكون UTF-8 والملف يُكتب بالترميز الصحيح فتُعرض العربية سليمة.
     *
     * @param  array<string>  $headers
     * @param  array<array<mixed>>  $rows
     */
    public static function makeXlsx(array $headers, array $rows): string
    {
        $sheet = self::buildSheet($headers, $rows);

        $temp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        if ($zip->open($temp, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('تعذر إنشاء ملف Excel.');
        }

        $zip->addFromString('[Content_Types].xml', self::xml(
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>'
        ));
        $zip->addFromString('_rels/.rels', self::xml(
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>'
        ));
        $zip->addFromString('xl/workbook.xml', self::xml(
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="تقرير" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>'
        ));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::xml(
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>'
        ));
        $zip->addFromString('xl/styles.xml', self::xml(
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Arial"/></font>'
            .'<font><b/><sz val="11"/><name val="Arial"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF0F766E"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"><color rgb="FFCBD5E1"/></left><right style="thin"><color rgb="FFCBD5E1"/></right>'
            .'<top style="thin"><color rgb="FFCBD5E1"/></top><bottom style="thin"><color rgb="FFCBD5E1"/></bottom><diagonal/></border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>'
        ));
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        $bytes = file_get_contents($temp);
        @unlink($temp);

        return $bytes;
    }

    protected static function buildSheet(array $headers, array $rows): string
    {
        $rowCounts = array_map('count', $rows);
        $maxCol = max(count($headers), $rowCounts ? max($rowCounts) : 0);
        $xml = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0" rightToLeft="1"/></sheetViews>'
            .'<cols>';
        for ($i = 0; $i < $maxCol; $i++) {
            $xml .= '<col min="'.($i + 1).'" max="'.($i + 1).'" width="'.(22).'" customWidth="1"/>';
        }
        $xml .= '</cols><sheetData>';

        // رأس الجدول
        $xml .= '<row r="1">';
        foreach ($headers as $i => $h) {
            $xml .= '<c r="'.self::colLetter($i + 1).'1" s="1" t="inlineStr"><is><t xml:space="preserve">'.self::esc($h).'</t></is></c>';
        }
        $xml .= '</row>';

        // بيانات
        foreach ($rows as $r => $row) {
            $excelRow = $r + 2;
            $xml .= '<row r="'.$excelRow.'">';
            for ($i = 0; $i < $maxCol; $i++) {
                $val = $row[$i] ?? '';
                $cell = '<c r="'.self::colLetter($i + 1).$excelRow.'" s="2"';
                if (is_numeric($val)) {
                    $xml .= $cell.'><v>'.self::esc((string) $val).'</v></c>';
                } else {
                    $xml .= $cell.' t="inlineStr"><is><t xml:space="preserve">'.self::esc((string) $val).'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    protected static function colLetter(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $mod = ($n - 1) % 26;
            $s = chr(65 + $mod).$s;
            $n = intdiv($n - 1, 26);
        }

        return $s;
    }

    protected static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    protected static function xml(string $inner): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.$inner;
    }
}