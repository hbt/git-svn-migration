<?php

# Usage:
# php step1_clone_repo.php repository_dump_directory/ authors_filename svn_url repo_name
# e.g php batch/step1_clone_repo.php /media/b91eeaef-82c7-4ae6-9713-44ce65eb25e6/home/hassen/web_dld/ssi/ authors.txt http://svn.sylsft.com/projects/uwo/ctms/ ctms


define('SF_ROOT_DIR', realpath(dirname(__FILE__) . '/../'));
define('SF_APP', 'frontend');
define('SF_ENVIRONMENT', 'dev');
define('SF_DEBUG', true);

require_once (SF_ROOT_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . SF_APP . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');

$repositoryDirectoryDumpFullPath = $argv[1];
$authorsFilename = $argv[2];
$svnURL = $argv[3];
$repositoryName = $argv[4];

main($repositoryDirectoryDumpFullPath, $authorsFilename, $svnURL, $repositoryName);

function main($repositoryDirectoryDumpFullPath, $authorsFilename, $svnURL, $repositoryName)
{
    // TODO(hbt): change this
//    $repositoryName = $repositoryName . time();
    convertSVNtoGit($repositoryDirectoryDumpFullPath, $authorsFilename, $svnURL, $repositoryName);
    // PART 2 -- get externals
    generateSubmodulesSymlinksFile($repositoryDirectoryDumpFullPath, $repositoryName, $svnURL);
}

function convertSVNtoGit($reposDir, $authorsFilename, $url, $repoName)
{
    // svn authors matching
    $authorsFile = $reposDir . $authorsFilename;
    if (!file_exists($authorsFile))
    {
        echo "authors file required. check batch/authors as an example";
        exit;
    }

    chdir($reposDir);

    if (file_exists($reposDir . $repoName))
    {
        echo "git repo already exists at " . $reposDir . $repoName;
    }
    else
    {
        // checkout svn project into git repo
        $cmd = "git-svn clone --authors-file $authorsFile --no-metadata $url $repoName";

        echo shell_exec($cmd);
    }
}

function generateSubmodulesSymlinksFile($reposDir, $repoName, $url)
{
    $svnRepoName = $repoName . '_svn';
    $svnRepoPath = $reposDir . $svnRepoName;

    if (file_exists($svnRepoPath))
    {
        echo "svn repo already exists at " . $svnRepoPath;
    }
    else
    {
        checkoutSVNRepository($svnRepoName, $url);
    }

    chdir($svnRepoPath);
    $xmlStringExternals = dumpExternals($svnRepoPath);

    $arrayExternals = convertXMLtoArray($xmlStringExternals);
    $organizedArray = sortExternals($arrayExternals, $url);

    $filename = $reposDir . "$repoName.externals_edit_me.txt";
    file_put_contents($filename, sfYaml::dump($organizedArray));

    chdir($reposDir);
}

/**
 * sort externals and determine which ones are submodules and which ones are symlinks
 * @param type array of externals
 * @return type array of organized externals
 */
function sortExternals($array, $url)
{
    $ret = array(
        'submodules' => array(),
        'symlinks' => array()
    );

    // determine if URLs starts by the same string
    // if it is the case, we have a symlink
    foreach ($array as $path => $external)
    {
        $extUrl = $external['url'];

        foreach ($array as $path2 => $external2)
        {
            $extUrl2 = $external2['url'];
            if ($extUrl === $extUrl2)
                continue;

            // symlinks relative to the externals
            // Note: needless to know which one is the haystack/needle. We will loop again and get them anyway
            if (startsWith($extUrl, $extUrl2, false))
            {
                $ret['symlinks'][$path] = $external;
            }

            // symlinks relative to the repo
            if (startsWith($extUrl, $url, false))
            {
                $ret['symlinks'][$path] = $external;
            }
        }
    }

    $allsymlinks = array();
    foreach ($ret['symlinks'] as $path => $symext)
    {
        $allsymlinks[] = $symext['url'];
    }


    foreach ($array as $path => $external)
    {
        if (!in_array($external['url'], $allsymlinks))
        {
            $ret['submodules'][$path] = $external;
        }
    }

    return $ret;
}

/**
 * convert XML externals property to Array
 * @param type $string XML
 * @return type  Array
 */
function convertXMLtoArray($string)
{
    $xml = simplexml_load_string($string);
    $ret = array();
    foreach ($xml->target as $target)
    {
        $path = (string) $target['path'];
        $str = (string) $target->property;
        $exts = explode("\n", $str);

        foreach ($exts as $ext)
        {
            if (!trim($ext))
                continue;

            $extData = explode(' ', $ext);
            $name = $extData[0];
            $extUrl = $extData[1];

            $ret[$path . "/" . $name] = array(
                'path' => $path,
                'name' => $name,
                'url' => $extUrl
            );
        }
    }

    return $ret;
}

/**
 * dump externals as xml
 * @param type $svnRepoPath string full path to svn repository
 * @return type  XML string
 */
function dumpExternals()
{
    $cmd = 'svn propget svn:externals -R --xml';
    $ret = shell_exec($cmd);

    return $ret;
}

function checkoutSVNRepository($svnRepoName, $url)
{
    # checkout svn project
    $cmd = "svn co $url $svnRepoName";

    echo shell_exec($cmd);
}

function startsWith($haystack, $needle, $case=true)
{
    if ($case)
    {
        return (strcmp(substr($haystack, 0, strlen($needle)), $needle) === 0);
    }
    return (strcasecmp(substr($haystack, 0, strlen($needle)), $needle) === 0);
}

?>