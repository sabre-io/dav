#!/bin/bash

TEST_METHOD_FILTER="$1"

if [ "$TEST_METHOD_FILTER" = "" ]; then
  docker run -ti --rm -v $(realpath "$(dirname $0)/../../"):/test-dir/ sabre-dav-unit-tests
else
  docker run -ti --rm -v $(realpath "$(dirname $0)/../../"):/test-dir/ -e TEST_METHOD_FILTER=$TEST_METHOD_FILTER sabre-dav-unit-tests
fi