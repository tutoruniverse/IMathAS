#!/bin/bash

# builds minified tinymce plugins
declare -a plugin
plugin[0]=ableplayer
plugin[1]=asciimath
plugin[2]=asciisvg
plugin[3]=attach
plugin[4]=drawing
plugin[5]=mathquill
plugin[6]=snippet

for name in ${plugin[@]}; do
  echo Minifying ${name}
  ./node_modules/.bin/terser ../../tinymce8/plugins/${name}/plugin.js --mangle --compress --output ../../tinymce8/plugins/${name}/plugin.min.js
done
