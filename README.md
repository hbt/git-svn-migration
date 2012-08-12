Migrates an subversion repository into a Git repository



- full migration
- handles externals by detecting if they should be submodules or symlinks
- migrates ignore statements
- migrates empty directories 
- generates .gitmodules + .gitignore files



Usage:


1. review + define authors in batch/authors.txt

use `svn log --xml | grep author | sort -u | perl -pe 's/.>(.?)<./$1 = /'` to list authors from your SVN repository


2. execute step1
`php step1_clone_repo.php repository_dump_directory/ authors_filename svn_url repo_name`


`php batch/step1_clone_repo.php /home/X/dump_path_dir/ http://mysubversion_repository/trunk mygitRepoName`


Note: yes, keep the trailing / 


After step 1 is executed, a file is created in your dump_path_dir listing all submodules and symbolic links. You can edit this file



3. execute step 2
`e.g php batch/step2_fix_externals.php /home/X/dump_path_dir ctms git@github.com:hbt/`


Step 2 will use the file and create the submodules + symlinks



Delete the residue directories e.g __tmp_svn



Run migration once
