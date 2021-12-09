<?php
ini_set("memory_limit","-1");
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

try {
  $inputFileName = readline("Paste path of PSGC excel file: ");
  $inputFileType = IOFactory::identify($inputFileName);
  $objReader = IOFactory::createReader($inputFileType);
  $objPHPExcel = $objReader->load($inputFileName);
} catch(Exception $e) {
  die(var_dump("Error reading file ".$inputFileName));
}
      
$objPHPExcel->setActiveSheetIndexByName("PSGC");
$sheet = $objPHPExcel->getActiveSheet();

$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();

//startRow = 2; code = A; name = B; geoLevel = C; oldNames = D;

$out = "CREATE DATABASE IF NOT EXISTS psgc_address_map;\n
USE psgc_address_map;\n

CREATE TABLE IF NOT EXISTS psgc_regions (
	id int UNIQUE PRIMARY KEY AUTO_INCREMENT,\n
	Code varchar(255),\n
    CurrentName varchar(255),\n
    OldName varchar(255)
);\n
CREATE TABLE IF NOT EXISTS psgc_provinces (
	id int UNIQUE PRIMARY KEY AUTO_INCREMENT,\n
    RegionID int,\n
	Code varchar(255),\n
    CurrentName varchar(255),\n
    OldName varchar(255),\n
    CONSTRAINT `RegionID` FOREIGN KEY (`RegionID`) REFERENCES `psgc_regions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);\n
CREATE TABLE IF NOT EXISTS psgc_cities (
	id int UNIQUE PRIMARY KEY AUTO_INCREMENT,\n
    ProvinceID int,\n
	Code varchar(255),\n
    CurrentName varchar(255),\n
    OldName varchar(255),\n
    CONSTRAINT `ProvinceID` FOREIGN KEY (`ProvinceID`) REFERENCES `psgc_provinces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);\n
CREATE TABLE IF NOT EXISTS psgc_bgys (
	id int UNIQUE PRIMARY KEY AUTO_INCREMENT,\n
    CityID int,\n
	Code varchar(255),\n
    CurrentName varchar(255),\n
    OldName varchar(255),\n
    CONSTRAINT `CityID` FOREIGN KEY (`CityID`) REFERENCES `psgc_cities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);\n
";
for($x = 2; $x <= $highestRow; $x++) {
  $code = trim($sheet->getCell("A".$x)->getValue());
  $name = trim($sheet->getCell("B".$x)->getValue());
  $geoLevel = trim($sheet->getCell("C".$x)->getValue());
  $oldNames = trim($sheet->getCell("D".$x)->getValue());
  if(strlen($geoLevel) > 0) {
    if(in_array($geoLevel,array('Prov','Dist')) !== false) {
      $out .= "INSERT INTO psgc_provinces VALUES (null,null,\"$code\",\"$name\",\"$oldNames\");\n";
    } elseif(in_array($geoLevel,array('City','Mun','SubMun')) !== false) {
      $out .= "INSERT INTO psgc_cities VALUES (null,null,\"$code\",\"$name\",\"$oldNames\");\n";
    } elseif($geoLevel == 'Reg') {
      $out .= "INSERT INTO psgc_regions VALUES (null,\"$code\",\"$name\",\"$oldNames\");\n";
    } elseif($geoLevel == 'Bgy') {
      $out .= "INSERT INTO psgc_bgys VALUES (null,null,\"$code\",\"$name\",\"$oldNames\");\n";
    }
  }
}
$out .= "
UPDATE psgc_provinces SET RegionID = (SELECT id FROM psgc_regions WHERE Code = RPAD(LEFT(psgc_provinces.Code,2),9,'0'));\n
UPDATE psgc_cities SET ProvinceID = (SELECT id FROM psgc_provinces WHERE Code = RPAD(LEFT(psgc_cities.Code,4),9,'0'));\n
UPDATE psgc_bgys SET CityID = (SELECT id FROM psgc_cities WHERE Code = RPAD(LEFT(psgc_bgys.Code,6),9,'0'));\n
";

$fileName = "psgc_".date('YmdHis').".sql";
$sqlFile = fopen($fileName,"w");
fwrite($sqlFile,$out);
fclose($sqlFile);
die("Generated file "."psgc_".date('YmdHis').".sql");