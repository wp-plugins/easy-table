=== Easy Table ===
Contributors: takien
Donate link: http://takien.com/donate
Tags: table,csv,csv-to-table,post,excel,csv file,widget
Requires at least: 3.0
Tested up to: 3.3.2
Stable tag: 0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easy Table is WordPress plugin to create table in post, page, or widget in easy way using CSV format. This can also display table from CSV file.

== Description ==

Easy Table is a WordPress plugin that allow you to insert table in easy way. Why it's easy? Because you don't need to write any complicated HTML syntax. Note that this plugin is not a graphical user interface table generator, so you can simply type your table data directly in your post while you writing. No need to switch to another window nor click any toolbar button.

Easy Table using standard CSV format to generate table data, it's easiest way to build a table. 

= Some Features =
* Easy to use, no advanced skill required
* Display table in post, page or even in widget
* Read data from CSV file and display the data in table
* Sortable table column (using tablesorter jQuery plugin)
* Fancy table design (using Twitter CSS bootstrap)
* WYSIWYG safe, I mean you can switch HTML/View tab in WordPress editor without breaking the table data.

Example usage:

* Basic table
`[table]
Year,Make,Model,Length
1997,Ford,E350,2.34
2000,Mercury,Cougar,2.38
[/table]`

* Table with additional parameter
`[table tablesorter="1" id="someid"]
Year,Make,Model,Length
1997,Ford,E350,2.34
2000,Mercury,Cougar,2.38
[/table]`

* Table from CSV file
`[table file="example.com/blog/wp-content/uploads/pricelist.csv"][/table]`

== Installation ==

There are many ways to install this plugin, e.g:

1. Upload compressed (zip) plugin using WordPress plugin uploader.
2. Directly install from WordPress.org directory
3. Upload manually uncompressed plugin file using FTP.

== Frequently Asked Questions ==

= Is there any question? =

Not yet.

== Screenshots ==

1. Various table in a post
2. Easy Table options page
3. It's easy to display your uploaded CSV file as HTML table.
4. Easy Table in text widget

== Upgrade Notice ==

No

== Changelog ==

= 0.2 =
* Fixed: Backward compatibility of str_getcsv that just not work in the version 0.1, now plugin should runs on PHP 5.2
* Fixed: Table now has 'table' class even when 'tablesorter' is not enabled.

= 0.1 =
* First release