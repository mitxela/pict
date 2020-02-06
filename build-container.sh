#!/bin/bash -e

docker() {
	command docker "$@" || (>&2 echo "trying with sudo..." && sudo docker "$@")
}

[[ -d src ]] || git worktree add src redo

docker build -t mitxela/pict -f container/Dockerfile .
