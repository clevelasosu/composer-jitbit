<?php

namespace OSUCOE\JitBit;

class Ticket
{
    /**
     * @var API
     */
    protected $api;

    /**
     * @var int
     */
    protected $TicketID;

    /**
     * @var object
     */
    protected $ticketDetails;

    /**
     * Stores the fields that have been updated before the save() call
     *
     * @var array
     */
    protected $updatedFields = [];

    /**
     * @var array
     */
    protected $updateableFields = [
        'CategoryID',
        'TimeSpentInSeconds',
        'AssignedToUserID',
        'StatusID',
        'Tags',
    ];

    /**
     * Maps from fields sent by JitBit on GET requests to the field names used during POST requests
     * Only needed for fields that don't match (JitBit API appears to not be case sensitive)
     *
     * @var array
     */
    protected $fieldMap = [
        'AssignedToUserID' => 'assignedUserId',
    ];

    public function __construct(API $api, int $TicketID)
    {
        $this->api = $api;
        $this->TicketID = $TicketID;
        $this->refresh();
    }

    /**
     * This is a static call to create a ticket.  It returns the ticket Id
     *
     * @param API $api
     * @param string $Subject
     * @param string $Body
     * @param int $CategoryID
     * @param int $PriorityID
     * @param int $UserID
     * @param string $tags
     * @return int
     */
    public static function createNew(API $api, $Subject, $Body, int $CategoryID,
        int $PriorityID=0, int $UserID=0, string $tags="")
    {
        $args = [];
        $args['Subject'] = $Subject;
        $args['Body'] = $Body;
        $args['CategoryID'] = $CategoryID;
        if ($PriorityID >= -1 AND $PriorityID <= 2) {
            $args['PriorityID'] = $PriorityID;
        }

        if ($UserID > 0) {
            $args['UserID'] = $UserID;
        }

        if ($tags) {
            $args['tags'] = $tags;
        }

        return $api->_request('POST', '/api/ticket?' . http_build_query($args));
    }

    /**
     * Pulls fresh data from the server and clears the list of updated fields
     */
    protected function refresh()
    {
        $this->ticketDetails = $this->api->_request('GET', '/api/ticket?id='.$this->TicketID);
        $this->updatedFields = [];
    }

    /**
     * Updates the local entry.  Make sure to run save() to write it to the server.
     *
     * @param $field
     * @param $value
     */
    protected function updateField($field, $value)
    {
        if (!in_array($field, $this->updateableFields)) {
            throw new JitBitException('Invalid Field Given');
        }

        $this->ticketDetails->$field = $value;
        $this->updatedFields[] = $field;
    }

    /**
     * Writes the updated ticket fields to the server
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
            $args[$fieldToUpdate] = $this->ticketDetails->$updatedField;
        }
        $args['id'] = $this->TicketID;

        $this->api->_request('POST', '/api/UpdateTicket?'.http_build_query($args));
        $this->refresh();
    }

    /**
     * Adds a comment to a ticket.  Returns the ticket ID, which is useless at the moment
     *
     * @param string $Body
     * @param bool $forTechsOnly
     * @return int
     */
    public function addComment(string $Body, bool $forTechsOnly)
    {
        $args['Body'] = $Body;
        $args['id'] = $this->TicketID;
        $args['forTechsOnly'] = ($forTechsOnly === true ? 'true' : 'false');

        $result = $this->api->_request('POST', '/api/comment?'.http_build_query($args));
        if (!is_int($result) OR $result < 1) {
            throw new JitBitException('Invalid response adding comment: '.$result);
        }
        return $result;
    }

    /**
     * Gets all comments on a ticket
     */
    public function getComments()
    {
        return $this->api->_request('GET', '/api/comments?id='.$this->TicketID);
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