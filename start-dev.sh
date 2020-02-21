#!/bin/bash -e

[[ -d src ]] || git worktree add src redo

# Config to match container db's default credentials
cp container/_db.php src/_db.php

# Script to help remember the docker compose command from within src/
cat > src/dev <<-EOFDEV
	#!/bin/bash
	docker-compose -f ${PWD}/container/docker-compose.yml "\$@"
EOFDEV
chmod +x src/dev

# Start the thing
src/dev up -d
