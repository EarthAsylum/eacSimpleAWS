=== {eac}Doojigger Simple AWS Extension for WordPress ===
Plugin URI:         https://eacdoojigger.earthasylum.com/eacsimpleaws/
Author:             [EarthAsylum Consulting](https://www.earthasylum.com)
Stable tag:         1.0.1
Last Updated:       09-Sep-2023
Requires at least:  5.5.0
Tested up to:       6.4
Requires PHP:       7.2
Requires EAC:       2.0
Contributors:       kevinburkholder
License:            GPLv3 or later
License URI:        https://www.gnu.org/licenses/gpl.html
Tags:               aws, amazon web services, AWS PHP SDK, {eac}Doojigger
WordPress URI:		https://wordpress.org/plugins/eacsimpleaws
GitHub URI:			https://github.com/KBurkholder/eacSimplaAWS

{eac}SimpleAWS includes and enables use of the Amazon Web Services (AWS) PHP Software Development Kit (SDK).

== Description ==

Once enabled, AWS services are easily accessable from other plugins, extensions and custom functions.

From the settings page, you can enter your AWS Region and your IAM account credentials to access AWS programmatically.

Please review:
+	[Policies and permissions in IAM](https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html)
+	[Managing access keys for IAM users](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html)

= Available Methods: =

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

= Available Filters: =

`SimpleAWS_version` returns the AWS version string

`SimpleAWS_region` returns your selected region

`SimpleAWS_access_key` returns your access key

`SimpleAWS_access_secret` returns your access secret

`SimpleAWS_credentials` returns a 'credentials' array with your key and secret

`SimpleAWS_client_params` returns an AWS client instantiation array

`SimpleAWS_endpoints` returns a (large) array of all AWS endpoint parameters

`SimpleAWS_regions` returns an array of all regions (name=>description)

= Examples: =

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


== Installation ==

**{eac}Doojigger Simple AWS Extension** is an extension plugin to and requires installation and registration of [{eac}Doojigger](https://eacDoojigger.earthasylum.com/).

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

Once installed and activated options for this extension will show in the 'General' tab of {eac}Doojigger settings.


== Screenshots ==

1. Simple AWS
![{eac}SimpleAWS Extension](https://d2xk802d4616wu.cloudfront.net/eacsimpleaws/assets/screenshot-1.png)

2. Simple AWS Help
![{eac}SimpleAWS Help](https://d2xk802d4616wu.cloudfront.net/eacsimpleaws/assets/screenshot-2.png)


== Other Notes ==

= Additional Information =

+   {eac}SimpleAWS is an extension plugin to and requires installation and registration of [{eac}Doojigger](https://eacDoojigger.earthasylum.com/).


== Copyright ==

= Copyright © 2023, EarthAsylum Consulting, distributed under the terms of the GNU GPL. =

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should receive a copy of the GNU General Public License along with this program. If not, see [https://www.gnu.org/licenses/](https://www.gnu.org/licenses/).


== Changelog ==

= Version 1.0.1 – September 9, 2023 =

+   Updated include file to prevent direct access.
+	Updated AWS PHP SDK to version 3.281.3

= Version 1.0.0 – June 3, 2023 =

+   Initial release.
