=== Houzez Property Feed ===
Contributors: PropertyHive
Tags: property import, houzez, houzez import property, expertagent, expert agent, loop, 10ninety, vebra, alto, dezrez, jupix, street, real estate
Requires at least: 3.8
Tested up to: 6.2.2
Stable tag: 1.0.15
Version: 1.0.15
Homepage: https://houzezpropertyfeed.com
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Automatically import properties into Houzez from a wide range of estate agency CRMs

== Description ==

This free plugin from the creators of [Property Hive](https://wordpress.org/plugins/propertyhive) makes it easy to import properties from various CRMs in various formats into the popular Houzez theme.

We can import properties from the following estate agency CRMs/formats:

* 10ninety
* Acquaint
* agentOS
* Alto by Vebra
* Apex27
* BDP
* BLM
* Dezrez Rezi
* Domus
* Expert Agent
* Jupix
* Loop
* Street

Here's just a couple of reasons why you should choose the Houzez Property Feed plugin to import your property stock:

* 20+ years experience in working with property feeds
* New formats always being added
* Lots of settings and easy to configure
* In-depth [documentation](https://houzezpropertyfeed.com/documentation/)

= Free features =

* Automatic imports
* One active import
* Import 25 properties
* Logs stored for one day

= PRO features =

* All of the above, plus:
* Import unlimited properties
* Multiple simulateous active imports
* Priority support
* Logs stored for seven days
* Logs emailed to a specified email address

[Update to PRO here](https://houzezpropertyfeed.com/#pricing)

== Installation ==

= Requirements =

* Houzez theme installed and activated
* For formats that use XML the PHP SimpleXML library will need to installed
* That WP Cron is firing automatically or an alternative cron job in place

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of Property Hive, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type "Houzez Property Feed" and click Search Plugins. Once you've found our plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking "Install Now".

= Manual installation =

The manual installation method involves downloading the Houzez Property Feed plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

Once installed and activated, you can access the import tool by navigating to 'Houzez > Import Properties' from within Wordpress.

= Updating =

Updating should work like a charm; as always though, ensure you backup your site just in case.

== Screenshots ==

1. Once activated you'll be presented with a new 'Import Properties' admin menu item where you can manage your imports
2. Existing automatic imports will be displayed along with scheduling information
3. Adding and editing imports is easy with our simple to use interface
4. Each time an import runs we'll store in-depth logs so you can see exactly what was imported and when, plus any errors that arose

== Changelog ==

= 1.0.15 - 2023-06-13 =
* Added new 'Field Mapping' section to import settings to allow complete control over fields imported, as well as catering for any fields added using the Houzez Field Builder feature

= 1.0.14 - 2023-06-13 =
* Corrected field name referenced for status in Street format

= 1.0.13 - 2023-06-13 =
* Added ability to map additional CRM values when configuring taxonomy mapping. Useful if property types, for example, have been customised in the CRM and isn't one of the standard ones

= 1.0.12 - 2023-06-12 =
* Added support for Jupix
* Cater for no display address/title in logs table

= 1.0.11 - 2023-06-11 =
* Added support for Expert Agent
* Added support for Domus
* Ensure features are cast to a string in Apex27
* Tweaks to open_ftp_connection() function so ftp_chdir() is only called when a directory is passed in

= 1.0.10 - 2023-06-09 =
* Added support for Dezrez Rezi format
* Corrected issues with email reports and remove action not saving

= 1.0.9 - 2023-06-08 =
* Added support for BLM format, specifically files sent via FTP to the server from the third party

= 1.0.8 - 2023-06-07 =
* Initial support for BDP format
* Added 'Import Data' meta box to property edit screen to see what data was sent by CRM

= 1.0.7 - 2023-06-07 =
* Initial support for Alto by Vebra format
* Show warnings when setting up an import if required libraries (cURL, SimpleXML etc) are missing

= 1.0.6 - 2023-06-07 =
* Initial support for agentOS format
* Added ability to add a warning message per format when setting up an import. Done for agentOS where we need to show a warning about throtlling
* Changed rent frequencies to lowercase
* Changed datatype of 'entry' field in logs table to store more text. Useful when wanting to see the full response

= 1.0.5 - 2023-06-06 =
* Initial support for Apex27 format

= 1.0.4 - 2023-06-06 =
* Initial support for Acquaint format

= 1.0.3 - 2023-06-06 =
* Initial support for 10ninety format
* Initial support for Street formats

= 1.0.2 - 2023-06-05 =
* Escaping and sanitization to meet WordPress plugin guidelines
* Don't set global PHP limits to meet WordPress plugin guidelines
* Remove use of ALLOW_UNFILTERED_UPLOADS to meet WordPress plugin guidelines

= 1.0.1 - 2023-05-26 =
* Taxonomy mapping
* Contact information mapping and rules
* License key integration
* Only show pro link in plugin list if pro not in place
* Corrected featured image not getting set when new property imported

= 1.0.0 - 2023-05-23 =
* First working release of the plugin