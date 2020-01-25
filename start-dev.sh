#!/bin/bash

# Config to match container db's default credentials
cp container/db.php src/db.php

# Script to help remember the docker compose command from within src/
cat > src/dev <<-EOFDEV
	#!/bin/bash
	docker-compose -f ${PWD}/docker-compose.yml "\$@"
EOFDEV
chmod +x src/dev

# Start the thing
src/dev up -d || (echo 'trying with sudo...' && sudo src/dev up -d)
