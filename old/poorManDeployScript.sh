#!/bin/bash

if [[ $1 == "" ]]; then
	echo "Are you slow in the head?" 
	echo "We need a commit message, OoooooooK ?"
	exit 1;
fi

git status
git commit -a -m "$@"
git push
s3cmd -M -P --exclude='.DS_Store' --add-header='Cache-Control:max-age=86400' sync include/ s3://iodie/website/include/
