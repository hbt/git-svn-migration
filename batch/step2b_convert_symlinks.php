<?php

# Usage:
# php step1_clone_repo.php repository_dump_directory/ authors_filename svn_url repo_name
# e.g php batch/step1_clone_repo.php /media/b91eeaef-82c7-4ae6-9713-44ce65eb25e6/home/hassen/web_dld/ssi/ http://svn.sylsft.com/projects/uwo/ctms/ ctms

/**
 * read list of symlinks defined for this repository
 * convert symlinks to regular files (copy)
 */

define('SF_ROOT_DIR', realpath(dirname(__FILE__) . '/../'));
define('SF_APP', 'frontend');
define('SF_ENVIRONMENT', 'dev');
define('SF_DEBUG', true);

require_once (SF_ROOT_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . SF_APP . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');

define('REPOS_DIR', $argv[1]);
define('REPO_NAME', $argv[2]);

include dirname(__FILE__) . '/commons.php';

main();

function main()
{
    $yaml = readYAMLFile();
    convertSymlinks($yaml);
}

function convertSymlinks($yaml)
{
    $repoPath = REPOS_DIR . REPO_NAME . '/';
    chdir($repoPath);
    
    foreach($yaml['symlinks'] as $p => $symlink)
    {
        chdir($repoPath);
        if(file_exists($p) && is_link($p))
        {
            // remove symbolic link
            unlink($p);
            chdir($symlink['path']);

            // copy files
            $cmd = 'cp -r ' . $symlink['ln_src'] . ' ' . $symlink['name'];
            echo shell_exec($cmd);

            echo shell_exec('git add ' . $symlink['name']);
        }
    }

    chdir($repoPath);

    echo shell_exec('git commit -m "(svn import) -- converts symlinks into regular files"');
    echo shell_exec('git push origin master');
}

?>