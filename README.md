horaro - Beautiful Schedules for the Web
========================================

horaro (Esperanto for *schedule*) is a small web application for creating
schedules for stream marathons (e.g. on Twitch or Hitbox). It's written in
PHP 7.2 and only requires PHP (duh) and MySQL 5 to run.

Features
--------

* Users can register their own accounts (i.e. horaro is meant to provide a
  hosted service for people, although registration can be disabled)
* Schedules can have up to 10 custom columns.
* responsive user interface
* semantic HTML5 markup
* clean, simple URLs
* proper timezone handling (never be confused about no or ambiguous timezone
  stuff in schedules)
* Each schedule is also available as JSON/XML/CSV.
* Each schedule has its own iCal feed for subscribing with Thunderbird or
  Google Calendar (or others).
* Schedules can be themed using Bootswatch.

Requirements
------------

* PHP 5.4+ or HHVM 3.6+
* MySQL 5 or MariaDB 5/10, with InnoDB support
* a webserver (Apache 2 and nginx are supported, others should work as well)
* mod_rewrite if you use Apache

Download
--------

Clone or download this repository.

You will need to create a dedicated vhost for horaro, because as of now, all
assets and links are absolute. Installing to something like
``http://localhost/horaro/`` will **not work**. Make sure the vhost points to
the ``www`` directory.

Installation
------------

You need quite a few tools to build horaro (just downloading the source won't be
enough to get it running). Make sure you have ``npm``, ``grunt-cli``, ``bower``
and Composer installed globally on your system. Then, perform the following steps
in your shell:

1. ``npm install`` to install the required node packages for the build process
2. ``bower install`` to download the required assets (CSS/JS)
3. ``composer install`` to install the required PHP packages
4. ``grunt`` to perform the actual build process

Now that the source is ready, you need to prepare your database by creating a
new one and executing ``resources/schema.sql``, which will create the needed
tables. Afterwards, execute the ``resources/seed-data.sql``, which will
initialize the configuration and create your very first account: **operator**
with the password **operator** (you should obviously change that as soon as
possible).

As the last step, duplicate the ``resources/config/parameters.dist.yml`` as
``resources/config/parameters.yml`` and edit the duplicated file. Follow the
comments in there to complete the configuration.

You're done! You should now be able to access horaro via the vhost you've created,
e.g. ``http://horaro.local/``.

Moving to Production
--------------------

When you install horaro on a production machine, be sure to set the installation's
``debug`` flag to ``false`` in the ``parameters.yml`` and then execute
``grunt ship`` (after you have done a ``grunt`` run). This will duplicate the
built assets and create versioned ones (e.g. ``app-backend.min.5763bb88.css``).

License
-------

horaro is licensed under the MIT license.
