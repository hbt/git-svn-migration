<?php

# use current dir pwd

# svn url

# checkout project 
//git-svn clone --authors-file authors.txt --no-metadata http://svn.sylsft.com/projects/bcpa

# get externals
// svn propget svn:externals -R http://svn.sylsft.com/projects/bcpa/

# output externals in a file

/**
submodules
- http://svn.sylsft.com/projects/symfony_plugins/sfWebBrowserPlugin/ sfWebBrowserPlugin plugins sfWebBrowserPlugin

 */

# end of step 1

# step 2

# user edits the file and confirms which externals are submodules and which ones are symlinks

# loop through submodules

# check path to repo + if we need to create

# execute step 1

# create .gitmodules file listing all submodules

# initialize submodules + update


# create .svn_externals file listing symlinks

# copy script to initalize submodules + symlinks

?>
