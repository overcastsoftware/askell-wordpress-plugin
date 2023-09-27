=== Askell Registration ===
Contributors:      overcast, aldavigdis
Tags:              block, subscription, user registration, memberships, payment, saltpay, valitor, rapyd
Tested up to:      6.3
Requires at least: 6.3
Requires PHP:      8.1
Stable tag:        0.1.0
License:           GPL-3.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-3.0.html

Let your users sign up and pay for for recurring subscriptions directly from your WordPress site using [Askell by Overcast Software](https://askell.is/).

Posts and pages can be restricted to certain subscription tiers using toggle switches in the Block Editor's sidebar.

This plugin adds an interactive block to your WordPress installation that you can use on in the Block Editor, Gutenberg and WordPress FSE enabled WordPress site that facilitates receiving payment information directly from your website, using Askell by Overcast Software.

Askell works as a secure intermediate between your WordPress site and your payment processor and manages information about your paid subscribers, their subsciptions and payment information.

Askell supports the following card payment processors:

* SaltPay (Teya) Credit Card Payments
* Rapyd (formerly Valitor Pay)

== Frequently Asked Questions ==

= Is the plugin secure? =

The plugin displays a credit card form directly in a WordPress block. The payment information is transferred over a secure TLS connection to the Askell JSON API from the user's browser and from there to your payment processor, without involving your WordPress database.

This means that credit card information is stored securely by the payment processor of your choice, but not on your WordPress site or Askell itself, reducing the chance of liability in case of a security breach on your site.

= I would like to customise or contribute to the plugin, where can I get the source code? =

We are always happy to receive suggestions and contributions. Our main development branches are at [https://github.com/overcastsoftware/askell-wordpress-plugin](https://github.com/overcastsoftware/askell-wordpress-plugin).

Out Github repository includes build instructions as well as information on installing development dependencies in case you would like to make your own build of the plugin.

Feel free to send us pull requests; or feature requests and bug reports on our Github page.

Please contact us via email at [hallo@overcast.is](mailto:hallo@overcast.is) or the WordPress Security Team in case you have discovered a security issue that you would like to report.

= Does the plugin support WordPress Multisite? =

The plugin has not been tested with WordPress Multisite as of yet, but we may support it in a future version.

= Dates and currency values are not in the correct format, what should I do? =

Formatting for currencies and such depends on the language settings of your WordPress installation as well as the ICU library and the PHP Internationalization Functions package installed on your server.

Contact your web host for more information on server-side localisation in PHP.

== 3rd party components ==

The plugin uses the following 3rd party open soruce components:

* [SVG Credit Card & Payment Icons by Aaron Fagan](https://github.com/aaronfagan/svg-credit-card-payment-icons), licensed under the Apache license.
* [Icons from the Twemoji collection](https://twemoji.twitter.com/). Copyright 2020 Twitter, Inc and other contributors. Licensed under Creative Commons CC-BY 4.0: [https://creativecommons.org/licenses/by/4.0/](https://creativecommons.org/licenses/by/4.0/).

== Changelog ==

= 0.1.0 =

Initial development version.
