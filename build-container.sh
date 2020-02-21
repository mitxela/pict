#!/bin/bash -e

[[ -d src ]] || git worktree add src master

# Config to match container db's default credentials
cp container/_db.php src/_db.php

docker build -t mitxela/pict -f container/Dockerfile .
