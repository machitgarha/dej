<?php

class MAC
{
    private $usersData;

    public function __construct(JSON $usersData)
    {
        $this->usersData = $usersData;
    }

    public function get()
    {
        $users = [];
        foreach ($this->usersData->iterate() as $user)
            if ($user !== null)
                foreach ((array)$user->mac as $mac)
                    $users[$mac] = $user->name;    
        
        return $users;
    }
}