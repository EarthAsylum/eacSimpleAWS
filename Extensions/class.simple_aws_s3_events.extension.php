<?php
namespace EarthAsylumConsulting\Extensions;

/**
 * Extension: Webhook for AWS S3 and EventBridge - Requires/uses {eac}SimpleAWS.
 *
 * Adds 'AWS Webhooks' for S3 - sends data as json file to AWS S3 bucket.
 * 		Add a new webhook in WooCommerce -> Settings -> Advanced -> Webhooks
 * 		Use the generated Delivery URL and Webhook Secret in your webhook.
 *
 * Creates AWS S3 bucket "wc-webhook-<blog-name>"
 *		filter "eacDoojigger_eventbridge_bucketname" to change.
 *
 * Provides a target rest api for AWS EventBridge
 * 		/wp-json/eac/eventbridge/v1/s3-event
 *		Actions for received order|product|coupon file.
 * 			"eacDoojigger_eventbridge_object"
 * 			"eacDoojigger_eventbridge_<order|product|coupon>"
 *
 * Drop this into
 * 		/wp-content/themes/{your-theme}/eacDoojigger/Doolollys
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Extensions
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
 * @link		https://eacDoojigger.earthasylum.com/
 *
 * @see 		https://repost.aws/knowledge-center/eventbridge-rule-monitors-s3
 * @see 		https://aws.amazon.com/blogs/aws/new-use-amazon-s3-event-notifications-with-amazon-eventbridge/
 */

if (! class_exists(__NAMESPACE__.'\Simple_AWS_S3_events', false) )
{
	class Simple_AWS_S3_events extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION			= '25.0429.1';

		/**
		 * @var string extension tab name
		 */
		const TAB_NAME 			= 'AWS';

		/**
		 * @var string|array|bool to set (or disable) default group display/switch
		 */
		const ENABLE_OPTION		= "<abbr title='Adds a Webhook endpoint for WooCommerce to send data to an AWS S3 file; ".
											   "Adds a REST API endpoint for AWS EventBridge to send data from the S3 file.'".
											   ">Simple AWS S3 Events</abbr>";


		/**
		 * @var string build rest route
		 */
		const API_ROUTE			= 'eac/eventbridge/v1';


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::ALLOW_ADMIN | self::ALLOW_CRON | self::DEFAULT_DISABLED);

			add_action('admin_init', function()
			{
				$this->registerExtension( $this->className );
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
			},11);

			// register api routes
			add_action( 'rest_api_init', 					array($this, 'register_api_routes') );
		}


		/**
		 * initialize method - called from main plugin
		 *
		 * @return 	void
		 */
		public function initialize()
		{
			// requires {eac}SimpleAWS
			if (! $this->isEnabled('Simple_AWS_extension')) return $this->isEnabled(false);
			return parent::initialize();
		}


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			$this->registerExtensionOptions( $this->className,
				[
					'_eventbridge_webhook_url' 	=> array(
						'type'		=> 	'disabled',
						'label'		=> 	'Webhook Delivery URL',
						'default'	=>	home_url("/wp-json/".self::API_ROUTE."/wc-webhook"),
						'info'		=>	'The WC webhook end-point URL.<br>'.
										'WooCommerce &rarr; Settings &rarr; Advanced &rarr; Webhooks.'
					),
					'eventbridge_webhook_key'	=> array(
						'type'		=> 	'disabled',
						'label'		=> 	'Webhook Secret',
						'default'	=>	hash('md5', uniqid(), false),
						'info'		=>	'Used to authenticate webhook requests and/or the EventBridge API request.',
					),
					'_eventbridge_target_url' 	=> array(
						'type'		=> 	'disabled',
						'label'		=> 	'EventBridge Target',
						'default'	=>	home_url("/wp-json/".self::API_ROUTE."/s3-event"),
						'info'		=>	'The EventBridge target end-point URL.<br>'.
										'(AWS) EventBridge &rarr; Integrations &rarr; API Destinations &rarr; endpoint.'
					),
				]
			);
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 * @return void
		 */
		public function addActionsAndFilters()
		{
			/**
			 * action <pluginname>_eventbridge_<object|order|product|coupon>
			 * @param object $file - S3 file object ($file->get('Body') to get contents)
			 * @param array  $meta - EventBridge API metadata
			 * @param string $type - object type (order|product|coupon)
			 * @param string $name - object file name
			 */
			$this->add_action( 'eventbridge_object', function($file,$meta,$type,$name)
				{
					$this->logDebug($meta,current_action()." - {$name} {$type} meta");
					$this->logDebug($file,current_action()." - {$name} {$type} file");
					$body = $file->get('Body');
					$this->logDebug($body,current_action()." - {$name} {$type} body");
				},10,4
			);
		}


		/**
		 * Register WP REST api
		 *
		 * @return void
		 */
		public function register_api_routes($restServer)
		{
			// WooCommerce Webhooks
			register_rest_route( self::API_ROUTE, '/wc-webhook', array(
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 's3_webhook' ),
						'permission_callback' => array( $this, 's3_webhook_auth' ),
					),
			));

			// AWS EventBridge target
			register_rest_route( self::API_ROUTE, '/s3-event', array(
					array(
						'methods'             => 'POST,PUT',
						'callback'            => array( $this, 's3_event' ),
						'permission_callback' => array( $this, 's3_event_auth' ),
					),
			));
		}


		/*
		 *
		 * WooCommerce Webhooks - send data as file to AWS S3 bucket
		 *
		 */


		/**
		 * WC Webhooks authentication
		 *
		 * @param 	object	$rest - WP_REST_Request Request object.
		 * @return 	bool
		 */
		public function s3_webhook_auth($rest)
		{
			if ( ($authKey = $rest->get_header( 'x-wc-webhook-signature' )) )
			{
				$hash 	= $this->get_option('eventbridge_webhook_key');
				$hash 	= base64_encode(hash_hmac('sha256', $rest->get_body(), $hash, true));

				if ($hash == $authKey)
				{
					// allow CORS origin when authenticated
					$origin = parse_url($rest->get_header( 'x-wc-webhook-source' ));
					$origin = $origin['scheme'].'://'.$origin['host'];
					add_filter( 'http_origin', function() use ($origin) {
						return $origin;
					});
					add_filter( 'allowed_http_origins', function ($allowed) use ($origin) {
						$allowed[] = $origin;
						return $allowed;
					});
					return true;
				}
			}
			else if (isset($_POST['webhook_id']))
			{
				// test ping from woo when the webhook is first created
				http_response_code(200);
				die();
			}

			http_response_code(401);
			return false;
		}


		/**
		 * Send WC webhook data to S3 bucket
		 *
		 * @param 	object	$rest - WP_REST_Request Request object.
		 * @return 	void
		 */
		public function s3_webhook($rest)
		{
			$payload = ($rest->is_json_content_type())
				? $rest->get_json_params()
				: $rest->get_params();

			if ($s3client = $this->getS3Client())
			{
				$bucketName = $this->getS3BucketName();

				// create the bucket if not exists
				try {
					$result = $s3client->createBucket([
						'Bucket' 	=> $bucketName,
					]);
					$this->logDebug($result,"Created bucket: {$bucketName}");
				} catch (\AwsException $exception) {
					$this->logError($exception,"AWS createBucket {$bucketName} Error");
				}
				// the bucket must be set for events:
				// AWS-S3->Buckets->{bucket}->Properties ... Amazon EventBridge ... on
				try {
					$s3client->putBucketNotificationConfiguration([
						'Bucket' 	=> $bucketName,
						'NotificationConfiguration' => [
							'EventBridgeConfiguration' => [],
						]
					]);
				} catch (\AwsException $exception) {
					$this->logError($exception,"AWS putBucketNotification {$bucketName} Error");
				}

				$metadata = [
					'source'		=> $rest->get_header('x-wc-webhook-source'),		// https://dev.earthasylum.net/
					'delivery_id'	=> $rest->get_header('x-wc-webhook-delivery_id'),	// 2fcc8dcf827447b348a35ee733a65a38
					'topic'			=> $rest->get_header('x-wc-webhook-topic'),			// order.updated
					'resource'		=> $rest->get_header('x-wc-webhook-resource'),		// order
					'event'			=> $rest->get_header('x-wc-webhook-event'),			// updated
				];
				$resource = $this->toKeyString($metadata['resource']);					// safety check

				// file name wc_order_<id> | wc_product_<id>
				$fileName= "wc_".$resource.'_'.$payload['id'].'.json';

				/**
				 * filter <pluginName>_eventbridge_filename - override default file name
				 * @param string fileName
				 * @param string object type (order|product|coupon)
				 * @param int object id
				 */
				$fileName = apply_filters( 'eventbridge_filename', $fileName, $resource, $payload['id'] );

				// upload the file to the (new) bucket
				// the file content includes the webhook metadata and payload
				try {
					$result = $s3client->putObject([
						'Bucket' 	=> $bucketName,
						'Key' 		=> $fileName,
						'Metadata' 	=> $metadata,
						'Body' 		=> json_encode(['metadata'=>$metadata,$resource=>$payload],JSON_PRETTY_PRINT),
					]);
					$this->logDebug($result,"Uploaded {$fileName} to {$bucketName}");
				} catch (\AwsException $exception) {
					$this->logError($exception,"AWS putObject {$fileName} Error");
				}
			}
		}


		/*
		 *
		 * EventBridge REST API - receive data from AWS S3 bucket via EventBridge
		 *
		 */


		/**
		 * EventBridge authentication - basic auth or X-RestAPI-Token
		 *
		 * @param 	object	$rest - WP_REST_Request Request object.
		 * @return 	bool
		 */
		public function s3_event_auth($rest)
		{
			// HTTP Authorization requires in .htaccess...
			//		RewriteEngine on
			//		RewriteCond %{HTTP:Authorization} ^(.*)
			//		RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]

			// Authorization: Basic base64_encode(usernamme:password)
			if ($username = $rest->get_header('Authorization'))
			{
				list ($authType,$username) = explode(' ',$username);
				switch (strtolower($authType))
				{
					case 'basic':
						$username = base64_decode($username);
						list($username,$password) = explode(':',$username.':');
						break;
					default:
						$username = $password = false;
				}
				$user = wp_authenticate( $username, $password );
				if (is_wp_error($user))
				{
					http_response_code(401);
					return false;
				}
			}
			// header X-RestAPI-Token: eventbridge_webhook_key
			else if ($username = $rest->get_header('X-RestAPI-Token'))
			{
				if ($username != $this->get_option('eventbridge_webhook_key'))
				{
					http_response_code(401);
					return false;
				}
			}
			else
			{
				http_response_code(401);
				return false;
			}

			// allow CORS origin when authenticated
			$origin = 'https://'.$this->varServer('User-Agent');
			add_filter( 'http_origin', function() use ($origin) {
				return $origin;
			});
			add_filter( 'allowed_http_origins', function ($allowed) use ($origin) {
				$allowed[] = $origin;
				return $allowed;
			});

			return true;
		}


		/**
		 * Process S3 event data
		 *
		 * @param 	object	$rest - WP_REST_Request Request object.
		 * @return 	void
		 */
		public function s3_event($rest)
		{
			$payload = ($rest->is_json_content_type())
				? $rest->get_json_params()
				: $rest->get_params();

			$bucketName = $this->getS3BucketName();

			$fileName = (isset($payload['detail'], $payload['detail']['object']))
				? $payload['detail']['object']['key']
				: false;

			if ($fileName && $s3client = $this->getS3Client())
			{
				try {
					$file = $s3client->getObject([
						'Bucket' 	=> $bucketName,
						'Key' 		=> $fileName,
					]);
					$type = $file['Metadata']['resource'] ?? 'unknown';
					/**
					 * action <pluginname>_eventbridge_<object|order|product|coupon>
					 * @param object S3 file object ($file->get('Body') to get contents)
					 * @param array EventBridge API metadata
					 * @param string object type (order|product|coupon)
					 * @param string object file name
					 */
					 $this->do_action( "eventbridge_{$type}",$file, $payload, $type, $fileName );
					 $this->do_action( "eventbridge_object", $file, $payload, $type, $fileName );
				} catch (\AwsException $exception) {
					$this->logError($exception,"AWS getObject {$fileName} Error");
				}
			}
		}


		/*
		 *
		 * S3 client object / bucket name
		 *
		 */


		/**
		 * get AWS S3 client object
		 *
		 * @return	object|bool
		 */
		private function getS3Client()
		{
			if (! $aws = $this->plugin->getExtension('Simple_AWS') )
			{
				$this->logError(null,"S3 support requires the activation of the {eac}SimpleAWS extension.");
				return false;
			}

			if (! $awsParams = $aws->getAwsClientParams() )
			{
				$this->logError(null,"AWS Error: You must provide your AWS credentials in the Amazon Web Services settings");
				return false;
			}

			try
			{
				$s3client = new \Aws\S3\S3Client($awsParams);
			}
			catch (\AwsException $exception)
			{
				$this->logError($exception,"AWS S3Client Error");
				return false;
			}
			return $s3client;
		}


		/**
		 * set AWS S3 bucket name
		 *
		 * @return string bucket name
		 */
		private function getS3BucketName()
		{
			$bucketName	= \get_bloginfo('name');
			$bucketName = $this->toKeyString("wc-webhook-{$bucketName}");

			/**
			 * filter <pluginName>_eventbridge_bucketname - override default bucket name
			 * @param string bucketName
			 */
			$bucketName = apply_filters( 'eventbridge_bucketname', $bucketName );

			return $bucketName;
		}
	}
}
/**
 * return a new instance of this class
 */
return new Simple_AWS_S3_events($this);
?>
