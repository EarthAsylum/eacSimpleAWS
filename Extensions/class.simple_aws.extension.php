<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\Simple_AWS_extension', false) )
{
	/**
	 * Extension: Simple_AWS_extension - enable Amazon Web Services (AWS)
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see			https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class Simple_AWS_extension extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION			= '25.0419.1';

		/**
		 * @var string default verion
		 */
		private $aws_version	= 'latest';

		/**
		 * @var string override region
		 */
		private $aws_region		= null;

		/**
		 * @var string override endpoint
		 */
		private $aws_endpoint	= null;


		/**
		 * constructor method
		 *
		 * @param	object	$plugin main plugin object
		 * @return	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::ALLOW_ALL|self::DEFAULT_DISABLED);

			$this->registerExtension( $this->className );
			// Register plugin options when needed
			$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
			// Add contextual help
			$this->add_action( 'options_settings_help', array($this, 'admin_options_help') );
		}


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			/* register this extension with group name on default tab, and settings fields */
			$this->registerExtensionOptions( $this->className,
				[
					'aws_region'		=> array(
											'type'		=> 'select',
											'label'		=> 'AWS Region',
											'default'	=> 'us-east-1',
											'options'	=> array_flip($this->getAwsRegions()),
											'info'		=> 'Your Amazon Web Services Region',
										),
					'aws_access_key'	=> array(
											'type'		=> 'text',
											'label'		=> 'AWS Access Key',
											'info'		=> 'Your Amazon Web Services Access Key <small>(encrypted when stored)</small>',
											'encrypt'	=>	true,
										),
					'aws_access_secret' => array(
											'type'		=> 'password',
											'label'		=> 'AWS Access Secret',
											'info'		=> 'Your Amazon Web Services Access Secret <small>(encrypted when stored)</small>',
											'encrypt'	=>	true,
										),
				]
			);
		}


		/**
		 * Add help tab on admin page
		 *
		 * @return	void
		 */
		public function admin_options_help()
		{
			if (!$this->plugin->isSettingsPage('General')) return;

			include 'includes/simple_aws.help.php';
		}


		/**
		 * initialize method - called from main plugin
		 *
		 * @return	void
		 */
		public function initialize()
		{
			if ( ! parent::initialize() ) return; // disabled
			require_once dirname(__DIR__).'/vendor/aws/aws-autoloader.php';
		}


		/**
		 * Add filters and actions - called from main plugin
		 *
		 * @return	void
		 */
		public function addActionsAndFilters()
		{
			add_filter('SimpleAWS_version', 		[$this,'getAwsVersion'],10,0);
			add_filter('SimpleAWS_region', 			[$this,'getAwsRegion'],10,0);
			add_filter('SimpleAWS_access_key', 		[$this,'getAwsAccessKey'],10,0);
			add_filter('SimpleAWS_access_secret',	[$this,'getAwsAccessSecret'],10,0);
			add_filter('SimpleAWS_credentials', 	[$this,'getAwsCredentials'],10,0);
			add_filter('SimpleAWS_client_params',	[$this,'getAwsClientParams'],10,0);

			add_filter('SimpleAWS_endpoints', 		[$this, 'getAwsEndpoints'],10,1);
			add_filter('SimpleAWS_regions',			[$this, 'getAwsRegions'],10,0);
		}


		/**
		 * Get version
		 *
		 * @param string $version
		 * @return	string
		 */
		public function getAwsVersion()
		{
			return $this->aws_version;
		}


		/**
		 * Set (override) version
		 *
		 * @param string $version
		 * @return	string
		 */
		public function setAwsVersion(string $version='latest')
		{
			return $this->aws_version = $version;
		}


		/**
		 * Get selected AWS Region
		 *
		 * @return	string|bool (false)
		 */
		public function getAwsRegion()
		{
			return $this->aws_region ?: $this->get_option('aws_region',false);
		}


		/**
		 * Set (override) selected AWS Region
		 *
		 * @param string|null $region
		 * @return	string
		 */
		public function setAwsRegion(?string $region=null)
		{
			return $this->aws_region = $region;
		}


		/**
		 * Get AWS Access Key
		 *
		 * @return	string|bool (false)
		 */
		public function getAwsAccessKey()
		{
			return $this->get_option_decrypt('aws_access_key',false);
		}


		/**
		 * Get AWS Access Secret
		 *
		 * @return	string|bool (false)
		 */
		public function getAwsAccessSecret()
		{
			return $this->get_option_decrypt('aws_access_secret',false);
		}


		/**
		 * Get AWS Credentials array
		 *
		 * @return	array|bool (false)
		 */
		public function getAwsCredentials()
		{
			if (! ($awsKey 		= $this->getAwsAccessKey()) ) return false;
			if (! ($awsSecret 	= $this->getAwsAccessSecret()) ) return false;

			return [
				'key'		=> $awsKey,
				'secret'	=> $awsSecret
			];
		}


		/**
		 * Get AWS Client Parameters array
		 *
		 * @return	array|bool (false)
		 */
		public function getAwsClientParams()
		{
			if (! ($awsRegion 		= $this->getAwsRegion()) ) return false;
			if (! ($awsCredentials 	= $this->getAwsCredentials()) ) return false;

			$return = [
				'version'	  => $this->aws_version,
				'region'	  => $awsRegion,
				'credentials' => $awsCredentials,
			];
			if ($this->aws_endpoint)
			{
				$return['endpoint'] = $this->aws_endpoint;
			}
			return $return;
		}


		/**
		 * Set (override) AWS EndPoint
		 *
		 * @example https://$account_id.r2.cloudflarestorage.com
		 * @param string|null $endpoint
		 * @return	string
		 */
		public function setAwsEndPoint(?string $endpoint=null)
		{
			return $this->aws_endpoint = $endpoint;
		}


		/**
		 * Get AWS endpoints
		 *
		 * @param 	string $key key within partition 0
		 * @return	mixed
		 */
		public function getAwsEndpoints($key=null)
		{
			static $endpoints = null;
			if (empty($endpoints))
			{
				$endpoints = require_once dirname(__DIR__).'/vendor/aws/Aws/data/endpoints.json.php';
			}
			return ($key) ? $endpoints['partitions'][0][$key] ?? [] : $endpoints;
		}


		/**
		 * Get all AWS regions
		 *
		 * @return	array
		 */
		public function getAwsRegions()
		{
			$regions = $this->getAwsEndpoints('regions');
			$result = array();
			foreach ($regions as $name => $value)
			{
				$result[$name] = $value['description'];
			}
			return $result;
		}
	}
}
/**
 * return a new instance of this class
 */
if (isset($this)) return new Simple_AWS_extension($this);
?>
