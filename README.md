# psgc-sql
Generate SQL File from PSGC Excel File. This aims to easily parse the PSGC excel file to be imported to an SQL database for use in addresses for web apps in the Philippines.

# Requirements
- [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/en/latest/#installation) (install via Composer)
- [PSGC Latest Publication excel file](https://psa.gov.ph/classification/psgc/) (Can be found under Download PSGC Publications > Latest Release > Publication)

# How to use
- Run PHP file via terminal / cmd: php path/to/generate.php
- Drag and drop file downloaded from PSGC website, and press Enter
- Done! An SQL file will be generated on the same directory as the generate.php file

# Function
This extracts the code, name, and old name from the excel file.

A database named 'psgc' will be created.
