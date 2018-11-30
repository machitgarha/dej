<?php

class MAC
{
    public static function extractMacAsKeys(JSON $usersData): array
    {
        $usersData->to(JSON::ARRAY_DATA_TYPE);

        $users = [];
        foreach ($usersData->iterate() as $user)
            if ($user !== null)
                foreach ((array)$user["mac"] as $mac)
                    $users[$mac] = $user["name"];    
        
        return $users;
    }
}