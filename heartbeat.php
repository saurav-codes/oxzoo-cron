<?php

$tag = getenv('GREETING_TAG');
if ($tag === false || $tag === '') {
    fwrite(STDERR, 'GREETING_TAG must be set (see .env.example)' . PHP_EOL);
    exit(1);
}

while (true) {
    echo 'hello world oxzoo-cron_' . $tag . PHP_EOL;
    // journald reads stdout through a pipe, which stdio block-buffers; flush so each line lands in the journal now
    fflush(STDOUT);
    sleep(10);
}
