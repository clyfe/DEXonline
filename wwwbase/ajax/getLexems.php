<?php
require_once("../../phplib/util.php");

$query = util_getRequestParameter('q');
$parts = preg_split('/\(/', $query, 2);
$name = AdminStringUtil::internalizeWordName(trim($parts[0]));
$field = StringUtil::hasDiacritics($name) ? 'formNoAccent' : 'formUtf8General';

if (count($parts) == 2) {
  $description = trim($parts[1]);
  $description = str_replace(')', '', $description);
  $lexems = db_find(new Lexem(), "$field = '{$name}' and description like '{$description}%' order by formNoAccent, description limit 10");
} else {
  $lexems = db_find(new Lexem(), "$field like '{$name}%' order by formNoAccent limit 10");
}

foreach ($lexems as $l) {
  print "{$l}\n";
}

?>
