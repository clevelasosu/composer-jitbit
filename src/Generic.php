<?php
/**
 * A generic class for JitBit used by other classes.
 *
 * @package JitBit
 * @author Steve Cleveland <steve.cleveland@oregonstate.edu
 *
 */

namespace OSUCOE\JitBit;


class Generic
{
    /**
     * @var API
     */
    protected $api;
    /**
     * @var int
     */
    protected $ID;
    /**
     * Where the full details of the object are stored and updated
     * @var array
     */
    protected $details;
    /**
     * @var array
     */
    protected $updatedFields = [];

    /**
     * Only this fields can be updated
     * @var array
     */
    protected $updateableFields = [
    ];

    /**
     * URL to save to
     * @var string
     */
    protected $saveUrl;
    /**
     * Unique field to save with
     * @var string
     */
    protected $saveIDField;

    /**
     * URL to get refresh the object
     * @var string
     */
    protected $refreshUrl;
    /**
     * Unique attribute
     * @var string
     */
    protected $refreshAttribute;
    /**
     * Which $this property to use as the value
     * @var string
     */
    protected $refreshUniqueValue;

    /**
     * Maps from fields sent by JitBit on GET requests to the field names used during POST requests
     * Only needed for fields that don't match (JitBit API appears to not be case sensitive)
     *
     * @var array
     */
    protected $fieldMap = [
    ];

    public function __construct(API $api)
    {
        $this->api = $api;
        $this->refresh();
    }

    /**
     * Updates the local attribute.  Be sure to run save() to write to the server
     *
     * @param $field
     * @param $value
     */
    protected function updateField($field, $value)
    {
//        echo "Field: ".$field.PHP_EOL;
        if (!in_array($field, $this->updateableFields)) {
            throw new JitBitException('Invalid Field Given');
        }

        $this->details->$field = $value;
        $this->updatedFields[] = $field;
    }

    /**
     * Save the updates to the server
     */
    public function save() {
        if (!count($this->updatedFields)) {
            return;
        }

        $args = [];
        foreach ($this->updatedFields as $updatedField) {
            if (array_key_exists($updatedField, $this->fieldMap)) {
                $fieldToUpdate = $this->fieldMap[$updatedField];
            } else {
                $fieldToUpdate = $updatedField;
            }
            $args[$fieldToUpdate] = $this->details->$updatedField;
        }
        $args[$this->saveIDField] = $this->ID;

        $this->api->_request('POST', $this->saveUrl."?".http_build_query($args));
        $this->refresh();
    }

    /**
     * Pulls fresh data from the server and wipes out local changes
     */
    protected function refresh()
    {
        $url = $this->refreshUrl.'?'.$this->refreshAttribute.'='.$this->{$this->refreshUniqueValue};
        $result = $this->api->_request('GET', $url);
        $this->updatedFields = [];
        $this->refreshStore($result);
    }

    /**
     * Store the request.  Having this in it's own class allows it to be overwritten if needed
     *
     * @param $result
     */
    protected function refreshStore($result) {
        $this->details = $result;
//        $this->ID = $result->{$this->IDField};
        $this->updatedFields = [];
    }

    /**
     * Running ->updateFirstName will update the FirstName attribute
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        if (preg_match('/^update([A-Z][a-zA-Z0-9]*)$/', $name, $matches)) {
            $field = $matches[1];
            if (count($arguments) > 1) {
                throw new JitBitException('Invalid number of arguments');
            }

            $value = $arguments[0];
            $this->updateField($field, $value);
        }
    }

    /**
     * Allows accessing of protected attributes.  This way they're read only
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }

        if (isset($this->details->$name)) {
            return $this->details->$name;
        }
        return false;
    }


}