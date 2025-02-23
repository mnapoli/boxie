# Boxie

Alternative to Homebrew based on Docker: install and run CLI tools **without polluting your system**.

## Why?

- **No installation**: no need to install anything on your system, nor compile anything. "Installing" a tool is usually just a matter of downloading a Docker image.
- **No dependencies**: installing a package does not download thousands of dependencies. No conflicts to deal with, or polluting your system.
- **Isolation**: each tool is installed in its own Docker container, so you can have multiple versions of the same tool installed at the same time.
- **Simple updates**: updating a tool means using a more recent Docker tag.

Read "how it works" below for more details.

## Installation

The only thing you need is Docker.

```bash
mkdir /opt/boxie
cd /opt/boxie
git clone https://github.com/mnapoli/boxie.git .
make build
```

Now add `/opt/boxie/bin` to your `PATH`.

> In the future, the goal is this:
> 
> - Boxie would be distributed as a Docker image (`boxie`)
> - Install by creating a script in `/usr/local/bin` that runs the Docker image:
> ```bash
> #!/bin/bash
> docker run -v /opt/boxie:/opt/boxie boxie $@
> ```
> That's it!

## Usage

CLI tools:

```bash
boxie install php
# Installs `php` in /opt/boxie/bin/php
php --version
# Related tools are also installed (e.g. installing `node` also installs `npm`)
composer --version

# Install multiple versions
boxie install php 8.3
php@8.3 --version
composer@8.3 --version
```

Long-lived services (**not implemented yet**):

```bash
boxie service:install mysql
# Installs `mysql` in /opt/boxie/bin/mysql
# Automatically starts the service
# Services have their persistent data (mounted from /opt/boxie/data/mysql)

# MySQL now listens on localhost:3306
```

## How it works

Packages are declared in `packages/`. Each package declares at least 1 script that runs the Docker image. For example, [`packages/php/php`](packages/php/php) runs the `php-cli` Docker image.

When installing a tool `xxx`, Boxie will:

- run `packages/xxx/*.install.sh` (some tools require to build some images, [check out the Composer example](packages/php/composer.install.sh))
- create a simple script in `/opt/boxie/bin/xxx` that executes `/opt/boxie/packages/xxx/xxx`

One package can install multiple tools. For example, the `node` package installs `node` and `npm`. "How" a CLI tool runs can be 100% customized by each package. The simplest case is to run a Docker image. But it could also mount directories, expose ports, etc.

For example `node` could mount the current directory in the container and run `node` in it. But `npm` could also mount the NPM cache directory (`~/.npm`). The fact that each package can define its own behavior is what makes Boxie powerful.

The downside for Boxie maintenance:

- We need to explicitly declare each package in Boxie (there is no automatic way to add support for "all" packages automatically)
- We need to adjust the behavior and make sane defaults for each package (for example which Docker image to run, which directories to mount, which ports to expose, etc.)

## Status of the project

This is a proof of concept. It's not ready for production use. I am opening this repository to gather feedback and see if there is interest in this approach.

You are welcome to get involved, the design is still very fresh and could benefit a lot from more eyes and ideas.
