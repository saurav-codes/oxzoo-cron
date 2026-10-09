# oxzoo-cron

Deployed with [ox](https://deploywithox.com): deploy a repo to your own server with one command, no Docker. [Docs](https://deploywithox.com/docs) · [Stack guides](https://deploywithox.com/docs/guides)

An [ox](https://deploywithox.com) deploy example for scheduled work: a plain PHP CLI project with a long-running **heartbeat** worker that prints the greeting every 10 seconds, and a **greet** cron job that prints it once a minute. ox runs the worker as a systemd service and the job as a systemd timer, so you never edit a crontab. There is no web process and no domain: both print `hello world oxzoo-cron_<GREETING_TAG>`, and the logs are the output.

## Stack

| Component | Version | Purpose |
| --------- | ------- | ------- |
| PHP | Ubuntu's `php-cli` | `heartbeat.php` (worker) and `greet.php` (cron job), no Composer |

## ox.toml

```toml
# A worker and a cron job, no web process at all.
packages = ["php-cli"]

[workers]
heartbeat = "php heartbeat.php"

[cron]
greet = { schedule = "* * * * *", run = "php greet.php" }
```

`packages` installs Ubuntu's `php-cli`. Cron schedules are five fields or `@hourly`, `@daily`, `@weekly`, `@monthly`, in UTC.

## Environment flow

`GREETING_TAG` is read at run time. `heartbeat.php` reads it once at startup and exits 1 with a clear message when it is missing, so an unset value fails loudly. Each `greet.php` run reads it fresh. There is no build step.

## Deploy with ox

```sh
curl -fsSL https://deploywithox.com/install.sh | sh
ox login
ox new https://github.com/saurav-codes/oxzoo-cron
ox review oxzoo-cron --from-file .env.example --wait
```

`ox review` sets `GREETING_TAG` from `.env.example` (edit the value first) and streams the first deploy. The plan, offline:

```console
$ ox check .
ox check . (manifest: ox.toml)

  workers.heartbeat          php heartbeat.php                                    declared
  cron.greet                 * * * * *  php greet.php                             declared
  packages                   php-cli                                              declared

  Provided by ox: PORT, HOST, OX_ENV, OX_PROJECT, OX_RELEASE, OX_DATA_DIR
  Set on the dashboard before the first deploy: GREETING_TAG

Ready to deploy.
```

## Expected output

```sh
ox logs oxzoo-cron --follow     # the heartbeat, one line every 10 seconds, and each greet run
ox crons oxzoo-cron             # the greet schedule, its next run, and how the last run ended
ox crons oxzoo-cron run greet   # run the job once now
```

Each line reads `hello world oxzoo-cron_<GREETING_TAG>`. Changing the value with `ox vars set oxzoo-cron GREETING_TAG` redeploys, and the next lines carry the new tag.

## Local development

```sh
GREETING_TAG=dev php heartbeat.php   # prints the line now, then every 10 seconds (Ctrl+C to stop)
GREETING_TAG=dev php greet.php       # prints the line once and exits 0
```
