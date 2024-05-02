#!/bin/bash
# by PS, 2024-02-28
#
# prints a changelog
#
# usage: scripts/git/changelog.sh TAG_OR_BRANCH HEAD [--full]
#

# TODO only show headline if git log has output
showCommits () {
  type=$1
  headline=$2
  echo -e "### $headline"
  git log $VERSION1...$VERSION2 --oneline | egrep " \"*$type *\(" | egrep -v "\(ci" | egrep -v "fixup" | egrep -v "WIP" | egrep -v "Draft" | sed -E ':a;N;$!ba;s/\n/  \n/g'
  echo -e
}

VERSION1=$1
VERSION2=$2

if [ "$VERSION1" = "" ]
then
  # TODO allow to fetch this via "git describe"
  echo "please enter version 1:"
  read -r VERSION1
fi

if [ "$VERSION2" = "" ]
then
  echo -e "please enter version 2:"
  read -r VERSION2
fi

showCommits feature Features
showCommits fix Bugfixes
showCommits tweak Tweaks

# TODO allow to get all other changes with a param --full

if [ "$3" = "--full" ]
then
  echo -e "\n### Other Changes"
  git log $VERSION1...$VERSION2 --oneline | grep -v "Merge branch" \
    | grep -v "Merge remote" | egrep -v " \"*feature *\(" | egrep -v " \"*fix *\(" | egrep -v " \"*refactor *\(" \
    | egrep -v " \"*tweak *\(" | egrep -v "\(ci" | egrep -v "ci *\(" | sed -E ':a;N;$!ba;s/\n/  \n/g'

  echo -e "\n### CI Changes"
  git log $VERSION1...$VERSION2 --oneline | egrep "\(ci" | egrep -v "fixup" | sed -E ':a;N;$!ba;s/\n/  \n/g'

  showCommits refactor Refactoring
fi
