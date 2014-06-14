=== Event Organiser Posterboard ===
Contributors: stephenharris
Donate link: http://www.wp-event-organiser.com/donate
Tags: events, event, posterboard, responsive, event-organiser, grid
Requires at least: 3.3
Tested up to: 3.9.1
Stable tag: 1.0.2
License: GPLv3

Adds an 'event board' to display your events in a responsive posterboard.

== Description ==

= Basic Usage =

To display the event posterboard simply use the shortcode `[event_board]` on any page or post. Full width pages work best.

= Filters =

You can add filters at the top of the event board to filter the events. Supported filters include:
 
 * venue
 * category
 * city (*when installed with Event Organiser Pro*)
 * state (*when installed with Event Organiser Pro*)
 * country (*when installed with Event Organiser Pro*)

For example

     [event_board filters="state"]
     
You can display multiple filters by listing them as a comma delimited list

     [event_board filters="venue,category"]
     

You can edit the template used for the event board. See the FAQ.
     
== Installation ==

Installation and set-up is standard and straight forward. 

1. Upload `event-organiser-event-board` folder (and all it's contents!) to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add the shortcode to a page.


== Frequently Asked Questions ==

= Can I change the content of the event boxes =
Yes. By default the plug-in uses the template found in `event-organiser-event-board/templates`. 
Simply copy that template (`single-event-board-item.html`) into your theme and edit it there. Please note that the template uses **underscore.js** templating.  

== Screenshots ==

1. Event posterboard
2. Event posterboard


== Changelog ==

= 1.0.2 =
* Fixes bug on some installs where the "load more" bar does not appear.
* Fixes rogue "dot" appearing 
* Added Hungarian translation (thanks to Daniel Kocsis).

= 1.0.1 =
* Renamed classes to use `eo-pb-` prefix.
* Fixed bug where draft events appeared on the board.
* Fixed bug where 'load more' would appear when there were fewer than 10 events.
* Corrected documentation in readme 

= 1.0.0 =
Initial release

== Upgrade Notice ==

If you have edited the template or added any styling, please note this update changes the classes used. 


