<?php
namespace Hestia\WebApp\Installers\Wordpress;

use Hestia\System\Util;
use \Hestia\WebApp\Installers\BaseSetup as BaseSetup;

class WordpressSetup extends BaseSetup {

    protected $appInfo = [
        'name' => 'Wordpress',
        'group' => 'cms',
        'enabled' => true,
        'langs' => [
		'active'=>true,
		'default'=>'en',
		'iso_code'=>[
			'en'=>'https://wordpress.org/latest.tar.gz',
			'es'=>'https://es.wordpress.org/latest-es_ES.tar.gz',
			'da'=>'https://da.wordpress.org/latest-da_DK.tar.gz',
			'de'=>'https://de.wordpress.org/latest-de_DE.tar.gz',
			'cs'=>'https://cs.wordpress.org/latest-cs_CZ.tar.gz',
			'fr'=>'https://fr.wordpress.org/latest-fr_FR.tar.gz',
			'it'=>'https://it.wordpress.org/latest-it_IT.tar.gz',
			'ja'=>'https://ja.wordpress.org/latest-ja.tar.gz',
			'pt-br'=>'https://br.wordpress.org/latest-pt_BR.tar.gz',
			'pt'=>'https://pt.wordpress.org/latest-pt_PT.tar.gz',
			'ru'=>'https://ru.wordpress.org/latest-ru_RU.tar.gz',
			'tr'=>'https://tr.wordpress.org/latest-tr_TR.tar.gz',
			'uk'=>'https://uk.wordpress.org/latest-uk.tar.gz'
		]
	],
	'version' => 'latest',
        'thumbnail' => 'wp-thumb.png'
    ];

    protected $appname = 'wordpress';
    protected $config = [
        'form' => [
            //'protocol' => [ 
            //    'type' => 'select',
            //    'options' => ['http','https'],
            //],
            'install_directory' => ['type'=>'text', 'value'=>'', 'placeholder'=>'/'],
            'site_name' => ['type'=>'text', 'value'=>'WordPress Blog'],
            'wordpress_account_username' => ['value'=>'wpadmin'],
            'wordpress_account_email' => 'text',
            'wordpress_account_password' => 'password',
            ],
        'database' => true,
        'resources' => [
            'archive'  => [ 'src' => 'https://wordpress.org/latest.tar.gz' ],
        ],
        'server' => [
            'nginx' => [
                'template' => 'wordpress',
            ],
        ],
    ];

    public function install(array $options = null)
    {
        $this->setAppDirInstall($options['install_directory']);
        if(isset($options['language'])) {
		list($options['language'],$options['lang_name']) = explode(':',$options['language']);
	}
	parent::install($options);
        parent::setup($options);
        $this->appcontext->runUser('v-open-fs-file',[$this->getDocRoot("wp-config-sample.php")], $result);
	$file = explode(PHP_EOL,$result->text);
	foreach($file as $fileline) {
		if(preg_match("/DB_NAME/", $fileline)) {
			$distconfig.= "define('DB_NAME', '" . $this->appcontext->user() . "_" . $options['database_name'] ."');".PHP_EOL;
			continue;
		}
		if(preg_match("/DB_USER/", $fileline)) {
                        $distconfig.= "define('DB_USER', '" . $this->appcontext->user() . "_" . $options['database_user'] ."');".PHP_EOL;
                        continue;
                }
		if(preg_match("/DB_PASSWORD/", $fileline)) {
                        $distconfig.= "define('DB_PASSWORD', '" . $options['database_password'] ."');".PHP_EOL;
                        continue;
                }
		if(preg_match("/DB_CHARSET/", $fileline)) {
                        $distconfig.= "define('DB_CHARSET', 'utf8mb4');".PHP_EOL;
                        continue;
                }
		if(preg_match("/table_prefix/", $fileline)) {
                        $distconfig.= "$"."table_prefix = '".Util::generate_string(3,'no')."_"."';".PHP_EOL;
                        continue;
                }
		if(preg_match("/AUTH_KEY/", $fileline)) {
                        $distconfig.= "define('AUTH_KEY', '" . Util::generate_string(64) ."');".PHP_EOL;
                        continue;
                }
		if(preg_match("/SECURE_AUTH_KEY/", $fileline)) {
                        $distconfig.= "define('SECURE_AUTH_KEY', '" . Util::generate_string(64) ."');".PHP_EOL;
                        continue;
                }
		if(preg_match("/LOGGED_IN_KEY/", $fileline)) {
                        $distconfig.= "define('LOGGED_IN_KEY', '" . Util::generate_string(64) ."');".PHP_EOL;
                        continue;
                }
		if(preg_match("/NONCE_KEY/", $fileline)) {
                        $distconfig.= "define('NONCE_KEY', '" . Util::generate_string(64) ."');".PHP_EOL;
                        continue;
                }
		if(preg_match("/AUTH_SALT/", $fileline)) {
                        $distconfig.= "define('AUTH_SALT', '" . Util::generate_string(64) ."');".PHP_EOL;
                        continue;
                }
		if(preg_match("/SECURE_AUTH_SALT/", $fileline)) {
                        $distconfig.= "define('SECURE_AUTH_SALT', '" . Util::generate_string(64) ."');".PHP_EOL;
                        continue;
                }
		if(preg_match("/LOGGED_IN_SALT/", $fileline)) {
                        $distconfig.= "define('LOGGED_IN_SALT', '" . Util::generate_string(64) ."');".PHP_EOL;
                        continue;
                }
		if(preg_match("/NONCE_SALT/", $fileline)) {
                        $distconfig.= "define('NONCE_SALT', '" . Util::generate_string(64) ."');".PHP_EOL;
                        continue;
                }

		$distconfig.= $fileline.PHP_EOL;
	}
        $tmp_configpath = $this->saveTempFile($distconfig);

        if(!$this->appcontext->runUser('v-move-fs-file',[$tmp_configpath, $this->getDocRoot("wp-config.php")], $result)) {
            throw new \Exception("Error installing config file in: " . $tmp_configpath . " to:" . $this->getDocRoot("wp-config.php") . $result->text );
        }

        exec("/usr/bin/curl --location --post301 --insecure --resolve ".$this->domain.":80:".$this->appcontext->getWebDomainIp($this->domain)." "
            . escapeshellarg("http://".$this->domain."/".$options['install_directory']."/wp-admin/install.php?step=2")
            . " -d " . escapeshellarg(
                "weblog_title=" . rawurlencode($options['site_name'])
            . "&user_name="      . rawurlencode($options['wordpress_account_username'])
            . "&admin_password=" . rawurlencode($options['wordpress_account_password'])
            . "&admin_password2=". rawurlencode($options['wordpress_account_password'])
            . "&admin_email="    . rawurlencode($options['wordpress_account_email'])), $output, $return_var);

        return ($return_var === 0);
    }
}
