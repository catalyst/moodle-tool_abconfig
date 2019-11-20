# A Moodle A/B testing admin tool

<a href="https://travis-ci.org/catalyst/moodle-tool_abconfig">
<img src="https://travis-ci.org/catalyst/moodle-tool_abconfig.svg?branch=master">
</a>

A way to A/B test config, or slowly turn on config for certain audiences or % of traffic

* [Branches](#branches)
* [Installation](#installation)
* [Configuration](#configuration)
* [Example use cases](#example-use-cases)
* [Support](#support)


Branches
--------

* For all supported Moodle versions use the master branch.

Installation
------------

### Moodle 3.8+

This plugin will work natively from Moodle 3.8 onwards, but can be used on earlier versions of Moodle.


### Moodle 3.4 - 3.7

To use on an older version of Moodle, MDL-66340 must be backported to the moodle instance. This plugin relies on a new hook, that enables configuration to be applied immediately following the Moodle setup process each page. For Moodle versions earlier than 3.7, MDL-60470 is required to enable Session scope experiments to be decided on, immediately after a user logs in.

To install the plugin, simply drop it into `path/to/moodle/admin/tool/abconfig` directory. When moodle is accessed it will prompt for installation of the plugin. Press Upgrade database now, and the plugin will be installed.

Configuration
-------------
Visit the Site Administration menu and navigate to Plugins->Admin Tools->Manage Experiments. This page allows you to add new experiments, as well as edit existing experiments. To add a new experiment, fill in the fields, and click 'Add Experiment'. To edit the details of an existing experiment, click on the Edit link inside of the experiments table, to go to the edit page.

### Scopes and audiences
The plugin currently has two scopes that experiments can lie under, Request scope and Session scope.

#### Request scope

Request scope experiments are run on every http request. Any request scope will treat a new page load as a new experiment call, and so a new set of conditions will be decided on. This means that behaviour can vary between loads of Moodle, so be careful when putting changes here that will affect a user's experience, as this may lead to an inconsistent experience for users. This includes each request including ajax calls within a single html page load.

#### Session scope

Session scope experiments are called when a user logs into the site. At this time, a condition set will be decided on, and users will continue to have that condition set applied for the length of their session. This does not apply to guest users, only logged in users. When a user logs out, and logs back in, a new set of conditions is applied to the account, which may be the same condition set.

### Conditions

Each experiment can have multiple condition sets avaiable, of which 1 is applied to a given user at a given time. The condition set is picked based on the weighting you specify when creating the condition, which corresponds to the % of users that it applies to.

#### IP Whitelist

In this condition, an IP whitelist can be specified, and any users that have an IP that matches the whitelist, will not have this condition applied to them if this is the condition set that is selected. Instead, no action will be taken for that user.

#### Experiment Commands

Here is where the commands for a condition set can be specified. These are applied sequentially right after loading Moodle. Each command should be on a newline. A list of valid commands is below:

##### CFG

This command sets moodle core configurations to a specified value.

Note: `CFG` and `forced_plugin_setting` commands will not overwrite config set inside config.php by design as a security measure.

```
CFG,config,value
CFG,passwordpolicy,1
```

##### forced_plugin_setting

This command sets a plugin configuration to a specified value.

```
forced_plugin_setting,plugin,config,value
forced_plugin_setting,auth_manual,expiration,1
```

##### http_header

This command sends HTTP headers during the page load.

```
http_header,header,content
http_header,From,example@example.org
```

##### error_log

This command logs the given messages into the PHP error_log for the webserver.

```
error_log,message
error_log,error message
```

##### js_header

This command runs small JavaScript chunks just before the page headers are sent.

```
js_header,javascript
js_header,console.log('example');
```

##### js_footer

```
js_footer,javascript
js_footer,console.log('exmaple');
```

This command runs small JavaScript chunks just before the page footer is sent.



### Enabling Experiments and forcing conditions
By default all experiments start disabled, so you can't accidentally apply broken behaviour to the full user group. Once conditions have specified for an experiment, they can be tested by using some URL params on any page. The params to use for any given condition are listed inside the table for the conditions. They follow the syntax `?experimentshortname=conditionset`. It is encouraged to use these params to properly test all conditions before enabling an experiment.

Once an experiment is enabled, it can also be enabled for admin accounts as well, which are ignored by the plugin by default. This option should be used with care, as bad configuration may result in all administrator accounts being locked out from the moodle instance, in extreme cases. In the event that something goes wrong when applying experiments to admins as well, the URL parameter `?abconfig=off` can be used to ignore the plugin entirely for that page, which can be used to regain access.

Example use cases
-----------------

### Analytics

To integrate analytics into the plugin configurations, you can use the Javascript Footer command inside of condition sets, which can be used to interact with the chosen analytics engine.




Support
-------

If you have issues please log them in github here

https://github.com/catalyst/moodle-tool_abconfig/issues

Please note our time is limited, so if you need urgent support or want to
sponsor a new feature then please contact Catalyst IT Australia:

https://www.catalyst-au.net/contact-us

<a href="https://www.catalyst-au.net/"><img alt="Catalyst IT" src="https://cdn.rawgit.com/CatalystIT-AU/moodle-auth_saml2/master/pix/catalyst-logo.svg" width="400"></a>
