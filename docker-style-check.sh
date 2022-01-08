#!/usr/bin/env bash

set -o errexit

docker-compose run --rm slurp composer style-check