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
* NotesRepository.php - Repository file
*
* This file is part of the Notes component.
*-----------------------------------------------------------------------------*/

namespace App\Yantrana\Services\YesTokenAuth\TokenRegistry\Repositories;

use App\Yantrana\Base\BaseRepository;
use App\Yantrana\Services\YesTokenAuth\TokenRegistry\Models\TokenRegistryModel;
use Carbon\Carbon;

class TokenRegistryRepository extends BaseRepository
{
    /**
     * Fetch the record of Notes
     *
     * @param    int || string $idOrUid
     * @return eloquent collection object
     *---------------------------------------------------------------- */
    public function fetch($idOrUid)
    {
        if (is_numeric($idOrUid)) {
            return TokenRegistryModel::where('_id', $idOrUid)->first();
        }

        return TokenRegistryModel::where('_uid', $idOrUid)->first();
    }

    /**
     * Update User Authority
     *
     * @return Eloquent collection object | bool
     *-----------------------------------------------------------------------*/
    public function storeTokenRegistry($inputData)
    {
        $expiry_at = Carbon::now()->addSeconds($inputData['expiry_at']);

        $keyValues = array_merge(array_keys($inputData), [
            'status' => 1,
            'expiry_at' => $expiry_at,
        ]);

        $tokenRegistryModel = new TokenRegistryModel;

        // Check if task testing record added then return positive response
        if ($tokenRegistryModel->assignInputsAndSave($inputData, $keyValues)) {
            return $tokenRegistryModel;
        }

        return false;
    }

    /**
     * Update User Authority
     *
     * @return Eloquent collection object
     *-----------------------------------------------------------------------*/
    public function deleteTokenRegistry($tokenRegistry)
    {

        // Check if deleted
        if ($tokenRegistry->delete()) {
            return $tokenRegistry;
        }

        return false;
    }

    /**
     * Clean Expired & Invalid Registry Entries
     *
     * @return bool
     *-----------------------------------------------------------------------*/
    public function cleanRegistry($olderThan = (30 * 60))
    {
        return TokenRegistryModel::where('expiry_at', '<', (Carbon::now()->subMinutes(2)->toDateTimeString()))->delete();
    }

    /**
     * Update User Authority
     *
     * @return Eloquent collection object
     *-----------------------------------------------------------------------*/
    public function delete($idOrUid)
    {
        if (is_numeric($idOrUid)) {
            return TokenRegistryModel::where('_id', $idOrUid)->delete();
        }

        return TokenRegistryModel::where('_uid', $idOrUid)->delete();
    }

    /**
     * Update User Authority
     *
     * @return Eloquent collection object
     *-----------------------------------------------------------------------*/
    public function deleteByToken($tokenRegistry)
    {
        return TokenRegistryModel::where(
            config('yes-token-auth.token_registry.schema.jwt_token', 'jwt_token'),
            $tokenRegistry
        )->delete();
    }
}
