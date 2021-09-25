<?php

header('Content-type: text/plain');
header('HTTP/1.1 200 OK');

echo 'Response from ', $_SERVER['HTTP_HOST'];
