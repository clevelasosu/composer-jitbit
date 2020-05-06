<?php


namespace OSUCOE\JitBit;


class User
{
    /**
     * @var string
     */
    protected $Username;
    /**
     * @var API
     */
    protected $api;
    /**
     * @var array
     */
    protected $userDetails;
    /**
     * @var array
     */
    private $updatedFields = [];
    private $updateableFields = [
//        'CompanyName',
        'FirstName',
        'LastName',
        'Disabled',
        'Location',
        'Phone',
        'DepartmentName',
    ];

    /**
     * Maps from fields sent by JitBit on GET requests to the field names used during POST requests
     * Only needed for fields that don't match (JitBit API appears to not be case sensitive)
     *
     * @var array
     */
    private $fieldMap = [
        'CompanyName' => 'company',
        'DepartmentName' => 'department',
        'Disabled' => 'disabled'
    ];
    /**
     * @var int
     */
    private $UserID;

    /**
     * User constructor.
     * @param API $api
     * @param $Username
     */
    public function __construct(API $api, string $Username)
    {
        $this->api = $api;
        $this->Username = $Username;
        $this->refresh();
    }

    /**
     * This is a static call to create a user.  It returns the user id
     *
     * @param API $api
     * @param string $Username
     * @param string $Password
     * @param string $Email
     * @param string $FirstName
     * @param string $LastName
     * @return int
     */
    public static function createNew(API $api, string $Username, string $Password, string $Email,
        string $FirstName="", string $LastName="")
    {
        $args = [];
        $args['Username'] = $Username;
        $args['Password'] = $Password;
        $args['Email'] = $Email;
        if ($FirstName) {
            $args['FirstName'] = $FirstName;
        }
        if ($LastName) {
            $args['LastName'] = $LastName;
        }
       $result = $api->_request('POST', '/api/CreateUser?' . http_build_query($args));
        if (!is_int($result)) {
            throw new JitBitException("Unable to create user: ".$result);
        }
        return $result;
    }

    /**
     * Updates the local attribute.  Be sure to run save() to write to the server
     *
     * @param $field
     * @param $value
     */
    protected function updateField($field, $value)
    {
        if (!in_array($field, $this->updateableFields)) {
            throw new JitBitException('Invalid Field Given');
        }

        $this->userDetails->$field = $value;
        $this->updatedFields[] = $field;
    }

    /**
     * Disable or enable a user.  Needs more than __call() as boolean needs to be transformed
     * to literal 'true' and 'false'
     *
     * @param bool $disabled
     */
    public function updateDisabled(bool $disabled)
    {
        $this->updateField('Disabled', ($disabled === true ? 'true': 'false'));
    }


    /**
     * Writes changes to the server
     */
    public function save() {
        if (!count($this->updatedFields)) {
            throw new JitBitException('No updated fields to save');
        }

        $args = [];
        foreach ($this->updatedFields as $updatedField) {
            if (array_key_exists($updatedField, $this->fieldMap)) {
                $fieldToUpdate = $this->fieldMap[$updatedField];
            } else {
                $fieldToUpdate = $updatedField;
            }
            $args[$fieldToUpdate] = $this->userDetails->$updatedField;
        }
        $args['userId'] = $this->UserID;

        $this->api->_request('POST', '/api/UpdateUser?'.http_build_query($args));
        $this->refresh();
    }

    /**
     * Pulls fresh data from the server and wipes out local changes
     */
    private function refresh()
    {
        $result = $this->api->_request('GET', '/api/UserByUsername?username='.$this->Username);
        if (!isset($result->UserID)) {
            throw new JitBitException("Invalid user result: ".$result);
        }
        $this->userDetails = $result;
        $this->UserID = $result->UserID;
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
        return false;
    }


}