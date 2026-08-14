<?php

/**
 * WhatsJet
 *
 * This file is part of the WhatsJet software package developed and licensed by livelyworks.
 *
 * You must have a valid license to use this software.
 *
 * © 2024 - 2026 livelyworks. All rights reserved.
 * Redistribution or resale of this file, in whole or in part, is prohibited without prior written permission from the author.
 *
 * For support or inquiries, contact: contact@livelyworks.net
 *
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 * @copyright   Copyright (c) 2024 - 2026 livelyworks
 * @website     https://livelyworks.net
 */


/**
 * Permissions manage_contacts@import_contacts
 *-----------------------------------------------------------------------------*/

return [
    'administrative' => [
        'title' => __tr('Administrative'),
        'description' => __tr('Allow/Deny permissions like Configuration, Subscription, Team Members, Message Log etc'),
    ],
    'manage_contacts' => [
        'title' => __tr('Manage Contacts'),
        'description' => __tr('Allow/Deny access for Manage Contacts, Groups, Custom Contact Fields etc'),
        'permissions' => [
            'import_contacts' => [
                'title' => __tr('Import Contacts'),
                'description' => __tr('Allow/Deny access to import contacts'),
            ],
            'export_contacts' => [
                'title' => __tr('Export Contacts'),
                'description' => __tr('Allow/Deny access to export contacts'),
            ],
            'delete_contacts' => [
                'title' => __tr('Delete Contacts'),
                'description' => __tr('Allow/Deny access to delete contacts'),
            ],
            'add_edit_contacts' => [
                'title' => __tr('Add/Edit Contacts'),
                'description' => __tr('Allow/Deny access to add/edit contacts'),
            ],
            'add_edit_delete_custom_contact_fields' => [
                'title' => __tr('Add/Edit/Delete Custom Contact Fields'),
                'description' => __tr('Allow/Deny access to add/edit custom contact fields'),
            ],
            'add_edit_delete_archive_group' => [
                'title' => __tr('Add/Edit/Delete/Archive Group'),
                'description' => __tr('Allow/Deny access to add/edit/delete/archive group'),
            ],
        ],
    ],
    'manage_campaigns' => [
        'title' => __tr('Manage Campaigns'),
        'description' => __tr('Allow/Deny access like Creating, Executing and Scheduling Campaigns etc'),
    ],
    'messaging' => [
        'title' => __tr('Messaging'),
        'description' => __tr('Allow/Deny access like Chat, Sync Templates etc'),
    ],
    'manage_templates' => [
        'title' => __tr('Manage Templates'),
        'description' => __tr('Allow/Deny access like Creating, Editing and Deleting Templates etc'),
        'permissions' => [
            'add_edit_templates' => [
                'title' => __tr('Add/Edit Templates'),
                'description' => __tr('Allow/Deny access to add/edit templates'),
            ],
            'delete_templates' => [
                'title' => __tr('Delete Templates'),
                'description' => __tr('Allow/Deny access to delete templates'),
            ],
        ],
    ],
    'assigned_chats_only' => [
        'title' => __tr('Assigned Chat Only'),
        'description' => __tr('Restrict users to assigned chat only, unless they will have access to all chats'),
    ],
    'hide_contact_phone_numbers' => [
        'title' => __tr('Hide Contact Phone Numbers'),
        'description' => __tr('Hide contact phone numbers for users having this permission'),
    ],
    'hide_contact_emails' => [
        'title' => __tr('Hide Contact Emails'),
        'description' => __tr('Hide contact emails for users having this permission'),
    ],
    'manage_bot_replies' => [
        'title' => __tr('Manage Bot Replies and Flows'),
        'description' => __tr('Allow/Deny access for Bot Replies and Flows'),
        'permissions' => [
            'add_edit_bot_replies' => [
                'title' => __tr('Add/Edit Bot Replies'),
                'description' => __tr('Allow/Deny access to add/edit bot replies'),
            ],
            'delete_bot_replies' => [
                'title' => __tr('Delete Bot Replies'),
                'description' => __tr('Allow/Deny access to delete bot replies'),
            ],
            'add_edit_bot_flows' => [
                'title' => __tr('Add/Edit Bot Flows'),
                'description' => __tr('Allow/Deny access to add/edit bot flows'),
            ],
            'delete_bot_flows' => [
                'title' => __tr('Delete Bot Flows'),
                'description' => __tr('Allow/Deny access to delete bot flows'),
            ],
            'manage_bot_flow_builder' => [
                'title' => __tr('Manage Bot Flow Builder'),
                'description' => __tr('Allow/Deny access to manage bot flow builder'),
            ],
        ],
    ],
];
