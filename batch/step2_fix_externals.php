<?php

# Usage:
# php step1_clone_repo.php repository_dump_directory/ repo_name
# e.g php batch/step2_fix_externals.php /media/b91eeaef-82c7-4ae6-9713-44ce65eb25e6/home/hassen/web_dld/ssi/ ctms



define('SF_ROOT_DIR', realpath(dirname(__FILE__) . '/../'));
define('SF_APP', 'frontend');
define('SF_ENVIRONMENT', 'dev');
define('SF_DEBUG', true);

require_once (SF_ROOT_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . SF_APP . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');

define('REPOS_DIR', $argv[1]);
define('REPO_NAME', $argv[2]);

main();

function main()
{
    fixSubmodules();
}

function fixSubmodules()
{
    $externalsFilename = REPOS_DIR . REPO_NAME. '.externals_edit_me.txt';
    if (!file_exists($externalsFilename))
    {
        echo "\n\nFile $externalsFilename not found. check step1";
        exit;
    }

    $yaml = sfYaml::load(file_get_contents($externalsFilename));

    createSubmodules($yaml);
}

function createSubmodules($yaml)
{
    chdir(REPOS_DIR);

    // loop through submodules
    foreach ($yaml['submodules'] as $submod)
    {
        // skip if repo already exists
        $repoPath = REPOS_DIR . $submod['name'];
        if(file_exists($repoPath))
        {
            echo "\n\n skipping $repoPath";
        }
        else
        {
            // convert svn repo to git
            $step1Script = dirname(__FILE__) . '/step1_clone_repo.php';

            $params = array(
                'php',
                $step1Script, 
                REPOS_DIR,
                'authors.txt',
                $submod['url'],
                $submod['name']
            );

            $cmd = implode(" ", $params);
            echo shell_exec($cmd);
        }
    }
}

// create .gitmodules file for the repo
function generateGitSubmodulesFile()
{
    // loop through submodules
}

// create .svn_externals_links file
function generateSymlinksFile()
{

}

function copyExternalsInfo()
{

}

?>