<?php
/**
 * Manage Users in JitBit.
 *
 * @package JitBit
 * @author Steve Cleveland <steve.cleveland@oregonstate.edu
 *
 */

namespace OSUCOE\JitBit;

class User extends Generic
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
//    private $updatedFields = [];
    protected $updateableFields = [
//        'CompanyName',
        'FirstName',
        'LastName',
        'Disabled',
        'Location',
        'Phone',
        'DepartmentName',
    ];

    /**
     * The field used for the unique ID (UserID, id, etc).  Will be used to store $this->ID value
     * @var string
     */
    protected $saveUrl = '/api/UpdateUser';
    protected $saveIDField = 'userId';

    protected $refreshUrl = '/api/UserByUsername';
    protected $refreshAttribute = 'username';
    protected $refreshUniqueValue = 'Username';

    /**
     * Maps from fields sent by JitBit on GET requests to the field names used during POST requests
     * Only needed for fields that don't match (JitBit API appears to not be case sensitive)
     *
     * @var array
     */
    protected $fieldMap = [
        'CompanyName' => 'company',
        'DepartmentName' => 'department',
        'Disabled' => 'disabled'
    ];

    /**
     * User constructor.
     * @param API $api
     * @param $Username
     * @throws UserNotFoundException
     */
    public function __construct(API $api, string $Username)
    {
        $this->Username = $Username;
        parent::__construct($api);
        if (!$this->details) {
            throw new UserNotFoundException("User not found: $Username");
        }
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
     * Pulls fresh data from the server and wipes out local changes
     */
    protected function refresh()
    {
        parent::refresh();
        if (isset($this->details->UserID)) {
            $this->ID = $this->details->UserID;
        }
    }

}