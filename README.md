woo2moodle-wordpress
=====================

Based on wp2moodle--wordpress- by Tim St. Clair (https://github.com/frumbert/wp2moodle--wordpress-)

WooCommerce to Moodle pass through authentication plugin (WordPress end). Takes the user that is logged onto WordPress and passes their details over to Moodle, enrols them and authenticates.

Activating and configuring the plugin
-------------------------------
Note, you must to rename the zip to be just 'woo2moodle.zip' before you upload the plugin to WordPress. If the zip extracts to a sub-folder, it won't work!

1. Upload this to your WordPress (should end up being called /wp-content/plugins/woo2moodle/)
2. Activate the plugin
3. Click woo2moodle on the WordPress menu
4. Set your Moodle url (e.g. http://your-site.com/moodle/)
5. Set the shared secret. This is a salt that is used to encrypt data that is sent to Moodle. Using a guid (http://newguid.com) is a good idea. It must match the shared secret that you use on the Moodle plugin. (https://github.com/ppv1979/woo2moodle-moodle)
6. Other instructions are available on the settings page.

How to use the plugin
------------------
1. Edit a post or a page
2. Use the moodle button on the editor to insert shortcodes around the text you want linked
3. When authenticated as subscriber, contributor, etc, click on the link.

Note: If the user is not yet authenticated, no hyperlink is rendered. The link does not function for Wordpress admins.

Shortcode examples
------------------

[woo2moodle class='my-class' cohort='course1' target='_blank']<img src='path.gif'>Open my course[/woo2moodle]

[woo2moodle group='group2']A hyperlink[/woo2moodle]

class: the css classname to apply to the link (default: woo2moodle)
target: the hyperlink target name to apply to the link (defaut: _self)
cohort: (optional) the id [mdl_cohort.idnumber] of the moodle cohort in which to enrol the user (can be a comma-seperated list for multiple enrolments)
group: (optional) the id [mdl_groups.idnumber] of the moodle group in which to enrol the user (can be a comma-seperated list for multiple enrolments)

Licence:
--------
GPL2, as per Moodle.
