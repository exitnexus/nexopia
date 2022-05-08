#!/bin/sh

echo "stats" | nc -q 1 10.0.0.1 8435
echo "stats" | nc -q 1 10.0.0.32 8435
echo "stats" | nc -q 1 10.0.0.82 8435
