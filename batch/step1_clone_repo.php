<?php

# Usage:
# php step1_clone_repo.php repository_dump_directory/ authors_filename svn_url repo_name
# e.g php batch/step1_clone_repo.php /media/b91eeaef-82c7-4ae6-9713-44ce65eb25e6/home/hassen/web_dld/ssi/ http://svn.sylsft.com/projects/uwo/ctms/ ctms


define('SF_ROOT_DIR', realpath(dirname(__FILE__) . '/../'));
define('SF_APP', 'frontend');
define('SF_ENVIRONMENT', 'dev');
define('SF_DEBUG', true);

require_once (SF_ROOT_DIR . DIRECTORY_SEPARATOR . 'apps' . DIRECTORY_SEPARATOR . SF_APP . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');

define('REPOS_DIR', $argv[1]);
define('SVN_URL', $argv[2]);
define('REPO_NAME', $argv[3]);

include dirname(__FILE__) . '/commons.php';

define('SVN_REPO_DIR', REPOS_DIR . REPO_NAME . TMP_SVN_POSTFIX);
main();

function main()
{
    convertSVNtoGit();
    generateSubmodulesSymlinksFile();
    fixIgnores();
    fixEmptyDirectories();
}

function convertSVNtoGit()
{
    // svn authors matching
    $authorsFile = dirname(__FILE__) . '/authors.txt';
    if (!file_exists($authorsFile))
    {
        echo "\n\nauthors file required. check batch/authors as an example. File $authorsFile not found";
        exit;
    }

    chdir(REPOS_DIR);

    $repoPath = REPOS_DIR . REPO_NAME;
    if (file_exists($repoPath))
    {
        echo "\n\ngit repo already exists at " . $repoPath;
    }
    else
    {
        // checkout svn project into git repo
        $cmd = implode(" ", array(
            'git-svn',
            'clone',
            '--authors-file',
            $authorsFile,
            '--no-metadata',
            SVN_URL,
            REPO_NAME
        ));

        echo shell_exec($cmd);
    }
}

function generateSubmodulesSymlinksFile()
{
    if (file_exists(SVN_REPO_DIR))
    {
        echo "\n\nsvn repo already exists at " . SVN_REPO_DIR;
    }
    else
    {
        checkoutSVNRepository();
    }

    chdir(SVN_REPO_DIR);
    $xmlStringExternals = dumpExternals(SVN_REPO_DIR);

    $arrayExternals = convertXMLtoArray($xmlStringExternals);
    $organizedArray = sortExternals($arrayExternals);

    chdir(REPOS_DIR);
    if(count($organizedArray['submodules']) || count($organizedArray['symlinks']))
    {
        $filename = REPOS_DIR . REPO_NAME . EXTERNALS_POSTFIX;
        file_put_contents($filename, sfYaml::dump($organizedArray));
    }
}

/**
 * Note(hbt): empty directories are not added to git
 * Some projects expect directories to exist.

 * create an empty hidden file
 */
function fixEmptyDirectories()
{
    chdir(REPOS_DIR . REPO_NAME);
    $cmd = 'find . -name .git -a -type d -prune -o -type d -empty -print';

    $dirs = shell_exec($cmd);
    $dirs = explode("\n", $dirs);

    $ret = array();
    foreach ($dirs as $dir)
    {
        if (!trim($dir))
            continue;
        $dir = substr($dir, 1);
        $notempty = $dir . '/.notempty';
        touch(REPOS_DIR . REPO_NAME . $notempty);

        // force an exception for .notempty file if the content of the directory is ignored
        $ret[] = '!' . $notempty;
        shell_exec('git add ' . substr($notempty, 1));
    }

    $gitignorefilename = REPOS_DIR . REPO_NAME . '/.gitignore';
    if (!file_exists($gitignorefilename))
        touch($gitignorefilename);

    $ret = implode("\n", $ret);
    $ret = file_get_contents($gitignorefilename) . "\n" . $ret;
    file_put_contents($gitignorefilename, $ret);

    shell_exec('git add ' . $gitignorefilename);
    shell_exec('git commit -m "(svn import) -- fixing empty directories"');
}

/**
 * imports svn ignore statements into .gitignore
 */
function fixIgnores()
{
    // get ignores from svn repo
    chdir(SVN_REPO_DIR);
    $cmd = 'svn propget svn:ignore -R --xml';
    $strxml = shell_exec($cmd);

    $xml = simplexml_load_string($strxml);
    $ret = array();
    foreach ($xml->target as $target)
    {
        $path = (string) $target['path'];
        $strlines = (string) $target->property;
        
        $arrlines = explode("\n", $strlines);
        
        foreach($arrlines as $line) 
        {
            if(!trim($line))
                continue;
            
            $ignoreline = '';
            if($path)
            {
                $ignoreline .= $path . '/';
            }
            
            $ignoreline .= $line;
            
            $ret[] = $ignoreline;
        }
    }

    $strignore = implode("\n", $ret);
    file_put_contents(REPOS_DIR . REPO_NAME . '/.gitignore', $strignore);

    chdir(REPOS_DIR . REPO_NAME);

    shell_exec('git add .gitignore');
    shell_exec('git commit .gitignore -m "(svn import) -- updates ignore list"');
}

/**
 * sort externals and determine which ones are submodules and which ones are symlinks
 * @param type array of externals
 * @return type array of organized externals
 */
function sortExternals($array)
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
                $external['ln_src'] = get_ln_src($external2, $external);
                $ret['symlinks'][$path] = $external;
            }

            // symlinks relative to the repo
            if (startsWith($extUrl, SVN_URL, false))
            {
                $external['ln_src'] = get_ln_src(SVN_URL, $external, true);
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

// determine symlink relative path
function get_ln_src($src, $dest, $replaceOnly = false)
{
    // count nb / 
    $level = substr_count($dest['path'], '/')+1;
    
    $ret = str_repeat('../', $level);
    if($replaceOnly)
    {
        $ret .= str_ireplace($src, '', $dest['url']);
    }
    else
    {
        $ret .= $src['path'] . '/' . $src['name'] . str_ireplace($src['url'], '', $dest['url']);
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
            
            $extUrl = trim($extUrl);
            // remove trailing / from url
            if($extUrl[strlen($extUrl)-1] === '/')
            {
                $extUrl = substr($extUrl, 0, strlen($extUrl)-1);
            }


            $ret[$path . "/" . $name] = array(
                'path' => $path,
                // name of the external repo within the project
                'name' => $name,
                'url' => $extUrl,
                // real name of repo
                'real_name' => substr($extUrl, strrpos($extUrl, '/')+1)
            );
        }
    }

    return $ret;
}

/**
 * dump externals as xml
 * @return type  XML string
 */
function dumpExternals()
{
    $cmd = 'svn propget svn:externals -R --xml';
    $ret = shell_exec($cmd);

    return $ret;
}

function checkoutSVNRepository()
{
    chdir(REPOS_DIR);
    $cmd = implode(' ', array(
        'svn',
        'co',
        SVN_URL,
        REPO_NAME . TMP_SVN_POSTFIX
    ));

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