<?php

namespace App\Database;

class MySqlQuerier extends \Illuminate\Database\Query\Grammars\MySqlGrammar
{
    public function getDateFormat()
    {
        return \App\Models\Model::DATE_FORMAT;
    }
}
