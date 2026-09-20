# oxzoo-cron

An official ox deploy example for scheduled work on a single Ubuntu VPS: a plain PHP 8 CLI project with two faces, a long-running **heartbeat** process that prints the greeting every 10 seconds, and a **greet** cron job that prints it once per run every 2 minutes. ox renders the `[[cron_jobs]]` table from `ox.toml` as a systemd oneshot service plus a timer with `Persistent=true`, so the schedule lives in systemd and you never edit a crontab. There is no web domain and no HTTP server here; both commands just print `hello world oxzoo-cron_<GREETING_TAG>`, and you verify them through `journalctl`.

## Stack

| Component | Version | Purpose |
| --------- | ------- | ------- |
| PHP | 8 (apt `php-cli`) | the only runtime: `heartbeat.php` (long-running process) and `greet.php` (cron oneshot), no Composer |
| systemd | host default | runs the heartbeat service and the greet timer + oneshot service |
| ox | current release | clones the repo, renders `ox.toml` into units and timers, streams both journals in the dashboard |

## Environment flow

One variable, runtime only:

**`GREETING_TAG`** is runtime env. ox writes it to `/srv/ox/oxzoo-cron/env` and injects it into every unit's environment. `heartbeat.php` reads it once at startup and refuses to run without it (a clear stderr message, exit 1), so a missing value fails loudly instead of printing an empty tag. Each `greet.php` run reads it fresh, so a new value shows up on the next scheduled run without a redeploy. Both faces print the same line: `hello world oxzoo-cron_<GREETING_TAG>`.

There is no build step and no build-time env path: two plain scripts, nothing compiled, nothing baked.

## Deploy with ox

1. Add the repo in the ox dashboard: paste the clone URL `git@github.com:saurav-codes/oxzoo-cron.git`.
2. In the Environment editor, set `GREETING_TAG=w3-07` before the first deploy.
3. Press **Deploy**. ox installs `php-cli`, clones the repo into a git worktree, starts `php heartbeat.php` as a systemd process, and renders the greet job as a systemd timer. The manifest declares `port = 9121` because ox requires a project port even for projects that never listen; there is no `[[domains]]` table and nothing for nginx to route.

## Expected output

Both faces land in the journal; verify from the VPS:

**Heartbeat** (long-running process, one line every 10 seconds):

```
journalctl -u ox-oxzoo-cron-heartbeat.service
```

```
hello world oxzoo-cron_w3-07
```

**Cron** (oneshot, once per run, every 2 minutes):

```
journalctl -u ox-oxzoo-cron-cron-greet.service
```

prints the same line once each time the timer fires. Check the timer's next fire time with:

```
systemctl list-timers 'ox-oxzoo-cron-*'
```

`w3-07` is whatever you set in the Environment editor; the scripts read the value at run time, so changing it there changes the line on the next restart or run.

## Local development

Nothing to install beyond PHP itself:

```
GREETING_TAG=dev php heartbeat.php   # prints the line immediately, then every 10 seconds (Ctrl+C to stop)
GREETING_TAG=dev php greet.php       # prints the line once and exits 0
```

Run either script without `GREETING_TAG` and it exits 1 with an error on stderr. Pass env inline per the commands above; never commit a real `.env`.
