<?php

class Authz_ACL
{
    private $aces = array();

    private $rolelist;
    
    private $resourcelist;
    
    public function __construct(Authz_RoleList $rolelist, Authz_ResourceList $resourcelist)
    {
        $this->rolelist = $rolelist;
        $this->resourcelist = $resourcelist;
    }
    
    public function allow($role, $action)
    {
        $ace = new Authz_ACE($role, $action, true);
        $this->aces[$ace->get_dn_hash()] = $ace;
    }
    
    public function deny($role, $action)
    {
        $ace = new Authz_ACE($role, $action, false);
        $this->aces[$ace->get_dn_hash()] = $ace;
    }
    
    //! Return an associative array with metric and allowed flag
    public function effective_permission($role, $action)
    {
        // Metric is used to calculate priority of flags
        // 0: Explicit local
        // 1: Explicit depth 1
        // .
        // .
        // 9999: Implicit
        // 10000: Undefined
        $response = array(
            'metric' => 10000,
            'allowed' => false
        );

        foreach($this->aces as $ace)
        {
            if ($ace->get_action() !== $action)
                continue;

            if (($ace->get_role() === null) && ($response['metric'] >= 9999))
            {
                $response['metric'] = 9999;
                $response['allowed'] = $ace->is_allowed();
            }
                
            if (($ace->get_role() == $role) && ($response['metric'] >= 0))
            {
                $response['metric'] = 0;
                $response['allowed'] = $ace->is_allowed();
            }
        }

        if ($response['metric'] < 9999)
            return $response;

        if (($role === null) || (!$this->rolelist->has_role($role)))
            return $response;
            
        // Search roles parents
        foreach($this->rolelist->get_role($role)->get_parents() as $prole)
        {   
            $presponse = $this->effective_permission($prole, $action);
            if ($presponse['metric'] >= 9999)
                continue;
            
            if ($presponse['metric'] + 1 <= $response['metric'])
            {
                $response['metric'] = $presponse['metric'] + 1;
                $response['allowed'] = $presponse['allowed'];
            }
        }
        
        return $response;
    }
    
    public function is_allowed($role, $action)
    {
        $response = $this->effective_permission($role, $action);
        
        return $response['allowed'];
    }
}

?>
