# git svn migration

Migrates a subversion repository into a Git repository. Uses git-svn + transforms svn externals into git submodules

## What it does

- full migration
- handles externals by detecting if they should be submodules or symlinks
- migrates ignore statements
- migrates empty directories 
- generates .gitmodules + .gitignore files
- supports bitbucket as a remote
- creates remote repositories + submodules



## Usage

* define authors in batch/authors.txt

    * use `svn log --xml | grep author | sort -u | perl -pe 's/.>(.?)<./$1 = /'` to list authors from your SVN repository
    * define authors in batch/authors.txt

* execute step1 
`php step1_clone_repo.php repository_dump_directory/ svn_url repo_name`
    
    * imports the svn repository into git (uses the authors.txt file)
    * generates a list of svn externals into a file -- to be converted into git submodules or symbolic links
    * converts svn ignore statements to git
    * imports empty svn directories
    * uses the information from batch/config.yml to push to the remote -- bitbucket

        * `user` = bitbucket user
        * `passwd` = bitbucket passwd
        * `owner` = different from user if repo belongs to an organization
        * `delete_before_import` = delete the repository on bitbucket before importing -- useful because it allows you to run the script multiple times without having to delete the git repositories and all the submodules manually


### e.g `php batch/step1_clone_repo.php /home/username/repos/ http://svn_url_to_repository git_repo_name;`


* Execute step 2
`e.g php batch/step2_fix_externals.php /home/username/repos/ git_repo_name git@bitbucket.org:hbt/`

    * reads file where submodules + symlinks are listed
    * loops through svn externals and calls step1 to import them as git submodules -- Note: recursive externals will generate their own files and must handled separately
    * creates symbolic links


* (Optional) Execute step 2b
`php batch/step2b_convert_symlinks.php /home/username/repos/ git_repo_name`

    * converts symbolic links into regular files by copying the content. -- Necessary for Windows support




## Aftermatch

* /home/username/repos/ will contain a lot of directories including __tmp_svn directories (svn repositories). This is useful if a mistake was made and you need to run the scripts again.
* If the repositories are pushed on the remote host. Feel free to delete the /home/username/repos/ directory



## Silent crashes

* If it stops mid-migration, review the authors.txt and make sure no authors are missing


## Side Notes

* Designed to be ran once. If you run it again, delete the remote repository
* The /home/username/repos directory contains cached information about the repository. Delete it if you fail to run the migration and want to try it again
