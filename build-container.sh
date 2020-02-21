#!/bin/bash -e

[[ -d src ]] || git worktree add src master

docker build -t mitxela/pict -f container/Dockerfile .
