<?php

include (__DIR__ . '/vendor/autoload.php');

use Kelly\Storage;

Storage::add('test_key', 'test_value_1');
Storage::add('test_key1', 'test_value_2');
Storage::add('test_key2', 'test_value_3');
Storage::add('test_key3', 'test_value_4');

Storage::delete('test_key2');

var_dump(Storage::get('test_key2'));

Storage::update('test_key3', '5');
echo Storage::get('test_key3');