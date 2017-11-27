<?php

namespace SilverCommerce\ContactAdmin\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText as HTMLText;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;

/**
 * Details on a particular contact
 * 
 * @author ilateral
 * @package Contacts
 */
class ContactLocation extends DataObject implements PermissionProvider
{
    private static $table_name = 'ContactLocation';

    private static $db = [
        "Address1" => "Varchar(255)",
        "Address2" => "Varchar(255)",
        "City" => "Varchar(255)",
        "County" => "Varchar(255)",
        "Country" => "Varchar(255)",
        "PostCode" => "Varchar(10)",
        "Default" => "Boolean"
    ];
    
    private static $has_one = [
        "Contact" => Contact::class
    ];
    
    private static $casting = [
        "Title" => "Varchar",
        "Address" => "Text"
    ];
    
    private static $summary_fields = [
        "Address1",
        "Address2",
        "City",
        "County",
        "Country",
        "PostCode",
        "Default"
    ];

    public function getTitle()
    {
        $title = $this->Address1 . " (" . $this->PostCode . ")";

        $this->extend("updateTitle", $title);

        return $title;
    }

    public function getAddress() 
    {
        $return = [];
        $return[] = $this->Address1;
        
		if (!empty($this->Address2)) {
            $return[] = $this->Address2;
        }
        
        $return[] = $this->City;

		if (!empty($this->County)) {
            $return[] = $this->County;
        }

        $return[] = $this->Country;
        $return[] = $this->PostCode;

        $this->extend("updateAddress", $return);
        
		return implode(",\n", $return);
	}
    
    public function getCMSValidator()
    {
        return new RequiredFields(array(
            "Address1",
            "City",
            "Country",
            "PostCode"
        ));
    }
    
    public function providePermissions()
    {
        return [
            "CONTACTS_MANAGE" => [
                'name' => _t(
                    'Contacts.PERMISSION_MANAGE_CONTACTS_DESCRIPTION',
                    'Manage contacts'
                ),
                'help' => _t(
                    'Contacts.PERMISSION_MANAGE_CONTACTS_HELP',
                    'Allow creation and editing of contacts'
                ),
                'category' => _t('Contacts.Contacts', 'Contacts')
            ],
            "CONTACTS_DELETE" => [
                'name' => _t(
                    'Contacts.PERMISSION_DELETE_CONTACTS_DESCRIPTION',
                    'Delete contacts'
                ),
                'help' => _t(
                    'Contacts.PERMISSION_DELETE_CONTACTS_HELP',
                    'Allow deleting of contacts'
                ),
                'category' => _t('Contacts.Contacts', 'Contacts')
            ]
        ];
    }
    
    public function canView($member = false)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);

        if ($extended !== null) {
            return $extended;
        }

        return $this->Contact()->canView($member);
    }

    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member, $context);

        if ($extended !== null) {
            return $extended;
        }

        return $this->Contact()->canCreate($member, $context);
    }

    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);

        if ($extended !== null) {
            return $extended;
        }

        return $this->Contact()->canView($member);
    }

    public function canDelete($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);

        if ($extended !== null) {
            return $extended;
        }

        return $this->Contact()->canDelete($member);
    }

    /**
     * If we have assigned this as a default location, loop through
     * other locations and disable default.
     *
     * @return void
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if ($this->Default) {
            foreach ($this->Contact()->Locations() as $location) {
                if ($location->ID != $this->ID && $location->Default) {
                    $location->Default = false;
                    $location->write();
                }
            }
        }
    }
}
