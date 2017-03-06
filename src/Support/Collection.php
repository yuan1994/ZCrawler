<?php
/**
 * yuan1994/ZCrawler
 *
 * @author        yuan1994 <tianpian0805@gmail.com>
 * @link          https://github.com/yuan1994/ZCrawler
 * @documentation http://zcrawler.yuan1994.com
 * @copyright     2017 yuan1994 all rights reserved.
 * @license       http://www.apache.org/licenses/LICENSE-2.0
 */

namespace ZCrawler\Support;

use Traversable;
use ArrayIterator;
use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Serializable;

class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable, Serializable
{
    /**
     * @var array|object data
     */
    private $data;

    /**
     * set data.
     *
     * @param mixed $data
     */
    public function __construct($data = [])
    {
        if (is_object($data)) {
            $this->data = get_object_vars($data);
        } else {
            $this->data = $data;
        }
    }

    /**
     * Reset the data
     *
     * @param array|object $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Return all items.
     *
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Return specific items.
     *
     * @param array $keys
     *
     * @return array
     */
    public function only(array $keys)
    {
        $ret = [];

        foreach ($keys as $key) {
            $value = $this->get($key);

            if (!is_null($value)) {
                $ret[$key] = $value;
            }
        }

        return $ret;
    }

    /**
     * Get all items except for those with the specified keys.
     *
     * @param mixed $keys
     *
     * @return static
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        return array_diff_key($this->data, array_flip((array) $keys));
    }

    /**
     * Merge data.
     *
     * @param array $items
     *
     * @return array
     */
    public function merge($items)
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }

        return $this->all();
    }

    /**
     * To determine Whether the specified element exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Retrieve the first item.
     *
     * @return mixed
     */
    public function first()
    {
        return reset($this->data);
    }

    /**
     * Retrieve the last item.
     *
     * @return mixed
     */
    public function last()
    {
        $end = end($this->data);

        return $end;
    }

    /**
     * Set the item value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Retrieve item from Collection.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * Remove item form Collection.
     *
     * @param string $key
     */
    public function delete($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Check whether the Collection is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Build to array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->all();
    }

    /**
     * Build to json.
     *
     * @param int $option
     *
     * @return string
     */
    public function toJson($option = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->all(), $option);
    }

    /**
     * To string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Get a data by key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Assigns a value to the specified data.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Whether or not an data exists by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * Unsets an data by key.
     *
     * @param string $key
     */
    public function __unset($key)
    {
        $this->delete($key);
    }

    /**
     * var_export.
     *
     * @return array
     */
    public function __set_state()
    {
        return $this->all();
    }

    /**
     * Retrieve an external iterator
     * @link  http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    /**
     * Whether a offset exists
     * @link  http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Offset to retrieve
     * @link  http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * Offset to set
     * @link  http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     * @link  http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * String representation of object
     * @link  http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize($this->data);
    }

    /**
     * Constructs the object
     * @link  http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized <p>
     *                           The string representation of the object.
     *                           </p>
     *
     * @return void
     * @since 5.1.0
     */
    public function unserialize($data)
    {
        $this->data = unserialize($data);
    }

    /**
     * Count elements of an object
     * @link  http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        return $this->data;
    }
}
