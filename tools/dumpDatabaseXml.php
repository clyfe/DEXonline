<?

require_once("../phplib/util.php");

smarty_init();

$TODAY = date("Ymd");
$FOLDER = util_getRootPath() . '/wwwbase/download/xmldump';

log_scriptLog("generating dump for $TODAY");

# dump sources table
smarty_assign('sources', db_find(new Source(), '1 ORDER BY id'));
$sources_xml = smarty_fetch('xmldump/sources.ihtml');
file_put_contents("$FOLDER/$TODAY-sources.xml.gz", gzencode($sources_xml));

# dump inflections table
smarty_assign('inflections', db_find(new Inflection(), '1 ORDER BY id'));
$inflections_xml = smarty_fetch('xmldump/inflections.ihtml');
file_put_contents("$FOLDER/$TODAY-inflections.xml.gz", gzencode($inflections_xml));

# dump abbrev table
$raw_abbrevs = text_loadRawAbbreviations();
$sources = array();
$sections = array();
foreach ($raw_abbrevs as $name => $raw_section) {
  if ($name == "sources") {
    # the index of sources
    foreach ($raw_section as $id => $source) {
      $sources[$id] = preg_split('/, */', $source);
    }
  } else {
    # a single source
    $section = array();
    foreach ($raw_section as $short => $long) {
      $abbrev_info = array('short' => $short, 'long' => $long, 'ambiguous' => false);
      if (substr($short, 0, 1) == "*") {
        $abbrev_info['short'] = substr($short, 1);
        $abbrev_info['ambiguous'] = true;
      }
      $section[] = $abbrev_info;
    }
    $sections[$name] = $section;
  }
}
smarty_assign('sources', $sources);
smarty_assign('sections', $sections);
$abbrevs_xml = smarty_fetch('xmldump/abbrev.ihtml');
file_put_contents("$FOLDER/$TODAY-abbrevs.xml.gz", gzencode($abbrevs_xml));

# dump definitions table
$users = getActiveUsers();
$defResults = db_execute("SELECT * FROM Definition WHERE sourceId IN " .
                         "(SELECT id FROM Source WHERE canDistribute) " .
                         " AND status = 0");

$defsFile = gzopen("$FOLDER/$TODAY-definitions.xml.gz", 'wb9');
gzwrite($defsFile, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
gzwrite($defsFile, "<Definitions>\n");
while (!$defResults->EOF) {
  $def = new Definition();
  $def->set($defResults->fields);
  $def->internalRep = text_xmlizeRequired($def->internalRep);
  smarty_assign('def', $def);
  smarty_assign('nick', $users[$def->userId]);
  $abbrevs_xml = smarty_fetch('xmldump/definition.ihtml');
  gzwrite($defsFile, $abbrevs_xml);
  $defResults->MoveNext();
}
gzwrite($defsFile, "</Definitions>\n");
gzclose($defsFile);


function getActiveUsers() {
  $usersResults = db_execute("SELECT id, nick FROM User WHERE id IN ".
                "(SELECT DISTINCT userId FROM Definition WHERE status = 0)");
  $users = array();
  while (!$usersResults->EOF) {
    $users[$usersResults->fields[0]] = $usersResults->fields[1];
    $usersResults->MoveNext();
  }
  return $users;
}

?>
