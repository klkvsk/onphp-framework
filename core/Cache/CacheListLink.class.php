<?php
/**
 * Created by PhpStorm.
 * User: byorty
 * Date: 01.01.14
 * Time: 16:25
 */

class CacheListLink {

    private $id;
    private $keys;
    private $values;
    private $count;
    private $result = false;
    /**
     * @var bool
     */
    private $hasSubLinks = false;

    /**
     * @return self
     */
    public static function create() {
        return new self;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setKey($id, $key) {
        $this->keys[$id] = $key;
        return $this;
    }

    /**
     * @param mixed $keys
     * @return $this
     */
    public function setKeys($keys) {
        $this->keys = $keys;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getKeys() {
        return $this->keys;
    }

    /**
     * @param mixed $id
     * @return $this
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function hasSubLinksOn() {
        $this->hasSubLinks = true;
        return $this;
    }

    /**
     * @return boolean
     */
    public function hasSubLinks() {
        return $this->hasSubLinks;
    }

    /**
     * @param $key
     * @return CacheListLink|null
     */
    public function getSubLink($key) {
        return isset($this->keys[$key]) ? $this->keys[$key] : null;
    }

    /**
     * @param mixed $count
     * @return $this
     */
    public function setCount($count) {
        $this->count = $count;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCount() {
        return $this->count;
    }

    /**
     * @param $result
     * @return $this
     */
    public function setResult($result) {
        $this->result = $result;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isResult() {
        return $this->result;
    }

    /**
     * @param mixed $values
     * @return $this
     */
    public function setValue($key, $value) {
        $this->values[$key] = $value;
        return $this;
    }

    /**
     * @param mixed $values
     * @return $this
     */
    public function setValues($values) {
        $this->values = $values;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValues() {
        return $this->values;
    }
} 