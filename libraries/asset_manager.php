<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if (!class_exists('CssMin'))
    require __DIR__.DIRECTORY_SEPARATOR.'CssMin.php';

if (!class_exists('\\JShrink\\Minifier'))
    require __DIR__.'/JShrink/Minifier.php';

/**
 * Interface iasset
 */
interface iasset
{
    /**
     * @param string $file
     * @param boolean $minify
     * @param array $observers
     * @return \asset
     */
    public static function asset_with_file_and_minify_and_observers($file, $minify, $observers);

    /**
     * @param string $file
     * @param array $logical_groups
     * @param boolean $minify
     * @param array $observers
     * @return \asset
     */
    public static function asset_with_file_and_logical_groups_and_minify_and_observers($file, $logical_groups, $minify, $observers);

    /**
     * @param string $param
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function __get($param);

    /**
     * @param string $group
     * @return bool
     */
    public function in_logical_group($group);

    /**
     * @param string $group
     * @return void
     */
    public function add_to_logical_group($group);

    /**
     * @param array $groups
     * @return void
     */
    public function add_to_logical_groups(array $groups);

    /**
     * @param string $group
     * @return void
     */
    public function remove_from_logical_group($group);

    /**
     * @param array $groups
     * @return void
     */
    public function remove_from_logical_groups(array $groups);
}

/**
 * Class ASSET_NOTIFY
 */
abstract class ASSET_NOTIFY
{
    const INITIALIZED = 0;
    const GROUP_ADDED = 1;
    const GROUP_REMOVED = 2;
}

// ---------------------------------------------------------------------------------------------------------------------

/**
 * Class asset
 *
 * @property string name
 * @property string minify_name
 * @property string file
 * @property string minify_file
 * @property string type
 * @property array logical_groups
 * @property int notify_status
 * @property bool minify
 * @property bool source_is_minified
 * @property \DateTime source_last_modified
 * @property \DateTime minify_last_modified
 */
class asset implements \iasset, \SplSubject
{
    /** @var string */
    public static $_asset_dir_full_path;

    /** @var string */
    public static $_asset_cache_dir_full_path;

    /** @var int */
    protected $_notify_status;

    /** @var string */
    protected $_name;

    /** @var string */
    protected $_minify_name;

    /** @var string */
    protected $_file;

    /** @var string */
    protected $_minify_file;

    /** @var string */
    protected $_type;

    /** @var array */
    protected $_logical_groups = array();

    /** @var bool */
    protected $_minify = true;

    /** @var bool */
    protected $_source_is_minified = false;

    /** @var \DateTime */
    protected $_source_last_modified;

    /** @var \DateTime */
    protected $_minify_last_modified;

    /** @var array */
    private $_observers = array();

    /**
     * Constructor
     *
     * @param string $file
     * @param string $name
     * @param array $logical_groups
     * @param boolean $minify
     * @param array $observers
     */
    protected function __construct($file, $name, $logical_groups, $minify, $observers)
    {
        $this->_file = $file;
        $this->_name = $name;
        $this->_logical_groups = $logical_groups;
        $this->_minify = $minify;
        $this->_observers = $observers;

        $this->_source_last_modified = \DateTime::createFromFormat('U', filemtime($file));
    }

    /**
     * @param string $file
     * @param boolean $minify
     * @param array $observers
     * @return \asset
     */
    public static function asset_with_file_and_minify_and_observers($file, $minify, $observers)
    {
        return static::asset_with_file_and_logical_groups_and_minify_and_observers($file, null, $minify, $observers);
    }

    /**
     * @param string $file
     * @param array $logical_groups
     * @param boolean $minify
     * @param array $observers
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @return \asset
     */
    public static function asset_with_file_and_logical_groups_and_minify_and_observers($file, $logical_groups, $minify, $observers)
    {
        if (!is_string($file))
            throw new \InvalidArgumentException('Argument 1 expected to be string, '.gettype($file).' seen.');
        if (($file = trim($file)) === '')
            throw new \InvalidArgumentException('Empty string passed for argument 1.');

        $realpath = realpath($file);

        if ($realpath === false)
            throw new \RuntimeException('File specified by argument 1 does not exist. Value: "'.$file.'".');
        if (!is_readable($realpath))
            throw new \RuntimeException('File specified by argument 1 is not readable.  Value: "'.$file.'".');

        $name = preg_replace(array('#[/\\\]+#', '#'.addslashes(self::$_asset_dir_full_path).'#i'), array(DIRECTORY_SEPARATOR, ''), $realpath);

        if (null === $logical_groups)
            $logical_groups = array($name);
        else if (!is_array($logical_groups))
            throw new \InvalidArgumentException('Argument 3 expected to be null or array, '.gettype($logical_groups).' seen.');

        if (!is_bool($minify))
            throw new \InvalidArgumentException('Argument 4 expected to be boolean, '.gettype($minify).' seen.');

        if (null === $observers)
            $observers = array();
        else if (!is_array($observers))
            throw new \InvalidArgumentException('Argument 5 expected to be null or array of objects implementing \\SplObserver.');

        /** @var \asset $asset */
        $asset = new static($realpath, $name, array_unique($logical_groups), $minify, $observers);

        $asset->initialize();

        return $asset;
    }

    /**
     * @param string $param
     * @return array|bool|DateTime|int|string
     * @throws OutOfBoundsException
     */
    public function __get($param)
    {
        switch($param)
        {
            case 'notify_status':
                return $this->_notify_status;

            case 'name':
                return $this->_name;

            case 'minify_name':
                return $this->_minify_name;

            case 'file':
                return $this->_file;

            case 'minify_file':
                return $this->_minify_file;

            case 'type':
                return $this->_type;

            case 'logical_groups':
                return $this->_logical_groups;

            case 'minify':
                return $this->_minify;

            case 'source_is_minified':
                return $this->_source_is_minified;

            case 'source_last_modified':
                return $this->_source_last_modified;

            case 'minify_last_modified':
                return $this->_minify_last_modified;

            default:
                throw new \OutOfBoundsException('Object does not contain public property with name "'.$param.'".');
        }
    }

    /**
     * Post-construct object initialization
     */
    protected function initialize()
    {
        $this->determine_type();
        $this->determine_minified_status();

        $this->_notify_status = ASSET_NOTIFY::INITIALIZED;
        $this->notify();
    }

    /**
     * @throws \RuntimeException
     */
    protected function determine_type()
    {
        $ext = strrchr($this->_file, '.');

        switch($ext)
        {
            case '.js':
                $this->_type = 'javascript';
                break;

            case '.css':
                $this->_type = 'stylesheet';
                break;

            default:
                throw new \RuntimeException('Asset with ext "'.$ext.'" is not a recognized type.  Recognized types: [.js, .css].');
        }
    }

    /**
     * Determine if a minified version of this asset already exists
     */
    protected function determine_minified_status()
    {
        $this->determine_source_is_minified();

        if ($this->_source_is_minified)
        {
            $this->_minify_name = $this->_name;
            $this->_minify_file = $this->_file;
            $this->_minify_last_modified = \DateTime::createFromFormat('U', filemtime($this->_file));
        }
        else
        {
            $this->determine_minified_name();
            if (file_exists($this->_minify_file))
                $this->_minify_last_modified = \DateTime::createFromFormat('U', filemtime($this->_minify_file));
            else
                $this->_minify_last_modified = null;
        }
    }

    /**
     * Determine the name to use for the minified version of this asset
     */
    protected function determine_minified_name()
    {
        switch($this->_type)
        {
            case 'javascript':
                $this->_minify_name = sha1($this->_name).'.min.js';
                break;

            case 'stylesheet':
                $this->_minify_name = sha1($this->_name).'.min.css';
                break;
        }

        $this->_minify_file = static::$_asset_cache_dir_full_path.$this->_minify_name;
    }

    /**
     * Test to see if source is already a minified asset
     *
     * TODO Improve minified source asset check
     */
    protected function determine_source_is_minified()
    {
        if (preg_match('/[\.\-_]min[\.\-_]/i', $this->_name))
            $this->_source_is_minified = true;
        else
            $this->_source_is_minified = false;
    }

    /**
     * @param string $group
     * @return bool
     */
    public function in_logical_group($group)
    {
        return in_array($group, $this->_logical_groups, true);
    }

    /**
     * @param string $group
     * @return void
     */
    public function add_to_logical_group($group)
    {
        if (!in_array($group, $this->_logical_groups, true))
        {
            $this->_logical_groups[] = $group;
            $this->_notify_status = ASSET_NOTIFY::GROUP_ADDED;
            $this->notify();
        }
    }

    /**
     * @param array $groups
     * @return void
     */
    public function add_to_logical_groups(array $groups)
    {
        $this->_logical_groups = $this->_logical_groups + $groups;

        $this->_notify_status = ASSET_NOTIFY::GROUP_ADDED;
        $this->notify();
    }

    /**
     * @param string $group
     * @return void
     */
    public function remove_from_logical_group($group)
    {
        $idx = array_search($group, $this->_logical_groups, true);
        if ($idx !== false)
        {
            unset($this->_logical_groups[$idx]);

            $this->_notify_status = ASSET_NOTIFY::GROUP_REMOVED;
            $this->notify();
        }
    }

    /**
     * @param array $groups
     * @return void
     */
    public function remove_from_logical_groups(array $groups)
    {
        $this->_logical_groups = array_diff($this->_logical_groups, $groups);

        $this->_notify_status = ASSET_NOTIFY::GROUP_REMOVED;
        $this->notify();
    }

    /**
     * @param string $filename
     * @return string
     */
    public static function clean_asset_filename($filename)
    {
        return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $filename);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->_name;
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Attach an SplObserver
     * @link http://php.net/manual/en/splsubject.attach.php
     *
     * @param \SplObserver $observer The SplObserver to attach.
     * @throws \RuntimeException
     * @return void
     */
    public function attach(\SplObserver $observer)
    {
        if (in_array($observer, $this->_observers, true))
            throw new \RuntimeException('Cannot add the same observer twice to this object');

        $this->_observers[] = $observer;
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Detach an observer
     * @link http://php.net/manual/en/splsubject.detach.php
     *
     * @param \SplObserver $observer The SplObserver to detach.
     * @throws \RuntimeException
     * @return void
     */
    public function detach(\SplObserver $observer)
    {
        $idx = array_search($observer, $this->_observers, true);

        if ($idx === false)
            throw new \RuntimeException('Argument 1 is not an observer of this object.');

        unset($this->_observers[$idx]);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Notify an observer
     * @link http://php.net/manual/en/splsubject.notify.php
     *
     * @return void
     */
    public function notify()
    {
        for ($i = 0, $count = count($this->_observers); $i < $count; $i++)
        {
            $this->_observers[$i]->update($this);
        }
    }
}

// ---------------------------------------------------------------------------------------------------------------------

/**
 * Class asset_manager
 *
 * @property string asset_dir_relative_path
 * @property string asset_dir_full_path
 * @property string asset_dir_uri
 * @property string asset_cache_dir_relative_path
 * @property string asset_cache_dir_full_path
 * @property array logical_groups
 * @property boolean minify
 */
class asset_manager implements \SplObserver
{
    /** @var array */
    protected $assets = array();

    /** @var string */
    protected $_asset_dir_relative_path;

    /** @var string */
    protected $_asset_dir_full_path;

    /** @var string */
    protected $_asset_cache_dir_relative_path;

    /** @var string */
    protected $_asset_cache_dir_full_path;

    /** @var string */
    protected $_asset_dir_uri;

    /** @var bool */
    protected $_minify = true;

    /** @var array */
    protected $_logical_groups = array();

    /** @var array */
    private $_queued_assets = array();

    /**
     * Constructor
     *
     * @param array $config
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct($config = array())
    {
        /** @var \MY_Controller|\CI_Controller $CI */
        $CI = &get_instance();

        if (is_array($config) && count($config) > 0)
        {
            log_message('debug', 'ci-asset-manager - Config loaded from array param.');
        }
        else if ($CI instanceof \CI_Controller && $CI->config->load('asset_manager', false, true))
        {
            $config = $CI->config->item('asset_manager');
            log_message('debug', 'ci-asset-manager - Config loaded from file.');
        }

        if (isset($config['asset_manager']))
            $config = $config['asset_manager'];

        if (isset($config['asset_dir_relative_path']))
        {
            log_message('debug', 'ci-asset-manager - Attempting to use "asset_dir_relative_path" from config array.');

            $path = realpath(FCPATH.$config['asset_dir_relative_path']);
            if ($path !== false)
            {
                $this->_asset_dir_full_path = $path.DIRECTORY_SEPARATOR;
                $this->_asset_dir_relative_path = trim($config['asset_dir_relative_path'], "/\\").DIRECTORY_SEPARATOR;
            }
            else
            {
                log_message(
                    'error',
                    'ci-asset-manager - Could not find specified directory ("'.$config['asset_dir_relative_path'].'") relative to FCPATH constant.');
                throw new \RuntimeException('Could not find specified directory ("'.$config['asset_dir_relative_path'].'") relative to FCPATH constant.');
            }
        }
        else
        {
            log_message(
                'debug',
                'ci-asset-manager - "asset_dir_relative_path" config parameter not found, attempting to use "'.FCPATH.'assets".');

            $path = realpath(FCPATH.'assets');
            if ($path)
            {
                $this->_asset_dir_full_path = $path.DIRECTORY_SEPARATOR;
                $this->_asset_dir_relative_path = 'assets'.DIRECTORY_SEPARATOR;
            }
        }

        if (!is_writable($this->_asset_dir_full_path))
        {
            log_message(
                'error',
                'ci-asset-manager - Specified asset path "'.$this->_asset_dir_full_path.'" is not writable.');
            throw new \RuntimeException('Specified asset path "'.$this->_asset_dir_full_path.'" is not writable.');
        }

        $this->_asset_dir_uri = rtrim(base_url(str_replace(DIRECTORY_SEPARATOR, '/', $this->_asset_dir_relative_path), " \t\n\r\0\x0B/")).'/';

        \asset::$_asset_dir_full_path = $this->_asset_dir_full_path;

        log_message('debug', 'ci-asset-manager - Relative asset dir path set to "'.$this->_asset_dir_relative_path.'".');
        log_message('debug', 'ci-asset-manager - Full asset dir path set to "'.$this->_asset_dir_full_path.'".');
        log_message('debug', 'ci-asset-manager - Asset URI set to "'.$this->_asset_dir_uri.'".');

        if (isset($config['asset_cache_dir_relative_path']))
        {
            log_message('debug', 'ci-asset-manager - Attempting to use "asset_cache_dir_relative_path" from config array.');

            $path = realpath(FCPATH.$config['asset_cache_dir_relative_path']);
            if ($path)
            {
                $this->_asset_cache_dir_full_path = $path.DIRECTORY_SEPARATOR;
                $this->_asset_cache_dir_relative_path = trim($config['asset_cache_dir_relative_path'], "/\\").DIRECTORY_SEPARATOR;
            }
            else
            {
                log_message(
                    'error',
                    'ci-asset-manager - Could not find specified directory ("'.$config['asset_cache_dir_relative_path'].'") relative to FCPATH constant.');
                throw new \RuntimeException('Could not find specified directory ("'.$config['asset_cache_dir_relative_path'].'") relative to FCPATH constant.');
            }
        }
        else
        {
            log_message(
                'debug',
                'ci-asset-manager - "asset_cache_dir_relative_path" config parameter not found, attempting to use "'.
                $this->_asset_dir_relative_path.'cache'.DIRECTORY_SEPARATOR.'".');

            $path = realpath($this->_asset_dir_full_path.'cache');
            if ($path)
            {
                $this->_asset_cache_dir_full_path = $path.DIRECTORY_SEPARATOR;
                $this->_asset_cache_dir_relative_path = $this->_asset_dir_relative_path.'cache'.DIRECTORY_SEPARATOR;
            }
            else if ((bool)@mkdir($this->_asset_dir_full_path.'cache'))
            {
                log_message(
                    'debug',
                    'ci-asset-manager - Successfully created cache directory under "'.$this->_asset_dir_relative_path.'".');

                $this->_asset_cache_dir_full_path = $this->_asset_dir_full_path.'cache'.DIRECTORY_SEPARATOR;
                $this->_asset_cache_dir_relative_path = $this->_asset_dir_relative_path.'cache'.DIRECTORY_SEPARATOR;
            }
            else
            {
                log_message(
                    'error',
                    'ci-asset-manager - Was not able to create cache directory under "'.$this->_asset_dir_relative_path.'".');
                throw new \RuntimeException('Was not able to create cache directory under "'.$this->_asset_dir_relative_path.'".');
            }
        }

        \asset::$_asset_cache_dir_full_path = $this->_asset_cache_dir_full_path;

        log_message('debug', 'ci-asset-manager - Full asset cache dir path set to "'.$this->_asset_cache_dir_full_path.'".');
        log_message('debug', 'ci-asset-manager - Relative asset cache dir path set to "'.$this->_asset_cache_dir_relative_path.'".');

        if (isset($config['minify']))
            $this->_minify = (bool)$config['minify'];
        else if (defined('ENVIRONMENT'))
            $this->_minify = ENVIRONMENT !== 'development';

        log_message('debug', 'ci-asset-manager - Global minify value set to "'.($this->_minify ? 'TRUE' : 'FALSE').'".');

        if (isset($config['logical_groups']))
        {
            if (!is_array($config['logical_groups']))
            {
                log_message(
                    'error',
                    'ci-asset-manager - "logical_groups" config parameter MUST be array, '.gettype($config['logical_groups']).' seen.');
                throw new \InvalidArgumentException('"logical_groups" config parameter MUST be array, '.gettype($config['logical_groups']).' seen.');
            }

            array_walk($config['logical_groups'], array($this, 'define_logical_asset_group'));
        }
    }

    /**
     * @param string $param
     * @return string
     * @throws OutOfBoundsException
     */
    public function __get($param)
    {
        switch($param)
        {
            case 'asset_dir_relative_path':
                return $this->_asset_dir_relative_path;

            case 'asset_dir_full_path':
                return $this->_asset_dir_full_path;

            case 'asset_dir_uri':
                return $this->_asset_dir_uri;

            case 'logical_groups':
                return $this->_logical_groups;

            case 'minify':
                return $this->_minify;

            default:
                throw new \OutOfBoundsException('Object does not contain public property with name "'.$param.'".');
        }
    }

    /**
     * @param array $asset_files
     * @param string $group_name
     * @throws InvalidArgumentException
     * @return \asset_manager
     */
    public function define_logical_asset_group($asset_files, $group_name)
    {
        if (!is_array($asset_files))
            throw new \InvalidArgumentException('Argument 2 expected to be array, '.gettype($asset_files).' seen.');

        $real_files = array();

        for($i = 0, $count = count($asset_files); $i < $count; $i++)
        {
            if (strpos($asset_files[$i], '*') !== false)
                $real_files = $real_files + $this->parse_glob_string($asset_files[$i]);
            else
                $real_files[] = $asset_files[$i];
        }

        if (isset($this->_logical_groups[$group_name]))
            $this->_logical_groups[$group_name] = $this->_logical_groups[$group_name] + $real_files;
        else
            $this->_logical_groups[$group_name] = $real_files;

        return $this;
    }

    /**
     * @param string $file
     * @return \asset
     */
    public function &get_asset($file)
    {
        $file = \asset::clean_asset_filename($file);

        if (!isset($this->assets[$file]))
            $this->initialize_asset($file);

        return $this->assets[$file];
    }

    /**
     * @param string $file
     * @param array $groups
     * @param bool $minify
     * @return \asset_manager
     */
    public function initialize_asset($file, $groups = null, $minify = null)
    {
        if (null === $minify)
            $minify = $this->_minify;

        \asset::asset_with_file_and_logical_groups_and_minify_and_observers(
            $this->_asset_dir_full_path.$file,
            $groups,
            $minify,
            array($this));

        return $this;
    }

    /**
     * @param string $file
     * @param bool $force_minify
     * @param array $attributes
     * @return $this
     */
    public function add_asset_to_output_queue($file, $force_minify = null, $attributes = array())
    {
        $this->_queued_assets[$file] = array(
            'force_minify' => $force_minify,
            'attributes' => $attributes,
        );

        return $this;
    }

    /**
     * @param array $files
     * @throws InvalidArgumentException
     * @return \asset_manager
     */
    public function add_assets_to_output_queue($files)
    {
        if (!is_array($files))
            throw new \InvalidArgumentException('Argument 1 expected to be array, '.gettype($files).' seen.');

        foreach($files as $k=>$v)
        {
            if (is_int($k))
            {
                $this->_queued_assets[$v] = array(
                    'force_minify' => null,
                    'attributes' => array(),
                );
            }
            else if (is_string($k) && is_array($v))
            {
                if (!isset($v['force_minify']))
                    $v['force_minify'] = $this->_minify;
                if (!isset($v['attributes']))
                    $v['attributes'] = array();

                $this->_queued_assets[$k] = $v;
            }
            else
            {
                log_message(
                    'error',
                    'ci-asset-manager - Invalid asset queue array seen.  Format must be array($file) or array($file => array("force_minify" => bool, "attributes" => array())).');
                throw new \InvalidArgumentException('Invalid asset queue array seen.  Format must be array($file) or array($file => array("force_minify" => bool, "attributes" => array())).');
            }
        }

        return $this;
    }

    /**
     * @param string $file
     * @param bool $force_minify
     * @param array $attributes
     * @return \asset_manager
     */
    public function prepend_asset_to_output_queue($file, $force_minify = null, $attributes = array())
    {
        $this->_queued_assets = array($file => array('force_minify' => $force_minify, 'attributes' => $attributes)) + $this->_queued_assets;

        return $this;
    }

    /**
     * @param string $logical_group
     * @throws RuntimeException
     */
    public function add_logical_group_to_output_queue($logical_group)
    {
        if (!isset($this->_logical_groups[$logical_group]))
        {
            log_message(
                'error',
                'ci-asset-manager - Could not add logical group "'.$logical_group.'" to output queue as it is not defined.');
            throw new \RuntimeException('Could not add logical group "'.$logical_group.'" to output queue as it is not defined.');
        }

        $this->add_assets_to_output_queue($this->_logical_groups[$logical_group]);
    }

    /**
     * @return string
     */
    public function generate_queue_asset_output()
    {
        $output = '';

        while(($key = key($this->_queued_assets)) !== null && ($current = current($this->_queued_assets)) !== false)
        {
            $output .= $this->generate_asset_tag($key, $current['force_minify'], $current['attributes']);
            next($this->_queued_assets);
        }

        reset($this->_queued_assets);

        return $output;
    }

    /**
     * Clear out the list of assets to be output
     */
    public function clear_output_queue()
    {
        $this->_queued_assets = array();
        return $this;
    }

    /**
     * @param string $logical_group
     * @param bool $force_minify
     * @throws RuntimeException
     * @return string
     */
    public function generate_logical_group_asset_output($logical_group, $force_minify = null)
    {
        if (!isset($this->logical_groups[$logical_group]))
        {
            log_message(
                'error',
                'ci-asset-manager - Group "'.$logical_group.'" does not exist.');
            throw new \RuntimeException('Group "'.$logical_group.'" does not exist.');
        }

        $output = '';

        for ($i = 0, $count = count($this->_logical_groups[$logical_group]); $i < $count; $i++)
        {
            $output .= $this->generate_asset_tag($this->_logical_groups[$logical_group][$i], $force_minify, null);
        }

        return $output;
    }

    /**
     * @param string $file
     * @param bool $force_minify
     * @param array $attributes
     * @return string
     */
    public function generate_asset_tag($file, $force_minify = null, $attributes = array())
    {
        if (strpos($file, '*') !== false)
        {
            $files = $this->parse_glob_string($file);

            $output = '';

            for ($i = 0, $count = count($files); $i < $count; $i++)
            {
                $output .= $this->generate_asset_tag($files[$i], $force_minify, $attributes);
            }

            return $output;
        }

        $asset = $this->get_asset($file);

        if (!is_bool($force_minify))
            $force_minify = ($this->_minify && $asset->minify);

        switch($asset->type)
        {
            case 'javascript':
                return $this->output_javascript_asset($asset, $force_minify, $attributes);

            case 'stylesheet':
                return $this->output_stylesheet_asset($asset, $force_minify, $attributes);

            default: return '';
        }
    }

    /**
     * @param string $glob_string
     * @return array
     */
    protected function parse_glob_string($glob_string)
    {
        $glob = glob($this->_asset_dir_full_path.$glob_string, GLOB_NOSORT | GLOB_BRACE);

        $files = array();

        for ($i = 0, $count = count($glob); $i < $count; $i++)
        {
            if (substr($glob[$i], -1) === '.')
                continue;

            $files[] = str_replace($this->_asset_dir_full_path, '', $glob[$i]);
        }

        return $files;
    }

    /**
     * @param asset $asset
     * @param bool $minify
     * @param array $attributes
     * @return string
     */
    public function output_javascript_asset(\asset $asset, $minify, $attributes)
    {
        $attribute_string = self::generate_asset_attribute_string($attributes);

        if ($minify)
        {
            if ($asset->source_is_minified || $this->generate_minified_javascript_asset($asset))
            {
                $include_file = str_replace(
                    array(
                        $this->_asset_dir_full_path,
                        DIRECTORY_SEPARATOR,
                    ),
                    array(
                        $this->_asset_dir_uri,
                        '/',
                    ),
                    $asset->minify_file);
            }
            else
                log_message(
                    'error',
                    'ci-asset-manager - Could not create minified version of asset "'.$asset->name.'".  Will use non-minified version.');
        }

        if (!isset($include_file))
        {
            $include_file = str_replace(
                array(
                    $this->_asset_dir_full_path,
                    DIRECTORY_SEPARATOR,
                ),
                array(
                    $this->_asset_dir_uri,
                    '/',
                ),
                $asset->file);
        }

        return '<script src="'.$include_file.
        '" type="text/javascript"'.$attribute_string.'></script>'."\n";
    }

    /**
     * @param asset $asset
     * @return bool
     */
    protected function generate_minified_javascript_asset(\asset $asset)
    {
        $source_modified = $asset->source_last_modified;
        $minify_modified = $asset->minify_last_modified;

        $ok = true;

        if (null === $minify_modified || $source_modified > $minify_modified)
            $ok = (bool)@file_put_contents($asset->minify_file, \JShrink\Minifier::minify(file_get_contents($asset->file)));

        return $ok;
    }

    /**
     * @param \asset $asset
     * @param bool $minify
     * @param array $attributes
     * @return string
     */
    public function output_stylesheet_asset(\asset $asset, $minify, $attributes)
    {
        $attribute_string = $this->generate_asset_attribute_string($attributes);

        if ($minify)
        {
            if ($asset->source_is_minified || $this->generate_minified_stylesheet_asset($asset))
            {
                $include_file = str_replace(
                    array(
                        $this->_asset_dir_full_path,
                        DIRECTORY_SEPARATOR,
                    ),
                    array(
                        $this->_asset_dir_uri,
                        '/',
                    ),
                    $asset->minify_file);
            }
            else
                log_message(
                    'error',
                    'ci-asset-manager - Could not create minified version of asset "'.$asset->name.'".  Will use non-minified version.');
        }

        if (!isset($include_file))
        {
            $include_file = str_replace(
                array(
                    $this->_asset_dir_full_path,
                    DIRECTORY_SEPARATOR,
                ),
                array(
                    $this->_asset_dir_uri,
                    '/',
                ),
                $asset->file);
        }

        return '<link href="'.$include_file.'" '.
        'rel="stylesheet" type="text/css"'.$attribute_string.' />'."\n";
    }

    /**
     * @param \asset $asset
     * @return bool
     */
    protected function generate_minified_stylesheet_asset(\asset $asset)
    {
        $source_modified = $asset->source_last_modified;
        $minify_modified = $asset->minify_last_modified;

        $ok = true;

        if (null === $minify_modified || $source_modified > $minify_modified)
            $ok = (bool)@file_put_contents($asset->minify_file, \CssMin::minify(file_get_contents($asset->file)));

        return $ok;
    }

    /**
     * @param array $attributes
     * @return string
     */
    protected function generate_asset_attribute_string($attributes)
    {
        if (!is_array($attributes))
            return '';

        $string = '';
        while (($key = key($attributes)) !== null && ($value = current($attributes)) !== false)
        {
            $string .= " {$key}='{$value}'";
            next($attributes);
        }

        return $string;
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Receive update from subject
     * @link http://php.net/manual/en/splobserver.update.php
     *
     * @param \SplSubject $subject The SplSubject notifying the observer of an update.
     * @return void
     */
    public function update(\SplSubject $subject)
    {
        if ($subject instanceof \asset)
        {
            switch($subject->notify_status)
            {
                case ASSET_NOTIFY::INITIALIZED:
                    $this->assets[$subject->name] = $subject;
                case ASSET_NOTIFY::GROUP_ADDED:
                    $logical_groups = $subject->logical_groups;
                    for($i = 0, $count = count($logical_groups); $i < $count; $i++)
                    {
                        $group = $logical_groups[$i];
                        if (!isset($this->_logical_groups[$group]))
                            $this->_logical_groups[$group] = array();

                        $this->_logical_groups[$group] = $this->_logical_groups[$group] + array($subject->name);
                    }
                    break;

                case ASSET_NOTIFY::GROUP_REMOVED:

                    break;
            }
        }
    }
}