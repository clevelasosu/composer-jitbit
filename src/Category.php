<?php


namespace OSUCOE\JitBit;


class Category
{

    /**
     * @var API
     */
    protected $api;
    protected $categoryDetails;
    protected $NameWithSection;
    protected $CategoryID;

    /**
     * Category constructor.
     * @param API $api
     * @param $NameWithSection "MIME \ Image Machine" or "General Issues"
     */
    public function __construct(API $api, $NameWithSection)
    {
        $this->api = $api;
        $this->NameWithSection = $NameWithSection;
        $this->refresh();

    }

    /**
     * Pulls fresh data from the server and clears the list of updated fields
     */
    public function refresh()
    {
        $categories = $this->api->_request('GET', '/api/categories');
        foreach ($categories as $category) {
            if ($this->NameWithSection == $category->NameWithSection) {
                $this->categoryDetails = $category;
                $this->CategoryID = $category->CategoryID;
                return;
            }
        }
        throw new JitBitException("Unable to find matching category");
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