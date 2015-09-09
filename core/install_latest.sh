#! /bin/bash
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/sh
# Run this script in the directory where you want the latest PHP source installed.

#==============================================================================

common_php_source_repository=https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
project_php_source_repository=https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
project_sh_source_repository=https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/sh
project_js_source_repository=https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/js
project_logo_source_repository=https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/logos_and_icons

live_php_directory=/var/apache2/cgi-bin/aeolus/aeolus2/cofk
launch_directory=/srv/data/aeolus2/interface

installing_live_source=N
if [ "$live_php_directory" = "$PWD" ]
then
  installing_live_source=Y
fi

#==============================================================================

clear
echo ''
echo ''
echo '=========================================================================='
echo ''
echo 'This is the Cultures of Knowledge / EMLO database.'
echo "The PHP source code will be installed in $PWD"

if [ "$installing_live_source" = "Y" ]
then
  echo 'This is the LIVE source code directory.'
fi

echo 'Please double-check that you do want to install new source code for CofK.'
echo ''
echo '=========================================================================='
echo ''


debug_setting=$1
answer=$2

if [ "$debug_setting" = "debug" ]
then
  echo 'Debug statements will be ENABLED'
else
  echo 'Debug statements will be disabled'
fi

if [ "$answer" != "y" -a "$answer" != "Y" ]
then
  echo -n 'Do you want to install the latest version of the code from Subversion for Cultures of Knowledge? (y/n) '
  read answer
fi

if [ "$answer" != "y" -a "$answer" != "Y" ]
then
  echo 'Cancelled.'
  exit
fi

if [ "$SVNUSER" = "" -o "$SVNPASS" = "" ]
then
  echo ''
  echo 'This script uses environment variables SVNUSER and SVNPASS to hold your Subversion username/password.'
  echo 'If you have not already set SVNUSER and SVNPASS, you will be prompted to enter them now.'
  echo 'Unfortunately the password will briefly be visible on screen, but will be cleared when you hit Return.'
fi

if [ "$SVNUSER" = "" ]
then
  echo ''
  echo -n "Enter Subversion username: "
  read SVNUSER
fi

if [ "$SVNUSER" = "" ]
then
  echo 'No Subversion username was entered.'
  echo 'Exiting...'
  exit
fi


if [ "$SVNPASS" = "" ]
then
  echo -n "Enter Subversion password: "
  read SVNPASS
  clear
fi

if [ "$SVNPASS" = "" ]
then
  echo 'No Subversion password was entered.'
  echo 'Exiting...'
  exit
fi

export SVNUSER
export SVNPASS

echo ''
date
echo 'Installing the latest version of the code from Subversion for Cultures of Knowledge ... '
echo ''

#==============================================================================

# Add common PHP files such as user.php

echo 'Getting COMMON components...'
svn export ${common_php_source_repository}/common_components.php --password $SVNPASS --username $SVNUSER

while read require_string the_file the_rest
do
  #echo "Original line is: $require_string $the_file $the_rest"
  if [ "$require_string" = "require_once" ]
  then
    the_file=$(echo $the_file | cut -f2 -d"'")
    echo "Required file = $the_file"
    if [ "$the_file" = "DB.php" -o "$the_file" = "MDB2.php" ]
    then
      echo "File '$the_file' is a built-in PHP module."
    else
      svn export ${common_php_source_repository}/$the_file --password $SVNPASS --username $SVNUSER
    fi
  fi
done < common_components.php
echo 'Installation of COMMON components complete.'
echo ''
echo ''

#----

# Add project-specific PHP for the main editing interface

echo 'Getting PROJECT-SPECIFIC components for MAIN EDITING INTERFACE...'
svn export ${project_php_source_repository}/proj_components.php --password $SVNPASS --username $SVNUSER

while read require_string the_file the_rest
do
  #echo "Original line is: $require_string $the_file $the_rest"
  if [ "$require_string" = "require_once" ]
  then
    the_file=$(echo $the_file | cut -f2 -d"'")
    echo "Required file = $the_file"
    if [ "$the_file" = "impt_components.php" ]
    then
      echo "File '$the_file' is not required for Cultures of Knowledge."
      continue
    elif [ "$the_file" = "menu.php" -o "$the_file" = "html.php" ]
    then
      echo "INFO: project-specific version of $the_file overwrites common version."
    fi
    svn export ${project_php_source_repository}/$the_file --password $SVNPASS --username $SVNUSER
  fi
done < proj_components.php
echo 'Installation of PROJECT-specific components for MAIN EDITING INTERFACE complete.'
echo ''
echo ''


#----

# Now add PHP for the offline data collection tool

echo 'Getting components of OFFLINE DATA COLLECTION TOOL...'
while read require_string the_file the_rest
do
  #echo "Original line is: $require_string $the_file $the_rest"
  if [ "$require_string" = "require_once" ]
  then
    the_file=$(echo $the_file | cut -f2 -d"'")
    echo "Required file = $the_file"
    svn export ${project_php_source_repository}/$the_file --password $SVNPASS --username $SVNUSER
  fi
done < collect_components.php
echo 'Installation of OFFLINE DATA COLLECTION TOOL complete.'
echo ''

#----

# Now add PHP for weekly data export to front end

echo 'Getting scripts for WEEKLY DATA EXPORT TO FRONT END...'
for the_file in export_cofk_union.php batch_manifestations_union.php reinstate_accents_union.php
do
  echo "Getting $the_file"
  svn export ${project_php_source_repository}/$the_file --password $SVNPASS --username $SVNUSER
done 
for the_file in export_cofk_union.sh reinstate_accents_selden_end.sh
do
  echo "Getting $the_file"
  svn export ${project_sh_source_repository}/$the_file --password $SVNPASS --username $SVNUSER
  chmod +x $the_file
done 
echo 'Installation of scripts for WEEKLY DATA EXPORT TO FRONT END complete.'
echo ''

#----

# If you are doing an install of the LIVE source code, 
# also make sure the launch scripts and logos etc are up to date.

if [ "$installing_live_source" = "Y" ]
then
  echo 'Getting launch scripts, logos and icons, and Javascript libraries...'
  cd $launch_directory

  for the_file in union.php dev_union.php cardindex.php dev_cardindex.php collect.php dev_collect.php
  do
    echo "Getting $the_file"
    svn export ${project_php_source_repository}/$the_file --password $SVNPASS --username $SVNUSER
  done 

  for the_file in CofKDatabaseLogoSmall.jpg \
                  CofKDatabaseMellonLogo.jpg \
                  CofKLogo.png \
                  CofK_Favicon.ico \
                  bod-libraries-140.png \
                  chk.png \
                  del.png \
                  ok.png \
                  ox_brand1_pos.gif \
                  ox_brand3_pos_rect.gif \
                  ox_brand4_pos_rect.gif \
                  pling.png \
                  relevance_unknown.png \
                  tinyCofKLogo.PNG 
  do
    echo "Getting $the_file"
    svn export ${project_logo_source_repository}/$the_file --password $SVNPASS --username $SVNUSER
  done 

  for the_file in md5.js
  do
    echo "Getting $the_file"
    svn export ${project_js_source_repository}/$the_file --password $SVNPASS --username $SVNUSER
  done 

  echo 'Installation of launch scripts, logos and icons, and Javascript libraries is now complete.'
  echo ''

  cd -
fi

#----

# Finally, enable debug messages if 'debug' parameter was passed in

if [ "$debug_setting" = "debug" ]
then
  echo 'Enabling debug in application entity...'
  mv application_entity.php application_entity.pre_edit
  sed -e '1,$s/debug = FALSE/debug = TRUE/g' application_entity.pre_edit > application_entity.php
  \rm application_entity.pre_edit
fi


echo ''
date
echo 'Installation complete.'
echo ''
