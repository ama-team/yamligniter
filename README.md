# YamlIgniter

This repository contains a simple tool to boot CodeIgniter projects 
with YAML config.

## Motivation

There are two types of configuration: declarative and executed (so it's 
basically a big function that somehow provides configuration to 
application, either via return or side-effects). While 
executed configuration (which is used by CodeIgniter) gives
infinite freedom to end user (you can compute values on-the-fly), it 
lacks the ability of easy machine processing that declarative 
configuration has, and limits your freedom in automation. Declarative
configuration, such as YAML files, is static, but can be easily created
by a script using any language, and gives huge benefit for automating 
things like continuous deployments and automated environment creation
(for feature branches, for example).

Because we had some troubles deploying our CodeIgniter projects from
scratch, i decided to help CodeIgniter to switch to YAML for 
configuration.

## Installation

Just the usual thing:

```
composer require ama-team/yamligniter 
```

## Usage

CodeIgniter takes it's config by letting user to fill some variables
in user-maintained scripts. Let's exploit that:

```php
// application/config/config.php

extract(AmaTeam\YamlIgniter::config(__DIR__ . '/config.yml'));
```

```php
// application/config/database.php

extract(AmaTeam\YamlIgniter::database(__DIR__ . '/database.yml');
```

You can now also use environment-based configuration files:

```php
// application/config/database.php

extract(AmaTeam\YamlIgniter::database(__DIR__ . '/' . ENVIRONMENT . '/database.yml');
```

YamlIgniter will take your configuration, fill the missing gaps with 
default values, and then return full config to you for future 
processing or extraction into local variables.
  
## Formals

Static methods `YamlIgniter::database()` and `YamlIgniter::config()` 
are just wrappers around similar non-static methods: they've been 
implemented solely for simplified access. Their implementation differ a
little - `::config()` simply reads YAML file, fills all gaps with 
default values and returns in an array under `config` key:

```yml
# config.yml
base_url: https://project.dev/

# results in
config:
  base_url: 'http://project.dev/'
  index_page: 'index.php'
  uri_protocol: 'REQUEST_URI'
  ...
```

Database method acts differently. The source config file should 
represent `database.php` structure:

```yml
# database.yml
query_builder: true
db:
  default:
    username: johnny
    password: rubber
    database: project
    failover:
      - username: johnny
        password: rubber
        database: project
        hostname: failover-host.intranet
```

YamlIgniter then takes this input and transforms as described:

- Merges root context with defaults for framework version (in current 
example, `active_group: default` would be added to config)
- Iterates over all entries of `db`, merging every entry with default 
database context
- Iterates over all entries of `db.*.failover`, merging every entry 
with default database context

So above example would result in:

```yml
query_builder: true
active_group: default
db:
  default:
    dsn: ''
    hostname: localhost
    username: johnny
    password: rubber
    database: project
    dbdriver: mysqli
    ...
    failover:
      - dsn: ''
        username: johnny
        password: rubber
        database: project
        hostname: failover-host.intranet
        dbdriver: mysqli
        ...
```

## Testing

Testing is done via [Codeception][codeception] framework alongside with
[Allure Framework][allure] for reporting. Launching tests is easy:

```bash
bin/codecept run
```

To get full-blown reporting, install Allure commandline and use 
following command:

```bash
composer run-script test:full
```

## Contributing

Fork, fix, enhance, create pull request, ping maintainers if there's no
reaction.
