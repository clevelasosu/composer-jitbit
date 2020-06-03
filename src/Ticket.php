<?php

namespace OSUCOE\JitBit;

class Ticket extends Generic
{

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

    /**
     * The field used for the unique ID (UserID, id, etc).  Will be used to store $this->ID value
     * @var string
     */
    protected $saveUrl = '/api/UpdateTicket';
    protected $saveIDField = 'id';

    protected $refreshUrl = '/api/ticket';
    protected $refreshAttribute = 'id';
    protected $refreshUniqueValue = 'ID';

    public function __construct(API $api, int $TicketID)
    {
        $this->ID = $TicketID;
        parent::__construct($api);
        if (!$this->details) {
            throw new TicketNotFoundException("Ticket not found: $TicketID");
        }

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
     * Adds a comment to a ticket.  Returns the ticket ID, which is useless at the moment
     *
     * @param string $Body
     * @param bool $forTechsOnly
     * @return int
     */
    public function addComment(string $Body, bool $forTechsOnly)
    {
        $args['Body'] = $Body;
        $args['id'] = $this->ID;
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
        return $this->api->_request('GET', '/api/comments?id='.$this->ID);
    }
}