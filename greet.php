<?php

// One-shot cron run: stdout lands in the journal of this unit (ox-oxzoo-cron-cron-greet.service).

$tag = getenv('GREETING_TAG');
if ($tag === false || $tag === '') {
    fwrite(STDERR, 'GREETING_TAG must be set (see .env.example)' . PHP_EOL);
    exit(1);
}

echo 'hello world oxzoo-cron_' . $tag . PHP_EOL;
