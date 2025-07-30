=== {eac}Doojigger Simple AWS Extension for WordPress ===
Plugin URI:         https://eacdoojigger.earthasylum.com/eacsimpleaws/
Author:             [EarthAsylum Consulting](https://www.earthasylum.com)
Stable tag:         1.1.1
Last Updated:       30-Jul-2025
Requires at least:  5.8
Tested up to:       6.8
Requires PHP:       7.4
Requires EAC:       3.1
Contributors:       kevinburkholder
Donate link:        https://github.com/sponsors/EarthAsylum
License:            GPLv3 or later
License URI:        https://www.gnu.org/licenses/gpl.html
Tags:               aws, amazon web services, AWS PHP SDK, S3 Bucket, EventBridge, {eac}Doojigger
WordPress URI:      https://wordpress.org/plugins/eacsimpleaws
GitHub URI:         https://github.com/EarthAsylum/eacSimplaAWS

Enables the AWS SDK for PHP; adds a Webhook for WooCommerce to write to an S3 bucket; adds a REST endpoint for EventBridge to post to WordPress.

== Description ==

= Simple AWS =

This extension, when enabled, provides easy access to Amazon Web Services (AWS) from other plugins, extensions and custom functions through the [AWS SDK for PHP](https://aws.amazon.com/sdk-for-php/).

From the settings page, enter your AWS Region and your IAM account credentials, then access AWS programmatically using the provided methods and filters along with the AWS SDK classes and methods.

Please review:
+   [Policies and permissions in IAM](https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html)
+   [Managing access keys for IAM users](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html)

= Available Methods =

`getAwsRegion()` returns your selected region

`getAwsAccessKey()` returns your access key

`getAwsAccessSecret()` returns your access secret

`getAwsCredentials()` returns a 'credentials' array with your key and secret

`getAwsClientParams()` returns an AWS client instantiation array

`getAwsEndpoints()` returns a (large) array of all AWS endpoint parameters

`getAwsRegions()` returns an array of all regions (name=>description)

`setAwsVersion()` override default ('latest') version

`setAwsRegion()` override set region

`setAwsEndPoint()` override default endpoint

= Available Filters =

`SimpleAWS_version` returns the AWS version string

`SimpleAWS_region` returns your selected region

`SimpleAWS_access_key` returns your access key

`SimpleAWS_access_secret` returns your access secret

`SimpleAWS_credentials` returns a 'credentials' array with your key and secret

`SimpleAWS_client_params` returns an AWS client instantiation array

`SimpleAWS_endpoints` returns a (large) array of all AWS endpoint parameters

`SimpleAWS_regions` returns an array of all regions (name=>description)

= Examples =
```php
$cloudFront = new Aws\CloudFront\CloudFrontClient([
    'version'       => 'latest',
    'region'        => apply_filters('SimpleAWS_region',''),
    'credentials'   => [
        'key'       => apply_filters('SimpleAWS_access_key',''),
        'secret'    => apply_filters('SimpleAWS_access_secret','')
    ]
]);

if ($aws = $this->getExtension('Simple_AWS')) {
    $cloudFront = new Aws\CloudFront\CloudFrontClient([
        'version'       => 'latest',
        'region'        => $aws->getAwsRegion(),
        'credentials'   => $aws->getAwsCredentials(),
    ]);
}

if ($aws = eacDoojigger()->getExtension('Simple_AWS')) {
    $cloudFront = new Aws\CloudFront\CloudFrontClient([
        'version'       => $aws->getAwsVersion(),
        'region'        => $aws->getAwsRegion(),
        'credentials'   => $aws->getAwsCredentials(),
    ]);
}

if ($aws = $this->getExtension('Simple_AWS')) {
    $cloudFront = new Aws\CloudFront\CloudFrontClient(
        $aws->getAwsClientParams()
    );
}

if ($aws = eacDoojigger()->getExtension('Simple_AWS')) {
    $cloudFront = new Aws\CloudFront\CloudFrontClient(
        $aws->getAwsClientParams()
    );
}

if ($aws = $this->plugin->getExtension('Simple_AWS')) {
	if ($awsParams = $aws->getAwsClientParams()) {
        try {
            $s3client = new \Aws\S3\S3Client($awsParams);
			$result = $s3client->createBucket([
				'Bucket' 	=> $bucketName,
			]);
            $result = $s3client->putObject([
                'Bucket' 	=> $bucketName,
                'Key' 		=> $fileName,
                'Metadata' 	=> $metadata,
                'Body' 		=> json_encode($payload,JSON_PRETTY_PRINT),
			]);
        } 
        catch (\AwsException $exception) {
            $this->logError($exception,"AWS S3Client Error");
        }
	}
}

```

= Simple AWS S3 Events =

The *Simple AWS S3 Events* extension is intended to facilitate events through AWS EventBridge, passing data from and to WordPress/WooCommerce ... but you may find other uses.

1.  A *webhook delivery URL* is created to be used by WooCommerce to send data (order, product, or coupon) as a file to an AWS S3 bucket.

2.  An *EventBridge Target URL* is created to accepts data from AWS EventBridge derived from the file saved to the S3 bucket.

These 2 features may be used by the same WordPress installation (though I'm not sure why) or by different, even several, installations to route WooCommerce data to other destinations.

For example:
<pre>
Site1 \                                                      / EventBridge Target -> Site5
Site2 -> - WC Webhook Delivery -> [ Site4 ] -> - S3 Bucket ->  EventBridge Target -> Site6
Site3 /                                                      \ EventBridge Target -> Site7
</pre>

One or many WooCommerce sites can use the *WebHook Delivery URL* of another site to send orders through that site and then on to an S3 bucket as individual files.

EventBridge can be configured to deliver S3 files to one (or many) WordPress sites using the *EventBridge Target URL*.

This extension creates the APIs and formats the data to be sent to or received from AWS. To process data beyond what this extension does, you may use any of these actions:
```php
/**
 * action <pluginname>_eventbridge_<object|order|product|coupon>
 * @param object $file - S3 file object ($file->get('Body') to get contents)
 * @param array  $meta - EventBridge API metadata
 * @param string $type - object type (order|product|coupon)
 * @param string $name - object file name
 */
add_action( "eacDoojigger_eventbridge_object",  'my_eventbridge_action', 10, 4 );
add_action( "eacDoojigger_eventbridge_order",   'my_eventbridge_action', 10, 4 );
add_action( "eacDoojigger_eventbridge_product", 'my_eventbridge_action', 10, 4 );
add_action( "eacDoojigger_eventbridge_coupon",  'my_eventbridge_action', 10, 4 );

function my_EventBridge_action($file, $payload, $type, $fileName) {
    $data = $file->get('Body');
}
```

__WooCommerce Setup__

WooCommerce Webhooks can be added for Orders, Products, or Coupons. When creating a new webhook (WooCommerce -> Settings -> Advanced -> Webhooks) use the `Webhook Delivery URL` and `Webhook Secret` provided by this extension.

__S3 Setup__

This extension, by default, creates a new S3 bucket named `wc-webhook-<your-site-name>`. To override this or use an existing bucket...
```php
add_action( "eacDoojigger_eventbridge_bucketname", function($bucketName) {
    return 'my-s3-bucket';
} );
```

S3 bucket filenames are `wc_<order|product|coupon>_##.json`, e.g. `wc_order_300.json`. To override the file name used...
```php
add_action( "eacDoojigger_eventbridge_filename", function($fileName, $objectType, $objectId) {
    return 'my_s3_' . $objectType . '_' . $objectId . '.json';
},10,3 );
```

When changing the bucket name and/or file name, the *Event pattern* shown below will also have to be changed.

__EventBridge Setup__

There are several steps and configurations needed to get EventBridge working properly. Below are the key components needed for proper configuration, other options may be set to your preferences/needs.

In AWS EventBridge:

1.  EventBridge -> Connections -> Create Connection

+   API type: *Public*
+   Authorization type: *Basic*
+   Username:  a WordPress user
+   Password:  the application password for the user

alternatively

+   Authorization type: *API Key*
+   API key name:  *X-RestAPI-Token*
+   Value:  the *Webhook Secret* provided by this extension

2.  EventBridge -> API Destinations -> Create API destination

+   API destination endpoint: The *EventBridge Target* URL provided by this extension.
+   HTTP method: *POST* or *PUT*
+   Connection type: *Use an existing connection* (select the connection created in step 1)

3.  EventBridge -> Rules -> Create rule (you may create 1 rule for all objects or a single rule for each object type)

+   Event bus: *default*
+   Rule type: *Rule with an event pattern*
+   Events, Event source: *Other*
+   Event pattern, Creation method: *Custom pattern (JSON editor)*
+   Event pattern: (defines the beginning part, or prefix, of the bucket name and file names)
```json
{
    "detail": {
        "bucket": {
            "name": [{
                "prefix": "wc-webhook-"
            }]
        },
        "object": {
            "key": [{
                "prefix": "wc_order_"
            }]
        }
    }
}
```
+   Target 1, Target types: *EventBridge API destination*
+   API destination: *Use an existing API destination* (select the API destination created in step 2)

You can add other targets, such as CloudWatch for logging, other WordPress sites using this extension, or other services that can ingest this data. This extension provides logging (debug level) to expose detailed data structures.

See also: [How do I create and troubleshoot an Amazon EventBridge rule that triggers on S3 objects or operations?](https://repost.aws/knowledge-center/eventbridge-rule-monitors-s3) on AWS re:Post.

= Using AWS [CloudFront](https://aws.amazon.com/cloudfront/) or [Simple Email Service](https://aws.amazon.com/ses/) =

+   [{eac}SimpleCDN](https://eacDoojigger.earthasylum.com/eacsimplecdn/)
An {eac}Doojigger extension to enable the use of Content Delivery Network assets on your WordPress site, significantly decreasing your page load times and improving the user experience.

+   [{eac}SimpleSMTP](https://eacDoojigger.earthasylum.com/eacsimplesmtp/)
An {eac}Doojigger extension to configure WordPress wp_mail and phpmailer to use your SMTP (outgoing) mail server when sending email.


== Installation ==

**{eac}SimpleAWS** is an extension plugin to and requires installation and registration of [{eac}Doojigger](https://eacDoojigger.earthasylum.com/).

= Automatic Plugin Installation =

This plugin is available from the [WordPress Plugin Repository](https://wordpress.org/plugins/search/earthasylum/) and can be installed from the WordPress Dashboard » *Plugins* » *Add New* page. Search for 'EarthAsylum', click the plugin's [Install] button and, once installed, click [Activate].

See [Managing Plugins -> Automatic Plugin Installation](https://wordpress.org/support/article/managing-plugins/#automatic-plugin-installation-1)

= Upload via WordPress Dashboard =

Installation of this plugin can be managed from the WordPress Dashboard » *Plugins* » *Add New* page. Click the [Upload Plugin] button, then select the eacsimpleaws.zip file from your computer.

See [Managing Plugins -> Upload via WordPress Admin](https://wordpress.org/support/article/managing-plugins/#upload-via-wordpress-admin)

= Manual Plugin Installation =

You can install the plugin manually by extracting the eacsimpleaws.zip file and uploading the 'eacsimpleaws' folder to the 'wp-content/plugins' folder on your WordPress server.

See [Managing Plugins -> Manual Plugin Installation](https://wordpress.org/support/article/managing-plugins/#manual-plugin-installation-1)

= Settings =

Once installed and activated options for this extension will show in the 'AWS' tab of {eac}Doojigger settings.


== Screenshots ==

1. Simple AWS
![{eac}SimpleAWS Extension](https://ps.w.org/eacsimpleaws/assets/screenshot-1.png)

2. Simple AWS Help
![{eac}SimpleAWS Help](https://ps.w.org/eacsimpleaws/assets/screenshot-2.png)


== Other Notes ==

= Additional Information =

{eac}SimpleAWS is an extension plugin to and requires installation and registration of [{eac}Doojigger](https://eacDoojigger.earthasylum.com/).

See Also:

+   [{eac}SimpleCDN](https://eacDoojigger.earthasylum.com/eacsimplecdn/)
An {eac}Doojigger extension to enable the use of Content Delivery Network assets on your WordPress site, significantly decreasing your page load times and improving the user experience.

+   [{eac}SimpleSMTP](https://eacDoojigger.earthasylum.com/eacsimplesmtp/)
An {eac}Doojigger extension to configure WordPress wp_mail and phpmailer to use your SMTP (outgoing) mail server when sending email.


== Copyright ==

= Copyright © 2023-2025, EarthAsylum Consulting, distributed under the terms of the GNU GPL. =

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should receive a copy of the GNU General Public License along with this program. If not, see [https://www.gnu.org/licenses/](https://www.gnu.org/licenses/).


== Changelog ==

= Version 1.1.1 – Jul 30, 2025 =

+   Updated AWS PHP SDK to version 3.351.10

= Version 1.1.0 – Apr 29, 2025 =

+   New `Simple AWS S3 Events` extension.
    +   Webhook for WooCommerce; REST endpoint for AWS EventBridge.
+   Moved from `General` to `AWS` tab.

= Version 1.0.3 – Apr 19, 2025 =

+   Updated AWS PHP SDK to version 3.342.28
+   Compatible with WordPress 6.8.
+   Prevent `_load_textdomain_just_in_time was called incorrectly` notice from WordPress.
    +   All extensions - via eacDoojigger 3.1.
    +   Modified extension registration in constructor.

= Version 1.0.2 – April 10, 2024 =

+   Added notice if activated without {eac}Doojigger.
+   Updated AWS PHP SDK to version 3.304.1

= Version 1.0.1 – September 9, 2023 =

+   Updated include file to prevent direct access.
+   Updated AWS PHP SDK to version 3.281.3

= Version 1.0.0 – June 3, 2023 =

+   Initial release.
