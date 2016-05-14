#!/bin/bash

mkdir $1;
cd $1;
mkdir v0;
echo {\"version\":\"0\"} > v.json;
