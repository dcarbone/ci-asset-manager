<?php namespace DCarbone\AssetManager\Collection;

// Copyright (c) 2012-2014 Daniel Carbone

// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

use DCarbone\AssetManager\Asset\AbstractAsset;
use DCarbone\AssetManager\Asset\IAsset;
use DCarbone\AssetManager\Config\AssetManagerConfig;
use DCarbone\CollectionPlus\AbstractCollectionPlus;

/**
 * Class AbstractAssetCollection
 *
 * @package DCarbone\AssetManager\Collection
 */
abstract class AbstractAssetCollection extends AbstractCollectionPlus implements \SplObserver, \SplSubject
{
    /** @var \DCarbone\AssetManager\Config\AssetManagerConfig */
    protected $config;

    /** @var array */
    protected $observers = array();

    /** @var array */
    protected $assets_to_render = array();

    /**
     * @param array $data
     * @param \DCarbone\AssetManager\Config\AssetManagerConfig $config
     */
    public function __construct(array $data = array(), AssetManagerConfig $config)
    {
        parent::__construct($data);

        $this->config = $config;

        $this->load_existing_cached_assets();
    }

    /**
     * @return void
     */
    abstract protected function load_existing_cached_assets();

    /**
     * @return string
     */
    abstract public function generate_output();

    /**
     * Combine Asset Files
     *
     * This method actually combines the assets passed to it and saves it to a file
     *
     * @return bool
     */
    abstract protected function build_combined_assets();

    /**
     * @param mixed $asset_name
     * @return bool
     */
    public function add_asset_to_output($asset_name)
    {
        if (!isset($this[$asset_name]))
            return false;

        $current = $this->assets_to_render;
        $current[$asset_name] = $this[$asset_name]->get_requires();
        $this->assets_to_render = $current;

        return true;
    }

    /**
     * @param mixed $asset_name
     * @return void
     */
    public function remove_asset_from_output($asset_name)
    {
        $current = $this->assets_to_render;

        if (isset($current[$asset_name]))
        {
            unset($current[$asset_name]);
            $this->assets_to_render = $current;
        }
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->assets_to_render = array();
    }

    /**
     * @return void
     */
    public function prepare_output()
    {
        $this->build_output_sequence();

        if ($this->config->is_dev() === false && $this->config->can_combine() === true)
            $this->build_combined_assets();
    }

    /**
     * @return void
     */
    public function build_output_sequence()
    {
        $required = array();

        foreach($this->assets_to_render as $asset_name=>$requires)
        {
            foreach($requires as $require)
            {
                if (array_key_exists($require, $required))
                    $required[$require]++;
                else
                    $required[$require] = 1;
            }
        }

        if (count($required) > 0)
        {
            asort($required, SORT_NUMERIC);
            $required = array_reverse($required);
            $reqs = array_keys($required);
            $this->assets_to_render = array_unique(array_merge($reqs, array_keys($this->assets_to_render)));
        }
        else
        {
            $this->assets_to_render = array_keys($this->assets_to_render);
        }
    }

    /**
     * Determine if file exists in cache
     *
     * @param string  $asset_name file name
     * @return bool
     */
    protected function cache_file_exists($asset_name)
    {
        return isset($this[$asset_name]);
    }

    /**
     * Get newest modification date of files within cache container
     *
     * @param array  array of files
     * @return \DateTime
     */
    protected function get_newest_date_modified(array $file_names)
    {
        $date = new \DateTime("0:00:00 January 1, 1970 UTC");
        foreach($file_names as $name)
        {
            /** @var AbstractAsset $asset */
            $asset = $this[$name];

            $d = $asset->get_file_date_modified();

            if (!($d instanceof \DateTime))
                continue;

            if ($d > $date)
                $date = $d;
        }
        return $date;
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
        $this->notify(\asset_manager::ASSET_MODIFIED, $subject);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Attach an SplObserver
     * @link http://php.net/manual/en/splsubject.attach.php
     *
     * @param \SplObserver $observer the SplObserver to attach.
     * @return void
     */
    public function attach(\SplObserver $observer)
    {
        if (!in_array($observer, $this->observers, true))
            $this->observers[] = $observer;
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Detach an observer
     * @link http://php.net/manual/en/splsubject.detach.php
     *
     * @param \SplObserver $observer the SplObserver to detach.
     * @return void
     */
    public function detach(\SplObserver $observer)
    {
        $idx = array_search($observer, $this->observers, true);
        if ($idx !== false)
            unset($this->observers[$idx]);
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
        $action = func_get_arg(0);
        $resource = func_get_arg(1);

        switch($action)
        {
            case \asset_manager::ASSET_REMOVED :
                if ($resource instanceof IAsset)
                    $resource->detach($this);

                break;
        }

        // For now, just pass the message along
        foreach($this->observers as $observer)
        {
            /** @var \SplObserver $observer */
            $observer->update($this, $action, $resource);
        }
    }

    /**
     * @param $index
     * @return mixed|null
     */
    public function remove($index)
    {
        $removed = parent::remove($index);
        if ($removed instanceof IAsset)
            $this->notify(\asset_manager::ASSET_REMOVED, $removed);

        return $removed;
    }

    /**
     * @param mixed $element
     * @return bool
     */
    public function removeElement($element)
    {
        $return = parent::removeElement($element);

        if ($return && $element instanceof IAsset)
            $this->notify(\asset_manager::ASSET_REMOVED, $element);

        return $return;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        parent::offsetSet($offset, $value);

        if (!($value instanceof IAsset))
            return;

        if ($offset === null)
        {
            $this->last()->attach($this);
            $this->notify(\asset_manager::ASSET_ADDED, $this->getLastKey());
        }
        else
        {
            $this[$offset]->attach($this);
            $this->notify(\asset_manager::ASSET_ADDED, $offset);
        }
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        if (isset($this[$offset]) && $this[$offset] instanceof IAsset)
            $this->notify(\asset_manager::ASSET_REMOVED, $this[$offset]);

        parent::offsetUnset($offset);
    }


    /**
     * Applies array_map to this dataset, and returns a new object.
     *
     * @link http://us1.php.net/array_map
     *
     * They scope "static" is used so that an instance of the extended class is returned.
     *
     * @param callable $func
     * @throws \InvalidArgumentException
     * @return static
     */
    public function map($func)
    {
        if (!is_callable($func, false, $callable_name))
            throw new \InvalidArgumentException(__CLASS__.'::map - Un-callable "$func" value seen!');

        if (strpos($callable_name, 'Closure::') !== 0)
            $func = $callable_name;

        /** @var self $new */
        $new = new static(array_map($func, $this->__toArray()), $this->config);

        foreach($this->observers as $observer)
            $new->attach($observer);

        return $new;
    }

    /**
     * Applies array_filter to internal dataset, returns new instance with resulting values.
     *
     * @link http://www.php.net/manual/en/function.array-filter.php
     *
     * Inspired by:
     *
     * @link http://www.doctrine-project.org/api/common/2.3/source-class-Doctrine.Common.Collections.ArrayCollection.html#377-387
     *
     * @param callable $func
     * @throws \InvalidArgumentException
     * @return static
     */
    public function filter($func = null)
    {
        if ($func !== null && !is_callable($func, false, $callable_name))
            throw new \InvalidArgumentException(__CLASS__.'::filter - Un-callable "$func" value seen!');

        /** @var self $new */

        if ($func === null)
        {
            $new = new static(array_filter($this->__toArray()), $this->config);
        }
        else
        {
            if (strpos($callable_name, 'Closure::') !== 0)
                $func = $callable_name;

            $new = new static(array_filter($this->__toArray(), $func), $this->config);
        }

        foreach($this->observers as $observer)
            $new->attach($observer);

        return $new;
    }
}