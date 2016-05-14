#!/bin/bash

cd $1;

if [ ! -d "v$2" ]; then
  mkdir v$2;
fi
echo {\"version\":\"$2\"} > v.json;
