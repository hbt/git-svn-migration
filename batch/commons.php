<?php

define('TMP_SVN_POSTFIX', '___tmpsvn');
define('EXTERNALS_POSTFIX', '___externals.edit.me.txt');

function readYAMLFile()
{
    $externalsFilename = REPOS_DIR . REPO_NAME . EXTERNALS_POSTFIX;
    if (!file_exists($externalsFilename))
    {
        echo "\n\nFile $externalsFilename not found. check step1";
        exit;
    }

    $yaml = sfYaml::load(file_get_contents($externalsFilename));

    return $yaml;
}

?>