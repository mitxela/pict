#!/bin/bash -e

[[ -d src ]] || git worktree add src redo

docker build -t mitxela/pict -f container/Dockerfile .
