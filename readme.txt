=== easyReservations ===
Contributors: feryaz
Tags: booking, reservations, hotel, reservation form, calendar, reservation, restaurant, booking form, hospitality, events, tours, availability, bookings, booking calendar, availability calendar
Requires at least: 5.3
Tested up to: 5.6
Requires PHP: 7.0
Stable tag: 5.0.11
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This powerful property and reservation management plugin allows you to receive, schedule and handle your bookings easily!

== Description ==

easyReservations is the perfect plugin for receiving, managing and handling bookings easily. It's designed to be used for any reservable business like hotels, cars, events, B&Bs, appointments or conferences.
It's very flexible and intuitive and has a huge amount of functions and possibilities. Of course it's completely translatable.

[Website](http://easyreservations.org/ "Website!")

= Features =
* Resource Catalog
* Availability calendar
* Unlimited customizable reservation forms
* Property management
* Half-hourly, hourly, daily, nightly and weekly billing
* Flexible price filters, rates, discounts and availability
* Live price calculation and error handling
* And a lot more!

[Documentation](http://easyreservations.org/knowledgebase/ "Documentation") | [Report bugs](http://easyreservations.org/forums/forum/bug-reports/ "Report bugs") | [Premium](http://easyreservations.org/premium/ "Support the development!")

== Installation ==

= Minimum Requirements =

* PHP 7.2 or greater is recommended
* MySQL 5.6 or greater is recommended

= Automatic installation =

Automatic installation is the easiest option -- WordPress will handles the file transfer, and you won’t need to leave your web browser. To do an automatic install of easyReservations, log in to your WordPress dashboard, navigate to the Plugins menu, and click “Add New.”

In the search field type easyReservations,” then click “Search Plugins.” Once you’ve found us,  you can view details about it such as the point release, rating, and description. Most importantly of course, you can install it by! Click “Install Now,” and WordPress will take it from there.

= Manual installation =

Manual installation method requires downloading the easyReservations plugin and uploading it to your web server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation).

= Updating =

Automatic updates should work smoothly and does not delete any data, but we still recommend you back up your site.

If you encounter issues with the catalog/category pages after an update, flush the permalinks by going to WordPress > Settings > Permalinks and hitting “Save.” That should return things to normal.

== Changelog ==

= 6.0-alpha.19 - 2020-02-26 =
* Enhancement - Setting order to processing or completed only possible if all reservations are approved
* Enhancement - Added option to add a price display suffix in tax settings
* Enhancement - Added option to edit the order note
* Enhancement - Admin created reservations have a resource item with the correct price attached now
* Enhancement - Display reservation status in admin order receipt
* Enhancement - Updated filter time unit condition years
* Enhancement - Custom fields default value get's used as placeholder for text fields, text areas and number fields
* Enhancement - Added email to privacy exports
* Enhancement - New order action to approve all attached reservations
* Fix - Reservations got instantly removed from cart on some setups
* Fix - Without premium emails could be sent empty
* Fix - Without premium the order status was not updated after submit and remained as draft
* Fix - Newly added tax rates did not get applied
* Fix - Order search
* Fix - Deposit form does not get displayed for free orders
* Fix - My account orders endpoint
* Fix - Filter form unavailability part did not get hidden when switching to price filter
* Fix - Filter names can have big letters and free spaces now
* Fix - Stylesheet fixes for the themes twenty twenty and twenty twentyone
* Fix - Stylesheet fixes for search
* Fix - Error message when departure is too soon
* Removed - Required permission option in resources settings
* Removed - Option to display custom field when adding reservations

= 6.0-alpha.18 - 2020-01-28 =
* Enhancement - Stylesheets for twenty twentyone wordpress theme
* Enhancement - Improved automatic order notes for changes in reservation
* Enhancement - New option to enforce difference in time between arrival and departure
* Enhancement - Handling of multiply countries in checkout form
* Enhancement - Improved translatability
* Fix - Timeline display when changing resources menu order
* Fix - Reservations in cart apply to calendar availability
* Fix - Display of unavailable departure days in calendar
* Fix - Selectability of arrival and departure time in calendar
* Fix - Resource filters in price calculation
* Fix - Resource archive
* Fix - Miscellaneous stylesheet fixes

= 6.0-alpha.17 - 2020-01-04 =
* Enhancement - Default arrival and departure time in date selection
* Enhancement - Stylesheets for wordpress themes
* Fix - Catalog page entries displayed twice
* Fix - Slots in date selection
* Fix - Redirect after submit on resources pages
* Fix - Checkout state wrongfully marked as invalid
* Fix - Checkout create account
* Fix - My account endpoints 404
* Fix - Reservations getting instantly removed from shopping cart in some time zones
* Fix - Premium update and install message
* Fix - Search redirect option
* Fix - Search calendar for hourly and half-hourly resources

= 6.0-alpha.16 - 2020-11-29 =
* Fix - Breaking bug

= 6.0-alpha.15 - 2020-11-26 =
* Feature - Invoices
* Feature - Debug tools
* Enhancement - Country specific required checkout data and labels
* Enhancement - Order of resources if no menu order is defined
* Enhancement - Template system
* Fix - Rounding in PHP 8.0
* Fix - Custom fields added to cart of unsuccessful checkout
* Fix - Prevent endless loop when [name] is in reservation name
* Fix - My account

= 6.0-alpha.14 - 2020-10-28 =
* Enhancement - Display of possible departure dates in calendar
* Enhancement - Order of resources if no menu order is defined
* Fix - Email settings overridden templates
* Fix - Center view on calendar in form
* Fix - Wrong month after selecting arrival

= 6.0-alpha.13 - 2020-04-24 =
* Feature - Direct checkout (option in form block)
* Enhancement - Admin can now set reservations to not belong to any resource
* Enhancement - Deposit form on order payment page
* Enhancement - Display deposit amount to pay in admin edit order
* Performance - Timeline generation time by 40%
* Fix - Update reservations receipt item when changing resource
* Fix - Display custom data in reservation preview

= 6.0-alpha.12 - 2020-03-30 =
* Feature - Cart widget
* Feature - Resources widget
* Feature - Set resource to be onsale
* Feature - Address in admin edit user profile

= 6.0-alpha.11 - 2020-03-25 =
* Fix - Price activated resulted in misalignment in hourly frequency

= 6.0-alpha.10 - 2020-03-25 =
* Enhancement - Option to display price in calendar for daily resources and slots
* Enhancement - Background emails
* Fix - Calendar slots greying out days before arrival when selecting departure
* Fix - JS error in filter settings

= 6.0-alpha.9 - 2020-03-22 =
* Feature - My account
* Feature - Removal of personal data

= 6.0-alpha.8 - 2020-03-18 =
* Fix - Tax settings js

= 6.0-alpha.7 - 2020-03-18 =
* Feature - Reservation timeline
* Enhancement - Filters can set the price to 0
* Tweak - Order of reservations in admin table
* Fix - Pagination in reservations table
* Fix - Arrival and departure default value in reservation add
* Fix - Nightly billing calculation of nights
* Fix - Format in date created field in orders and reservations

= 6.0-alpha.6 - 2020-03-04 =
* Tweak - Improved separation between reservation name and title
* Fix - Upgrading could be bypassed
* Fix - Arrival and departure fields trigger validation

= 6.0-alpha.5 - 2020-02-26 =
* Fix - Departure field in form and search shortcode has no datepicker
* Fix - AIT Themes removing all select fields in forms
* Fix - Cannot delete global availability filters
* Fix - Edit reservation cannot set children to 0

= 6.0-alpha.4 - 2020-02-23 =
* Fix - Adding new reservation as admin

= 6.0-alpha.3 - 2020-02-22 =
* Fix - Adding form block without changing any setting resulted in empty form

= 6.0-alpha.2 - 2020-02-20 =
* Fix - Data deletion on uninstall

= 6.0-alpha.1 - 2020-02-18 =
* Initial release
See http://easyreservations.org/easyreservations-6-0-alpha/ for more information.

== Upgrade Notice ==

= 6.0-alpha.1 =
6.0 is a major update. Make a full site and database backup and update your theme and extensions before upgrading.
