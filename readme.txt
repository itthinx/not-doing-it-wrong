=== Not Doing it Wrong ===
Contributors: itthinx, proaktion
Donate link: https://www.itthinx.com/shop/
Tags: debug, log, error, warning, notice
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3

A WordPress plugin as a last resort to issues when _doing_it_wrong() is too eager and we want to find out what is happening.

== Description ==

This plugin is intended for developers to use as a tool to find issues.

It is ideally deployed temporarily as a MUST-USE plugin until issues have been identified.

A WordPress plugin as a last resort to issues when `_doing_it_wrong()` is too eager and we want to find out what is happening.

Avoids triggering a user error for calls to `_doing_it_wrong()` and gathers information which is logged at shutdown.

The information logged includes originating functions, counts and stack traces.

The plugin file `not-doing-it-wrong.php` must be placed in `mu-plugins` so it can catch all instances.

*The plugin will produce very extensive log entries!*
