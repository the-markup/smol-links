# Shlinkify #
**Contributors:** [dphiffer](https://profiles.wordpress.org/dphiffer/)  
**Donate link:** https://themarkup.org/donate  
**Tags:** short url, shlink  
**Requires at least:** 4.5  
**Tested up to:** 6.0.0  
**Requires PHP:** 7.3  
**Stable tag:** 0.0.1  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

Create and manage Shlink short links from WordPress

## Description ##

__This plugin is a work in progress, development is ongoing.__

A WordPress dashboard interface for managing a self-hosted [Shlink URL shortener](https://shlink.io/) instance.

* Create and edit Shlinks short links from a manager interface
* Optionally generate new short URLs upon saving new posts

# Developer setup #

__Developer dependencies:__

* [node.js](https://nodejs.org/) (tested on v16)
* [Docker Desktop](https://www.docker.com/products/docker-desktop)

__Build and start:__

```
./bin/build.sh
./bin/start.sh
```

__Running tests:__

```
docker-compose exec web composer --working-dir="/var/www/html/wp-content/plugins/shlinkify" test
```

## Installation ##

1. Upload `shlinkify` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the __Shlinkify__ settings from your WordPress dashboard

## Changelog ##

### 0.0.1 ###
* Generate short URLs upon saving a post
* Create/edit short URLs from a manager

## Upgrade Notice ##
