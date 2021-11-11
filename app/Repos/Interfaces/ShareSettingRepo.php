<?php

namespace App\Repos\Interfaces;

interface ShareSettingRepo
{
    public function get($group = null, $is_prior = false, $with_user = false);
    public function getComposition($group = null, $is_prior = false, $with_user = false);
    public function create($values);
    public function deactivate($share_setting);
}
