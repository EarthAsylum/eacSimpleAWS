<?php
/**
 * Extension: Simple_AWS_extension - enable Amazon Web Services (AWS)
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version 	25.0429.1
 *
 * included for admin_options_help() method
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

ob_start();
?>
<P>{eac}SimpleAWS includes and enables use of the Amazon Web Services (AWS) PHP Software Development Kit (SDK).
Once enabled, AWS services are easily accessable from other plugins, extensions and custom functions.</p>
<p>From here you can enter your AWS Region and your IAM account credentials to access AWS programmatically.<p>

<details open><summary>Please review:</summary>
<ul>
<li><a href="https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html" target="_blank">Policies and permissions in IAM</a>
<li><a href="https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html" target="_blank">Managing access keys for IAM users</a>
<ul>
</details>

<details><summary>Available Methods:</summary>
<ul>
	<li><code>getAwsRegion()</code> returns your selected region
	<li><code>getAwsAccessKey()</code> returns your access key
	<li><code>getAwsAccessSecret()</code> returns your access secret
	<li><code>getAwsCredentials()</code> returns a 'credentials' array with your key and secret
	<li><code>getAwsClientParams()</code> returns an AWS client instantiation array
	<li><code>getAwsEndpoints()</code> returns a (large) array of all AWS endpoint parameters
	<li><code>getAwsRegions()</code> returns an array of all regions (name=>description)
	<li><code>setAwsVersion()</code> override default ('latest') version
	<li><code>setAwsRegion()</code> override set region
	<li><code>setAwsEndPoint()</code> override default endpoint
</li>
</details>

<details><summary>Available Filters:</summary>
<ul>
	<li><code>SimpleAWS_version</code> returns the AWS version string
	<li><code>SimpleAWS_region</code> returns your selected region
	<li><code>SimpleAWS_access_key</code> returns your access key
	<li><code>SimpleAWS_access_secret</code> returns your access secret
	<li><code>SimpleAWS_credentials</code> returns a 'credentials' array with your key and secret
	<li><code>SimpleAWS_client_params</code> returns an AWS client instantiation array
	<li><code>SimpleAWS_endpoints</code> returns a (large) array of all AWS endpoint parameters
	<li><code>SimpleAWS_regions</code> returns an array of all regions (name=>description)
</li>
</details>
<?php
$content = ob_get_clean();

$this->addPluginHelpTab(['Simple AWS',self::TAB_NAME],$content,['Amazon Web Services','open']);

ob_start();
?>
<P>The <em>Simple AWS S3 Events</em> extension is intended to facilitate events through AWS EventBridge,
passing data from and to WordPress/WooCommerce.
<ul>
	<li>A "webhook delivery URL" is created to be used by WooCommerce to send data (order, product, or coupon) as a file to an AWS S3 bucket.
	<li>An "EventBridge Target URL" is created to accepts data from AWS EventBridge derived from the file saved to the S3 bucket.
</ul>

<p>These 2 features may be used by the same WordPress installation (though I'm not sure why) or by different,
even several, installations to route WooCommerce data to other destinations.</p>

<p>One or many WooCommerce sites can use the "WebHook Delivery URL" of another site to
send orders through that site and then on to an S3 bucket as individual files.</p>

<p>EventBridge can be configured to deliver S3 files to one (or many) WordPress sites using the "EventBridge Target URL".</p>
<?php
$content = ob_get_clean();

$this->addPluginHelpTab(['Simple AWS',self::TAB_NAME],$content,['Simple AWS S3 Events']);

$this->addPluginSidebarLink(
	"<span class='dashicons dashicons-amazon'></span>{eac}SimpleAWS",
	"https://eacdoojigger.earthasylum.com/eacsimpleaws/",
	"{eac}SimpleAWS Extension Plugin"
);
