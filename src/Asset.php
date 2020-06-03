<?php
/**
 * Manage Assets in JitBit.
 *
 * @package JitBit
 * @author Steve Cleveland <steve.cleveland@oregonstate.edu
 *
 */

namespace OSUCOE\JitBit;


class Asset extends Generic
{
    /**
     * @var API
     */
    protected $api;

    /**
     * Fields that we allow updates to.  Should include the custom fields as
     * well (use WarrantyExpiration, see $customFieldMap)
     * @var array
     */
    protected $updateableFields = [
        'ModelName',
        'Manufacturer',
        'Type',
        'Supplier',
        'SerialNumber',
        'Location',
        'Comments',
        'Quantity',
        'Additional',
        'WarrantyExpiration',
    ];

    /**
     * There is no API to get the custom asset IDs, so we need to hardcode them here
     * @var array
     */
    protected $customFields = [
        'Additional' => 2,
        'Warranty Expiration' => 5,
    ];

    /**
     * If there are spaces in the real name, add it here without the spaces.
     * For use with $asset->updateWarrantyExpiration, etc.
     * @var array
     */
    protected $customFieldMap = [
        'WarrantyExpiration' => 'Warranty Expiration',
    ];

    /**
     * The field used for the unique ID (UserID, id, etc).  Will be used to store $this->ID value
     * @var string
     */
    protected $IDField = 'id';
    protected $saveUrl = '/api/UpdateAsset';
    protected $saveIDField = 'id';

    protected $refreshUrl = '/api/Asset';
    protected $refreshAttribute = 'id';
    protected $refreshUniqueValue = 'ID';

    public function __construct(API $api, int $ItemID)
    {
        $this->ID = $ItemID;
        parent::__construct($api);
        if (!$this->details) {
            throw new AssetNotFoundException("Asset not found: $ItemID");
        }

    }

    /**
     * @param API $api
     * @param string $ModelName
     * @param string $Manufacturer
     * @param string $Type
     * @param string $Supplier
     * @param string $SerialNumber
     * @param string $Location
     * @param string $Comments
     * @param int|null $Quantity
     * @throws JitBitException
     * @return int
     */
    public static function createNew(API $api, string $ModelName, string $Manufacturer, string $Type,
        string $Supplier, string $SerialNumber="", string $Location="", string $Comments="", int $Quantity=null)
    {
        $args = [];
        $args['ModelName'] = $ModelName;
        $args['Manufacturer'] = $Manufacturer;
        $args['Type'] = $Type;
        $args['Supplier'] = $Supplier;
        if ($SerialNumber) {
            $args['SerialNumber'] = $SerialNumber;
        }
        if ($Location) {
            $args['Location'] = $Location;
        }
        if ($Comments) {
            $args['Comments'] = $Comments;
        }
        if ($Quantity) {
            $args['Quantity'] = $Quantity;
        }

        $result = $api->_request('POST', '/api/Asset?' . http_build_query($args));
        if (!isset($result->id) OR !is_int($result->id)) {
            throw new JitBitException("Unable to create asset");
        }
        return $result->id;
    }

    /**
     * The Additional field is used as a running log.  This function will append (by default) instead
     * of replacing the contents.
     *
     * @param string $additional
     * @param bool $replace
     */
    public function updateAdditional(string $additional, bool $replace=false)
    {
        if ($replace) {
            $newAdditional = $additional;
        } else {
            $oldValue = $this->getCustomFieldValue('Additional');
            $newAdditional = $oldValue . "\r\n" . $additional;
        }
        $this->updateField('Additional', $newAdditional);
    }

    /**
     * Custom fields are stored in a different spot
     *
     * @param string $fieldName
     * @return bool
     */
    protected function getCustomFieldValue(string $fieldName)
    {
        if (!isset($this->details->Fields)) {
            return false;
        }
        foreach ($this->details->Fields as $field) {
            if ($field->FieldName == $fieldName) {
                return $field->Value;
            }
        }
        return false;
    }

    /**
     * Save changes to the server.  Overriding parent to handle Quantity and custom fields
     */
    public function save()
    {
        // Quantity is reset to 1 if it's not specified, so make sure it's included
        if (count($this->updatedFields) AND !in_array('Quantity', $this->updatedFields)) {
            $this->updatedFields[] = 'Quantity';
        }

        // Need to save custom fields first
        $this->saveCustom();
        parent::save();
    }

    /**
     * Custom asset fields used a different URL
     */
    public function saveCustom()
    {
        // Look through all of the updated fields to see if there are any Custom fields
        foreach ($this->updatedFields as $id => $updatedField) {

            // Translates WarrantyExpiration to "Warranty Expiration" for use with searching for the customID
            if (array_key_exists($updatedField, $this->customFieldMap)) {
                $realField = $this->customFieldMap[$updatedField];
            } else {
                $realField = $updatedField;
            }

            // Now see if Warranty Expiration is in the list of Custom fields
            if (array_key_exists($realField, $this->customFields)) {

                // If it is, get the ID needed for the API call
                $customID = $this->customFields[$realField];

                $args = [];
                $args['fieldId'] = $customID;
                // The value is stored in the main details array using WarrantyExpiration
                $args['value'] = $this->details->$updatedField;
                $args['id'] = $this->ID;
                $this->api->_request('POST', '/api/SetCustomFieldForAsset'."?".http_build_query($args));
                // Remove it from the updatedFields list so the main save() doesn't try to update it
                unset($this->updatedFields[$id]);
            }
        }

    }

}