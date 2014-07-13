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
use DCarbone\AssetManager\Config\AssetManagerConfig;

/**
 * Class AbstractAssetCollection
 *
 * This class is a derivative of my DCarbone\CollectionPlus\AbstractCollectionPlus found here:
 * @link https://github.com/dcarbone/collection-plus
 *
 * @property array output_assets
 * @package DCarbone\AssetManager\Collection
 */
abstract class AbstractAssetCollection implements \Countable, \RecursiveIterator, \SeekableIterator, \ArrayAccess, \Serializable
{
    /** @var \DCarbone\AssetManager\Config\AssetManagerConfig */
    protected $config;

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

        $current = $this->output_assets;
        $current[$asset_name] = $this[$asset_name]->get_requires();
        $this->output_assets = $current;
        return true;
    }

    /**
     * @param mixed $asset_name
     * @return void
     */
    public function remove_asset_from_output($asset_name)
    {
        $current = $this->output_assets;

        if (isset($current[$asset_name]))
        {
            unset($current[$asset_name]);
            $this->output_assets = $current;
        }
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->output_assets = array();
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

        foreach($this->output_assets as $asset_name=>$requires)
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
            $this->output_assets = array_unique(array_merge($reqs, array_keys($this->output_assets)));
        }
        else
        {
            $this->output_assets = array_keys($this->output_assets);
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
     *
     *
     *
     * It is un-advisable to edit things below here.
     *
     *
     *
     */

    /** @var array */
    private $_storage = array();

    /** @var mixed */
    private $_firstKey = null;
    /** @var mixed */
    private $_lastKey = null;

    /** @var string */
    protected $iteratorClass = '\ArrayIterator';

    /**
     * @param array $data
     * @param \DCarbone\AssetManager\Config\AssetManagerConfig $config
     */
    public function __construct(array $data = array(), AssetManagerConfig &$config)
    {
        $this->config = &$config;

        $this->_storage = $data;
        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
    }

    /**
     * Credit for this method goes to php5 dot man at lightning dot hu
     *
     * @link http://www.php.net/manual/en/class.arrayobject.php#107079
     *
     * This method allows you to call any of PHP's built-in array_* methods that would
     * normally expect an array parameter.
     *
     * Example: $myobj = new $concreteClass(array('b','c','d','e','a','z')):
     *
     * $myobj->array_keys();  returns array(0, 1, 2, 3, 4, 5)
     *
     * $myobj->array_merge(array('1', '2', '3', '4', '5')); returns array('b','c','d','e','a','z','1','2','3','4,'5');
     *
     * And so on.
     *
     * WARNING:  In utilizing call_user_func_array(), using this method WILL have an adverse affect on performance.
     * I recommend using this method only for development purposes.
     *
     * @param $func
     * @param $argv
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($func, $argv)
    {
        if (!is_callable($func) || substr($func, 0, 6) !== 'array_')
            throw new \BadMethodCallException(__CLASS__.'->'.$func);

        return call_user_func_array($func, array_merge(array($this->_storage), $argv));
    }

    /**
     * @return array
     */
    public function array_keys()
    {
        return array_keys($this->_storage);
    }

    /**
     * echo this object!
     *
     * @return string
     */
    public function __toString()
    {
        return get_class($this);
    }

    /**
     * make this object an array!
     *
     * @return array
     */
    public function __toArray()
    {
        return $this->_storage;
    }

    /**
     * @param $param
     * @return mixed
     * @throws \OutOfRangeException
     */
    public function &__get($param)
    {
        if (!$this->offsetExists($param))
            throw new \OutOfRangeException('No data element with the key "'.$param.'" found');

        return $this->_storage[$param];
    }

    /**
     * This method was inspired by Zend Framework 2.2.x PhpReferenceCompatibility class
     *
     * @link https://github.com/zendframework/zf2/blob/release-2.2.6/library/Zend/Stdlib/ArrayObject/PhpReferenceCompatibility.php#L179
     *
     * @param $dataSet
     * @return array
     * @throws \InvalidArgumentException
     */
    public function exchangeArray($dataSet)
    {
        if (!is_array($dataSet) && !is_object($dataSet))
            throw new \InvalidArgumentException(__CLASS__.'::exchangeArray - "$dataSet" parameter expected to be array or object');

        if ($dataSet instanceof \stdClass)
            $dataSet = (array)$dataSet;
        else if ($dataSet instanceof self)
            $dataSet = $dataSet->__toArray();
        else if (is_object($dataSet) && is_callable(array($dataSet, 'getArrayCopy')))
            $dataSet = $dataSet->getArrayCopy();

        if (!is_array($dataSet))
            throw new \InvalidArgumentException(__CLASS__.'::exchangeArray - Could not convert "$dataSet" value of type "'.gettype($dataSet).'" to an array!');

        $storage = $this->_storage;
        $this->_storage = $dataSet;
        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
        return $storage;
    }


    /**
     * Set a value on this collection
     *
     * @param mixed $key
     * @param mixed $value
     * @return bool
     */
    public function set($key, $value)
    {
        $this->offsetSet($key, $value);
        return true;
    }

    /**
     * Append a value
     *
     * @param mixed $value
     * @return bool
     */
    public function append($value)
    {
        $this->offsetSet(null, $value);
        return true;
    }

    /**
     * Try to determine if an identical element is already in this collection
     *
     * @param mixed $element
     * @return bool
     */
    public function contains($element)
    {
        return in_array($element, $this->_storage, true);
    }

    /**
     * Custom "contains" method
     *
     * @param callable $func
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function exists($func)
    {
        if (!is_callable($func, false, $callable_name))
            throw new \InvalidArgumentException(__CLASS__.'::exists - Un-callable "$func" value seen!');

        if (strpos($callable_name, 'Closure::') !== 0)
            $func = $callable_name;

        reset($this->_storage);
        while(($key = key($this->_storage)) !== null && ($value = current($this->_storage)) !== false)
        {
            if ($func($key, $value))
                return true;

            next($this->_storage);
        }

        return false;
    }

    /**
     * Return index of desired key
     *
     * @param mixed $value
     * @return mixed
     */
    public function indexOf($value)
    {
        return array_search($value, $this->_storage, true);
    }

    /**
     * Remove and return an element
     *
     * @param $index
     * @return mixed|null
     */
    public function remove($index)
    {
        if (!isset($this->_storage[$index]) && !array_key_exists($index, $this->_storage))
            return null;

        $removed = $this->_storage[$index];
        unset($this->_storage[$index]);

        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);

        return $removed;
    }

    /**
     * @param $element
     * @return bool
     */
    public function removeElement($element)
    {
        $key = array_search($element, $this->_storage, true);

        if ($key === false)
            return false;

        unset($this->_storage[$key]);

        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);

        return true;
    }

    /**
     * Get an Iterator instance for this data set
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $class = $this->iteratorClass;
        return new $class($this->_storage);
    }

    /**
     * @return string
     */
    public function getIteratorClass()
    {
        return $this->iteratorClass;
    }

    /**
     * Sets the iterator classname for the ArrayObject
     *
     * @param  string $class
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setIteratorClass($class)
    {
        if (class_exists($class))
        {
            $this->iteratorClass = $class;
            return;
        }

        if (strpos($class, '\\') === 0)
        {
            $class = '\\' . $class;
            if (class_exists($class))
            {
                $this->iteratorClass = $class;
                return;
            }
        }

        throw new \InvalidArgumentException(__CLASS__.'::setIteratorClass - The iterator class does not exist');
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

        return new static(array_map($func, $this->_storage), $this->config);
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

        if ($func === null)
            return new static(array_filter($this->_storage), $this->config);

        if (strpos($callable_name, 'Closure::') !== 0)
            $func = $callable_name;

        return new static(array_filter($this->_storage, $func), $this->config);
    }

    /**
     * Is this collection empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return (count($this) === 0);
    }

    /**
     * Return the first item from storage
     *
     * @return mixed
     */
    public function first()
    {
        if ($this->isEmpty())
            return null;

        return $this->_storage[$this->_firstKey];
    }

    /**
     * Return the last element from storage
     *
     * @return mixed
     */
    public function last()
    {
        if ($this->isEmpty())
            return null;

        return $this->_storage[$this->_lastKey];
    }

    /**
     * Sort values by standard PHP sort method
     *
     * @link http://www.php.net/manual/en/function.sort.php
     *
     * @param int $flags
     * @return bool
     */
    public function sort($flags = SORT_REGULAR)
    {
        $sort = sort($this->_storage, $flags);
        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
        return $sort;
    }

    /**
     * Reverse sort values
     *
     * @link http://www.php.net/manual/en/function.rsort.php
     *
     * @param int $flags
     * @return bool
     */
    public function rsort($flags = SORT_REGULAR)
    {
        $sort = rsort($this->_storage, $flags);
        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
        return $sort;
    }

    /**
     * Sort values by custom function
     *
     * @link http://www.php.net/manual/en/function.usort.php
     *
     * @param string|array $func
     * @return bool
     */
    public function usort($func)
    {
        $sort = usort($this->_storage, $func);
        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
        return $sort;
    }

    /**
     * Sort by keys
     *
     * @link http://www.php.net/manual/en/function.ksort.php
     *
     * @param int $flags
     * @return bool
     */
    public function ksort($flags = SORT_REGULAR)
    {
        $sort = ksort($this->_storage, $flags);
        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
        return $sort;
    }

    /**
     * Reverse sort by keys
     *
     * @link http://www.php.net/manual/en/function.krsort.php
     *
     * @param int $flags
     * @return bool
     */
    public function krsort($flags = SORT_REGULAR)
    {
        $sort = krsort($this->_storage, $flags);
        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
        return $sort;
    }

    /**
     * Sort by keys with custom function
     *
     * http://www.php.net/manual/en/function.uksort.php
     *
     * @param string|array $func
     * @return bool
     */
    public function uksort($func)
    {
        $sort = uksort($this->_storage, $func);
        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
        return $sort;
    }

    /**
     * Sort values while retaining indices.
     *
     * @link http://www.php.net/manual/en/function.asort.php
     *
     * @param int $flags
     * @return bool
     */
    public function asort($flags = SORT_REGULAR)
    {
        $sort = asort($this->_storage, $flags);
        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
        return $sort;
    }

    /**
     * Reverse sort values while retaining indices
     *
     * @link http://www.php.net/manual/en/function.arsort.php
     *
     * @param int $flags
     * @return bool
     */
    public function arsort($flags = SORT_REGULAR)
    {
        $sort = arsort($this->_storage, $flags);
        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
        return $sort;
    }

    /**
     * Sort values while preserving indices with custom function
     *
     * @link http://www.php.net/manual/en/function.uasort.php
     *
     * @param $func
     * @return bool
     */
    public function uasort($func)
    {
        $sort = uasort($this->_storage, $func);
        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
        return $sort;
    }

    /**
     * (PHP 5 >= 5.0.0)
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return current($this->_storage);
    }

    /**
     * (PHP 5 >= 5.0.0)
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        next($this->_storage);
    }

    /**
     * (PHP 5 >= 5.0.0)
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->_storage);
    }

    /**
     * (PHP 5 >= 5.0.0)
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return (key($this->_storage) !== null && current($this->_storage) !== false);
    }

    /**
     * (PHP 5 >= 5.0.0)
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->_storage);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Returns if an iterator can be created for the current entry.
     * @link http://php.net/manual/en/recursiveiterator.haschildren.php
     * @return bool true if the current entry can be iterated over, otherwise returns false.
     */
    public function hasChildren()
    {
        return ($this->valid() && is_array(current($this->_storage)));
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Returns an iterator for the current entry.
     * @link http://php.net/manual/en/recursiveiterator.getchildren.php
     * @return \RecursiveIterator An iterator for the current entry.
     */
    public function getChildren()
    {
        return current($this->_storage);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Seeks to a position
     * @link http://php.net/manual/en/seekableiterator.seek.php
     * @param int $position The position to seek to.
     *
     * @throws \OutOfBoundsException
     * @return void
     */
    public function seek($position)
    {
        if (!isset($this->_storage[$position]) && !array_key_exists($position, $this->_storage))
            throw new \OutOfBoundsException('Invalid seek position ('.$position.')');

        while (key($this->_storage) !== $position)
        {
            next($this->_storage);
        }
    }

    /**
     * (PHP 5 >= 5.0.0)
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for.
     *
     * @return boolean true on success or false on failure.
     *
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->_storage[$offset]) || array_key_exists($offset, $this->_storage);
    }

    /**
     * (PHP 5 >= 5.0.0)
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        if (isset($this->_storage[$offset]) || array_key_exists($offset, $this->_storage))
            return $this->_storage[$offset];
        else
            return null;
    }

    /**
     * (PHP 5 >= 5.0.0)
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to.
     *
     * @param mixed $value The value to set.
     * @return mixed
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null)
            $this->_storage[] = $value;
        else
            $this->_storage[$offset] = $value;

        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
    }

    /**
     * (PHP 5 >= 5.0.0)
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     *
     * @throws \OutOfBoundsException
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (isset($this->_storage[$offset]) || array_key_exists($offset, $this->_storage))
            unset($this->_storage[$offset]);
        else
            throw new \OutOfBoundsException('Tried to unset undefined offset ('.$offset.')');

        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     *
     * The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->_storage);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize($this->_storage);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized The string representation of the object.
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        $this->_storage = unserialize($serialized);

        end($this->_storage);
        $this->_lastKey = key($this->_storage);
        reset($this->_storage);
        $this->_firstKey = key($this->_storage);
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->_storage;
    }
}