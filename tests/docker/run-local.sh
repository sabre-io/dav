#!/bin/bash

docker run -ti --rm -v $(realpath "$(dirname $0)/../../"):/test-dir/ sabre-dav-unit-tests
