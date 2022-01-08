#!/usr/bin/env bash

set -o errexit

docker-compose run --rm tblschema composer style-check