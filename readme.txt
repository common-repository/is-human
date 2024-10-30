=== is_human() ===
Contributors: Nick Berlette
Donate link: http://www.pancak.es/plugins/is-human/
Tags: captcha, comments, anti-spam, admin, post, widget, AJAX, plugin, international
Requires at least: 2.5
Stable tag: 1.4.2

Multi-method verification of humanity for comments and user registrations.

== Description ==

is_human() is the simplest yet most extensive human verification system available on the WordPress market. Users can choose one of three verification types, or have the script show one at random on each page-load.

Currently, is_human() verifies comments and new user registrations.

= Verification Methods =

* Standard Captcha Image
* Simple Math Equation
* Simple Custom Questions (w/ Question Generator)

= New Features =

* Completely internationalized
* Hide is_human() from registered users / admins
* Control log display settings
* Rejected comments logging
* Rejected user registrations logging
* Blacklisting for spammers
* Status update on Dashboard
* Widget to display statistics to public

== Other Notes ==

= Detailed Features Information =
* <strong>Standard Captcha Image</strong> - 
The standard captcha image that we all have come to know and love. Font family, size, and color are all editable on the options page, along with the image's dimensions and background color.  The background has random lines drawn across it to help prevent decryption.

* <strong>Simple Math Equation</strong> - 
is_human() has the ability to calculate two random numbers and throw them into a simple equation that only humans could solve. Only uses addition and subtraction, none of the hard stuff ;-)

* <strong>Simple Custom Questions</strong> - 
Another option is_human() gives you is a simple questions system. The administrator (that will be you shortly, I hope) is given a few pre-defined questions, but can also create their own questions with our simple generator or hard-coding them (for more advanced users). We try to keep it simple, to mostly logic questions that anybody can answer.

* <strong>Logging</strong> - 
If logging is enabled in the admin panel, all rejected user registrations and comments will be logged. Admin can view the logs in an appealing, paginated area.

* <strong>Blacklisting</strong> - 
Admin can select IP Addresses for blacklisting, by which they will be blocked from accessing any part of the site. They don't even see the title.

* <strong>Internationalized</strong> -
If you're from a non-English speaking country, is_human() automatically adapts to your blog's locale settings. Pretty neat, huh?

The system adds an extra page to the WordPress Dashboard to make things a lot easier to maintain. Nearly every feature of is_human() is changeable from the admin panel - from captcha colors to font sizes to input fields, the dashboard addition also includes complete walkthroughs for all of the editing.

= Change Logs =

* <strong>1.4.2</strong> - Fixed bug with creating the link on the admin menu.
* <strong>1.4.1</strong> - Completely internationalized, hide from admins / users, updated options page style, several bug fixes, cleaned up code.
* <strong>1.3.5</strong> - Admin can set logs per page, and disable logging. Bug fixes.
* <strong>1.3.4</strong> - Fixed a few important bugs and errors.
* <strong>1.3.3</strong> - Added <em>rejected comments / user registrations logging</em>, <em>blacklisting</em>, <em>status update on dashboard</em>, and a <em>widget</em>.
* <strong>1.3.2</strong> - Added captcha to User Registration page
* <strong>1.3.1</strong> - Added inline reload functionality
* <strong>1.2.9</strong> - Options page upgraded, more user-friendly
* <strong>1.2.8</strong> - Initial public release

== Installation ==

1. Upload whole is-human folder to the /wp-content/plugins/ directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add &lt;?php is_human(); ?&gt; to your theme's comments page where you want it displayed.

See also: [Plugin Homepage](http://www.pancak.es/plugins/is-human/), [Author Homepage](http://www.pancak.es/).

== Screenshots ==

Below is a visual overview of the main features contained in is_human()'s backend.

1. Short installation guide to apply is_human() to your comment's page.
2. Spam Management center including comments and user logs, as well as blacklisting section.
3. General Settings area, for controlling the basic display options of is_human().
4. Controls for how the Captcha Image is displayed.
5. Simple questions section with generator and detailed documentation.