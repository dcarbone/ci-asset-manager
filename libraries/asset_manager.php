<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if (!class_exists('CssMin'))
    require __DIR__.DIRECTORY_SEPARATOR.'CssMin.php';

/**
 * Interface iasset
 */
interface iasset
{
    /**
     * @param string $file
     * @param string $name
     * @param boolean $minify
     * @param array $observers
     * @return \asset
     */
    public static function asset_with_file_and_name_and_minify_and_observers($file, $name, $minify, $observers);

    /**
     * @param string $file
     * @param string $name
     * @param array $groups
     * @param boolean $minify
     * @param array $observers
     * @return \asset
     */
    public static function asset_with_file_and_name_and_groups_and_minify_and_observers($file, $name, $groups, $minify, $observers);

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
    public function in_group($group);

    /**
     * @param string $group
     * @return void
     */
    public function add_to_group($group);

    /**
     * @param array $groups
     * @return void
     */
    public function add_to_groups(array $groups);

    /**
     * @param string $group
     * @return void
     */
    public function remove_from_group($group);

    /**
     * @param array $groups
     * @return void
     */
    public function remove_from_groups(array $groups);
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
 * @property string file
 * @property string type
 * @property array groups
 * @property int notify_status
 * @property bool remote
 */
class asset implements \iasset, \SplSubject
{
    /** @var int */
    protected $_notify_status;

    /** @var string */
    protected $_name;

    /** @var string */
    protected $_file;

    /** @var string */
    protected $_type;

    /** @var array */
    protected $_groups = array();

    /** @var bool */
    protected $_minify = true;

    /** @var bool */
    protected $_remote;

    /** @var array */
    private $_observers = array();

    /**
     * Constructor
     *
     * @param string $file
     * @param string $name
     * @param array $groups
     * @param boolean $minify
     * @param array $observers
     */
    protected function __construct($file, $name, $groups, $minify, $observers)
    {
        $this->_file = $file;
        $this->_name = $name;
        $this->_groups = $groups;
        $this->_minify = $minify;
        $this->_observers = $observers;
    }

    /**
     * @param string $file
     * @param string $name
     * @param boolean $minify
     * @param array $observers
     * @return \asset
     */
    public static function asset_with_file_and_name_and_minify_and_observers($file, $name, $minify, $observers)
    {
        return static::asset_with_file_and_name_and_groups_and_minify_and_observers($file, $name, null, $minify, $observers);
    }

    /**
     * @param string $file
     * @param string $name
     * @param array $groups
     * @param boolean $minify
     * @param array $observers
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @return \asset
     */
    public static function asset_with_file_and_name_and_groups_and_minify_and_observers($file, $name, $groups, $minify, $observers)
    {
        if (!is_string($file))
            throw new \InvalidArgumentException('Argument 1 expected to be string, '.gettype($file).' seen.');
        if (($file = trim($file)) === '')
            throw new \InvalidArgumentException('Empty string passed for argument 1.');
        if (!is_file($file))
            throw new \RuntimeException('File specified by argument 1 does not exist. Value: "'.$file.'"');

        if ($name === null)
        {
            $split = preg_split('#[/\\\]+#', $file);
            $name = end($split);
        }
        else
        {
            if (!is_string($name))
                throw new \InvalidArgumentException('Argument 2 expected to be null or string, '.gettype($file).' seen.');
            if (($name = trim($name)) === '')
                throw new \InvalidArgumentException('Empty string passed for argument 2.');
        }

        if (null === $groups)
            $groups = array($name);
        else if (!is_array($groups))
            throw new \InvalidArgumentException('Argument 3 expected to be null or array, '.gettype($groups).' seen.');

        if (!is_bool($minify))
            throw new \InvalidArgumentException('Argument 4 expected to be boolean, '.gettype($minify).' seen.');

        if (null === $observers)
            $observers = array();
        else if (!is_array($observers))
            throw new \InvalidArgumentException('Argument 5 expected to be null or array of objects implementing \\SplObserver.');

        /** @var \asset $asset */
        $asset = new static($file, $name, array_unique($groups), $minify, $observers);

        $asset->initialize();

        return $asset;
    }

    /**
     * @param string $param
     * @return array|string
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

            case 'file':
                return $this->_file;

            case 'type':
                return $this->_type;

            case 'groups':
                return $this->_groups;

            case 'remote':
                return $this->_remote;

            default:
                throw new \OutOfBoundsException('ci-asset: No property with name "'.(string)$param.'" exists on this class.');
        }
    }

    /**
     * Post-construct object initialization
     */
    protected function initialize()
    {
        $this->determine_remote();
        $this->determine_type();

        $this->_notify_status = ASSET_NOTIFY::INITIALIZED;
        $this->notify();
    }

    /**
     * Determine whether file is remote or not (really dumb test for the moment)
     *
     * TODO Improve remote asset determination
     */
    protected function determine_remote()
    {
        switch(true)
        {
            case (stripos('http', $this->_file) === 0) :
            case (stripos('//', $this->_file) === 0):
                $this->_remote = true;
                break;

            default:
                $this->_remote = false;
                break;
        }
    }

    /**
     * @throws \RuntimeException
     */
    protected function determine_type()
    {
        $ext = strrchr('.', $this->_file);

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
     * @param string $group
     * @return bool
     */
    public function in_group($group)
    {
        return in_array($group, $this->_groups, true);
    }

    /**
     * @param string $group
     * @return void
     */
    public function add_to_group($group)
    {
        if (!in_array($group, $this->_groups, true))
        {
            $this->_groups[] = $group;
            $this->_notify_status = ASSET_NOTIFY::GROUP_ADDED;
            $this->notify();
        }
    }

    /**
     * @param array $groups
     * @return void
     */
    public function add_to_groups(array $groups)
    {
        $this->_groups = $this->_groups + $groups;

        $this->_notify_status = ASSET_NOTIFY::GROUP_ADDED;
        $this->notify();
    }

    /**
     * @param string $group
     * @return void
     */
    public function remove_from_group($group)
    {
        $idx = array_search($group, $this->_groups, true);
        if ($idx !== false)
        {
            unset($this->_groups[$idx]);

            $this->_notify_status = ASSET_NOTIFY::GROUP_REMOVED;
            $this->notify();
        }
    }

    /**
     * @param array $groups
     * @return void
     */
    public function remove_from_groups(array $groups)
    {
        $this->_groups = array_diff($this->_groups, $groups);

        $this->_notify_status = ASSET_NOTIFY::GROUP_REMOVED;
        $this->notify();
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
 * @property string asset_dir_name
 * @property string asset_dir_path
 * @property string asset_dir_uri
 */
class asset_manager implements \SplObserver
{
    /** @var array */
    protected $assets = array();

    /** @var string */
    protected $_asset_dir_name;

    /** @var string */
    protected $_asset_dir_path;

    /** @var string */
    protected $_asset_dir_uri;

    /** @var bool */
    protected $_force_minify = false;

    /** @var array */
    protected  $_asset_group_map = array();

    /** @var array */
    protected $_group_asset_map = array();

    /**
     * Constructor
     *
     * @param array $config
     * @throws RuntimeException
     */
    public function __construct(array $config = array())
    {
        /** @var \MY_Controller|\CI_Controller $CI */
        $CI = &get_instance();

        if (count($config) > 0)
        {
            log_message('debug', 'ci-asset-manager - Config loaded from array param.');
        }
        else if ($CI instanceof \CI_Controller && $CI->config->load('asset_manager', false, true))
        {
            $config = $CI->config->item('asset_manager');
            log_message('debug', 'ci-asset-manager - Config loaded from file.');
        }

        if (isset($config['asset_dir_name']))
        {
            $this->asset_dir_name = trim((string)$config['asset_dir_name']);

            log_message(
                'debug',
                'ci-asset-manager - Using "asset_dir_name" parameter from user configuration.');
        }
        else
        {
            $this->asset_dir_name = 'assets';
            log_message(
                'debug',
                'ci-asset-manager - User configuration did not specify "asset_dir_name" property, assuming "assets".');
        }

        $this->asset_dir_path = rtrim(FCPATH.$this->asset_dir_name, "/\\").DIRECTORY_SEPARATOR;
        $this->asset_dir_uri = rtrim(base_url($this->asset_dir_name), "/").'/';

        if (!file_exists($this->asset_dir_path))
        {
            log_message(
                'debug',
                'ci-asset-manager - Could not find asset directory "'.$this->asset_dir_path.'", will try to create it.');

            $mkdir = @mkdir($this->asset_dir_path, 0777, true);

            if ($mkdir === false)
            {
                log_message(
                    'error',
                    'ci-asset-manager - Could not create asset directory "'.$this->asset_dir_path.'".');
                throw new \RuntimeException('ci-asset-manager: Could not create directory at path: "'.$this->asset_dir_path.'".');
            }
            else
            {
                log_message(
                    'debug',
                    'ci-asset-manager - Directory "'.$this->asset_dir_path.'" successfully created');

                file_put_contents($this->asset_dir_path.'index.html', <<<HTML
<html>
<head>
	<title>403 Forbidden</title>
</head>
<body>

<p>Directory access is forbidden.</p>

</body>
</html>
HTML
                );
            }
        }


    }

    public function &get_asset_by_name($name)
    {
        if (isset($this->assets[$name]))
            return $this->assets[$name];

        throw new \RuntimeException('Asset named "'.$name.'" does not exist.');
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
                    $groups = $subject->groups;
                    $this->_asset_group_map[$subject->name] = $groups;

                    for($i = 0, $count = count($groups); $i < $count; $i++)
                    {
                        $group = $groups[$i];
                        if (!isset($this->_group_asset_map[$group]))
                            $this->_group_asset_map[$group] = array();

                        $this->_group_asset_map[$group] = $this->_group_asset_map[$group] + array($subject->name);
                    }
                    break;

                case ASSET_NOTIFY::GROUP_REMOVED:

                    break;
            }
        }
    }
}