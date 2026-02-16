<?php
$files = array_slice(scandir(__DIR__."/classes/"), 2);
foreach ($files as $f):
    if (is_dir(__DIR__."/classes/$f")) continue;
    require_once __DIR__."/classes/$f";
endforeach;