#!/bin/bash -e

[[ -d src ]] || git worktree add src master

# Config to match container db's default credentials
cp container/_db.php src/_db.php

(cd src; git clean -nxd | grep -v _db.php -q) && (
	>&2 echo 'WARNING -- src directory is not clean, do not push image!'
	>&2 echo
)

docker build -t mitxela/pict -f container/Dockerfile .
