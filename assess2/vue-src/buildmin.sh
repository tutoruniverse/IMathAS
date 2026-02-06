#!/bin/bash
# Define version, timestamp, and the comment tag
VERSION="1.0.0" # Update this manually or pass it as an argument: VERSION=$1
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
BUILD_TAG="/*! Build: v${VERSION} - ${TIMESTAMP} */"

declare -a filename
filename[0]=javascript/drawing
filename[1]=javascript/AMhelpers2
filename[2]=javascript/eqntips
filename[3]=javascript/mathjs
filename[4]=mathquill/AMtoMQ
filename[5]=mathquill/mqeditor
filename[6]=mathquill/mqedlayout
filename[7]=javascript/ASCIIMathML
filename[8]=javascript/ASCIIsvg
filename[9]=javascript/ASCIIMathTeXImg
filename[10]=javascript/rubric

for name in ${filename[@]}; do
  echo "Minifying ${name}"
# --preamble adds the comment to the very top of each individual minified file
  ./node_modules/.bin/uglifyjs --mangle --compress hoist_vars=true \
    --preamble "$BUILD_TAG" \
    ../../${name}.js > ../../${name}_min.js
done

rm -f ../../javascript/assess2_min.js
# Initialize the bundle file with the build tag at the very top
echo "$BUILD_TAG" > ../../javascript/assess2_min.js

for i in {0..6}; do
  echo "adding ${filename[$i]} to assess2_min.js";
  cat ../../${filename[$i]}_min.js >> ../../javascript/assess2_min.js
done
