<?php

namespace App\Database;

use Illuminate\Support\Fluent;

class MySqlSchemar extends \Illuminate\Database\Schema\Grammars\MySqlGrammar
{
    protected function typeTimestamp(Fluent $column)
    {
        return 'bigint';
    }
}
